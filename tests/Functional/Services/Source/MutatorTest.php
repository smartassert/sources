<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services\Source;

use App\Entity\FileSource;
use App\Entity\GitSource;
use App\Request\FileSourceRequest;
use App\Request\GitSourceRequest;
use App\Services\Source\Mutator;
use App\Tests\Model\UserId;
use App\Tests\Services\EntityRemover;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;

class MutatorTest extends WebTestCase
{
    private Mutator $mutator;

    protected function setUp(): void
    {
        parent::setUp();

        $mutator = self::getContainer()->get(Mutator::class);
        \assert($mutator instanceof Mutator);
        $this->mutator = $mutator;

        $entityRemover = self::getContainer()->get(EntityRemover::class);
        if ($entityRemover instanceof EntityRemover) {
            $entityRemover->removeAll();
        }
    }

    /**
     * @dataProvider updateFileNoChangesDataProvider
     */
    public function testUpdateFileNoChanges(FileSource $source, FileSourceRequest $request): void
    {
        $mutatedSource = $this->mutator->updateFile($source, $request);

        self::assertSame($source, $mutatedSource);
    }

    /**
     * @return array<mixed>
     */
    public function updateFileNoChangesDataProvider(): array
    {
        $userId = UserId::create();
        $label = 'file source label';
        $fileSource = new FileSource($userId, $label);

        return [
            'file source, no changes' => [
                'source' => $fileSource,
                'request' => new FileSourceRequest(new Request(
                    request: [
                        FileSourceRequest::PARAMETER_LABEL => $label,
                    ]
                )),
            ],
        ];
    }

    /**
     * @dataProvider updateGitNoChangesDataProvider
     */
    public function testUpdateGitNoChanges(GitSource $source, GitSourceRequest $request): void
    {
        $mutatedSource = $this->mutator->updateGit($source, $request);

        self::assertSame($source, $mutatedSource);
    }

    /**
     * @return array<mixed>
     */
    public function updateGitNoChangesDataProvider(): array
    {
        $userId = UserId::create();
        $hostUrl = 'https://example.com/repository.git';
        $path = '/path';
        $credentials = 'credentials';
        $gitSourceNoCredentials = new GitSource($userId, $hostUrl, $path, '');
        $gitSourceHasCredentials = new GitSource($userId, $hostUrl, $path, $credentials);

        return [
            'git source, no credentials, no changes' => [
                'source' => $gitSourceNoCredentials,
                'request' => new GitSourceRequest(new Request(
                    request: [
                        GitSourceRequest::PARAMETER_HOST_URL => $hostUrl,
                        GitSourceRequest::PARAMETER_PATH => $path,
                        GitSourceRequest::PARAMETER_CREDENTIALS => '',
                    ]
                )),
            ],
            'git source, has credentials, no changes' => [
                'source' => $gitSourceHasCredentials,
                'request' => new GitSourceRequest(new Request(
                    request: [
                        GitSourceRequest::PARAMETER_HOST_URL => $hostUrl,
                        GitSourceRequest::PARAMETER_PATH => $path,
                        GitSourceRequest::PARAMETER_CREDENTIALS => $credentials,
                    ]
                )),
            ],
        ];
    }

    /**
     * @dataProvider updateFileDataProvider
     */
    public function testUpdateFile(FileSource $source, FileSourceRequest $request, FileSource $expected): void
    {
        $mutatedSource = $this->mutator->updateFile($source, $request);

        self::assertEquals($expected, $mutatedSource);
    }

    /**
     * @return array<mixed>
     */
    public function updateFileDataProvider(): array
    {
        $userId = UserId::create();
        $label = 'file source label';
        $newLabel = 'new file source label';
        $originalFileSource = new FileSource($userId, $label);
        $updatedFileSource = clone $originalFileSource;
        $updatedFileSource->setLabel($newLabel);

        return [
            'file source, no changes' => [
                'source' => $originalFileSource,
                'request' => new FileSourceRequest(new Request(
                    request: [
                        FileSourceRequest::PARAMETER_LABEL => $label,
                    ]
                )),
                'expected' => $originalFileSource,
            ],
            'file source, update label' => [
                'source' => $originalFileSource,
                'request' => new FileSourceRequest(new Request(
                    request: [
                        FileSourceRequest::PARAMETER_LABEL => $newLabel,
                    ]
                )),
                'expected' => $updatedFileSource,
            ],
        ];
    }

    /**
     * @dataProvider updateGitDataProvider
     */
    public function testUpdateGit(GitSource $source, GitSourceRequest $request, GitSource $expected): void
    {
        $mutatedSource = $this->mutator->updateGit($source, $request);

        self::assertEquals($expected, $mutatedSource);
    }

    /**
     * @return array<mixed>
     */
    public function updateGitDataProvider(): array
    {
        $userId = UserId::create();
        $hostUrl = 'https://example.com/repository.git';
        $path = '/path';
        $credentials = 'credentials';
        $newHostUrl = 'https://new.example.com/repository.git';
        $newPath = '/path/new';
        $newCredentials = 'new credentials';
        $originalGitSourceWithoutCredentials = new GitSource($userId, $hostUrl, $path, '');
        $originalGitSourceWithCredentials = new GitSource($userId, $hostUrl, $path, $credentials);
        $originalGitSourceWithNullifiedCredentials = clone $originalGitSourceWithCredentials;
        $originalGitSourceWithNullifiedCredentials->setCredentials('');
        $updatedGitSource = clone $originalGitSourceWithCredentials;
        $updatedGitSource->setHostUrl($newHostUrl);
        $updatedGitSource->setPath($newPath);
        $updatedGitSource->setCredentials($newCredentials);

        return [
            'git source, no credentials, no changes' => [
                'source' => $originalGitSourceWithoutCredentials,
                'request' => new GitSourceRequest(new Request(
                    request: [
                        GitSourceRequest::PARAMETER_HOST_URL => $hostUrl,
                        GitSourceRequest::PARAMETER_PATH => $path,
                        GitSourceRequest::PARAMETER_CREDENTIALS => '',
                    ]
                )),
                'expected' => $originalGitSourceWithoutCredentials,
            ],
            'git source, has credentials, no changes' => [
                'source' => $originalGitSourceWithCredentials,
                'request' => new GitSourceRequest(new Request(
                    request: [
                        GitSourceRequest::PARAMETER_HOST_URL => $hostUrl,
                        GitSourceRequest::PARAMETER_PATH => $path,
                        GitSourceRequest::PARAMETER_CREDENTIALS => $credentials,
                    ]
                )),
                'expected' => $originalGitSourceWithCredentials,
            ],
            'git source, update all' => [
                'source' => $originalGitSourceWithCredentials,
                'request' => new GitSourceRequest(new Request(
                    request: [
                        GitSourceRequest::PARAMETER_HOST_URL => $newHostUrl,
                        GitSourceRequest::PARAMETER_PATH => $newPath,
                        GitSourceRequest::PARAMETER_CREDENTIALS => $newCredentials,
                    ]
                )),
                'expected' => $updatedGitSource,
            ],
            'git source, nullify credentials' => [
                'source' => $originalGitSourceWithCredentials,
                'request' => new GitSourceRequest(new Request(
                    request: [
                        GitSourceRequest::PARAMETER_HOST_URL => $hostUrl,
                        GitSourceRequest::PARAMETER_PATH => $path,
                        GitSourceRequest::PARAMETER_CREDENTIALS => '',
                    ]
                )),
                'expected' => $originalGitSourceWithNullifiedCredentials,
            ],
        ];
    }
}
