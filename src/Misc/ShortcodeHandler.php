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
            'causeuuid' => '',
            'stringoverwritesjson' => '{}'
        ];

        $args = shortcode_atts($defaults, $atts);

        try {
            $stringOverwrites = json_decode($args['stringoverwritesjson'], true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            $stringOverwrites = [];
            trigger_error(
                "Invalid JSON in collectme shortcode's stringOverwritesJson property. Overwrites ignored.",
                E_USER_NOTICE
            );
        }

        return $this->appController->index(
            $args['causeuuid'],
            $stringOverwrites
        );
    }
}