<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\AbstractSource;
use App\Entity\FileSource;
use App\Repository\SourceRepository;
use App\Services\FileStoreManager;
use App\Services\Source\Store;
use App\Tests\Model\Route;
use App\Tests\Services\EntityRemover;
use webignition\ObjectReflector\ObjectReflector;

class UserSourceControllerDeleteFileSourceFilesTest extends AbstractSourceControllerTest
{
    private const USER_ID = '01FVHKTM3V53JVCW1HPN1125NF';
    private const SOURCE_ID = '01FVHM0XGXGAD463JTW05CN2TF';
    private const SOURCE_RELATIVE_PATH = self::USER_ID . '/' . self::SOURCE_ID;
    private const EXPECTED_FILE_RELATIVE_PATH = self::SOURCE_RELATIVE_PATH . '/' . self::FILENAME;

    private const FILENAME = 'filename.yaml';
    private const CONTENT = '- list item';

    private const CREATE_DATA = [
        'content' => self::CONTENT,
    ];

    private SourceRepository $sourceRepository;

    private string $expectedFileStorePath;
    private string $expectedFilePath;

    protected function setUp(): void
    {
        parent::setUp();

        $sourceRepository = self::getContainer()->get(SourceRepository::class);
        \assert($sourceRepository instanceof SourceRepository);
        $this->sourceRepository = $sourceRepository;

        $fileStoreBasePath = self::getContainer()->getParameter('file_store_base_path');
        \assert(is_string($fileStoreBasePath));
        $this->expectedFileStorePath = $fileStoreBasePath . '/' . self::SOURCE_RELATIVE_PATH;
        $this->expectedFilePath = $fileStoreBasePath . '/' . self::EXPECTED_FILE_RELATIVE_PATH;

        $entityRemover = self::getContainer()->get(EntityRemover::class);
        if ($entityRemover instanceof EntityRemover) {
            $entityRemover->removeAll();
        }

        $source = new FileSource(self::USER_ID, 'file source label');
        ObjectReflector::setProperty(
            $source,
            AbstractSource::class,
            'id',
            self::SOURCE_ID
        );

        $store = self::getContainer()->get(Store::class);
        \assert($store instanceof Store);
        $store->add($source);
        self::assertSame(1, $this->sourceRepository->count([]));
    }

    public function testAddFileSuccess(): void
    {
        $fileStoreManager = self::getContainer()->get(FileStoreManager::class);
        \assert($fileStoreManager instanceof FileStoreManager);
        $fileStoreManager->remove(self::SOURCE_RELATIVE_PATH);
        self::assertDirectoryDoesNotExist(self::SOURCE_RELATIVE_PATH);

        $this->setUserServiceAuthorizedResponse(self::USER_ID);

        $response = $this->applicationClient->makeAuthorizedRequest(
            'POST',
            new Route('file_source_file_add', [
                'sourceId' => self::SOURCE_ID,
                'filename' => self::FILENAME,
            ]),
            self::CREATE_DATA
        );

        self::assertSame(200, $response->getStatusCode());
        self::assertFileExists($this->expectedFilePath);
        self::assertSame(self::CREATE_DATA['content'], file_get_contents($this->expectedFilePath));
    }

    /**
     * @depends testAddFileSuccess
     */
    public function testDeleteSourceDeletesFiles(): void
    {
        self::assertFileExists($this->expectedFilePath);
        self::assertSame(self::CREATE_DATA['content'], file_get_contents($this->expectedFilePath));

        self::assertDirectoryExists($this->expectedFileStorePath);
        self::assertFileExists($this->expectedFilePath);

        $this->setUserServiceAuthorizedResponse(self::USER_ID);

        $response = $this->applicationClient->makeAuthorizedSourceRequest(
            'DELETE',
            'user_source_delete',
            self::SOURCE_ID
        );

        self::assertSame(200, $response->getStatusCode());
        self::assertSame(0, $this->sourceRepository->count([]));

        self::assertDirectoryDoesNotExist($this->expectedFileStorePath);
        self::assertFileDoesNotExist($this->expectedFilePath);
    }
}
