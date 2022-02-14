<?php

declare(strict_types=1);

namespace App\Controller;

class SourceRoutes
{
    public const ROUTE_SOURCE_ID_PATTERN = '{sourceId<[A-Z90-9]{26}>}';
    public const ROUTE_SOURCE = '/' . self::ROUTE_SOURCE_ID_PATTERN;
}
