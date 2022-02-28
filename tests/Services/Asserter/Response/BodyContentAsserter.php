<?php

declare(strict_types=1);

namespace App\Tests\Services\Asserter\Response;

use PHPUnit\Framework\Assert;

class BodyContentAsserter implements BodyAsserterInterface
{
    public function __construct(
        private string $expected
    ) {
    }

    public function assert(string $body): string
    {
        Assert::assertSame($this->expected, $body);

        return $body;
    }
}
