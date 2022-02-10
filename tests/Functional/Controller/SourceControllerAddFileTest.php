<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\AbstractSource;
use App\Entity\FileSource;
use App\Services\FileStoreManager;
use App\Services\Source\Store;
use App\Tests\Model\Route;
use App\Tests\Services\EntityRemover;
use webignition\ObjectReflector\ObjectReflector;

class SourceControllerAddFileTest extends AbstractSourceControllerTest
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

    private const UPDATE_DATA = [
        'content' => self::CONTENT . ' updated',
    ];

    private string $expectedFilePath;

    protected function setUp(): void
    {
        parent::setUp();

        $fileStoreBasePath = self::getContainer()->getParameter('file_store_base_path');
        \assert(is_string($fileStoreBasePath));
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
    }

    public function testAddFile(): void
    {
        $fileStoreManager = self::getContainer()->get(FileStoreManager::class);
        \assert($fileStoreManager instanceof FileStoreManager);
        $fileStoreManager->remove(self::SOURCE_RELATIVE_PATH);
        self::assertDirectoryDoesNotExist(self::SOURCE_RELATIVE_PATH);

        $this->setUserServiceAuthorizedResponse(self::USER_ID);

        $response = $this->applicationClient->makeAuthorizedRequest(
            'POST',
            new Route('add_file', [
                'sourceId' => self::SOURCE_ID,
                'filename' => self::FILENAME,
            ]),
            self::CREATE_DATA
        );

        self::assertSame(200, $response->getStatusCode());
        self::assertSame(self::CREATE_DATA['content'], file_get_contents($this->expectedFilePath));
    }

    public function testUpdateAddedFile(): void
    {
        $this->setUserServiceAuthorizedResponse(self::USER_ID);

        $response = $this->applicationClient->makeAuthorizedRequest(
            'POST',
            new Route('add_file', [
                'sourceId' => self::SOURCE_ID,
                'filename' => self::FILENAME,
            ]),
            self::UPDATE_DATA
        );

        self::assertSame(200, $response->getStatusCode());
        self::assertSame(self::UPDATE_DATA['content'], file_get_contents($this->expectedFilePath));
    }
}
