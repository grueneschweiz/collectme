<?php

declare(strict_types=1);

namespace Collectme\Misc;

use Collectme\Collectme;

class Translator
{
    private const DEFAULT_CONTEXT = 'collectme';

    private array $overrides;
    private string $lang;

    public function __construct(
        private readonly Settings $settings
    ) {
    }

    public function overrideNGettext(
        string $translation,
        string $single,
        string $plural,
        int $number
    ): string {
        $text = $number === 1 ? $single : $plural;

        return $this->overrideGettext($translation, $text);
    }

    public function overrideGettext(
        string $translation,
        string $text
    ): string {
        $cause = Collectme::getCauseUuid();
        if (! $cause) {
            return $translation;
        }

        return $this->hasOverride($cause, $text, self::DEFAULT_CONTEXT)
            ? $this->getOverride($cause, $text, self::DEFAULT_CONTEXT)
            : $translation;
    }

    public function hasOverride(string $causeUuid, string $text, ?string $context): bool
    {
        if (!$context) {
            $context = self::DEFAULT_CONTEXT;
        }

        return $this->getOverride($causeUuid, $text, $context) !== null;
    }

    public function getOverride(string $causeUuid, string $text, ?string $context): string|null
    {
        if (!$context) {
            $context = self::DEFAULT_CONTEXT;
        }

        return $this->getOverrides($causeUuid)[$text][$context] ?? null;
    }

    private function getOverrides(string $causeUuid): array
    {
        $lang = $this->getLanguageCode();

        if (!isset($this->overrides[$causeUuid])) {
            $this->overrides[$causeUuid] = $this->settings->getStringOverrides($causeUuid)[$lang] ?? [];
        }

        return $this->overrides[$causeUuid];
    }

    private function getLanguageCode(): string
    {
        if (!isset($this->lang)) {
            $this->lang = substr(get_locale(), 0, 2);
        }

        return $this->lang;
    }

    public function overrideNGettextWithContext(
        string $translation,
        string $single,
        string $plural,
        int $number,
        string $context
    ): string {
        $text = $number === 1 ? $single : $plural;

        return $this->overrideGettextWithContext($translation, $text, $context);
    }

    public function overrideGettextWithContext(
        string $translation,
        string $text,
        string $context
    ): string {
        $cause = Collectme::getCauseUuid();
        if (! $cause) {
            return $translation;
        }

        return $this->hasOverride($cause, $text, $context) ? $this->getOverride($cause, $text, $context) : $translation;
    }

    public function addOverride(string $causeUuid, string $text, string $translation, ?string $context): void
    {
        if (!$context) {
            $context = self::DEFAULT_CONTEXT;
        }

        if (!isset($this->overrides[$causeUuid])) {
            $this->getOverrides($causeUuid);
        }

        $this->overrides[$causeUuid][$text][$context] = $translation;
    }

    public function removeOverride(string $causeUuid, string $text, ?string $context): void
    {
        if (!$context) {
            $context = self::DEFAULT_CONTEXT;
        }

        if (!isset($this->overrides[$causeUuid])) {
            $this->getOverrides($causeUuid);
        }

        unset($this->overrides[$causeUuid][$text][$context]);
    }

    public function saveOverrides(string $causeUuid): void
    {
        $lang = $this->getLanguageCode();

        $overrides = $this->settings->getStringOverrides($causeUuid);
        $overrides[$lang] = $this->getOverrides($causeUuid);

        $this->settings->setStringOverrides($overrides, $causeUuid);
    }
}