<?php

/** @noinspection PhpUnused */
declare(strict_types=1);

namespace Collectme;

use Collectme\Misc\Installer;
use Collectme\Misc\MailScheduler;
use Collectme\Misc\ShortcodeHandler;
use Collectme\Misc\Translator;

/**
 * Shortcode identifier. Example shortcode: [collectme cause=asdf123]
 */
const SHORTCODE_TAG = 'collectme';

/**
 * Path to the directory holing the manifest.json and the assets' directory. Relative to plugin base path.
 */
const DIST_DIR = 'dist';

/**
 * Path to the manifest.json generated by vite
 */
const PATH_MANIFEST = COLLECTME_BASE_PATH . '/' . DIST_DIR . '/manifest.json';

/**
 * Translation related
 */
const TRANSLATION_DIR = 'languages';
const PATH_APP_STRINGS = COLLECTME_BASE_PATH . '/' . TRANSLATION_DIR . '/app-strings.php';
const PATH_POT_FILE = COLLECTME_BASE_PATH . '/' . TRANSLATION_DIR . '/collectme.pot';
const I18N_DEFAULT_CONTEXT = 'collectme';

/**
 * Routing related
 */
const REST_ROUTE_PREFIX = 'collectme';
const REST_V1_NAMESPACE = REST_ROUTE_PREFIX . '/v1';

const ASSET_PATH_REL = 'dist';

/**
 * Database related
 */
const DB_PREFIX = 'collectme_';

/**
 * Auth related
 */
const AUTH_COOKIE_KEY = 'wp-collectme-auth';
const AUTH_COOKIE_TTL = '5 years';
const AUTH_SESSION_KEY = 'WP_COLLECTME_AUTH';
const AUTH_SESSION_ACTIVATION_TIMEOUT = '15 minutes';

/**
 * Options API
 */
const OPTIONS_PREFIX = 'collectme_';
const OPTION_KEY_DB_VERSION = OPTIONS_PREFIX . 'db_version';
const OPTION_KEY_PLUGIN_VERSION = OPTIONS_PREFIX . 'plugin_version';

class Collectme
{
    private static string $causeUuid = '';

    public function __construct(
        private readonly Installer $installer,
        private readonly ShortcodeHandler $shortcodeHandler,
        private readonly RestRouterV1 $restRouter,
        private readonly Translator $translator,
        private readonly AdminRouter $adminRouter,
        private readonly MailScheduler $mailScheduler,
    ) {
    }

    public static function getCauseUuid(): string
    {
        return self::$causeUuid;
    }

    public static function setCauseUuid(string $causeUuid): void
    {
        self::$causeUuid = $causeUuid;
    }

    /**
     * @throws \JsonException
     */
    public function init(): void
    {
        $this->registerHooks();
        $this->registerShortcodes();
    }

    private function registerHooks(): void
    {
        /**
         * Installation and uninstallation
         */
        register_activation_hook(COLLECTME_PLUGIN_NAME, [$this->installer, 'activate']);
        add_action('wp_initialize_site', [$this->installer, 'afterSiteAdd']);
        register_deactivation_hook(COLLECTME_PLUGIN_NAME, [$this->installer, 'deactivate']);
        register_uninstall_hook(COLLECTME_PLUGIN_NAME, [Installer::class, 'uninstall']);
        add_action('admin_init', [$this->installer, 'afterPluginUpdated']);

        /**
         * REST API
         */
        add_action('rest_api_init', [$this->restRouter, 'init']);

        /**
         * Admin pages
         */
        add_action('admin_menu', [$this->adminRouter, 'init']);

        /**
         * Translations and overrides
         */
        add_action('init', [$this->translator, 'loadTextdomain']);
        add_filter('gettext_collectme', [$this->translator, 'overrideGettext'], 10, 2);
        add_filter('gettext_with_context_collectme', [$this->translator, 'overrideGettextWithContext'], 10, 3);
        add_filter('ngettext_collectme', [$this->translator, 'overrideNGettext'], 10, 4);
        add_filter('ngettext_with_context_collectme', [$this->translator, 'overrideNGettextWithContext'], 10, 5);

        /**
         * Cron jobs
         */
        add_action('collectme_schedule_mails', [$this->mailScheduler, 'run']);

        /**
         * Don't add styles and scripts the WordPress way, this doesn't allow to add them only if the
         * shortcode is present in combination with a timber based theme. Additionally, it's hacky
         * as we need to customize the script tag to support modules.
         *
         * Scripts and styles are therefore directly printed by the controller.
         */
        // add_action('wp_enqueue_scripts', '');
    }

    private function registerShortcodes(): void
    {
        add_shortcode(SHORTCODE_TAG, [$this->shortcodeHandler, 'process']);
    }
}