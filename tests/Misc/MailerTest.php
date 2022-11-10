<?php

declare(strict_types=1);

namespace Misc;

use Collectme\Email\LoginEmail;
use Collectme\Misc\Mailer;
use Collectme\Misc\Settings;
use PHPUnit\Framework\TestCase;

class MailerTest extends TestCase
{

    public function test_send(): void
    {
        $emailMock = $this->createMock(LoginEmail::class);

        $emailMock
            ->expects($this->once())
            ->method('getToAddr')
            ->willReturn('receiver@example.com');

        $emailMock
            ->expects($this->once())
            ->method('getSubject')
            ->willReturn('the subject');

        $emailMock
            ->expects($this->once())
            ->method('getMessage')
            ->willReturn('the message');

        $settingsMock = $this->createMock(Settings::class);

        $settingsMock
            ->expects($this->once())
            ->method('getEmailConfig')
            ->willReturn([
                'fromName' => 'Sender',
                'fromAddress' => 'sender@example.com',
                'replyToAddress' => 'replyto@example.com',
            ]);

        add_filter('wp_mail', static function(array $mail) {
            self::assertEquals('receiver@example.com', $mail['to']);
            self::assertContains('From: Sender <sender@example.com>', $mail['headers']);
            self::assertContains('Reply-To: replyto@example.com', $mail['headers']);
            self::assertEquals('the message', $mail['message']);
            self::assertEquals('the subject', $mail['subject']);
            self::assertEmpty($mail['attachments']);
        } );

        $mailer = new Mailer($settingsMock);
        $mailer->send($emailMock, wp_generate_uuid4());
    }
}
