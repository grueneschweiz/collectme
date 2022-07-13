<?php

declare(strict_types=1);

namespace Collectme\Controller;

use Collectme\Controller\Validators\EmailValidator;
use Collectme\Controller\Validators\TokenValidator;
use Collectme\Exceptions\CollectmeDBException;
use Collectme\Misc\AssetLoader;
use Collectme\Misc\Auth;
use Collectme\Misc\Settings;
use Collectme\Model\Entities\AccountToken;

use const Collectme\ASSET_PATH_REL;
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
            'encodedAdminEmail' => base64_encode(get_bloginfo('admin_email')), // yeah, yeah, spam. but it's good enough
            'locale' => get_locale(),
            'nonce' => wp_create_nonce('wp_rest'),
            'objectives' => $this->settings->getObjectives($causeUuid),
            't' => $translations,
        ];

        return '<div id="collectme-app"></div>'
            . $this->assetLoader->getStylesHtml()
            . $this->assetLoader->getScriptDataHtml('collectme', $data)
            . $this->assetLoader->getScriptsHtml();
    }
}