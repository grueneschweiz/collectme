<?php

declare(strict_types=1);

namespace Collectme\Controller;

use Collectme\Controller\Http\UuidValidator;
use Collectme\Exceptions\CollectmeDBException;
use Collectme\Misc\Translator;
use Collectme\Model\Entities\Cause;
use Gettext\Loader\PoLoader;

use const Collectme\I18N_DEFAULT_CONTEXT;
use const Collectme\PATH_POT_FILE;

class AdminController
{
    private const NONCE_ACTION = 'collectme-settings';

    public function __construct(
        private readonly Translator $translator
    ) {

    }

    public function showSettings(): void
    {
        if (!current_user_can('manage_options')) {
            /** @noinspection ForgottenDebugOutputInspection */
            wp_die(__('You do not have sufficient permissions to access this page.', 'collectme'));
        }

        if (!empty($_POST['cause']) && wp_verify_nonce($_POST['_wpnonce'], self::NONCE_ACTION)) {
            $cause = UuidValidator::check($_POST['cause']) ? $_POST['cause'] : null;
            if (!$cause) {
                /** @noinspection ForgottenDebugOutputInspection */
                wp_die('Invalid data');
            }

            foreach($_POST['override'] as $context => $override){
                foreach($override as $key => $translation) {
                    $text = base64_decode($key);

                    if (empty($translation)) {
                        $this->translator->removeOverride($cause, $text, $context);
                        continue;
                    }

                    $translation = strip_tags($translation, '<strong><a>');
                    $this->translator->addOverride($cause, $text, $translation, $context);
                }
            }

            $this->translator->saveOverrides($cause);

            echo '<div class="notice notice-success is-dismissible"><p>'. __( 'Saved!', 'collectme' ) .'</p></div>';
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

        include COLLECTME_BASE_PATH . '/admin/settings.php';
    }
}