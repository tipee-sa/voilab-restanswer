<?php

declare(strict_types=1);

namespace Voilab\Restanswer;

use ArrayAccess;
use Psr\Http\Message\ResponseInterface;
use Voilab\Serviceanswer\Interfaces\Returnable;
use function is_array;
use function is_callable;
use function is_object;
use function is_string;

class Processor
{
    /**
     * @var array<string, mixed>|null
     */
    private ?array $mapping = null;

    public function __construct(
        private readonly Container $container,
    ) {}

    /**
     * Process a service return and render as a PSR-7 response.
     */
    public function process(Returnable $returnable, ResponseInterface $response, ?string $format = null): ResponseInterface
    {
        if (!$returnable->isSuccess()) {
            /** @var array<string|int, int> $codeTranslator */
            $codeTranslator = $this->container->config()['codeTranslator'];
            $httpStatus = $codeTranslator[$returnable->getErrorCode()] ?? 400;

            return $this->container->response()
                ->setHttpStatus($httpStatus)
                ->setContent($returnable->getMessage())
                ->getRenderer($format)
                ->render($response);
        }

        $voilabResponse = $this->getProcessedResponse($returnable);

        return $voilabResponse->getRenderer($format)->render($response);
    }

    public function getProcessedResponse(Returnable $returnable): Response
    {
        $content = $returnable->getBody();

        if (null !== $this->mapping) {
            $content = $this->recursiveMap($content, $this->mapping);
        }

        /** @var array<string, string> $metadatas */
        $metadatas = $returnable->getMetadatas();
        $response = $this->container->response()
            ->setHeaders($metadatas);

        /** @phpstan-ignore method.notFound (isEmpty() is provided by the Base trait, not declared in the Returnable interface) */
        if (!$returnable->isEmpty()) {
            $response->setContent($content);
        } else {
            $response->setHttpStatus(204);
        }

        return $response;
    }

    /**
     * @param array<string, mixed> $mapping
     */
    public function map(array $mapping): self
    {
        $this->mapping = $mapping;

        return $this;
    }

    /**
     * @param array<string, mixed> $mapping
     * @return list<mixed>|array<string, mixed>|null
     */
    private function recursiveMap(mixed $content, array $mapping): ?array
    {
        /** @var bool $isCollection */
        $isCollection = $mapping['isCollection'] ?? false;

        /** @var array<string, mixed> $fieldMapping */
        $fieldMapping = $mapping['mapping'] ?? $mapping;
        $mapped = [];

        if ($isCollection) {
            if (is_iterable($content)) {
                foreach ($content as $value) {
                    $mapped[] = $this->recursiveMap($value, $fieldMapping);
                }
            }
        } elseif ($content) {
            foreach ($fieldMapping as $key => $map) {
                if (is_string($map)) {
                    $mapped[$key] = $this->getKeyContent($content, $map);
                } elseif (is_callable($map)) {
                    $mapped[$key] = $map($content);
                } elseif (is_array($map)) {
                    /** @var string $accessor */
                    $accessor = $map['accessor'] ?? $key;
                    if (isset($map['__name__']) && $map['__name__']) {
                        /** @var string $key */
                        $key = $map['__name__'];
                    }

                    /** @var array<string, mixed> $subMapping */
                    $subMapping = $map;
                    $mapped[$key] = $this->recursiveMap(
                        $this->getKeyContent($content, $accessor),
                        $subMapping,
                    );
                }
            }
        } else {
            return null;
        }

        return $mapped;
    }

    private function getKeyContent(mixed $object, string $accessor): mixed
    {
        $parts = explode('.', $accessor);
        foreach ($parts as $part) {
            if (is_array($object)) {
                if (isset($object[$part])) {
                    $object = $object[$part];
                } else {
                    return null;
                }
            } elseif (is_object($object) && isset($object->{$part})) {
                $object = $object->{$part};
            } elseif (is_object($object) && method_exists($object, $part)) {
                $object = $object->{$part}();
            } elseif (
                $this->isPropertyArrayAccessCheckEnabled()
                && $object instanceof ArrayAccess
                && isset($object[$part])
            ) {
                $object = $object[$part];
            } else {
                return null;
            }
        }

        return $object;
    }

    private function isPropertyArrayAccessCheckEnabled(): bool
    {
        /** @var array{propertyArrayAccessCheck?: bool} $processorMapping */
        $processorMapping = $this->container->config()['processorMapping'] ?? [];

        return $processorMapping['propertyArrayAccessCheck'] ?? false;
    }
}
