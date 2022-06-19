<?php

declare(strict_types=1);

namespace Collectme\Misc;

use Collectme\Exceptions\CollectmeException;

use const Collectme\OPTION_KEY_PLUGIN_VERSION;

class Installer
{
    public function __construct(
        private readonly DbInstaller $dbInstaller
    ) {
    }

    /**
     * @throws CollectmeException
     * @noinspection PhpDocRedundantThrowsInspection
     */
    public static function uninstall(): void
    {
        // this function must be static, so we can use be used by the register_uninstall_hook

        self::forEachSite([DbInstaller::class, 'removeTables']);
    }

    private static function forEachSite(callable $callback): void
    {
        // this function must be static, so we can use be used by the register_uninstall_hook

        if (!is_multisite()) {
            $callback();
            return;
        }

        $siteIds = get_sites(['fields' => 'ids', 'archived' => 0, 'deleted' => 0]);

        foreach ($siteIds as $siteId) {
            switch_to_blog($siteId);
            /** @noinspection DisconnectedForeachInstructionInspection */
            $callback();
            restore_current_blog();
        }
    }

    /**
     * @throws CollectmeException
     */
    public function activate(bool $networkWide): void
    {
        if ($networkWide) {
            self::forEachSite([$this->dbInstaller, 'setupTables']);
        } else {
            $this->dbInstaller->setupTables();
        }

        $this->storeCurrentPluginVersion();
    }

    private function storeCurrentPluginVersion(): void
    {
        update_option(OPTION_KEY_PLUGIN_VERSION, COLLECTME_VERSION);
    }

    public function deactivate(): void
    {
    }

    /**
     * @throws CollectmeException
     */
    public function afterPluginUpdated(): void
    {
        if (!$this->wasPluginUpdated()) {
            return;
        }

        $this->dbInstaller->setupTables();
        $this->storeCurrentPluginVersion();
    }

    private function wasPluginUpdated(): bool
    {
        $storedVersion = get_option(OPTION_KEY_PLUGIN_VERSION, '0');
        return 1 === version_compare(COLLECTME_VERSION, $storedVersion);
    }
}