<?php

declare(strict_types=1);

namespace Collectme\Model\Entities;

enum EnumPermission: string
{
    case READ = 'r';
    case READ_WRITE = 'rw';
}