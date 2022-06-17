<?php

declare(strict_types=1);

namespace Collectme\Controller;

use Collectme\Misc\AssetLoader;

use const Collectme\FRONTEND_SCRIPT_HANDLE;
use const Collectme\FRONTEND_STYLE_HANDLE;

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
        $this->assetLoader->requireScript(FRONTEND_SCRIPT_HANDLE);
        $this->assetLoader->requireStyle(FRONTEND_STYLE_HANDLE);

        $data = [
            'cause' => $causeUuid
        ];
        $this->assetLoader->addScriptData(FRONTEND_SCRIPT_HANDLE, 'collectme', $data);

        return '<div id="collectme-app"></div>';
    }
}