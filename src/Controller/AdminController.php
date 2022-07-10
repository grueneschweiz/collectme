<?php

declare(strict_types=1);

namespace Collectme\Controller;

use Collectme\Controller\Http\UuidValidator;
use Collectme\Exceptions\CollectmeDBException;
use Collectme\Misc\Settings;
use Collectme\Misc\Translator;
use Collectme\Model\Entities\Cause;
use Gettext\Loader\PoLoader;

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
            $causeUuid = UuidValidator::check($_POST['cause']) ? $_POST['cause'] : null;
            if (!$causeUuid) {
                /** @noinspection ForgottenDebugOutputInspection */
                wp_die('Invalid data');
            }

            if (
                $this->saveObjectives($causeUuid)
                && $this->saveOverrides($causeUuid)
            ) {
                echo '<div class="notice notice-success is-dismissible"><p>'. __( 'Saved!', 'collectme' ) .'</p></div>';
            }
        }

        $nonce = wp_create_nonce(self::NONCE_ACTION);
        try {
            $causes = Cause::findAll();
        } catch (CollectmeDBException $e) {
            /** @noinspection ForgottenDebugOutputInspection */
            wp_die('Database error. Failed to load causes. Try again.');
        }

        $poLoader = new PoLoader();
        $stringTemplates = $poLoader->loadFile(PATH_POT_FILE);

        $translator = $this->translator;
        $defaultContext = I18N_DEFAULT_CONTEXT;

        $settings = $this->settings;

        include COLLECTME_BASE_PATH . '/admin/settings.php';
    }

    private function saveOverrides(string $causeUuid): bool
    {
        foreach($_POST['override'] as $context => $override){
            foreach($override as $key => $translation) {
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

    private function saveObjectives(string $causeUuid): bool {
        $objectives = $this->settings->getObjectivesDefaults();

        foreach ($_POST['objective'] as $key => $attr) {
            // validate key
            if (!array_key_exists($key, $objectives)) {
                echo '<div class="notice notice-error is-dismissible"><p>'. __( 'Invalid objective key.', 'collectme' ) .'</p></div>';
                return false;
            }

            $objective = (int)$attr['objective'];
            if ($objective < 1 || $objective > 1000000) {
                echo '<div class="notice notice-error is-dismissible"><p>'. __( 'Invalid goal.', 'collectme' ) .'</p></div>';
                return false;
            }
            $objectives[$key]['objective'] = $objective;

            $img = filter_var($attr['img'], FILTER_VALIDATE_URL);
            if (false === $img && !empty($attr['img'])) {
                echo '<div class="notice notice-error is-dismissible"><p>'. __( 'Invalid image url.', 'collectme' ) .'</p></div>';
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
}