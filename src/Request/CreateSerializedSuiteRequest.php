<?php

declare(strict_types=1);

namespace App\Request;

use App\Entity\Suite;

class CreateSerializedSuiteRequest
{
    /**
     * @param non-empty-string[] $runParameters
     */
    public function __construct(
        public readonly Suite $suite,
        public readonly array $runParameters,
    ) {
    }
}
