<?php

declare(strict_types=1);

namespace Collectme\Misc;

use JsonException;

use const Collectme\DIST_DIR;
use const Collectme\PATH_MANIFEST;

class AssetLoader
{
    private array $manifest;

    /**
     * @throws JsonException
     */
    public function getScriptDataHtml(string $jsVarName, array $data): string
    {
        $pretty = $this->isDevMode() ? JSON_PRETTY_PRINT : 0;
        $json = json_encode($data, JSON_THROW_ON_ERROR | $pretty);
        return "<script>const $jsVarName = $json;</script>";
    }

    private function isDevMode(): bool
    {
        return defined('SCRIPT_DEBUG') && SCRIPT_DEBUG;
    }

    /**
     * @throws JsonException
     */
    public function getScriptsHtml(): string
    {
        /** @noinspection HtmlUnknownTarget */
        $template = '<script type="module" src="%s"></script>';
        $htmlTags = array_map(static fn($src) => sprintf($template, $src), $this->getScriptUrls());
        return implode('', $htmlTags);
    }

    /**
     * @throws JsonException
     */
    private function getScriptUrls(): array
    {
        if ($this->isDevMode()) {
            $defaultDevServerUrl = 'http://localhost:3000';
            $serverUrl = function_exists('getenv_docker') ?
                getenv_docker('NODEJS_DEV_SERVER_BASE_URL', $defaultDevServerUrl)
                : $defaultDevServerUrl;

            $spaUrl = "$serverUrl/wp-content/plugins/collectme/app/src/main.ts";
        } else {
            $spaUrl = plugin_dir_url(COLLECTME_PLUGIN_NAME)
                . DIST_DIR . '/'
                . $this->getManifest()['index.html']['file'];
        }

        return [
            $spaUrl,
        ];
    }

    /**
     * @throws JsonException
     */
    private function getManifest(): array
    {
        if (empty($this->manifest)) {
            $manifestData = file_get_contents(PATH_MANIFEST);
            $this->manifest = json_decode($manifestData, true, 512, JSON_THROW_ON_ERROR);
        }

        return $this->manifest;
    }

    /**
     * @throws JsonException
     */
    public function getStylesHtml(): string
    {
        /** @noinspection HtmlUnknownTarget */
        $template = '<link rel="stylesheet" href="%s">';
        $htmlTags = array_map(static fn($src) => sprintf($template, $src), $this->getStylesUrls());
        return implode('', $htmlTags);
    }

    /**
     * @throws JsonException
     */
    private function getStylesUrls(): array
    {
        if ($this->isDevMode()) {
            return []; // the styles are inlined in the css
        }

        return [
            plugin_dir_url(COLLECTME_PLUGIN_NAME)
            . DIST_DIR . '/'
            . $this->getManifest()['index.html']['css'][0],
        ];
    }
}