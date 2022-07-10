<?php

declare(strict_types=1);

namespace Collectme\Controller;

use Collectme\Misc\AssetLoader;

use Collectme\Misc\Settings;

use const Collectme\ASSET_PATH_REL;
use const Collectme\PATH_APP_STRINGS;
use const Collectme\REST_V1_NAMESPACE;


class HtmlController
{
    public function __construct(
        private readonly AssetLoader $assetLoader,
        private readonly Settings $settings,
    ) {
    }

    /**
     * @throws \JsonException
     */
    public function index(string $causeUuid): string
    {
        $translations = require PATH_APP_STRINGS;

        $data = [
            'apiBaseUrl' => rest_url(REST_V1_NAMESPACE),
            'assetBaseUrl' => plugin_dir_url(COLLECTME_PLUGIN_NAME) . ASSET_PATH_REL,
            'cause' => $causeUuid,
            'encodedAdminEmail' => base64_encode(get_bloginfo('admin_email')), // yeah, yeah, spam. but it's good enough
            'locale' => get_locale(),
            'nonce' => wp_create_nonce('wp_rest'),
            'objectives' => $this->settings->getObjectives($causeUuid),
            't' => $translations,
        ];

        return '<div id="collectme-app"></div>'
            . $this->assetLoader->getStylesHtml()
            . $this->assetLoader->getScriptDataHtml('collectme', $data)
            . $this->assetLoader->getScriptsHtml();
    }
}