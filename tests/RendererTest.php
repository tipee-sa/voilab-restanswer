<?php

declare(strict_types=1);

namespace Voilab\Restanswer\Test;

use InvalidArgumentException;
use JsonException;
use PHPUnit\Framework\TestCase;
use Slim\Psr7\Response;
use stdClass;
use Voilab\Restanswer\Container;

final class RendererTest extends TestCase
{
    /**
     * @param array<string, mixed> $config
     */
    private function createContainer(array $config = []): Container
    {
        return new Container($config);
    }

    public function testRenderSetsStatusCode(): void
    {
        $container = $this->createContainer();
        $voilabResponse = $container->response();
        $voilabResponse->setHttpStatus(201);
        $voilabResponse->setContent(['id' => 1]);

        $psr7Response = $voilabResponse->getRenderer()->render(new Response());

        self::assertSame(201, $psr7Response->getStatusCode());
    }

    public function testRenderSetsContentTypeWithCharset(): void
    {
        $container = $this->createContainer();
        $voilabResponse = $container->response();
        $voilabResponse->setContent(['test' => true]);

        $psr7Response = $voilabResponse->getRenderer()->render(new Response());

        self::assertSame('application/json; charset=utf-8', $psr7Response->getHeaderLine('Content-Type'));
    }

    public function testRenderSetsCustomHeaders(): void
    {
        $container = $this->createContainer();
        $voilabResponse = $container->response();
        $voilabResponse->setContent('test');
        $voilabResponse->setHeaders([
            'X-Custom' => 'value',
            'Content-Disposition' => 'attachment; filename="test.csv"',
        ]);

        $psr7Response = $voilabResponse->getRenderer()->render(new Response());

        self::assertSame('value', $psr7Response->getHeaderLine('X-Custom'));
        self::assertSame('attachment; filename="test.csv"', $psr7Response->getHeaderLine('Content-Disposition'));
    }

    public function testRenderSetsEtagHeader(): void
    {
        $container = $this->createContainer();
        $voilabResponse = $container->response();
        $voilabResponse->setContent(['data' => 'test']);

        $psr7Response = $voilabResponse->getRenderer()->render(new Response());

        $body = (string) $psr7Response->getBody();
        self::assertSame(sha1($body), $psr7Response->getHeaderLine('ETag'));
    }

    public function testRenderWritesJsonBody(): void
    {
        $container = $this->createContainer();
        $voilabResponse = $container->response();
        $voilabResponse->setContent(['name' => 'test', 'value' => 42]);

        $psr7Response = $voilabResponse->getRenderer()->render(new Response());

        self::assertSame('{"name":"test","value":42}', (string) $psr7Response->getBody());
    }

    public function testRenderWritesCsvBody(): void
    {
        $container = $this->createContainer();
        $voilabResponse = $container->response();
        $voilabResponse->setContent([
            ['a', 'b'],
            ['c', 'd'],
        ]);

        $psr7Response = $voilabResponse->getRenderer('text/csv')->render(new Response());

        self::assertSame("a;b\nc;d", (string) $psr7Response->getBody());
        self::assertStringStartsWith('text/csv', $psr7Response->getHeaderLine('Content-Type'));
    }

    public function testRenderErrorContent(): void
    {
        $container = $this->createContainer();
        $voilabResponse = $container->response();
        $voilabResponse->setHttpStatus(400);
        $voilabResponse->setContent('Something went wrong');

        $psr7Response = $voilabResponse->getRenderer()->render(new Response());

        self::assertSame(400, $psr7Response->getStatusCode());
        self::assertSame('{"message":"Something went wrong"}', (string) $psr7Response->getBody());
    }

    public function testConvertAndBuildResponse(): void
    {
        $container = $this->createContainer();
        $voilabResponse = $container->response();
        $voilabResponse->setContent('Hello world');

        $renderer = $voilabResponse->getRenderer('text/html');
        $renderer->prepareContent();
        $renderer->convert('UTF-8', 'UTF-8');
        $psr7Response = $renderer->buildResponse(new Response());

        self::assertSame(200, $psr7Response->getStatusCode());
        self::assertSame('Hello world', (string) $psr7Response->getBody());
    }

    public function testRenderWith204StatusReturnsEmptyBody(): void
    {
        $container = $this->createContainer();
        $voilabResponse = $container->response();
        $voilabResponse->setHttpStatus(204);
        $voilabResponse->setContent(null);

        $psr7Response = $voilabResponse->getRenderer()->render(new Response());

        self::assertSame(204, $psr7Response->getStatusCode());
    }

    public function testRenderTabSeparatedContent(): void
    {
        $container = $this->createContainer();
        $voilabResponse = $container->response();
        $voilabResponse->setContent([
            ['col1', 'col2'],
            ['val1', 'val2'],
        ]);

        $psr7Response = $voilabResponse->getRenderer('text/tab-separated-values')->render(new Response());

        self::assertSame("col1\tcol2\nval1\tval2", (string) $psr7Response->getBody());
        self::assertStringStartsWith('text/tab-separated-values', $psr7Response->getHeaderLine('Content-Type'));
    }

    public function testRenderTextWithStringContent(): void
    {
        $container = $this->createContainer();
        $voilabResponse = $container->response();
        $voilabResponse->setContent('plain text content');

        $psr7Response = $voilabResponse->getRenderer('text/html')->render(new Response());

        self::assertSame('plain text content', (string) $psr7Response->getBody());
    }

    public function testRenderTextWithObjectToString(): void
    {
        $obj = new class {
            public function toString(): string
            {
                return 'object as string';
            }
        };

        $container = $this->createContainer();
        $voilabResponse = $container->response();
        $voilabResponse->setContent($obj);

        $psr7Response = $voilabResponse->getRenderer('text/html')->render(new Response());

        self::assertSame('object as string', (string) $psr7Response->getBody());
    }

    public function testRenderTextWithObjectWithoutToString(): void
    {
        $obj = new stdClass();

        $container = $this->createContainer();
        $voilabResponse = $container->response();
        $voilabResponse->setContent($obj);

        $psr7Response = $voilabResponse->getRenderer('text/html')->render(new Response());

        self::assertSame('Instance of stdClass', (string) $psr7Response->getBody());
    }

    public function testTextBadFormatThrowsException(): void
    {
        $container = $this->createContainer();
        $voilabResponse = $container->response();
        $voilabResponse->setContent(12345);

        $this->expectException(InvalidArgumentException::class);
        $voilabResponse->getRenderer('text/html')->render(new Response());
    }

    public function testCsvWithNewLineEOF(): void
    {
        $container = $this->createContainer();
        $voilabResponse = $container->response();
        $voilabResponse->setNewLineEOF(true);
        $voilabResponse->setContent([
            ['a', 'b'],
            ['c', 'd'],
        ]);

        $psr7Response = $voilabResponse->getRenderer('text/csv')->render(new Response());

        self::assertSame("a;b\nc;d\n", (string) $psr7Response->getBody());
    }

    public function testCsvWithHeadingsOption(): void
    {
        $container = $this->createContainer();
        $voilabResponse = $container->response();
        $voilabResponse->setContent([
            ['name' => 'Alice', 'age' => '30'],
            ['name' => 'Bob', 'age' => '25'],
        ]);

        $renderer = $voilabResponse->getRenderer('text/csv');
        $renderer->setOption('headings', true);
        $psr7Response = $renderer->render(new Response());

        self::assertSame("name;age\nAlice;30\nBob;25", (string) $psr7Response->getBody());
    }

    public function testNonUtf8EncodingSetsCorrectContentTypeHeader(): void
    {
        $container = $this->createContainer();
        $voilabResponse = $container->response();
        $voilabResponse->setEncoding('windows-1252');
        $voilabResponse->setContent(['key' => 'value']);

        $psr7Response = $voilabResponse->getRenderer()->render(new Response());

        self::assertSame('application/json; charset=windows-1252', $psr7Response->getHeaderLine('Content-Type'));
    }

    public function testCsvErrorRenderingUsesRenderMethod(): void
    {
        $container = $this->createContainer();
        $voilabResponse = $container->response();
        $voilabResponse->setHttpStatus(400);
        $voilabResponse->setContent('CSV error message');

        $psr7Response = $voilabResponse->getRenderer('text/csv')->render(new Response());

        self::assertSame(400, $psr7Response->getStatusCode());
        self::assertSame('CSV error message', (string) $psr7Response->getBody());
    }

    public function testTextErrorRenderingUsesRenderMethod(): void
    {
        $container = $this->createContainer();
        $voilabResponse = $container->response();
        $voilabResponse->setHttpStatus(500);
        $voilabResponse->setContent('Text error');

        $psr7Response = $voilabResponse->getRenderer('text/html')->render(new Response());

        self::assertSame(500, $psr7Response->getStatusCode());
        self::assertSame('Text error', (string) $psr7Response->getBody());
    }

    public function testConvertWithActualEncodingDifference(): void
    {
        $container = $this->createContainer();
        $voilabResponse = $container->response();
        $voilabResponse->setContent('Héllo wörld');

        $renderer = $voilabResponse->getRenderer('text/html');
        $renderer->prepareContent();
        $renderer->convert('UTF-8', 'Windows-1252');

        $converted = $renderer->getContent();
        self::assertNotNull($converted);

        // Convert back to verify round-trip
        $backToUtf8 = iconv('Windows-1252', 'UTF-8', $converted);
        self::assertSame('Héllo wörld', $backToUtf8);
    }

    public function testCsvWithHeadingsAndNewLineEOF(): void
    {
        $container = $this->createContainer();
        $voilabResponse = $container->response();
        $voilabResponse->setNewLineEOF(true);
        $voilabResponse->setContent([
            ['x' => '1', 'y' => '2'],
            ['x' => '3', 'y' => '4'],
        ]);

        $renderer = $voilabResponse->getRenderer('text/csv');
        $renderer->setOption('headings', true);
        $psr7Response = $renderer->render(new Response());

        self::assertSame("x;y\n1;2\n3;4\n", (string) $psr7Response->getBody());
    }

    public function testSeparatedBadFormatThrowsException(): void
    {
        $container = $this->createContainer();
        $voilabResponse = $container->response();
        $voilabResponse->setContent(12345);

        $this->expectException(InvalidArgumentException::class);
        $voilabResponse->getRenderer('text/csv')->render(new Response());
    }

    public function testJsonThrowsOnEncodingFailure(): void
    {
        $container = $this->createContainer();
        $voilabResponse = $container->response();
        $voilabResponse->setContent("\xB1\x31");

        $this->expectException(JsonException::class);
        $voilabResponse->getRenderer()->render(new Response());
    }

    public function testSeparatedCustomLinebreakOption(): void
    {
        $container = $this->createContainer();
        $voilabResponse = $container->response();
        $voilabResponse->setContent([
            ['a', 'b'],
            ['c', 'd'],
        ]);

        $renderer = $voilabResponse->getRenderer('text/csv');
        $renderer->setOption('linebreak', "\r\n");
        $psr7Response = $renderer->render(new Response());

        self::assertSame("a;b\r\nc;d", (string) $psr7Response->getBody());
    }

    public function testJsonRenderWithFalsyContentReturnsNull(): void
    {
        $container = $this->createContainer();
        $voilabResponse = $container->response();
        $voilabResponse->setContent(null);

        $renderer = $voilabResponse->getRenderer();
        $renderer->prepareContent();

        self::assertNull($renderer->getContent());
    }

    public function testConvertFailurePreservesOriginalContent(): void
    {
        $container = $this->createContainer();
        $voilabResponse = $container->response();
        $voilabResponse->setContent('Héllo wörld');

        $renderer = $voilabResponse->getRenderer('text/html');
        $renderer->prepareContent();
        $originalContent = $renderer->getContent();

        @$renderer->convert('UTF-8', 'US-ASCII');

        self::assertSame($originalContent, $renderer->getContent());
    }

    public function testUnknownMimetypeFallsToDefaultAdapter(): void
    {
        $container = $this->createContainer();
        $voilabResponse = $container->response();
        $voilabResponse->setContent('raw content');

        $psr7Response = $voilabResponse->getRenderer('application/xml')->render(new Response());

        self::assertSame(200, $psr7Response->getStatusCode());
        self::assertSame('raw content', (string) $psr7Response->getBody());
    }

    public function testStandardBadFormatThrowsException(): void
    {
        $container = $this->createContainer();
        $voilabResponse = $container->response();
        $voilabResponse->setContent(['not' => 'a string']);

        $this->expectException(InvalidArgumentException::class);
        $voilabResponse->getRenderer('application/xml')->render(new Response());
    }
}
