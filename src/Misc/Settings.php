<?php

declare(strict_types=1);

namespace Collectme\Misc;

use Collectme\Model\Entities\EnumMessageKey;
use Collectme\Model\Entities\Stat;

use const Collectme\ASSET_PATH_REL;
use const Collectme\OPTIONS_PREFIX;

class Settings
{
    private const STRING_OVERRIDES = 'string_overrides';
    private const OBJECTIVES = 'objectives';
    private const DEFAULT_OBJECTIVE = 'default_objective';
    private const EMAIL_CONFIG = 'email_config';
    private const CUSTOM_CSS = 'custom_css';
    private const PLEDGE_SETTINGS = 'pledge_settings';
    private const SIGNATURE_SETTINGS = 'signature_settings';
    private const TIMINGS = 'timings';
    private const MAIL_DELAYS = 'mail_delays';
    private static Settings $instance;
    private array $settings = [];

    public function __construct()
    {
        self::$instance = $this;
    }

    public static function getInstance(): self
    {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

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

    public function getEmailConfig(string $causeUuid): array
    {
        $config = $this->get(self::EMAIL_CONFIG, $causeUuid) ?? [];
        $defaults = $this->getEmailConfigDefaults();

        return array_replace_recursive($defaults, $config);
    }

    public function getEmailConfigDefaults(): array
    {
        $domain = preg_replace(
            '/^www\./',
            '',
            wp_parse_url(network_home_url(), PHP_URL_HOST) ?? 'example.com'
        );

        return [
            'fromName' => get_bloginfo('name'),
            'fromAddress' => "website@$domain",
            'replyToAddress' => get_bloginfo('admin_email'),
        ];
    }

    public function setStringOverrides(array $overrides, string $causeUuid): void
    {
        $this->set(self::STRING_OVERRIDES, $overrides, $causeUuid);
    }

    private function set(string $key, array $values, string $causeUuid): void
    {
        $this->settings[$causeUuid] = get_option(OPTIONS_PREFIX . $causeUuid, []);
        $this->settings[$causeUuid][$key] = $values;

        update_option(OPTIONS_PREFIX . $causeUuid, $this->settings[$causeUuid], false);
    }

    public function setObjectives(array $objectives, string $causeUuid): void
    {
        $this->set(self::OBJECTIVES, $objectives, $causeUuid);
    }

    public function setEmailConfig(array $config, string $causeUuid): void
    {
        $this->set(self::EMAIL_CONFIG, $config, $causeUuid);
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
        $defaults = $this->getDefaultObjectiveDefaults();

        return array_replace($defaults, $defaultObjective);
    }

    public function getDefaultObjectiveDefaults(): array
    {
        return [
            'id' => 'default',
            'name' => __('Default', 'collectme'),
            'enabled' => true,
            'objective' => 0,
            'img' => plugin_dir_url(COLLECTME_PLUGIN_NAME) . ASSET_PATH_REL . '/img/goal-default.png',
            'hot' => false,
        ];
    }

    public function setDefaultObjective(array $defaultObjective, string $causeUuid): void
    {
        $this->set(self::DEFAULT_OBJECTIVE, $defaultObjective, $causeUuid);
    }

    public function getPledgeSettings(string $causeUuid): array
    {
        $settings = $this->get(self::PLEDGE_SETTINGS, $causeUuid) ?? [];
        $defaults = [
            'objective' => 100000,
            'offset' => 0,
        ];

        return array_replace($defaults, $settings);
    }

    public function getSignatureSettings(string $causeUuid): array
    {
        $settings = $this->get(self::SIGNATURE_SETTINGS, $causeUuid) ?? [];
        $defaults = [
            'objective' => 100000,
            'offset' => 0,
        ];

        return array_replace($defaults, $settings);
    }

    public function setPledgeSettings(array $settings, string $causeUuid): void
    {
        $this->set(self::PLEDGE_SETTINGS, $settings, $causeUuid);
        Stat::clearCache($causeUuid);
    }

    public function setSignatureSettings(array $settings, string $causeUuid): void
    {
        $this->set(self::SIGNATURE_SETTINGS, $settings, $causeUuid);
        Stat::clearCache($causeUuid);
    }

    /**
     * @param string $causeUuid
     * @return \DateTime[]|null[]
     */
    public function getTimings(string $causeUuid): array
    {
        $timings = $this->get(self::TIMINGS, $causeUuid) ?? [];
        $defaults = [
            'start' => null,
            'stop' => null,
        ];

        return array_replace($defaults, $timings);
    }

    /**
     * @param \DateTime[]|null[] $settings
     * @param string $causeUuid
     */
    public function setTimings(array $settings, string $causeUuid): void
    {
        $this->set(self::TIMINGS, $settings, $causeUuid);
    }

    /**
     * @param string $causeUuid
     * @return \DateInterval[]|null[]
     */
    public function getMailDelays(string $causeUuid): array
    {
        $mails = $this->get(self::MAIL_DELAYS, $causeUuid) ?? [];
        $defaults = [
            EnumMessageKey::COLLECTION_REMINDER->value => null,
            EnumMessageKey::OBJECTIVE_CHANGE->value => null,
        ];

        return array_replace($defaults, $mails);
    }

    /**
     * @param \DateInterval[]|null[] $settings
     * @param string $causeUuid
     */
    public function setMailDelays(array $settings, string $causeUuid): void
    {
        $this->set(self::MAIL_DELAYS, $settings, $causeUuid);
    }
}