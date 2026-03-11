<?php

declare(strict_types=1);

namespace Voilab\Restanswer;

use Psr\Http\Message\ResponseInterface;

class Response
{
    private string $encoding = 'utf-8';
    private int $httpStatus = 200;
    private mixed $content = null;
    private bool $newLineEOF = false;

    /**
     * @var array<string, string>
     */
    private array $headers = [];

    public function __construct(
        private readonly Container $container,
    ) {}

    /**
     * Fast error helper. Returns a PSR-7 response with the given status and content.
     */
    public function error(int $httpStatus, string $content, ResponseInterface $response): ResponseInterface
    {
        return $this
            ->setHttpStatus($httpStatus)
            ->setContent($content)
            ->getRenderer()
            ->render($response);
    }

    public function getRenderer(?string $contentType = null): Renderer
    {
        $renderer = $this->container->renderer();
        $renderer->setResponse($this);
        if (null !== $contentType) {
            $renderer->setContentType($contentType);
        }

        return $renderer;
    }

    public function getHttpStatus(): int
    {
        return $this->httpStatus;
    }

    public function setHttpStatus(int $status): self
    {
        $this->httpStatus = $status;

        return $this;
    }

    public function getContent(): mixed
    {
        return $this->content;
    }

    public function setContent(mixed $content): self
    {
        $this->content = $content;

        return $this;
    }

    public function getEncoding(): string
    {
        return $this->encoding;
    }

    public function setEncoding(string $encoding): self
    {
        $this->encoding = $encoding;

        return $this;
    }

    /**
     * @return array<string, string>
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * @param array<string, string> $headers
     */
    public function setHeaders(array $headers): self
    {
        $this->headers = $headers;

        return $this;
    }

    public function isNewLineEOF(): bool
    {
        return $this->newLineEOF;
    }

    public function setNewLineEOF(bool $newLineEOF): self
    {
        $this->newLineEOF = $newLineEOF;

        return $this;
    }
}
