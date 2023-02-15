<?php

declare(strict_types=1);

namespace App\Controller;

class SourceRoutes
{
    public const ATTRIBUTE_SOURCE_ID = 'sourceId';
    public const ROUTE_SOURCE_ID_PATTERN = '{' . self::ATTRIBUTE_SOURCE_ID . '<[A-Z90-9]{26}>}';
    public const ROUTE_SOURCE = '/source/' . self::ROUTE_SOURCE_ID_PATTERN;
}
