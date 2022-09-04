<?php

declare(strict_types=1);

namespace Collectme\Misc;

use Collectme\Collectme;
use Collectme\Controller\Validators\CauseUuidValidator;
use Collectme\FrontendRouter;

class ShortcodeHandler
{
    public function __construct(
        private readonly FrontendRouter $router
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
        ];

        $args = shortcode_atts($defaults, $atts);

        if (!CauseUuidValidator::check($args['causeuuid']) && !is_admin()) {
            /** @noinspection ForgottenDebugOutputInspection */
            wp_die('Invalid cause in shortcode.');
        }

        Collectme::setCauseUuid($args['causeuuid']);

        return $this->router->init($args);
    }
}