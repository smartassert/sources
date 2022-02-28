<?php

declare(strict_types=1);

namespace App\Tests\Services\Asserter\Response;

interface HeaderAsserterInterface
{
    /**
     * @param string[][] $headers
     */
    public function assert(array $headers): void;
}
