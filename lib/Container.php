<?php

declare(strict_types=1);

namespace Voilab\Restanswer;

use Pimple\Container as PimpleContainer;
use Voilab\Restanswer\ContentType\Csv;
use Voilab\Restanswer\ContentType\Json;
use Voilab\Restanswer\ContentType\Standard;
use Voilab\Restanswer\ContentType\Tab;
use Voilab\Restanswer\ContentType\Text;
use Voilab\Restanswer\Interfaces\ContentType;

/**
 * @phpstan-type RestConfig array{
 *     'content-type': string,
 *     mimetypes: array<string, string>,
 *     codeTranslator: array<string|int, int>,
 *     processorMapping: array{propertyArrayAccessCheck?: bool},
 * }
 */
class Container extends PimpleContainer
{
    /**
     * @param array<string, mixed> $config
     */
    public function __construct(array $config = [])
    {
        parent::__construct();

        $this['config'] = array_merge([
            'content-type' => 'application/json',
            'mimetypes' => [
                'application/json' => 'json',
                'json' => 'json',
                'text/html' => 'string',
                'text/csv' => 'csv',
                'text/tab-separated-values' => 'tab',
                'default' => 'default',
                'standard' => 'default',
            ],
            'codeTranslator' => [],
            'processorMapping' => [
                'propertyArrayAccessCheck' => true,
            ],
        ], $config);

        $this['response'] = $this->factory(fn (): Response => new Response($this));
        $this['renderer'] = $this->factory(fn (): Renderer => new Renderer($this));
        $this['processor'] = $this->factory(fn (): Processor => new Processor($this));

        $this['defaultContentType'] = fn (): Standard => new Standard();
        $this['jsonContentType'] = fn (): Json => new Json();
        $this['csvContentType'] = fn (): Csv => new Csv();
        $this['tabContentType'] = fn (): Tab => new Tab();
        $this['stringContentType'] = fn (): Text => new Text();
    }

    /**
     * @return RestConfig
     */
    public function config(): array
    {
        /** @var RestConfig */
        return $this['config'];
    }

    public function response(): Response
    {
        /** @var Response */
        return $this['response'];
    }

    public function renderer(): Renderer
    {
        /** @var Renderer */
        return $this['renderer'];
    }

    public function processor(): Processor
    {
        /** @var Processor */
        return $this['processor'];
    }

    /**
     * @param 'defaultContentType'|'jsonContentType'|'csvContentType'|'tabContentType'|'stringContentType' $name
     */
    public function contentTypeAdapter(string $name): ContentType
    {
        /** @var ContentType */
        return $this[$name];
    }
}
