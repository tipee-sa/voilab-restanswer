<?php

declare(strict_types=1);

namespace Voilab\Restanswer\Test;

use PHPUnit\Framework\TestCase;
use Voilab\Restanswer\Container;

final class ContainerTest extends TestCase
{
    public function testResponseIsFactory(): void
    {
        $container = new Container();
        $response1 = $container->response();
        $response2 = $container->response();

        self::assertNotSame($response1, $response2);
    }

    public function testRendererIsFactory(): void
    {
        $container = new Container();
        $renderer1 = $container->renderer();
        $renderer2 = $container->renderer();

        self::assertNotSame($renderer1, $renderer2);
    }

    public function testProcessorIsFactory(): void
    {
        $container = new Container();
        $processor1 = $container->processor();
        $processor2 = $container->processor();

        self::assertNotSame($processor1, $processor2);
    }

    public function testDefaultConfigKeys(): void
    {
        $container = new Container();
        $config = $container->config();

        self::assertArrayHasKey('content-type', $config);
        self::assertArrayHasKey('mimetypes', $config);
        self::assertArrayHasKey('codeTranslator', $config);
        self::assertArrayHasKey('processorMapping', $config);
        self::assertSame('application/json', $config['content-type']);
    }

    public function testCustomConfigMergesWithDefaults(): void
    {
        $container = new Container([
            'codeTranslator' => ['not_found' => 404],
            'custom_key' => 'custom_value',
        ]);
        $config = $container->config();

        self::assertSame(['not_found' => 404], $config['codeTranslator']);
        self::assertSame('custom_value', $config['custom_key']);
        self::assertArrayHasKey('content-type', $config);
        self::assertArrayHasKey('mimetypes', $config);
    }
}
