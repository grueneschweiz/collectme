<?php

declare(strict_types=1);

namespace Collectme\Model;

enum EnumPaginationCursorPointsTo: string
{
    case FIRST = 'first';
    case LAST = 'last';
}