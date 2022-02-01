<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services\Source;

use App\Entity\FileSource;
use App\Entity\GitSource;
use App\Request\FileSourceRequest;
use App\Request\GitSourceRequest;
use App\Services\Source\Mutator;
use App\Services\Source\Store;
use App\Tests\Model\UserId;
use App\Tests\Services\EntityRemover;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

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

        $entityRemover = self::getContainer()->get(EntityRemover::class);
        if ($entityRemover instanceof EntityRemover) {
            $entityRemover->removeAll();
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
        $userId = UserId::create();
        $hostUrl = 'https://example.com/repository.git';
        $path = '/path';
        $credentials = 'credentials';

        return [
            'empty credentials' => [
                'source' => new GitSource($userId, $hostUrl, $path, ''),
                'request' => new GitSourceRequest($hostUrl, $path, ''),
            ],
            'non-empty credentials' => [
                'source' => new GitSource($userId, $hostUrl, $path, $credentials),
                'request' => new GitSourceRequest($hostUrl, $path, $credentials),
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
        $userId = UserId::create();
        $hostUrl = 'https://example.com/repository.git';
        $path = '/path';
        $credentials = 'credentials';
        $newHostUrl = 'https://new.example.com/repository.git';
        $newPath = '/path/new';
        $newCredentials = 'new credentials';

        return [
            'changes' => [
                'source' => new GitSource($userId, $hostUrl, $path, $credentials),
                'request' => new GitSourceRequest($newHostUrl, $newPath, $newCredentials),
                'assertions' => function (GitSource $mutatedSource) use (
                    $userId,
                    $newHostUrl,
                    $newPath,
                    $newCredentials
                ): void {
                    self::assertSame($userId, $mutatedSource->getUserId());
                    self::assertSame($newHostUrl, $mutatedSource->getHostUrl());
                    self::assertSame($newPath, $mutatedSource->getPath());
                    self::assertSame($newCredentials, $mutatedSource->getCredentials());
                }
            ],
            'nullify credentials' => [
                'source' => new GitSource($userId, $hostUrl, $path, $credentials),
                'request' => new GitSourceRequest($hostUrl, $path, ''),
                'assertions' => function (GitSource $mutatedSource) use ($userId, $hostUrl, $path): void {
                    self::assertSame($userId, $mutatedSource->getUserId());
                    self::assertSame($hostUrl, $mutatedSource->getHostUrl());
                    self::assertSame($path, $mutatedSource->getPath());
                    self::assertSame('', $mutatedSource->getCredentials());
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
        $userId = UserId::create();
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
