<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Tests\Application\AbstractUnauthorizedUserTest;

class InvalidSourceUserTest extends AbstractUnauthorizedUserTest
{
    use GetClientAdapterTrait;
}
