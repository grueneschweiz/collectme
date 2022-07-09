<?php

declare(strict_types=1);

namespace Collectme\Misc;

use Collectme\Collectme;
use Collectme\Controller\HtmlController;
use Collectme\Controller\Http\UuidValidator;

class ShortcodeHandler
{
    public function __construct(
        private readonly HtmlController $appController
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

        if (!UuidValidator::check($args['causeuuid'])) {
            /** @noinspection ForgottenDebugOutputInspection */
            wp_die('Invalid shortcode.');
        }

        Collectme::setCauseUuid($args['causeuuid']);

        return $this->appController->index(
            $args['causeuuid'],
        );
    }
}