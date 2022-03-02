<?php

declare(strict_types=1);

namespace App\Tests\Services\Asserter\Response;

use PHPUnit\Framework\Assert;

class NonEmptyBodyContentAsserter implements BodyAsserterInterface
{
    public function assert(string $body): string
    {
        Assert::assertNotSame('', trim($body));

        return $body;
    }
}
