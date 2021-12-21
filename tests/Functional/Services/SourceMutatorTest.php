<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Entity\GitSource;
use App\Request\GitSourceRequest;
use App\Services\SourceMutator;
use App\Services\SourcePersister;
use App\Tests\Services\SourceRemover;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Uid\Ulid;

class SourceMutatorTest extends WebTestCase
{
    private SourceMutator $mutator;
    private SourcePersister $sourcePersister;

    protected function setUp(): void
    {
        parent::setUp();

        $mutator = self::getContainer()->get(SourceMutator::class);
        \assert($mutator instanceof SourceMutator);
        $this->mutator = $mutator;

        $sourcePersister = self::getContainer()->get(SourcePersister::class);
        \assert($sourcePersister instanceof SourcePersister);
        $this->sourcePersister = $sourcePersister;

        $sourceRemover = self::getContainer()->get(SourceRemover::class);
        if ($sourceRemover instanceof SourceRemover) {
            $sourceRemover->removeAll();
        }
    }

    /**
     * @dataProvider updateGitSourceDataProvider
     */
    public function testUpdateGitSource(GitSource $source, GitSourceRequest $request, GitSource $expected): void
    {
        $this->sourcePersister->persist($source);

        $mutatedSource = $this->mutator->updateGitSource($source, $request);

        self::assertEquals($expected, $mutatedSource);
    }

    /**
     * @return array<mixed>
     */
    public function updateGitSourceDataProvider(): array
    {
        $id = (string) new Ulid();
        $userId = (string) new Ulid();
        $hostUrl = 'https://example.com/repository.git';
        $path = '/path';
        $accessToken = 'access token';
        $newHostUrl = 'https://new.example.com/repository.git';
        $newPath = '/path/new';
        $newAccessToken = 'new access token';

        return [
            'no changes with null access token' => [
                'source' => new GitSource($id, $userId, $hostUrl, $path, null),
                'request' => new GitSourceRequest($hostUrl, $path, null),
                'expected' => new GitSource($id, $userId, $hostUrl, $path, null),
            ],
            'no changes with non-null access token' => [
                'source' => new GitSource($id, $userId, $hostUrl, $path, $accessToken),
                'request' => new GitSourceRequest($hostUrl, $path, $accessToken),
                'expected' => new GitSource($id, $userId, $hostUrl, $path, $accessToken),
            ],
            'changes' => [
                'source' => new GitSource($id, $userId, $hostUrl, $path, $accessToken),
                'request' => new GitSourceRequest($newHostUrl, $newPath, $newAccessToken),
                'expected' => new GitSource($id, $userId, $newHostUrl, $newPath, $newAccessToken),
            ],
            'nullify access token' => [
                'source' => new GitSource($id, $userId, $hostUrl, $path, $accessToken),
                'request' => new GitSourceRequest($hostUrl, $path, null),
                'expected' => new GitSource($id, $userId, $hostUrl, $path, null),
            ],
        ];
    }
}
