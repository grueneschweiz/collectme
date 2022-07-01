<?php

declare(strict_types=1);

namespace Collectme\Controller;

use Collectme\Misc\AssetLoader;

use const Collectme\PATH_APP_STRINGS;


class HtmlController
{
    public function __construct(
        private readonly AssetLoader $assetLoader
    ) {
    }

    /**
     * @throws \JsonException
     */
    public function index(string $causeUuid, array $stringOverwrites): string
    {
        $translations = require PATH_APP_STRINGS;

        $data = [
            'cause' => $causeUuid,
            't' => array_replace_recursive($translations, $stringOverwrites)
        ];

        return '<div id="collectme-app"></div>'
            .$this->assetLoader->getStylesHtml()
            .$this->assetLoader->getScriptDataHtml('collectme', $data)
            .$this->assetLoader->getScriptsHtml();
    }
}