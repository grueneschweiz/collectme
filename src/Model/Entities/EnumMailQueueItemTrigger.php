<?php

declare(strict_types=1);

namespace Collectme\Model\Entities;

enum EnumMailQueueItemTrigger: string
{
    case SIGNATURE = 'signature';
    case OBJECTIVE = 'objective';
    case GROUP = 'group';
}