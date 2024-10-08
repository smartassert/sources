<?php

declare(strict_types=1);

namespace App\Tests\Services;

use App\Entity\SourceInterface;
use App\Entity\Suite;
use App\Services\EntityIdFactory;

class SuiteFactory
{
    /**
     * @param null|non-empty-string   $label
     * @param null|non-empty-string[] $tests
     */
    public static function create(
        SourceInterface $source,
        ?string $label = null,
        ?array $tests = null,
    ): Suite {
        $suite = new Suite((new EntityIdFactory())->create());

        $suite->setSource($source);

        $label = is_string($label) ? $label : StringFactory::createRandom();
        $suite->setLabel($label);

        $tests = is_array($tests) ? $tests : [];
        $suite->setTests($tests);

        return $suite;
    }
}
