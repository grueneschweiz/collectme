<?php

declare(strict_types=1);

namespace Collectme\Misc;

use Collectme\Email\Mailable;
use Collectme\Exceptions\CollectmeException;

class Mailer
{
    public function __construct(
        private readonly Settings $settings,
    ) {
    }

    public function send(Mailable $mailable, string $causeUuid): void
    {
        $success = wp_mail(
            $mailable->getToAddr(),
            $mailable->getSubject(),
            $mailable->getMessage(),
            $this->getHeaders($causeUuid)
        );

        if (!$success) {
            throw new CollectmeException('Failed to send email.');
        }
    }

    private function getConfig(string $causeUuid): array
    {
        return $this->settings->getEmailConfig($causeUuid);
    }

    private function getHeaders(string $causeUuid): array
    {
        $config = $this->getConfig($causeUuid);

        return [
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $config['fromName'] . ' <' . $config['fromAddress'] . '>',
            'Reply-To: ' . $config['replyToAddress'],
        ];
    }
}