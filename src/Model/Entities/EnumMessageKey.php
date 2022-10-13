<?php

declare(strict_types=1);

namespace Collectme\Model\Entities;

enum EnumMessageKey: string {
    case NO_COLLECT = 'reminder_start_collecting';
    case REMINDER_1 = 'reminder_1';
    case GOAL = 'goal_reached_not_upgraded';
    case GOAL_AND_UPGRADED = 'goal_reached_and_upgraded';
}