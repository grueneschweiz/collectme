<?php

declare(strict_types=1);

namespace Misc;

use Collectme\Misc\LoginEmail;
use Collectme\Misc\Settings;
use Collectme\Model\Entities\EnumLang;
use Collectme\Model\Entities\PersistentSession;
use Collectme\Model\Entities\User;
use PHPUnit\Framework\TestCase;

class LoginEmailTest extends TestCase
{

    public function testSend()
    {
        $user = new User(
            wp_generate_uuid4(),
            'receiver@example.com',
            'John',
            'Doe',
            EnumLang::EN,
            'app',
            date_create(),
            date_create()
        );

        $session = new PersistentSession(
            wp_generate_uuid4(),
            $user->uuid,
            0,
            null,
            wp_generate_password(64, false, false),
            wp_hash_password(wp_generate_password()),
            null,
            null,
            date_create(),
            date_create(),
        );

        $settingsMock = $this->createMock(Settings::class);
        $settingsMock->method('getEmailConfig')
            ->willReturn([
                'fromName' => 'Sender',
                'fromAddress' => 'sender@example.com',
                'replyToAddress' => 'replyto@example.com'
            ]);

        $loginEmail = new LoginEmail($settingsMock);
        $loginEmail->user = $user;
        $loginEmail->session = $session;
        $loginEmail->appUrl = 'https://example.com';
        $loginEmail->causeUuid = wp_generate_uuid4();

        add_filter('wp_mail', static function(array $mail) use ($user, $session) {
            self::assertEquals($user->email, $mail['to']);
            self::assertContains('From: Sender <sender@example.com>', $mail['headers']);
            self::assertContains('Reply-To: replyto@example.com', $mail['headers']);
            self::assertStringContainsString($user->firstName, $mail['message']);
            self::assertStringContainsString('Sender', $mail['message']);
            self::assertStringContainsString(
                "https://example.com?action=activate&session=$session->uuid&token=$session->activationSecret",
                $mail['message']
            );
        } );

        $loginEmail->send();
    }
}
