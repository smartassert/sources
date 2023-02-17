<?php

declare(strict_types=1);

namespace App\Request;

class CreateSuiteRequest extends SuiteRequest
{
    /**
     * @param non-empty-string             $sourceId
     * @param non-empty-string             $label
     * @param array<int, non-empty-string> $tests
     */
    public function __construct(
        public readonly string $sourceId,
        string $label,
        array $tests,
    ) {
        parent::__construct($label, $tests);
    }
}
