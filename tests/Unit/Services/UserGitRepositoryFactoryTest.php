<?php

declare(strict_types=1);

namespace App\Tests\Unit\Services;

use App\Entity\GitSource;
use App\Model\UserGitRepository;
use App\Services\EntityIdFactory;
use App\Services\UserGitRepositoryFactory;
use App\Tests\Model\UserId;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class UserGitRepositoryFactoryTest extends WebTestCase
{
    public function testCreate(): void
    {
        $source = new GitSource(
            (new EntityIdFactory())->create(),
            UserId::create(),
            'label',
            'http://example.com/repository.git'
        );

        $userGitRepository = (new UserGitRepositoryFactory())->create($source);

        self::assertInstanceOf(UserGitRepository::class, $userGitRepository);
        self::assertSame($source, $userGitRepository->getSource());
    }
}
