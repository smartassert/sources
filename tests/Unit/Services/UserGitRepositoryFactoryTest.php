<?php

declare(strict_types=1);

namespace App\Tests\Unit\Services;

use App\Model\UserGitRepository;
use App\Services\EntityIdFactory;
use App\Services\UserGitRepositoryFactory;
use App\Tests\Services\GitSourceFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class UserGitRepositoryFactoryTest extends WebTestCase
{
    public function testCreate(): void
    {
        $source = GitSourceFactory::create();

        $userGitRepositoryFactory = new UserGitRepositoryFactory(new EntityIdFactory());

        $userGitRepository = $userGitRepositoryFactory->create($source);

        self::assertInstanceOf(UserGitRepository::class, $userGitRepository);
        self::assertSame($source, $userGitRepository->getSource());
    }
}
