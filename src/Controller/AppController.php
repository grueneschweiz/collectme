<?php

declare(strict_types=1);

namespace Collectme\Controller;

use Collectme\Misc\AssetLoader;


class AppController
{
    public function __construct(
        private readonly AssetLoader $assetLoader
    ) {
    }

    /**
     * @throws \JsonException
     */
    public function cause(string $causeUuid): string
    {
        $data = [
            'cause' => $causeUuid
        ];

        return '<div id="collectme-app"></div>'
            .$this->assetLoader->getStylesHtml()
            .$this->assetLoader->getScriptDataHtml('collectme', $data)
            .$this->assetLoader->getScriptsHtml();
    }
}