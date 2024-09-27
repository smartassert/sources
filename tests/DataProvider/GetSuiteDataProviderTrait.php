<?php

declare(strict_types=1);

namespace App\Tests\DataProvider;

use App\Entity\FileSource;
use App\Entity\Suite;
use App\Tests\Services\StringFactory;
use App\Tests\Services\SuiteFactory;

trait GetSuiteDataProviderTrait
{
    /**
     * @return array<mixed>
     */
    public static function getSuiteDataProvider(): array
    {
        $label = StringFactory::createRandom();
        $tests = [
            StringFactory::createRandom() . '.yaml',
            StringFactory::createRandom() . '.yml',
            StringFactory::createRandom() . '.yaml',
        ];

        return [
            'default' => [
                'suiteCreator' => function (FileSource $source) use ($label, $tests) {
                    return SuiteFactory::create(source: $source, label: $label, tests: $tests);
                },
                'expectedResponseDataCreator' => function (Suite $suite) use ($label, $tests) {
                    $data = [
                        'id' => $suite->id,
                        'source_id' => $suite->getSource()->getId(),
                        'label' => $label,
                        'tests' => $tests,
                    ];

                    $deletedAt = $suite->getDeletedAt();
                    if ($deletedAt instanceof \DateTimeInterface) {
                        $data['deleted_at'] = (int) $deletedAt->format('U');
                    }

                    return $data;
                },
            ],
        ];
    }
}
