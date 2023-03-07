<?php

declare(strict_types=1);

namespace App\Request;

use App\Entity\SourceOriginInterface;

class SuiteRequest
{
    public const PARAMETER_SOURCE_ID = 'source_id';
    public const PARAMETER_LABEL = 'label';
    public const PARAMETER_TESTS = 'tests';

    /**
     * @param non-empty-string             $label
     * @param array<int, non-empty-string> $tests
     */
    public function __construct(
        public readonly SourceOriginInterface $source,
        public readonly string $label,
        public readonly array $tests,
    ) {
    }
}
