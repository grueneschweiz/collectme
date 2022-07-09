<?php

declare(strict_types=1);

namespace Collectme\Misc;

use const Collectme\SETTINGS_PREFIX;

class Settings
{
    private const STRING_OVERRIDES = 'string_overrides';

    private array $settings = [];

    public function getStringOverrides(string $causeUuid): array
    {
        $overrides = $this->get(self::STRING_OVERRIDES, $causeUuid);

        return is_array($overrides) ? $overrides : [];
    }

    private function get(string $key, string $causeUuid): mixed
    {
        if (!isset($this->settings[$causeUuid])) {
            $this->settings[$causeUuid] = get_option(SETTINGS_PREFIX . $causeUuid, []);
        }

        if (!is_array($this->settings[$causeUuid]) || !isset($this->settings[$causeUuid][$key])) {
            return null;
        }

        return $this->settings[$causeUuid][$key];
    }

    public function setStringOverrides(array $overrides, string $causeUuid): void
    {
        $this->set(self::STRING_OVERRIDES, $overrides, $causeUuid);
    }

    private function set(string $key, array $overrides, string $causeUuid): void
    {
        $this->settings[$causeUuid][$key] = $overrides;

        update_option(SETTINGS_PREFIX . $causeUuid, $this->settings[$causeUuid], false);
    }
}