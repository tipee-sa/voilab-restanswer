<?php

declare(strict_types=1);

namespace Voilab\Restanswer\Test;

use ArrayObject;
use PHPUnit\Framework\TestCase;
use Slim\Psr7\Response;
use stdClass;
use Voilab\Restanswer\Container as RestContainer;
use Voilab\Serviceanswer\Answer;
use Voilab\Serviceanswer\Container as ServiceContainer;

final class ProcessorTest extends TestCase
{
    /**
     * @phpstan-ignore property.uninitialized
     */
    private ServiceContainer $serviceContainer;

    protected function setUp(): void
    {
        $this->serviceContainer = new ServiceContainer([]);
    }

    /**
     * @param array<string, mixed> $config
     */
    private function createContainer(array $config = []): RestContainer
    {
        return new RestContainer($config);
    }

    private function createAnswer(): Answer
    {
        /** @var Answer */
        return $this->serviceContainer['answer'];
    }

    private function createError(): Answer
    {
        /** @var Answer */
        return $this->serviceContainer['error'];
    }

    public function testProcessSuccessfulReturnable(): void
    {
        $answer = $this->createAnswer();
        $answer->setBody(['id' => 1, 'name' => 'test']);

        $psr7Response = $this->createContainer()->processor()->process($answer, new Response());

        self::assertSame(200, $psr7Response->getStatusCode());
        self::assertSame('{"id":1,"name":"test"}', (string) $psr7Response->getBody());
    }

    public function testProcessEmptyReturnable(): void
    {
        $psr7Response = $this->createContainer()->processor()->process($this->createAnswer(), new Response());

        self::assertSame(204, $psr7Response->getStatusCode());
    }

    public function testProcessFailedReturnableUsesCodeTranslator(): void
    {
        $container = $this->createContainer([
            'codeTranslator' => [
                'not found' => 404,
                'forbidden' => 403,
            ],
        ]);

        $error = $this->createError();
        $error->setErrorCode('not found');
        $error->setPublicMessage('Resource not found');

        $psr7Response = $container->processor()->process($error, new Response());

        self::assertSame(404, $psr7Response->getStatusCode());
        self::assertSame('{"message":"Resource not found"}', (string) $psr7Response->getBody());
    }

    public function testProcessFailedReturnableDefaultsTo400(): void
    {
        $error = $this->createError();
        $error->setErrorCode('unknown code');
        $error->setPublicMessage('Bad request');

        $psr7Response = $this->createContainer()->processor()->process($error, new Response());

        self::assertSame(400, $psr7Response->getStatusCode());
    }

    public function testProcessWithMapping(): void
    {
        $answer = $this->createAnswer();
        $answer->setBody(['id' => 1, 'name' => 'John', 'email' => 'john@example.com']);

        $psr7Response = $this->createContainer()->processor()->map([
            'id' => 'id',
            'fullName' => 'name',
        ])->process($answer, new Response());

        self::assertSame(200, $psr7Response->getStatusCode());
        self::assertSame('{"id":1,"fullName":"John"}', (string) $psr7Response->getBody());
    }

    public function testProcessWithCollectionMapping(): void
    {
        $answer = $this->createAnswer();
        $answer->setBody([
            ['id' => 1, 'name' => 'Alice'],
            ['id' => 2, 'name' => 'Bob'],
        ]);

        $psr7Response = $this->createContainer()->processor()->map([
            'isCollection' => true,
            'mapping' => [
                'id' => 'id',
                'label' => 'name',
            ],
        ])->process($answer, new Response());

        self::assertSame(200, $psr7Response->getStatusCode());
        self::assertSame('[{"id":1,"label":"Alice"},{"id":2,"label":"Bob"}]', (string) $psr7Response->getBody());
    }

    public function testProcessWithCallableMapping(): void
    {
        $answer = $this->createAnswer();
        $answer->setBody(['first' => 'John', 'last' => 'Doe']);

        $psr7Response = $this->createContainer()->processor()->map([
            'fullName' => static function (array $data): string {
                /** @var string $first */
                $first = $data['first'];
                /** @var string $last */
                $last = $data['last'];

                return $first . ' ' . $last;
            },
        ])->process($answer, new Response());

        self::assertSame(200, $psr7Response->getStatusCode());
        self::assertSame('{"fullName":"John Doe"}', (string) $psr7Response->getBody());
    }

    public function testDotNotationAccessorResolvesNestedArrayKeys(): void
    {
        $answer = $this->createAnswer();
        $answer->setBody([
            'user' => [
                'name' => 'Alice',
                'address' => [
                    'city' => 'Lausanne',
                ],
            ],
        ]);

        $psr7Response = $this->createContainer()->processor()->map([
            'name' => 'user.name',
            'city' => 'user.address.city',
        ])->process($answer, new Response());

        self::assertSame('{"name":"Alice","city":"Lausanne"}', (string) $psr7Response->getBody());
    }

    public function testDotNotationReturnsNullForMissingNestedKey(): void
    {
        $answer = $this->createAnswer();
        $answer->setBody(['user' => ['name' => 'Alice']]);

        $psr7Response = $this->createContainer()->processor()->map([
            'email' => 'user.email',
        ])->process($answer, new Response());

        self::assertSame('{"email":null}', (string) $psr7Response->getBody());
    }

    public function testMappingWithObjectPropertyAccess(): void
    {
        $obj = new stdClass();
        $obj->id = 42;
        $obj->name = 'Bob';

        $answer = $this->createAnswer();
        $answer->setBody($obj);

        $psr7Response = $this->createContainer()->processor()->map([
            'id' => 'id',
            'label' => 'name',
        ])->process($answer, new Response());

        self::assertSame('{"id":42,"label":"Bob"}', (string) $psr7Response->getBody());
    }

    public function testMappingWithObjectMethodAccess(): void
    {
        $obj = new class {
            public function getId(): int
            {
                return 99;
            }

            public function getLabel(): string
            {
                return 'from method';
            }
        };

        $answer = $this->createAnswer();
        $answer->setBody($obj);

        $psr7Response = $this->createContainer()->processor()->map([
            'id' => 'getId',
            'label' => 'getLabel',
        ])->process($answer, new Response());

        self::assertSame('{"id":99,"label":"from method"}', (string) $psr7Response->getBody());
    }

    public function testPropertyArrayAccessCheckWithArrayAccessObject(): void
    {
        $obj = new ArrayObject(['key' => 'value']);

        $answer = $this->createAnswer();
        $answer->setBody($obj);

        // propertyArrayAccessCheck defaults to true in Container config
        $psr7Response = $this->createContainer()->processor()->map([
            'result' => 'key',
        ])->process($answer, new Response());

        self::assertSame('{"result":"value"}', (string) $psr7Response->getBody());
    }

    public function testPropertyArrayAccessCheckDisabledReturnsNull(): void
    {
        $obj = new ArrayObject(['key' => 'value']);

        $answer = $this->createAnswer();
        $answer->setBody($obj);

        $container = $this->createContainer([
            'processorMapping' => [
                'propertyArrayAccessCheck' => false,
            ],
        ]);

        $psr7Response = $container->processor()->map([
            'result' => 'key',
        ])->process($answer, new Response());

        self::assertSame('{"result":null}', (string) $psr7Response->getBody());
    }

    public function testMetadatasFromReturnableAreSetAsResponseHeaders(): void
    {
        $answer = $this->createAnswer();
        $answer->setBody(['id' => 1]);
        $answer->setMetadatas([
            'X-Total-Count' => '42',
            'X-Page' => '1',
        ]);

        $psr7Response = $this->createContainer()->processor()->process($answer, new Response());

        self::assertSame('42', $psr7Response->getHeaderLine('X-Total-Count'));
        self::assertSame('1', $psr7Response->getHeaderLine('X-Page'));
    }

    public function testMappingIsResetBetweenProcessCalls(): void
    {
        $container = $this->createContainer();
        $processor = $container->processor();

        // First call with mapping
        $answer1 = $this->createAnswer();
        $answer1->setBody(['id' => 1, 'name' => 'Alice', 'email' => 'alice@example.com']);

        $psr7Response1 = $processor->map([
            'id' => 'id',
        ])->process($answer1, new Response());
        self::assertSame('{"id":1}', (string) $psr7Response1->getBody());

        // Second call without map() — mapping from first call still applies (stale mapping)
        $answer2 = $this->createAnswer();
        $answer2->setBody(['id' => 2, 'name' => 'Bob', 'email' => 'bob@example.com']);

        $psr7Response2 = $processor->process($answer2, new Response());
        // Stale mapping is still active — only 'id' key is returned
        self::assertSame('{"id":2}', (string) $psr7Response2->getBody());
    }

    public function testDotNotationWithObjectPropertiesMultiLevel(): void
    {
        $address = new stdClass();
        $address->city = 'Geneva';

        $user = new stdClass();
        $user->address = $address;

        $answer = $this->createAnswer();
        $answer->setBody($user);

        $psr7Response = $this->createContainer()->processor()->map([
            'city' => 'address.city',
        ])->process($answer, new Response());

        self::assertSame('{"city":"Geneva"}', (string) $psr7Response->getBody());
    }

    public function testSubMappingWithAccessorAndNameRename(): void
    {
        $answer = $this->createAnswer();
        $answer->setBody([
            'details' => ['city' => 'Lausanne', 'zip' => '1000'],
        ]);

        $psr7Response = $this->createContainer()->processor()->map([
            'location' => [
                '__name__' => 'address',
                'accessor' => 'details',
                'mapping' => [
                    'city' => 'city',
                    'zip' => 'zip',
                ],
            ],
        ])->process($answer, new Response());

        self::assertSame('{"address":{"city":"Lausanne","zip":"1000"}}', (string) $psr7Response->getBody());
    }

    public function testRecursiveNestedSubMapping(): void
    {
        $answer = $this->createAnswer();
        $answer->setBody([
            'company' => [
                'address' => ['street' => '123 Main St', 'city' => 'Zurich'],
            ],
        ]);

        $psr7Response = $this->createContainer()->processor()->map([
            'info' => [
                'accessor' => 'company',
                'mapping' => [
                    'addr' => [
                        'accessor' => 'address',
                        'mapping' => [
                            'street' => 'street',
                            'city' => 'city',
                        ],
                    ],
                ],
            ],
        ])->process($answer, new Response());

        self::assertSame('{"info":{"addr":{"street":"123 Main St","city":"Zurich"}}}', (string) $psr7Response->getBody());
    }

    public function testProcessWithFormatParameterOverridesContentType(): void
    {
        $answer = $this->createAnswer();
        $answer->setBody('Hello world');

        $psr7Response = $this->createContainer()->processor()->process($answer, new Response(), 'text/html');

        self::assertSame('Hello world', (string) $psr7Response->getBody());
        self::assertStringStartsWith('text/html', $psr7Response->getHeaderLine('Content-Type'));
    }

    public function testCollectionMappingWithNonIterableReturnsEmpty(): void
    {
        $answer = $this->createAnswer();
        $answer->setBody('not iterable');

        $container = $this->createContainer();
        $processor = $container->processor();
        $voilabResponse = $processor->map([
            'isCollection' => true,
            'mapping' => [
                'id' => 'id',
            ],
        ])->getProcessedResponse($answer);

        self::assertSame([], $voilabResponse->getContent());
    }

    public function testRecursiveMapWithFalsyContentReturnsNull(): void
    {
        $answer = $this->createAnswer();
        $answer->setBody(['user' => null]);

        $psr7Response = $this->createContainer()->processor()->map([
            'profile' => [
                'accessor' => 'user',
                'mapping' => [
                    'name' => 'name',
                ],
            ],
        ])->process($answer, new Response());

        self::assertSame('{"profile":null}', (string) $psr7Response->getBody());
    }

    public function testMixedTypeDotNotation(): void
    {
        $inner = new class {
            public function getName(): string
            {
                return 'deep';
            }
        };

        $answer = $this->createAnswer();
        $answer->setBody([
            'wrapper' => (object) ['nested' => $inner],
        ]);

        $psr7Response = $this->createContainer()->processor()->map([
            'result' => 'wrapper.nested.getName',
        ])->process($answer, new Response());

        self::assertSame('{"result":"deep"}', (string) $psr7Response->getBody());
    }
}
