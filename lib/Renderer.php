<?php

declare(strict_types=1);

namespace Voilab\Restanswer;

use Psr\Http\Message\ResponseInterface;

class Renderer
{
    /**
     * @phpstan-ignore property.uninitialized (set via setResponse() before use)
     */
    private Response $response;
    private string $contentType;
    private ?string $content = null;
    private int $status = 200;

    /**
     * @var array<string, mixed>
     */
    private array $options = [];

    public function __construct(
        private readonly Container $container,
    ) {
        /** @var string $defaultContentType */
        $defaultContentType = $this->container->config()['content-type'];
        $this->contentType = $defaultContentType;
    }

    /**
     * Render the response content and build a PSR-7 response.
     */
    public function render(ResponseInterface $response): ResponseInterface
    {
        $this->prepareContent();

        return $this->buildResponse($response);
    }

    /**
     * Apply prepared content + headers to a PSR-7 response.
     * Separate from render() for the convert() use case (prepare -> convert -> buildResponse).
     */
    public function buildResponse(ResponseInterface $response): ResponseInterface
    {
        $response = $response->withStatus($this->status);
        $response = $response->withHeader(
            'Content-Type',
            $this->contentType . '; charset=' . $this->response->getEncoding(),
        );

        foreach ($this->response->getHeaders() as $key => $value) {
            $response = $response->withHeader($key, $value);
        }

        $content = $this->content ?? '';
        $response = $response->withHeader('ETag', sha1($content));
        if ('' !== $content) {
            $response->getBody()->write($content);
        }

        return $response;
    }

    public function convert(string $from, string $to): self
    {
        $converted = iconv($from, $to, $this->content ?? '');
        $this->content = false !== $converted ? $converted : $this->content;

        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function getContentType(): string
    {
        return $this->contentType;
    }

    public function setContentType(string $type): self
    {
        $this->contentType = $type;

        return $this;
    }

    public function setResponse(Response $response): self
    {
        $this->response = $response;

        return $this;
    }

    public function getResponse(): Response
    {
        return $this->response;
    }

    public function getOption(string $name, mixed $default = null): mixed
    {
        return $this->options[$name] ?? $default;
    }

    public function setOption(string $name, mixed $value): self
    {
        $this->options[$name] = $value;

        return $this;
    }

    private function getContentTypeAdapterName(): string
    {
        $config = $this->container->config();

        /** @var array<string, string> $mimetypes */
        $mimetypes = $config['mimetypes'];

        if (isset($mimetypes[$this->contentType])) {
            return $mimetypes[$this->contentType] . 'ContentType';
        }

        return $mimetypes['default'] . 'ContentType';
    }

    /**
     * Prepare content from the response using the appropriate content type adapter.
     */
    public function prepareContent(): void
    {
        $content = $this->response->getContent();
        $this->status = $this->response->getHttpStatus();

        $adapter = $this->container->contentTypeAdapter($this->getContentTypeAdapterName());

        if ($this->status >= 200 && $this->status < 400) {
            $rendered = $adapter->render($content, $this, $this->response->isNewLineEOF());
        } else {
            $rendered = $adapter->renderError($content, $this);
        }
        $this->content = $rendered;
    }
}
