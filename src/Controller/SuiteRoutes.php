<?php

declare(strict_types=1);

namespace App\Controller;

class SuiteRoutes
{
    public const ATTRIBUTE_SUITE_ID = 'suiteId';
    public const ROUTE_SUITE_ID_PATTERN = '{' . self::ATTRIBUTE_SUITE_ID . '<[A-Z90-9]{26}>}';
    public const ROUTE_SUITE_BASE = SourceRoutes::ROUTE_SOURCE . '/suite';
    public const ROUTE_SUITE = self::ROUTE_SUITE_BASE . '/' . self::ROUTE_SUITE_ID_PATTERN;
    public const ROUTE_SUITES = '/suites';
}
