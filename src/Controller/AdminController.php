<?php

declare(strict_types=1);

namespace Collectme\Controller;

use Collectme\Controller\Validators\CauseUuidValidator;
use Collectme\Controller\Validators\EmailValidator;
use Collectme\Controller\Validators\StringValidator;
use Collectme\Controller\Validators\UrlValidator;
use Collectme\Exceptions\CollectmeDBException;
use Collectme\Misc\Settings;
use Collectme\Misc\Translator;
use Collectme\Model\Entities\Cause;
use Collectme\Model\Entities\EnumMessageKey;
use Gettext\Loader\PoLoader;
use Gettext\Translation;

use const Collectme\I18N_DEFAULT_CONTEXT;
use const Collectme\PATH_POT_FILE;

class AdminController
{
    private const NONCE_ACTION = 'collectme-settings';

    public function __construct(
        private readonly Translator $translator,
        private readonly Settings $settings,
    ) {
    }

    public function showSettings(): void
    {
        if (!current_user_can('manage_options')) {
            /** @noinspection ForgottenDebugOutputInspection */
            wp_die(__('You do not have sufficient permissions to access this page.', 'collectme'));
        }

        if (!empty($_POST['cause']) && wp_verify_nonce($_POST['_wpnonce'], self::NONCE_ACTION)) {
            $causeUuid = $_POST['cause'];
            if (!CauseUuidValidator::check($causeUuid)) {
                /** @noinspection ForgottenDebugOutputInspection */
                wp_die('Invalid data');
            }

            if (
                $this->saveEmailConfigs($causeUuid)
                && $this->saveObjectives($causeUuid)
                && $this->saveDefaultObjective($causeUuid)
                && $this->saveSignatureSettings($causeUuid)
                && $this->savePledgeSettings($causeUuid)
                && $this->saveTimings($causeUuid)
                && $this->saveMailDelays($causeUuid)
                && $this->saveCustomCss($causeUuid)
                && $this->saveOverrides($causeUuid)
            ) {
                echo '<div class="notice notice-success is-dismissible"><p>' . __('Saved!', 'collectme') . '</p></div>';
            }
        }

        if (!empty($_POST['delete-cause']) && wp_verify_nonce($_POST['_wpnonce'], self::NONCE_ACTION)) {
            $this->deleteCause();
        }

        if (!empty($_POST['create-cause']) && wp_verify_nonce($_POST['_wpnonce'], self::NONCE_ACTION)) {
            $this->createCause();
        }

        $nonce = wp_create_nonce(self::NONCE_ACTION);

        try {
            $causes = Cause::findAll();
        } catch (CollectmeDBException $e) {
            /** @noinspection ForgottenDebugOutputInspection */
            wp_die('Database error. Failed to load causes. Try again.');
        }
        usort($causes, static fn($b, $a) => $a->created <=> $b->created);

        $poLoader = new PoLoader();
        $stringTemplates = $poLoader
            ->loadFile(PATH_POT_FILE)
            ->getTranslations();

        // Show translations that should be overridden first
        uasort($stringTemplates, static function (Translation $a, Translation $b) {
            $aOverride = stripos(implode('; ', $a->getExtractedComments()->toArray()), 'Translators: Override');
            $bOverride = stripos(implode('; ', $b->getExtractedComments()->toArray()), 'Translators: Override');

            return match (true) {
                false === $aOverride && $bOverride !== false => 1,
                false !== $aOverride && $bOverride === false => -1,
                default => 0
            };
        });

        $translator = $this->translator;
        $defaultContext = I18N_DEFAULT_CONTEXT;

        $settings = $this->settings;

        foreach ($causes as $cause) {
            $this->initCodeEditor($cause->uuid);
        }

        include COLLECTME_BASE_PATH . '/views/admin/settings.php';
    }

    private function saveEmailConfigs(string $causeUuid): bool
    {
        $fromName = strip_tags(trim($_POST['email']['fromName'] ?? ''));
        $fromAddress = trim($_POST['email']['fromAddress'] ?? '');
        $replyToAddress = trim($_POST['email']['replyToAddress'] ?? '');

        if (!StringValidator::check($fromName, 1, 80) ||
            !EmailValidator::check($fromAddress) ||
            !EmailValidator::check($replyToAddress)
        ) {
            echo '<div class="notice notice-error is-dismissible"><p>' . __(
                    'Invalid email config.',
                    'collectme'
                ) . '</p></div>';
            return false;
        }

        $config = [
            'fromName' => $fromName,
            'fromAddress' => $fromAddress,
            'replyToAddress' => $replyToAddress,
        ];

        $this->settings->setEmailConfig($config, $causeUuid);

        return true;
    }

    private function saveObjectives(string $causeUuid): bool
    {
        $objectives = $this->settings->getObjectivesDefaults();

        foreach ($_POST['objective'] as $key => $attr) {
            // validate key
            if (!array_key_exists($key, $objectives)) {
                echo '<div class="notice notice-error is-dismissible"><p>' . __(
                        'Invalid objective key.',
                        'collectme'
                    ) . '</p></div>';
                return false;
            }

            $objective = (int)$attr['objective'];
            if ($objective < 1 || $objective > 1000000) {
                echo '<div class="notice notice-error is-dismissible"><p>' . __(
                        'Invalid goal.',
                        'collectme'
                    ) . '</p></div>';
                return false;
            }
            $objectives[$key]['objective'] = $objective;

            $img = $attr['img'] ?? null;
            if (!UrlValidator::check($img, 'http')) {
                echo '<div class="notice notice-error is-dismissible"><p>' . __(
                        'Invalid image url.',
                        'collectme'
                    ) . '</p></div>';
                return false;
            }
            if (!empty($img)) {
                $objectives[$key]['img'] = $img;
            }

            $objectives[$key]['hot'] = array_key_exists('hot', $attr);
            $objectives[$key]['enabled'] = array_key_exists('enabled', $attr);
        }

        $this->settings->setObjectives($objectives, $causeUuid);

        return true;
    }

    private function saveDefaultObjective(string $causeUuid): bool
    {
        $objective = $this->settings->getDefaultObjective($causeUuid);

        $img = $_POST['defaultObjective']['img'] ?? null;
        if (!empty($img) && !UrlValidator::check($img, 'http')) {
            echo '<div class="notice notice-error is-dismissible"><p>' . __(
                    'Invalid image url.',
                    'collectme'
                ) . '</p></div>';
            return false;
        }
        if (empty($img)) {
            $objective = $this->settings->getDefaultObjectiveDefaults();
        } else {
            $objective['img'] = $img;
        }

        $this->settings->setDefaultObjective($objective, $causeUuid);

        return true;
    }

    private function saveSignatureSettings(string $causeUuid): bool
    {
        $objective = absint($_POST['signatures']['objective']);
        $offset = absint($_POST['signatures']['offset']);

        $this->settings->setSignatureSettings([
            'objective' => $objective,
            'offset' => $offset,
        ], $causeUuid);

        return true;
    }

    private function savePledgeSettings(string $causeUuid): bool
    {
        $objective = absint($_POST['pledges']['objective']);
        $offset = absint($_POST['pledges']['offset']);

        $this->settings->setPledgeSettings([
            'objective' => $objective,
            'offset' => $offset,
        ], $causeUuid);

        return true;
    }

    private function saveTimings(string $causeUuid): bool
    {
        $extractDate = static fn(string|null $dateString) => null === $dateString ? null : date_create($dateString);

        $timings = $this->settings->getTimings($causeUuid);

        $timings['start'] = $extractDate($_POST['timings']['start'] ?? null);
        $timings['stop'] = $extractDate($_POST['timings']['stop'] ?? null);

        if (false === $timings['start'] || false === $timings['stop']) {
            return false;
        }

        if ($timings['stop']) {
            $timings['stop']->setTime(23, 59, 59);
        }

        $this->settings->setTimings($timings, $causeUuid);

        return true;
    }

    private function saveMailDelays(mixed $causeUuid): bool
    {
        $mailDelays = [];
        foreach (EnumMessageKey::cases() as $case) {
            $delay = $_POST['mail_delays'][$case->value] ?? null;
            if (null === $delay) {
                $mailDelays[$case->value] = null;
                continue;
            }

            $delay = trim($delay);

            if ('' === $delay) {
                $mailDelays[$case->value] = null;
                continue;
            }

            $delay = absint($delay);

            try {
                $mailDelays[$case->value] = match ($case) {
                    EnumMessageKey::NO_COLLECT,
                    EnumMessageKey::REMINDER_1 => new \DateInterval("P{$delay}D"),

                    EnumMessageKey::OBJECTIVE_ACHIEVED,
                    EnumMessageKey::OBJECTIVE_ACHIEVED_FINAL,
                    EnumMessageKey::OBJECTIVE_ADDED => new \DateInterval("PT{$delay}H"),
                };
            } catch(\Exception) {
                return false;
            }
        }

        $this->settings->setMailDelays($mailDelays, $causeUuid);

        return true;
    }

    private function saveCustomCss(string $causeUuid): bool
    {
        $customCss = strip_tags($_POST['customCss'] ?? '');

        if (isset($_POST['customCss']) && $customCss !== $_POST['customCss']) {
            echo '<div class="notice notice-error is-dismissible"><p>' . __(
                    'Markup is not allowed in CSS.',
                    'collectme'
                ) . '</p></div>';
            return false;
        }

        $this->settings->setCustomCss(trim($customCss), $causeUuid);
        return true;
    }

    private function saveOverrides(string $causeUuid): bool
    {
        foreach ($_POST['override'] as $context => $override) {
            foreach ($override as $key => $translation) {
                $text = base64_decode($key);

                if (empty($translation)) {
                    $this->translator->removeOverride($causeUuid, $text, $context);
                    continue;
                }

                $translation = strip_tags($translation, '<strong><a>');
                $this->translator->addOverride($causeUuid, $text, $translation, $context);
            }
        }

        $this->translator->saveOverrides($causeUuid);

        return true;
    }

    private function deleteCause(): void
    {
        try {
            Cause::get($_POST['delete-cause'])->delete();
            echo '<div class="notice notice-success is-dismissible"><p>' . __(
                    'Cause deleted.',
                    'collectme'
                ) . '</p></div>';
        } catch (CollectmeDBException) {
            echo '<div class="notice notice-error is-dismissible"><p>' . __(
                    'Failed to delete cause.',
                    'collectme'
                ) . '</p></div>';
        }
    }

    private function createCause(): void
    {
        $name = trim(strip_tags($_POST['create-cause']));

        if (strlen($name) < 2 || strlen($name) > 45) {
            echo '<div class="notice notice-error is-dismissible"><p>' . __(
                    'Failed to create cause: Invalid name.',
                    'collectme'
                ) . '</p></div>';
            return;
        }

        try {
            (new Cause(null, $name))->save();

            echo '<div class="notice notice-success is-dismissible"><p>' . __(
                    'Cause created.',
                    'collectme'
                ) . '</p></div>';
        } catch (CollectmeDBException) {
            echo '<div class="notice notice-error is-dismissible"><p>' . __(
                    'Failed to create cause.',
                    'collectme'
                ) . '</p></div>';
        }
    }

    private function initCodeEditor(string $causeUuid): void
    {
        $settings = wp_enqueue_code_editor([
            'type' => 'text/css',
        ]);

        // if user disabled CodeMirror.
        if (false === $settings) {
            return;
        }

        wp_add_inline_script(
            'code-editor',
            sprintf(
                "jQuery( function() { wp.codeEditor.initialize( 'customCss-%s', %s ); } );",
                $causeUuid,
                wp_json_encode($settings)
            )
        );
    }
}