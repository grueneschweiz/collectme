<?php

declare(strict_types=1);

namespace Collectme\Model;

enum EnumPaginationOrder: string
{
    case ASC = 'ASC';
    case DESC = 'DESC';
}