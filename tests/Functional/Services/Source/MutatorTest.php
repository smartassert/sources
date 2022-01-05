<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services\Source;

use App\Entity\FileSource;
use App\Entity\GitSource;
use App\Request\FileSourceRequest;
use App\Request\GitSourceRequest;
use App\Services\Source\Mutator;
use App\Services\Source\Store;
use App\Tests\Services\Source\SourceRemover;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Uid\Ulid;

class MutatorTest extends WebTestCase
{
    private Mutator $mutator;
    private Store $store;

    protected function setUp(): void
    {
        parent::setUp();

        $mutator = self::getContainer()->get(Mutator::class);
        \assert($mutator instanceof Mutator);
        $this->mutator = $mutator;

        $store = self::getContainer()->get(Store::class);
        \assert($store instanceof Store);
        $this->store = $store;

        $sourceRemover = self::getContainer()->get(SourceRemover::class);
        if ($sourceRemover instanceof SourceRemover) {
            $sourceRemover->removeAll();
        }
    }

    /**
     * @dataProvider updateGitSourceNoChangesDataProvider
     */
    public function testUpdateGitSourceNoChanges(GitSource $source, GitSourceRequest $request): void
    {
        $this->store->add($source);
        $mutatedSource = $this->mutator->updateGitSource($source, $request);

        self::assertSame($source, $mutatedSource);
    }

    /**
     * @return array<mixed>
     */
    public function updateGitSourceNoChangesDataProvider(): array
    {
        $userId = (string) new Ulid();
        $hostUrl = 'https://example.com/repository.git';
        $path = '/path';
        $accessToken = 'access token';

        return [
            'null access token' => [
                'source' => new GitSource($userId, $hostUrl, $path, null),
                'request' => new GitSourceRequest($hostUrl, $path, null),
            ],
            'non-null access token' => [
                'source' => new GitSource($userId, $hostUrl, $path, $accessToken),
                'request' => new GitSourceRequest($hostUrl, $path, $accessToken),
            ],
        ];
    }

    /**
     * @dataProvider updateGitSourceDataProvider
     */
    public function testUpdateGitSource(GitSource $source, GitSourceRequest $request, callable $assertions): void
    {
        $this->store->add($source);
        $mutatedSource = $this->mutator->updateGitSource($source, $request);

        $assertions($mutatedSource);
    }

    /**
     * @return array<mixed>
     */
    public function updateGitSourceDataProvider(): array
    {
        $userId = (string) new Ulid();
        $hostUrl = 'https://example.com/repository.git';
        $path = '/path';
        $accessToken = 'access token';
        $newHostUrl = 'https://new.example.com/repository.git';
        $newPath = '/path/new';
        $newAccessToken = 'new access token';

        return [
            'changes' => [
                'source' => new GitSource($userId, $hostUrl, $path, $accessToken),
                'request' => new GitSourceRequest($newHostUrl, $newPath, $newAccessToken),
                'assertions' => function (GitSource $mutatedSource) use (
                    $userId,
                    $newHostUrl,
                    $newPath,
                    $newAccessToken
                ): void {
                    self::assertSame($userId, $mutatedSource->getUserId());
                    self::assertSame($newHostUrl, $mutatedSource->getHostUrl());
                    self::assertSame($newPath, $mutatedSource->getPath());
                    self::assertSame($newAccessToken, $mutatedSource->getAccessToken());
                }
            ],
            'nullify access token' => [
                'source' => new GitSource($userId, $hostUrl, $path, $accessToken),
                'request' => new GitSourceRequest($hostUrl, $path, null),
                'assertions' => function (GitSource $mutatedSource) use ($userId, $hostUrl, $path): void {
                    self::assertSame($userId, $mutatedSource->getUserId());
                    self::assertSame($hostUrl, $mutatedSource->getHostUrl());
                    self::assertSame($path, $mutatedSource->getPath());
                    self::assertNull($mutatedSource->getAccessToken());
                }
            ],
        ];
    }

    /**
     * @dataProvider updateFileSourceDataProvider
     */
    public function testUpdateFileSource(FileSource $source, FileSourceRequest $request, callable $assertions): void
    {
        $this->store->add($source);
        $mutatedSource = $this->mutator->updateFileSource($source, $request);

        $assertions($mutatedSource);
    }

    /**
     * @return array<mixed>
     */
    public function updateFileSourceDataProvider(): array
    {
        $userId = (string) new Ulid();
        $label = 'file source label';
        $newLabel = 'new file source label';

        return [
            'no changes' => [
                'source' => new FileSource($userId, $label),
                'request' => new FileSourceRequest($label),
                'assertions' => function (FileSource $mutatedFileSource) use ($userId, $label): void {
                    self::assertSame($userId, $mutatedFileSource->getUserId());
                    self::assertSame($label, $mutatedFileSource->getLabel());
                }
            ],
            'changes' => [
                'source' => new FileSource($userId, $label),
                'request' => new FileSourceRequest($newLabel),
                'assertions' => function (FileSource $mutatedFileSource) use ($userId, $newLabel): void {
                    self::assertSame($userId, $mutatedFileSource->getUserId());
                    self::assertSame($newLabel, $mutatedFileSource->getLabel());
                }
            ],
        ];
    }
}
