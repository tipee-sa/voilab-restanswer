<?php

declare(strict_types=1);

namespace Voilab\Restanswer\ContentType;

use Voilab\Restanswer\Interfaces\ContentType;
use Voilab\Restanswer\Renderer;
use const JSON_THROW_ON_ERROR;

class Json implements ContentType
{
    public function render(mixed $content, Renderer $renderer, bool $newLineEOF = false): ?string
    {
        if (null !== $content) {
            return json_encode($content, JSON_THROW_ON_ERROR);
        }

        return null;
    }

    public function renderError(mixed $message, Renderer $renderer): ?string
    {
        return json_encode(['message' => $message], JSON_THROW_ON_ERROR);
    }
}
