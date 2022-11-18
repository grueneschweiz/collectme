<?php

declare(strict_types=1);

namespace Collectme\Model\Entities;

enum EnumMessageKey: string {
    case COLLECTION_REMINDER = 'collection_reminder';
    case OBJECTIVE_CHANGE = 'objective_change';
}