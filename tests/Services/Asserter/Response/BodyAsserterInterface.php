<?php

declare(strict_types=1);

namespace App\Tests\Services\Asserter\Response;

interface BodyAsserterInterface
{
    public function assert(string $body): mixed;
}
