<?php

declare(strict_types=1);

namespace Collectme\Misc;

use Collectme\Exceptions\CollectmeDBException;
use Collectme\Exceptions\CollectmeException;
use Collectme\Model\Entities\MailQueueItem;
use DI\DependencyException;
use DI\NotFoundException;


class MailQueueProcessor
{

    public function __construct(
        private readonly MailQueueItemProcessor $itemProcessor,
    )
    {
    }

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

    /**
     * @throws CollectmeDBException
     * @throws CollectmeException
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function process(): void
    {
        foreach (MailQueueItem::findUnsent() as $item) {
            $this->itemProcessor->process($item);
        }
    }
}