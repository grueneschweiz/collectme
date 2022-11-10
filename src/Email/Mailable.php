<?php

declare(strict_types=1);

namespace Collectme\Email;

interface Mailable
{
    /**
     * The receivers email address
     */
    public function getToAddr(): string;

    /**
     * The email subject
     */
    public function getSubject(): string;

    /**
     * The email body
     */
    public function getMessage(): string;
}