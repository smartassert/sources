<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services\Source;

use App\Entity\FileSource;
use App\Entity\GitSource;
use App\Entity\SourceInterface;
use App\Request\FooFileSourceRequest;
use App\Request\FooGitSourceRequest;
use App\Request\SourceRequestInterface;
use App\Services\Source\Mutator;
use App\Tests\Model\UserId;
use App\Tests\Services\EntityRemover;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

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
     * @dataProvider updateNoChangesDataProvider
     */
    public function testUpdateNoChanges(SourceInterface $source, SourceRequestInterface $request): void
    {
        $mutatedSource = $this->mutator->update($source, $request);

        self::assertSame($source, $mutatedSource);
    }

    /**
     * @return array<mixed>
     */
    public function updateNoChangesDataProvider(): array
    {
        $userId = UserId::create();
        $hostUrl = 'https://example.com/repository.git';
        $path = '/path';
        $credentials = 'credentials';
        $label = 'file source label';

        $gitSourceNoCredentials = new GitSource($userId, $hostUrl, $path, '');
        $gitSourceHasCredentials = new GitSource($userId, $hostUrl, $path, $credentials);
        $fileSource = new FileSource($userId, $label);

        return [
            'git source, no credentials, no changes' => [
                'source' => $gitSourceNoCredentials,
                'request' => new FooGitSourceRequest([
                    FooGitSourceRequest::PARAMETER_HOST_URL => $hostUrl,
                    FooGitSourceRequest::PARAMETER_PATH => $path,
                    FooGitSourceRequest::PARAMETER_CREDENTIALS => '',
                ]),
            ],
            'git source, has credentials, no changes' => [
                'source' => $gitSourceHasCredentials,
                'request' => new FooGitSourceRequest([
                    FooGitSourceRequest::PARAMETER_HOST_URL => $hostUrl,
                    FooGitSourceRequest::PARAMETER_PATH => $path,
                    FooGitSourceRequest::PARAMETER_CREDENTIALS => $credentials,
                ]),
            ],
            'file source, no changes' => [
                'source' => $fileSource,
                'request' => new FooGitSourceRequest([
                    FooFileSourceRequest::PARAMETER_LABEL => $label,
                ]),
            ],
        ];
    }

    /**
     * @dataProvider updateDataProvider
     */
    public function testUpdateFoo(
        SourceInterface $source,
        SourceRequestInterface $request,
        SourceInterface $expected,
    ): void {
        $mutatedSource = $this->mutator->update($source, $request);

        self::assertEquals($expected, $mutatedSource);
    }

    /**
     * @return array<mixed>
     */
    public function updateDataProvider(): array
    {
        $userId = UserId::create();
        $hostUrl = 'https://example.com/repository.git';
        $path = '/path';
        $credentials = 'credentials';
        $newHostUrl = 'https://new.example.com/repository.git';
        $newPath = '/path/new';
        $newCredentials = 'new credentials';
        $label = 'file source label';
        $newLabel = 'new file source label';

        $originalGitSourceWithoutCredentials = new GitSource($userId, $hostUrl, $path, '');
        $originalGitSourceWithCredentials = new GitSource($userId, $hostUrl, $path, $credentials);
        $originalGitSourceWithNullifiedCredentials = clone $originalGitSourceWithCredentials;
        $originalGitSourceWithNullifiedCredentials->setCredentials('');
        $updatedGitSource = clone $originalGitSourceWithCredentials;
        $updatedGitSource->setHostUrl($newHostUrl);
        $updatedGitSource->setPath($newPath);
        $updatedGitSource->setCredentials($newCredentials);

        $originalFileSource = new FileSource($userId, $label);
        $updatedFileSource = clone $originalFileSource;
        $updatedFileSource->setLabel($newLabel);

        return [
            'git source, no credentials, no changes' => [
                'source' => $originalGitSourceWithoutCredentials,
                'request' => new FooGitSourceRequest([
                    FooGitSourceRequest::PARAMETER_HOST_URL => $hostUrl,
                    FooGitSourceRequest::PARAMETER_PATH => $path,
                    FooGitSourceRequest::PARAMETER_CREDENTIALS => '',
                ]),
                'expected' => $originalGitSourceWithoutCredentials,
            ],
            'git source, has credentials, no changes' => [
                'source' => $originalGitSourceWithCredentials,
                'request' => new FooGitSourceRequest([
                    FooGitSourceRequest::PARAMETER_HOST_URL => $hostUrl,
                    FooGitSourceRequest::PARAMETER_PATH => $path,
                    FooGitSourceRequest::PARAMETER_CREDENTIALS => $credentials,
                ]),
                'expected' => $originalGitSourceWithCredentials,
            ],
            'file source, no changes' => [
                'source' => $originalFileSource,
                'request' => new FooGitSourceRequest([
                    FooFileSourceRequest::PARAMETER_LABEL => $label,
                ]),
                'expected' => $originalFileSource,
            ],
            'git source, update all' => [
                'source' => $originalGitSourceWithCredentials,
                'request' => new FooGitSourceRequest([
                    FooGitSourceRequest::PARAMETER_HOST_URL => $newHostUrl,
                    FooGitSourceRequest::PARAMETER_PATH => $newPath,
                    FooGitSourceRequest::PARAMETER_CREDENTIALS => $newCredentials,
                ]),
                'expected' => $updatedGitSource,
            ],
            'git source, nullify credentials' => [
                'source' => $originalGitSourceWithCredentials,
                'request' => new FooGitSourceRequest([
                    FooGitSourceRequest::PARAMETER_HOST_URL => $hostUrl,
                    FooGitSourceRequest::PARAMETER_PATH => $path,
                    FooGitSourceRequest::PARAMETER_CREDENTIALS => '',
                ]),
                'expected' => $originalGitSourceWithNullifiedCredentials,
            ],
            'file source, update label' => [
                'source' => $originalFileSource,
                'request' => new FooFileSourceRequest([
                    FooFileSourceRequest::PARAMETER_LABEL => $newLabel,
                ]),
                'expected' => $updatedFileSource,
            ],
        ];
    }
}
