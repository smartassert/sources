<?php

declare(strict_types=1);

namespace App\Request;

class SuiteRequest
{
    public const KEY_SOURCE_ID = 'source_id';
    public const KEY_LABEL = 'label';
    public const KEY_TESTS = 'tests';

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
