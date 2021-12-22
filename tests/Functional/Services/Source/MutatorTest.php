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
     * @dataProvider updateGitSourceDataProvider
     */
    public function testUpdateGitSource(GitSource $source, GitSourceRequest $request, GitSource $expected): void
    {
        $this->store->add($source);

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

    /**
     * @dataProvider updateFileSourceDataProvider
     */
    public function testUpdateFileSource(FileSource $source, FileSourceRequest $request, FileSource $expected): void
    {
        $this->store->add($source);

        $mutatedSource = $this->mutator->updateFileSource($source, $request);

        self::assertEquals($expected, $mutatedSource);
    }

    /**
     * @return array<mixed>
     */
    public function updateFileSourceDataProvider(): array
    {
        $id = (string) new Ulid();
        $userId = (string) new Ulid();
        $label = 'file source label';
        $newLabel = 'new file source label';

        return [
            'no changes' => [
                'source' => new FileSource($id, $userId, $label),
                'request' => new FileSourceRequest($label),
                'expected' => new FileSource($id, $userId, $label),
            ],
            'changes' => [
                'source' => new FileSource($id, $userId, $label),
                'request' => new FileSourceRequest($newLabel),
                'expected' => new FileSource($id, $userId, $newLabel),
            ],
        ];
    }
}
