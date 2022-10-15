<?php

declare(strict_types=1);

namespace Collectme\Misc;

class Mailer
{
    public static function scheduleCron(): void
    {
        if (!wp_next_scheduled('collectme_send_mails')) {
            wp_schedule_event(time(), 'hourly', 'collectme_send_mails');
        }
    }

    public static function removeCron(): void
    {
        $timestamp = wp_next_scheduled('collectme_send_mails');
        wp_unschedule_event($timestamp, 'collectme_send_mails');
    }

}