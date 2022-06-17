<?php

declare(strict_types=1);

namespace Collectme\Misc;

use Collectme\Controller\AppController;

class ShortcodeHandler
{
    public function __construct(
        private readonly AppController $appController
    ) {
    }

    /**
     * @throws \JsonException
     */
    public function process(array|string|null $atts): string
    {
        $atts = (array)$atts;

        $defaults = [
            'cause' => ''
        ];

        $attributes = shortcode_atts($defaults, $atts);

        return $this->appController->cause($attributes['cause']);
    }
}