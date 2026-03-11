<?php

declare(strict_types=1);

namespace Voilab\Restanswer\ContentType;

use InvalidArgumentException;
use Voilab\Restanswer\Interfaces\ContentType;
use Voilab\Restanswer\Renderer;
use function is_object;
use function is_string;
use function sprintf;

class Text implements ContentType
{
    public function render(mixed $content, Renderer $renderer, bool $newLineEOF = false): ?string
    {
        if (is_string($content)) {
            return $content;
        }

        if (is_object($content)) {
            if (method_exists($content, 'toString')) {
                /** @var string */
                return $content->toString();
            }

            return sprintf('Instance of %s', $content::class);
        }

        throw new InvalidArgumentException('Bad format according to the required response Content-Type.');
    }

    public function renderError(mixed $content, Renderer $renderer): ?string
    {
        return $this->render($content, $renderer);
    }
}
