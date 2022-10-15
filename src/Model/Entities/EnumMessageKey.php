<?php

declare(strict_types=1);

namespace Collectme\Model\Entities;

enum EnumMessageKey: string {
    case NO_COLLECT = 'no_collect';
    case REMINDER_1 = 'reminder_1';
    case OBJECTIVE_ACHIEVED = 'objective_achieved';
    case OBJECTIVE_ACHIEVED_FINAL = 'objective_achieved_final';
    case OBJECTIVE_ADDED = 'objective_raised';
}