<?php

declare(strict_types=1);

namespace Voilab\Restanswer\ContentType;

use InvalidArgumentException;
use Voilab\Restanswer\Interfaces\ContentType;
use Voilab\Restanswer\Renderer;
use function is_string;

class Standard implements ContentType
{
    public function render(mixed $content, Renderer $renderer, bool $newLineEOF = false): ?string
    {
        if (null === $content || is_string($content)) {
            return $content;
        }

        throw new InvalidArgumentException('Bad format according to the required response Content-Type.');
    }

    public function renderError(mixed $content, Renderer $renderer): ?string
    {
        return $this->render($content, $renderer);
    }
}
