<?php

declare(strict_types=1);

/**
 * @wordpress-plugin
 * Plugin Name:       Collectme
 * Plugin URI:        https://github.com/grueneschweiz/collectme
 * Description:       Handle signature collections with ease.
 * Version:           2.0.1
 * Requires at least: 6.0
 * Requires PHP:      8.1
 * Author:            Cyrill Bolliger
 * Author URI:        https://github.com/cyrillbolliger
 * Text Domain:       collectme
 * Domain Path:       /languages
 * License:           GPL v2 or later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Update URI:        https://github.com/grueneschweiz/collectme
 */

if (!defined('WPINC')) {
    die;
}

const COLLECTME_VERSION = '2.0.0';
const COLLECTME_PLUGIN_NAME = __FILE__;
const COLLECTME_BASE_PATH = __DIR__;

require 'vendor/autoload.php';

// print hooks. enable for debugging
//add_action( 'all', function() {echo "<pre>\n" . current_action() . '</pre>';} );


function collectme_get_container(): \DI\Container
{
    if (defined('COLLECTME_DEPENDENCY_CONFIG_PATH')) {
        $configPath = COLLECTME_DEPENDENCY_CONFIG_PATH;
    } else {
        $configPath = COLLECTME_BASE_PATH . '/dependency-config.php';
    }

    $builder = new \DI\ContainerBuilder();
    $builder->addDefinitions($configPath);

    if (!(defined('WP_DEBUG') && WP_DEBUG)) {
        $builder->enableCompilation(__DIR__ . '/tmp');
        $builder->writeProxiesToFile(true, __DIR__ . '/tmp/proxies');
    }

    /** @noinspection PhpUnhandledExceptionInspection */
    return $builder->build();
}

function collectme_run(): void
{
    /** @noinspection PhpUnhandledExceptionInspection */
    collectme_get_container()->get(Collectme\Collectme::class)->init();
}

collectme_run();