<?php

declare(strict_types=1);

namespace App\Request;

use App\Entity\Suite;

readonly class CreateSerializedSuiteRequest
{
    /**
     * @param non-empty-string                $id
     * @param array<non-empty-string, string> $runParameters
     * @param non-empty-string                $notifyUrl
     */
    public function __construct(
        public string $id,
        public Suite $suite,
        public array $runParameters,
        public string $notifyUrl,
    ) {}
}
