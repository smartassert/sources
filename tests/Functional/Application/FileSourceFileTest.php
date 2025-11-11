<?php

declare(strict_types=1);

namespace App\Tests\Functional\Application;

use App\Tests\Application\AbstractFileSourceFileTest;

class FileSourceFileTest extends AbstractFileSourceFileTest
{
    use GetClientAdapterTrait;

    public static function storeFileInvalidRequestDataProvider(): array
    {
        return self::storeFileInvalidRequestDefaultDataProvider();
    }

    public static function yamlFileInvalidRequestDataProvider(): array
    {
        return self::yamlFileInvalidRequestDefaultDataProvider();
    }
}
