<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Services\FileStoreManager;
use App\Tests\Model\Route;

class UserSourceControllerDeleteFileSourceFilesTest extends AbstractFileSourceFilesTest
{
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
