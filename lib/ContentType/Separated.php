<?php

declare(strict_types=1);

namespace Voilab\Restanswer\ContentType;

use InvalidArgumentException;
use Voilab\Restanswer\Interfaces\ContentType;
use Voilab\Restanswer\Renderer;
use function is_array;
use function is_string;

class Separated implements ContentType
{
    protected string $separator = ',';

    public function render(mixed $content, Renderer $renderer, bool $newLineEOF = false): ?string
    {
        if (is_string($content)) {
            return $content;
        }

        if (is_array($content)) {
            /** @var list<array<mixed>> $content */
            $formatted = array_map(fn (array $line): string => implode($this->separator, $line), $content);

            if ($renderer->getOption('headings', false)) {
                /** @var array<mixed> $first */
                $first = array_shift($content);
                $heading = implode($this->separator, array_keys($first));
                array_unshift($formatted, $heading);
            }

            /** @var string $linebreak */
            $linebreak = $renderer->getOption('linebreak', "\n");
            $plainText = implode($linebreak, $formatted);

            if ($newLineEOF) {
                $plainText .= $linebreak;
            }

            return $plainText;
        }

        throw new InvalidArgumentException('Bad format according to the required response Content-Type.');
    }

    public function renderError(mixed $content, Renderer $renderer): ?string
    {
        return $this->render($content, $renderer);
    }
}
