<?php

declare(strict_types=1);

namespace Collectme\Model\Entities;

enum EnumMessageKey: string {
    case NO_COLLECT = 'no_collect';
    case REMINDER_1 = 'reminder_1';
    case GOAL_ACHIEVED = 'goal_achieved';
    case GOAL_ACHIEVED_FINAL = 'goal_achieved_final';
    case GOAL_RAISED = 'goal_raised';
}