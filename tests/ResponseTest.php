<?php

declare(strict_types=1);

namespace Voilab\Restanswer\Test;

use PHPUnit\Framework\TestCase;
use Slim\Psr7\Response;
use Voilab\Restanswer\Container;

final class ResponseTest extends TestCase
{
    /**
     * @param array<string, mixed> $config
     */
    private function createContainer(array $config = []): Container
    {
        return new Container($config);
    }

    public function testErrorReturnsResponseWithCorrectStatus(): void
    {
        $psr7Response = $this->createContainer()->response()->error(404, 'Not found', new Response());

        self::assertSame(404, $psr7Response->getStatusCode());
    }

    public function testErrorReturnsResponseWithJsonErrorBody(): void
    {
        $psr7Response = $this->createContainer()->response()->error(400, 'Missing parameters', new Response());

        self::assertSame(400, $psr7Response->getStatusCode());
        self::assertSame('{"message":"Missing parameters"}', (string) $psr7Response->getBody());
    }

    public function testErrorSetsContentTypeHeader(): void
    {
        $psr7Response = $this->createContainer()->response()->error(500, 'Server error', new Response());

        self::assertSame('application/json; charset=utf-8', $psr7Response->getHeaderLine('Content-Type'));
    }

    public function testErrorSetsEtagHeader(): void
    {
        $psr7Response = $this->createContainer()->response()->error(400, 'Bad request', new Response());

        $body = (string) $psr7Response->getBody();
        self::assertSame(sha1($body), $psr7Response->getHeaderLine('ETag'));
    }
}
