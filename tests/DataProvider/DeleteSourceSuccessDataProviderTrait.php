<?php

declare(strict_types=1);

namespace App\Tests\DataProvider;

use App\Entity\FileSource;
use App\Entity\GitSource;
use App\Entity\RunSource;
use App\Enum\Source\Type;

trait DeleteSourceSuccessDataProviderTrait
{
    /**
     * @return array<mixed>
     */
    public function deleteSourceSuccessDataProvider(): array
    {
        return [
            Type::FILE->value => [
                'source' => new FileSource(TestConstants::AUTHENTICATED_USER_ID_PLACEHOLDER, 'label'),
                'expectedRepositoryCount' => 0,
            ],
            Type::GIT->value => [
                'source' => new GitSource(
                    TestConstants::AUTHENTICATED_USER_ID_PLACEHOLDER,
                    'https://example.com/repository.git'
                ),
                'expectedRepositoryCount' => 0,
            ],
            Type::RUN->value => [
                'source' => new RunSource(
                    new FileSource(TestConstants::AUTHENTICATED_USER_ID_PLACEHOLDER, 'label')
                ),
                'expectedRepositoryCount' => 1,
            ],
        ];
    }
}
