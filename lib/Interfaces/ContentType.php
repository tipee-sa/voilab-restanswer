<?php

declare(strict_types=1);

namespace Voilab\Restanswer\Interfaces;

use Voilab\Restanswer\Renderer;

interface ContentType
{
    public function render(mixed $content, Renderer $renderer, bool $newLineEOF = false): ?string;

    public function renderError(mixed $content, Renderer $renderer): ?string;
}
