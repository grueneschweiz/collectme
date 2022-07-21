<?php

declare(strict_types=1);

namespace Collectme\Misc;

use const Collectme\ASSET_PATH_REL;
use const Collectme\OPTIONS_PREFIX;

class Settings
{
    private const STRING_OVERRIDES = 'string_overrides';
    private const OBJECTIVES = 'objectives';
    private const DEFAULT_OBJECTIVE = 'default_objective';
    private const EMAIL_CONFIG = 'email_config';
    private const CUSTOM_CSS = 'custom_css';

    private array $settings = [];

    public function getStringOverrides(string $causeUuid): array
    {
        $overrides = $this->get(self::STRING_OVERRIDES, $causeUuid);

        return is_array($overrides) ? $overrides : [];
    }

    private function get(string $key, string $causeUuid): mixed
    {
        if (!isset($this->settings[$causeUuid])) {
            $this->settings[$causeUuid] = get_option(OPTIONS_PREFIX . $causeUuid, []);
        }

        if (!is_array($this->settings[$causeUuid]) || !isset($this->settings[$causeUuid][$key])) {
            return null;
        }

        return $this->settings[$causeUuid][$key];
    }

    public function getObjectives(string $causeUuid): array
    {
        $objectives = $this->get(self::OBJECTIVES, $causeUuid) ?? [];
        $defaults = $this->getObjectivesDefaults();

        return array_replace_recursive($defaults, $objectives);
    }

    public function getEmailConfig(string $causeUuid): array
    {
        $config = $this->get(self::EMAIL_CONFIG, $causeUuid) ?? [];
        $defaults = $this->getEmailConfigDefaults();

        return array_replace_recursive($defaults, $config);
    }

    public function setStringOverrides(array $overrides, string $causeUuid): void
    {
        $this->set(self::STRING_OVERRIDES, $overrides, $causeUuid);
    }

    public function setObjectives(array $objectives, string $causeUuid): void
    {
        $this->set(self::OBJECTIVES, $objectives, $causeUuid);
    }

    public function setEmailConfig(array $config, string $causeUuid): void
    {
        $this->set(self::EMAIL_CONFIG, $config, $causeUuid);
    }

    private function set(string $key, array $values, string $causeUuid): void
    {
        $this->settings[$causeUuid][$key] = $values;

        update_option(OPTIONS_PREFIX . $causeUuid, $this->settings[$causeUuid], false);
    }

    public function getObjectivesDefaults(): array
    {
        return [
            'sm' => [
                'id' => 'sm',
                'name' => __('Small', 'collectme'),
                'enabled' => true,
                'objective' => 50,
                'img' => plugin_dir_url(COLLECTME_PLUGIN_NAME) . ASSET_PATH_REL . '/img/goal-sm.png',
                'hot' => false,
            ],
            'md' => [
                'id' => 'md',
                'name' => __('Medium', 'collectme'),
                'enabled' => true,
                'objective' => 100,
                'img' => plugin_dir_url(COLLECTME_PLUGIN_NAME) . ASSET_PATH_REL . '/img/goal-md.png',
                'hot' => false,
            ],
            'lg' => [
                'id' => 'lg',
                'name' => __('Large', 'collectme'),
                'enabled' => true,
                'objective' => 200,
                'img' => plugin_dir_url(COLLECTME_PLUGIN_NAME) . ASSET_PATH_REL . '/img/goal-lg.png',
                'hot' => true,
            ],
            'xl' => [
                'id' => 'xl',
                'name' => __('Extra large', 'collectme'),
                'enabled' => true,
                'objective' => 500,
                'img' => plugin_dir_url(COLLECTME_PLUGIN_NAME) . ASSET_PATH_REL . '/img/goal-xl.png',
                'hot' => false,
            ],
        ];
    }

    public function getEmailConfigDefaults(): array
    {
        $domain = preg_replace(
            '/^www\./',
            '',
            wp_parse_url( network_home_url(), PHP_URL_HOST ) ?? 'example.com'
        );

        return [
            'fromName' => get_bloginfo('name'),
            'fromAddress' => "website@$domain",
            'replyToAddress' => get_bloginfo('admin_email'),
        ];
    }

    public function setCustomCss(string $css, string $causeUuid): void
    {
        $this->set(self::CUSTOM_CSS, [$css], $causeUuid);
    }

    public function getCustomCss(string $causeUuid): string
    {
        $customCss = $this->get(self::CUSTOM_CSS, $causeUuid);

        return $customCss[0] ?? '';
    }

    public function getDefaultObjective(string $causeUuid): array
    {
        $defaultObjective = $this->get(self::DEFAULT_OBJECTIVE, $causeUuid) ?? [];
        $defaults = [
            'id' => 'default',
            'name' => __('Default', 'collectme'),
            'enabled' => true,
            'objective' => 0,
            'img' => plugin_dir_url(COLLECTME_PLUGIN_NAME) . ASSET_PATH_REL . '/img/goal-default.png',
            'hot' => false,
        ];

        return array_replace($defaults, $defaultObjective);
    }

    public function setDefaultObjective(array $defaultObjective, string $causeUuid): void
    {
        $this->set(self::DEFAULT_OBJECTIVE, $defaultObjective, $causeUuid);
    }
}