<?php

declare(strict_types=1);

namespace Controller;

use Collectme\Controller\HtmlController;
use Collectme\Misc\AssetLoader;
use Collectme\Misc\Auth;
use Collectme\Misc\Settings;
use Collectme\Model\Entities\AccountToken;
use Collectme\Model\Entities\Cause;
use Collectme\Model\Entities\EnumLang;
use Collectme\Model\Entities\PersistentSession;
use Collectme\Model\Entities\User;
use PHPUnit\Framework\TestCase;

class HtmlControllerTest extends TestCase
{

    public function test_activateSession__success__sameBrowser(): void
    {
        $GLOBALS['post'] = wp_insert_post([
            'post_title' => 'collectme_test_'.wp_generate_uuid4(),
            'post_content' => 'this is the collectme page',
            'post_status' => 'publish',
        ]);

        $user = new User(
            null,
            wp_generate_uuid4().'@example.com',
            'Jane',
            'Doe',
            EnumLang::EN,
            'test: some string',
        );
        $user->save();

        $activationSecret = wp_generate_password(64, false, false);
        $sessionSecret = wp_generate_password(64, false, false);
        $sessionHash = password_hash($sessionSecret, PASSWORD_DEFAULT);
        $session = new PersistentSession(
            null,
            $user->uuid,
            0,
            null,
            $activationSecret,
            $sessionHash,
            null,
            null,
        );
        $session->save();

        $cause = new Cause(
            null,
            'html_controller' . wp_generate_password(),
        );
        $cause->save();

        $authMock = $this->createMock(Auth::class);
        $authMock->expects($this->once())
            ->method('getPersistentSession')
            ->willReturn($session);
        $authMock->expects($this->once())
            ->method('isAuthenticated')
            ->willReturn(true);

        $htmlController = new HtmlController(
            $this->createMock(AssetLoader::class),
            $this->createMock(Settings::class),
            $authMock,
        );

        $_GET['token'] = $activationSecret;
        $_GET['session'] = $session->uuid;

        add_filter('wp_redirect', static function($location, $status) {
            self::assertEquals(302, $status);
            return false;
        }, 10, 2 );

        $html = $htmlController->activateSession($cause->uuid);
        $this->assertStringContainsString('id="collectme-app"', $html);
    }

    public function test_activateSession__success__differentBrowser(): void
    {
        $user = new User(
            null,
            wp_generate_uuid4().'@example.com',
            'Jane',
            'Doe',
            EnumLang::EN,
            'test: some string',
        );
        $user->save();

        $activationSecret = wp_generate_password(64, false, false);
        $sessionSecret = wp_generate_password(64, false, false);
        $sessionHash = password_hash($sessionSecret, PASSWORD_DEFAULT);
        $session = new PersistentSession(
            null,
            $user->uuid,
            0,
            null,
            $activationSecret,
            $sessionHash,
            null,
            null,
        );
        $session->save();

        $cause = new Cause(
            null,
            'html_controller' . wp_generate_password(),
        );
        $cause->save();

        $authMock = $this->createMock(Auth::class);
        $authMock->expects($this->once())
            ->method('getPersistentSession')
            ->willReturn($session);
        $authMock->expects($this->once())
            ->method('isAuthenticated')
            ->willReturn(false);

        $htmlController = new HtmlController(
            $this->createMock(AssetLoader::class),
            $this->createMock(Settings::class),
            $authMock,
        );

        $_GET['token'] = $activationSecret;
        $_GET['session'] = $session->uuid;

        $html = $htmlController->activateSession($cause->uuid);
        $this->assertStringContainsString('class="collectme-success"', $html);
    }

    public function test_activateSession__timeout(): void
    {
        $user = new User(
            null,
            wp_generate_uuid4().'@example.com',
            'Jane',
            'Doe',
            EnumLang::EN,
            'test: some string',
        );
        $user->save();

        $activationSecret = wp_generate_password(64, false, false);
        $sessionSecret = wp_generate_password(64, false, false);
        $sessionHash = password_hash($sessionSecret, PASSWORD_DEFAULT);
        $session = new PersistentSession(
            null,
            $user->uuid,
            0,
            null,
            $activationSecret,
            $sessionHash,
            null,
            null,
            date_create('-1 hour'),
        );
        $session->save();

        $cause = new Cause(
            null,
            'html_controller' . wp_generate_password(),
        );
        $cause->save();

        $authMock = $this->createMock(Auth::class);

        $htmlController = new HtmlController(
            $this->createMock(AssetLoader::class),
            $this->createMock(Settings::class),
            $authMock,
        );

        $_GET['token'] = $activationSecret;
        $_GET['session'] = $session->uuid;

        $html = $htmlController->activateSession($cause->uuid);
        $this->assertStringContainsString('class="collectme-error"', $html);
        $this->assertStringContainsString('expired', $html);
    }

    public function test_activateSession__closed(): void
    {
        $user = new User(
            null,
            wp_generate_uuid4().'@example.com',
            'Jane',
            'Doe',
            EnumLang::EN,
            'test: some string',
        );
        $user->save();

        $activationSecret = wp_generate_password(64, false, false);
        $sessionSecret = wp_generate_password(64, false, false);
        $sessionHash = password_hash($sessionSecret, PASSWORD_DEFAULT);
        $session = new PersistentSession(
            null,
            $user->uuid,
            0,
            null,
            $activationSecret,
            $sessionHash,
            null,
            date_create('-1 hour'),
        );
        $session->save();

        $cause = new Cause(
            null,
            'html_controller' . wp_generate_password(),
        );
        $cause->save();

        $authMock = $this->createMock(Auth::class);

        $htmlController = new HtmlController(
            $this->createMock(AssetLoader::class),
            $this->createMock(Settings::class),
            $authMock,
        );

        $_GET['token'] = $activationSecret;
        $_GET['session'] = $session->uuid;

        $html = $htmlController->activateSession($cause->uuid);
        $this->assertStringContainsString('class="collectme-error"', $html);
    }

    public function test_createUserFromToken__success(): void
    {
        $token = wp_generate_password(64, false, false);
        $accountToken = new AccountToken(
            null,
            $token,
            wp_generate_uuid4() . '@mail.com',
            'Jane',
            'Doe',
            EnumLang::EN,
            date_create('+2 years')
        );
        $accountToken = $accountToken->save();

        $user = new User(
            null,
            $accountToken->email,
            $accountToken->firstName,
            $accountToken->lastName,
            $accountToken->lang,
            'test: some string',
            date_create(),
            date_create(),
        );
        $user->save();

        $sessionSecret = wp_generate_password(64, false, false);
        $sessionHash = password_hash($sessionSecret, PASSWORD_DEFAULT);
        $session = new PersistentSession(
            wp_generate_uuid4(),
            $user->uuid,
            0,
            null,
            $sessionSecret,
            $sessionHash,
            date_create(),
            null,
            date_create(),
            date_create(),
        );

        $cause = new Cause(
            null,
            'html_controller_' . wp_generate_password(),
        );
        $cause->save();

        $authMock = $this->createMock(Auth::class);
        $authMock->expects($this->once())
            ->method('getOrSetupUserFromAccountToken')
            ->with($accountToken)
            ->willReturn($user);
        $authMock->expects($this->once())
            ->method('createPersistentSession')
            ->with($user, true);
        $authMock->expects($this->once())
            ->method('getPersistentSession')
            ->willReturn($session);

        $htmlController = new HtmlController(
            $this->createMock(AssetLoader::class),
            $this->createMock(Settings::class),
            $authMock,
        );

        $_GET['token'] = $token;
        $_GET['email'] = $accountToken->email;

        $html = $htmlController->createUserFromToken($cause->uuid);
        $this->assertStringContainsString('id="collectme-app"', $html);
    }

    public function test_createUserFromToken__tokenExpired(): void
    {
        $token = wp_generate_password(64, false, false);
        $accountToken = new AccountToken(
            null,
            $token,
            wp_generate_uuid4() . '@mail.com',
            'Jane',
            'Doe',
            EnumLang::EN,
            date_create('-1 second')
        );
        $accountToken = $accountToken->save();

        $user = new User(
            null,
            $accountToken->email,
            $accountToken->firstName,
            $accountToken->lastName,
            $accountToken->lang,
            'test: some string',
            date_create(),
            date_create(),
        );
        $user->save();

        $cause = new Cause(
            null,
            'html_controller_' . wp_generate_password(),
        );
        $cause->save();

        $authMock = $this->createMock(Auth::class);
        $authMock->expects($this->never())
            ->method('getOrSetupUserFromAccountToken');
        $authMock->expects($this->never())
            ->method('createPersistentSession');
        $authMock->expects($this->never())
            ->method('getPersistentSession');

        $htmlController = new HtmlController(
            $this->createMock(AssetLoader::class),
            $this->createMock(Settings::class),
            $authMock,
        );

        $_GET['token'] = $token;
        $_GET['email'] = $accountToken->email;

        $html = $htmlController->createUserFromToken($cause->uuid);
        $this->assertStringContainsString('id="collectme-app"', $html);
    }

    public function test_createUserFromToken__invalidToken(): void
    {
        $cause = new Cause(
            null,
            'html_controller_' . wp_generate_password(),
        );
        $cause->save();

        $authMock = $this->createMock(Auth::class);
        $authMock->expects($this->never())
            ->method('getOrSetupUserFromAccountToken');
        $authMock->expects($this->never())
            ->method('createPersistentSession');
        $authMock->expects($this->never())
            ->method('getPersistentSession');

        $htmlController = new HtmlController(
            $this->createMock(AssetLoader::class),
            $this->createMock(Settings::class),
            $authMock,
        );

        $_GET['token'] = 'invalid token';
        $_GET['email'] = 'mail@example.com';

        $html = $htmlController->createUserFromToken($cause->uuid);
        $this->assertStringContainsString('id="collectme-app"', $html);
    }

    public function test_index(): void
    {
        $cause = new Cause(
            null,
            'html_controller' . wp_generate_password(),
        );
        $cause->save();

        $assetLoaderMock = $this->createMock(AssetLoader::class);
        $assetLoaderMock->expects($this->once())
            ->method('getStylesHtml');
        $assetLoaderMock->expects($this->once())
            ->method('getScriptDataHtml')
            ->with(
                $this->equalTo('collectme'),
                $this->callback(static fn($arg) => is_array($arg) && !empty($arg['t']))
            );
        $assetLoaderMock->expects($this->once())
            ->method('getScriptsHtml');

        $settingsMock = $this->createMock(Settings::class);
        $settingsMock->expects($this->once())
            ->method('getObjectives');

        $htmlController = new HtmlController(
            $assetLoaderMock,
            $settingsMock,
            $this->createMock(Auth::class),
        );

        $html = $htmlController->index($cause->uuid);

        $this->assertStringContainsString('id="collectme-app"', $html);
    }
}
