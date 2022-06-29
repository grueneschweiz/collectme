<?php

declare(strict_types=1);

namespace Collectme\Model\Entities;

enum EnumGroupType: string {
    case PERSON = 'person';
    case ORGANIZATION = 'organization';
}