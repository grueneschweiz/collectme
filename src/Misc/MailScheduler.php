<?php

declare(strict_types=1);

namespace Collectme\Misc;


use Collectme\Model\Entities\Cause;
use Collectme\Model\Entities\Group;

class MailScheduler
{
    public static function scheduleCron(): void
    {
        if ( ! wp_next_scheduled( 'collectme_schedule_mails' ) ) {
            wp_schedule_event( time(), 'daily', 'collectme_schedule_mails' );
        }
    }

    public static function removeCron(): void
    {
        $timestamp = wp_next_scheduled( 'collectme_schedule_mails' );
        wp_unschedule_event( $timestamp, 'collectme_schedule_mails' );
    }

    public function run(): void
    {
        $container = collectme_get_container();

        foreach(Cause::findAllActive() as $cause) {
            foreach(Group::findPersonalByCause($cause->uuid) as $group) {
                foreach($group->users() as $user) {
                    if (!$user->mailPermission) {
                        return;
                    }

                    $container->make(ReminderMailScheduler::class, [$group, $user])->run();
                    $container->make(ActionMailScheduler::class, [$group, $user])->run();
                }
            }
        }
    }
}