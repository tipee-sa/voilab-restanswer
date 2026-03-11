<?php

declare(strict_types=1);

namespace Voilab\Restanswer\ContentType;

use Voilab\Restanswer\Interfaces\ContentType;
use Voilab\Restanswer\Renderer;

class Standard implements ContentType
{
    public function render(mixed $content, Renderer $renderer, bool $newLineEOF = false): ?string
    {
        /** @var string|null */
        return $content;
    }

    public function renderError(mixed $content, Renderer $renderer): ?string
    {
        /** @var string|null */
        return $content;
    }
}
