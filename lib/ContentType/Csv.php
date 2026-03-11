<?php

declare(strict_types=1);

namespace Voilab\Restanswer\ContentType;

class Csv extends Separated
{
    protected string $separator = ';';
}
