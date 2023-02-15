<?php

declare(strict_types=1);

namespace App\Request;

class SuiteRequest
{
    public const PARAMETER_LABEL = 'label';
    public const PARAMETER_TESTS = 'tests';

    /**
     * @param non-empty-string             $sourceId
     * @param non-empty-string             $label
     * @param array<int, non-empty-string> $tests
     */
    public function __construct(
        public readonly string $sourceId,
        public readonly string $label,
        public readonly array $tests,
    ) {
    }
}
