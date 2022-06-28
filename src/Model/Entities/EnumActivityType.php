<?php

declare(strict_types=1);

namespace Collectme\Model\Entities;

enum EnumActivityType: string
{
    // personal types
    case PLEDGE = 'pledge';
    case PERSONAL_SIGNATURE = 'personal signature';
    case PERSONAL_GOAL_ACHIEVED = 'personal goal achieved';
    case PERSONAL_GOAL_RAISED = 'personal goal raised';

    // group types
    case ORGANIZATION_SIGNATURE = 'organization signature';
}