<?php

declare(strict_types=1);

namespace App\Tests\Unit\Services;

use App\Entity\GitSource;
use App\Model\UserGitRepository;
use App\Services\EntityIdFactory;
use App\Services\UserGitRepositoryFactory;
use App\Tests\Services\SourceOriginFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class UserGitRepositoryFactoryTest extends WebTestCase
{
    public function testCreate(): void
    {
        $source = SourceOriginFactory::create(type: 'git');
        \assert($source instanceof GitSource);

        $userGitRepositoryFactory = new UserGitRepositoryFactory(new EntityIdFactory());

        $userGitRepository = $userGitRepositoryFactory->create($source);

        self::assertInstanceOf(UserGitRepository::class, $userGitRepository);
        self::assertSame($source, $userGitRepository->getSource());
    }
}
