<?php

declare(strict_types=1);

namespace Collectme\Misc;


use Collectme\Exceptions\CollectmeDBException;
use Collectme\Model\Entities\Cause;
use Collectme\Model\Entities\EnumGroupType;
use Collectme\Model\Entities\Group;
use DI\DependencyException;
use DI\NotFoundException;

class MailScheduler
{
    public function __construct(
        private readonly Settings $settings,
    ) {
    }

    public static function scheduleCron(): void
    {
        if (!wp_next_scheduled('collectme_schedule_mails')) {
            wp_schedule_event(time(), 'daily', 'collectme_schedule_mails');
        }
    }

    public static function removeCron(): void
    {
        $timestamp = wp_next_scheduled('collectme_schedule_mails');
        wp_unschedule_event($timestamp, 'collectme_schedule_mails');
    }

    /**
     * @throws NotFoundException
     * @throws CollectmeDBException
     * @throws DependencyException
     */
    public function run(): void
    {
        $container = collectme_get_container();

        foreach ($this->getActiveCauses() as $cause) {
            foreach (Group::findByTypeAndCause($cause->uuid, EnumGroupType::PERSON) as $group) {
                foreach ($group->users() as $user) {
                    if (!$user->mailPermission) {
                        return;
                    }

                    $container->make(ReminderMailScheduler::class, [$group, $user])->run();
                    $container->make(ActionMailScheduler::class, [$group, $user])->run();
                }
            }
        }
    }

    /**
     * @throws CollectmeDBException
     */
    public function getActiveCauses(): array
    {
        $now = date_create();

        return array_filter(Cause::findAll(), function (Cause $cause) use ($now) {
            [$start, $stop] = $this->settings->getTimings($cause->uuid);

            return !($start && $start > $now)
                && !($stop && $stop < $now);
        });
    }
}