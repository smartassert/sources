<?php

declare(strict_types=1);

namespace App\Controller;

class SerializedSuiteRoutes
{
    public const ATTRIBUTE_SUITE_ID = 'serializedSuiteId';
    public const ROUTE_SUITE_ID_PATTERN = '{' . self::ATTRIBUTE_SUITE_ID . '<[A-Z90-9]{26}>}';
    public const ROUTE_SERIALIZED_SUITE = '/serialized_suite/' . self::ROUTE_SUITE_ID_PATTERN;
}
