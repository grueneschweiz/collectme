<?php

declare(strict_types=1);

namespace Collectme\Controller;

use Collectme\Controller\Validators\EmailValidator;
use Collectme\Controller\Validators\TokenValidator;
use Collectme\Controller\Validators\UuidValidator;
use Collectme\Exceptions\CollectmeDBException;
use Collectme\Misc\AssetLoader;
use Collectme\Misc\Auth;
use Collectme\Misc\Settings;
use Collectme\Model\Entities\AccountToken;
use Collectme\Model\Entities\PersistentSession;

use const Collectme\ASSET_PATH_REL;
use const Collectme\AUTH_SESSION_ACTIVATION_TIMEOUT;
use const Collectme\PATH_APP_STRINGS;
use const Collectme\REST_V1_NAMESPACE;


class HtmlController
{
    public function __construct(
        private readonly AssetLoader $assetLoader,
        private readonly Settings $settings,
        private readonly Auth $auth,
    ) {
    }

    /**
     * @throws \JsonException
     */
    public function createUserFromToken(string $causeUuid): string
    {
        $token = trim($_GET['token'] ?? '');
        $email = trim($_GET['email'] ?? '');

        if (!TokenValidator::check($token) || !EmailValidator::check($email)) {
            return $this->index($causeUuid);
        }

        $token = apply_filters('collectme_account_token', $token, $email);

        if (!$token) {
            return $this->index($causeUuid);
        }

        try {
            $accountToken = AccountToken::getByEmailAndToken($email, $token);
        } catch (CollectmeDBException $e) {
            // token not found / invalid
            return $this->index($causeUuid);
        }

        try {
            $user = $this->auth->getOrSetupUserFromAccountToken($accountToken, $causeUuid);
            $this->auth->createPersistentSession($user, true);
        } catch (\Exception $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                /** @noinspection ForgottenDebugOutputInspection */
                wp_die($e->getMessage());
            } else {
                return $this->index($causeUuid);
            }
        }

        $this->auth->getPersistentSession();

        return $this->index($causeUuid);
    }

    /**
     * @throws \JsonException
     */
    public function index(string $causeUuid): string
    {
        $translations = require PATH_APP_STRINGS;

        $data = [
            'apiBaseUrl' => rest_url(REST_V1_NAMESPACE),
            'appUrl' => get_permalink(),
            'appUrlAuthentication' => wp_hash(get_permalink(), 'nonce'),
            'assetBaseUrl' => plugin_dir_url(COLLECTME_PLUGIN_NAME) . ASSET_PATH_REL,
            'cause' => $causeUuid,
            'defaultObjective' => $this->settings->getDefaultObjective($causeUuid),
            'encodedAdminEmail' => base64_encode(get_bloginfo('admin_email')), // yeah, yeah, spam. but it's good enough
            'locale' => get_locale(),
            'nonce' => wp_create_nonce('wp_rest'),
            'objectives' => $this->settings->getObjectives($causeUuid),
            't' => $translations,
        ];

        $customCss = preg_replace('/\s+/', ' ', $this->settings->getCustomCss($causeUuid));

        return '<div id="collectme-app"></div>'
            . $this->assetLoader->getStylesHtml()
            . '<style>' . $customCss . '</style>'
            . $this->assetLoader->getScriptDataHtml('collectme', $data)
            . $this->assetLoader->getScriptsHtml();
    }

    /**
     * @throws \JsonException
     */
    public function activateSession(string $causeUuid): string
    {
        $activationSecret = trim($_GET['token'] ?? '');
        $sessionUuid = trim($_GET['session'] ?? '');

        if (!TokenValidator::check($activationSecret) || !UuidValidator::check($sessionUuid)) {
            return $this->getView('activation-error');
        }

        try {
            $session = PersistentSession::get($sessionUuid);

            if ($session->isClosed()) {
                return $this->getView('activation-error');
            }

            if (!$session->isActivated() && hash_equals($session->activationSecret, $activationSecret)) {
                if ($session->created < date_create('-' . AUTH_SESSION_ACTIVATION_TIMEOUT)) {
                    return $this->getView('activation-timeout');
                }

                $session->activated = date_create('-1 second');
                $session = $session->save();
            }

            if ($session->isActivated()) {
                $this->auth->getPersistentSession();

                if ($this->auth->isAuthenticated()) {
                    // user activated session in same browser as he requested
                    // activation. so he is now logged in, and we can redirect
                    // him directly to the app.
                    return $this->index($causeUuid);
                }

                // user activated session in different browser than he
                // requested activation. so lets tell him, that he is now
                // logged-in in the other browser.
                return $this->getView('activation-success');
            }
        } catch (CollectmeDBException) {
        }

        return $this->getView('activation-error');
    }

    private function getView(string $viewName): string
    {
        ob_start();
        /** @noinspection PhpIncludeInspection */
        include COLLECTME_BASE_PATH . "/views/$viewName.php";
        return ob_get_clean();
    }
}