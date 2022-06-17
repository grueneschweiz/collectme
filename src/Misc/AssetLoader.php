<?php

declare(strict_types=1);

namespace Collectme\Misc;

use JsonException;

use const Collectme\DIST_DIR;
use const Collectme\FRONTEND_SCRIPT_HANDLE;
use const Collectme\FRONTEND_STYLE_HANDLE;
use const Collectme\MANIFEST_PATH;

class AssetLoader
{
    private array $manifest;
    private array $requiredStyles = [];
    private array $requiredScripts = [];
    private array $addedScriptData = [];

    public function requireScript(string $handle): void
    {
        $this->requiredScripts[] = $handle;
    }

    public function requireStyle(string $handle): void
    {
        $this->requiredStyles[] = $handle;
    }

    /**
     * @throws JsonException
     */
    public function addScriptData(string $scriptHandle, string $jsVarName, array $data): void
    {
        $pretty = defined('WP_DEBUG') && WP_DEBUG ? JSON_PRETTY_PRINT : 0;
        $json = json_encode($data, JSON_THROW_ON_ERROR | $pretty);
        $this->addedScriptData[$scriptHandle] = "const $jsVarName = $json;";
    }

    public function modifyScriptTag(string $tag, string $handle, string $src): string
    {
        if (FRONTEND_SCRIPT_HANDLE === $handle) {
            return str_replace('<script src=', '<script type="module" src=', $tag);
        }

        return $tag;
    }

    /**
     * @throws JsonException
     */
    public function enqueueAssets(): void
    {
        $styles = $this->getStyles();
        foreach ($this->requiredStyles as $handle) {
            $style = $styles[$handle];
            wp_enqueue_style(...$style);
        }

        $scripts = $this->getScripts();
        foreach ($this->requiredScripts as $handle) {
            $script = $scripts[$handle];
            wp_enqueue_script(...$script);
        }

        foreach ($this->addedScriptData as $handle => $data) {
            wp_add_inline_script($handle, $data, 'before');
        }
    }

    /**
     * @throws JsonException
     */
    private function getStyles(): array
    {
        return [
            FRONTEND_STYLE_HANDLE => [
                'handle' => FRONTEND_STYLE_HANDLE,
                'src' => plugin_dir_url(COLLECTME_PLUGIN_NAME)
                    . DIST_DIR . '/'
                    . $this->getManifest()['index.html']['css'][0],
            ],
        ];
    }

    /**
     * @throws JsonException
     */
    private function getManifest(): array
    {
        if (empty($this->manifest)) {
            $manifestData = file_get_contents(MANIFEST_PATH);
            $this->manifest = json_decode($manifestData, true, 512, JSON_THROW_ON_ERROR);
        }

        return $this->manifest;
    }

    /**
     * @throws JsonException
     */
    private function getScripts(): array
    {
        return [
            FRONTEND_SCRIPT_HANDLE => [
                'handle' => FRONTEND_SCRIPT_HANDLE,
                'src' => plugin_dir_url(COLLECTME_PLUGIN_NAME)
                    . DIST_DIR . '/'
                    . $this->getManifest()['index.html']['file'],
                'deps' => [],
                'ver' => COLLECTME_VERSION,
                'in_footer' => true
            ],
        ];
    }
}