<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services\Source;

use App\Entity\FileSource;
use App\Entity\GitSource;
use App\Repository\SourceRepository;
use App\Request\FileSourceRequest;
use App\Request\GitSourceRequest;
use App\Services\Source\Mutator;
use App\Tests\Model\UserId;
use App\Tests\Services\EntityRemover;
use App\Tests\Services\SourceOriginFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class MutatorTest extends WebTestCase
{
    private Mutator $mutator;
    private SourceRepository $sourceRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $mutator = self::getContainer()->get(Mutator::class);
        \assert($mutator instanceof Mutator);
        $this->mutator = $mutator;

        $sourceRepository = self::getContainer()->get(SourceRepository::class);
        \assert($sourceRepository instanceof SourceRepository);
        $this->sourceRepository = $sourceRepository;

        $entityRemover = self::getContainer()->get(EntityRemover::class);
        if ($entityRemover instanceof EntityRemover) {
            $entityRemover->removeAll();
        }
    }

    #[DataProvider('updateFileNoChangesDataProvider')]
    public function testUpdateFileNoChanges(FileSource $source, FileSourceRequest $request): void
    {
        $this->sourceRepository->save($source);

        $mutatedSource = $this->mutator->updateFile($source, $request);

        self::assertSame($source, $mutatedSource);
    }

    /**
     * @return array<mixed>
     */
    public static function updateFileNoChangesDataProvider(): array
    {
        $userId = UserId::create();
        $label = 'file source label';
        $fileSource = SourceOriginFactory::create(type: 'file', userId: $userId, label: $label);
        $fileSource->setLabel($label);

        return [
            'file source, no changes' => [
                'source' => $fileSource,
                'request' => new FileSourceRequest($label),
            ],
        ];
    }

    #[DataProvider('updateGitNoChangesDataProvider')]
    public function testUpdateGitNoChanges(GitSource $source, GitSourceRequest $request): void
    {
        $this->sourceRepository->save($source);

        $mutatedSource = $this->mutator->updateGit($source, $request);

        self::assertSame($source, $mutatedSource);
    }

    /**
     * @return array<mixed>
     */
    public static function updateGitNoChangesDataProvider(): array
    {
        $userId = UserId::create();
        $label = 'git source label';
        $hostUrl = 'https://example.com/repository.git';
        $path = '/path';
        $credentials = 'credentials';

        $gitSourceNoCredentials = SourceOriginFactory::create('git', $userId, $label, $hostUrl, $path, '');
        $gitSourceHasCredentials = SourceOriginFactory::create('git', $userId, $label, $hostUrl, $path, $credentials);

        return [
            'git source, no credentials, no changes' => [
                'source' => $gitSourceNoCredentials,
                'request' => new GitSourceRequest($label, $hostUrl, $path, ''),
            ],
            'git source, has credentials, no changes' => [
                'source' => $gitSourceHasCredentials,
                'request' => new GitSourceRequest($label, $hostUrl, $path, $credentials),
            ],
        ];
    }

    #[DataProvider('updateFileDataProvider')]
    public function testUpdateFile(FileSource $source, FileSourceRequest $request, FileSource $expected): void
    {
        $mutatedSource = $this->mutator->updateFile($source, $request);

        self::assertEquals($expected, $mutatedSource);
    }

    /**
     * @return array<mixed>
     */
    public static function updateFileDataProvider(): array
    {
        $userId = UserId::create();
        $label = 'file source label';
        $newLabel = 'new file source label';
        $originalFileSource = SourceOriginFactory::create(type: 'file', userId: $userId, label: $label);
        $updatedFileSource = clone $originalFileSource;
        $updatedFileSource->setLabel($newLabel);

        return [
            'file source, no changes' => [
                'source' => $originalFileSource,
                'request' => new FileSourceRequest($label),
                'expected' => $originalFileSource,
            ],
            'file source, update label' => [
                'source' => $originalFileSource,
                'request' => new FileSourceRequest($newLabel),
                'expected' => $updatedFileSource,
            ],
        ];
    }

    #[DataProvider('updateGitDataProvider')]
    public function testUpdateGit(GitSource $source, GitSourceRequest $request, GitSource $expected): void
    {
        $mutatedSource = $this->mutator->updateGit($source, $request);

        self::assertEquals($expected, $mutatedSource);
    }

    /**
     * @return array<mixed>
     */
    public static function updateGitDataProvider(): array
    {
        $userId = UserId::create();
        $label = 'label';
        $hostUrl = 'https://example.com/repository.git';
        $path = '/path';
        $credentials = 'credentials';
        $newLabel = 'new label';
        $newHostUrl = 'https://new.example.com/repository.git';
        $newPath = '/path/new';
        $newCredentials = 'new credentials';

        $originalGitSourceWithoutCredentials = SourceOriginFactory::create('git', $userId, $label, $hostUrl, $path);
        $originalGitSourceWithCredentials = SourceOriginFactory::create(
            'git',
            $userId,
            $label,
            $hostUrl,
            $path,
            $credentials
        );
        \assert($originalGitSourceWithCredentials instanceof GitSource);

        $originalGitSourceWithNullifiedCredentials = clone $originalGitSourceWithCredentials;
        $originalGitSourceWithNullifiedCredentials->setCredentials('');

        $updatedGitSource = clone $originalGitSourceWithCredentials;
        $updatedGitSource->setLabel($newLabel);
        $updatedGitSource->setHostUrl($newHostUrl);
        $updatedGitSource->setPath($newPath);
        $updatedGitSource->setCredentials($newCredentials);

        return [
            'git source, no credentials, no changes' => [
                'source' => $originalGitSourceWithoutCredentials,
                'request' => new GitSourceRequest($label, $hostUrl, $path, ''),
                'expected' => $originalGitSourceWithoutCredentials,
            ],
            'git source, has credentials, no changes' => [
                'source' => $originalGitSourceWithCredentials,
                'request' => new GitSourceRequest($label, $hostUrl, $path, $credentials),
                'expected' => $originalGitSourceWithCredentials,
            ],
            'git source, update all' => [
                'source' => $originalGitSourceWithCredentials,
                'request' => new GitSourceRequest($newLabel, $newHostUrl, $newPath, $newCredentials),
                'expected' => $updatedGitSource,
            ],
            'git source, nullify credentials' => [
                'source' => $originalGitSourceWithCredentials,
                'request' => new GitSourceRequest($label, $hostUrl, $path, ''),
                'expected' => $originalGitSourceWithNullifiedCredentials,
            ],
        ];
    }
}
