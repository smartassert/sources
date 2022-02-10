<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\AbstractSource;
use App\Entity\FileSource;
use App\Services\FileStoreManager;
use App\Services\Source\Store;
use App\Tests\Services\EntityRemover;
use App\Tests\Services\ApplicationClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use webignition\ObjectReflector\ObjectReflector;

class SourceControllerAddFileTest extends WebTestCase
{
    private const USER_ID = '01FVHKTM3V53JVCW1HPN1125NF';
    private const SOURCE_ID = '01FVHM0XGXGAD463JTW05CN2TF';
    private const SOURCE_RELATIVE_PATH = self::USER_ID . '/' . self::SOURCE_ID;
    private const EXPECTED_FILE_RELATIVE_PATH = self::SOURCE_RELATIVE_PATH . '/' . self::FILENAME;

    private const FILENAME = 'filename.yaml';
    private const CONTENT = '- list item';

    private const CREATE_DATA = [
        'name' => self::FILENAME,
        'content' => self::CONTENT,
    ];

    private const UPDATE_DATA = [
        'name' => self::FILENAME,
        'content' => self::CONTENT . ' updated',
    ];

    private MockHandler $mockHandler;
    private ApplicationClient $applicationClient;

    private string $expectedFilePath;

    protected function setUp(): void
    {
        parent::setUp();

        $client = static::createClient();

        $mockHandler = self::getContainer()->get(MockHandler::class);
        \assert($mockHandler instanceof MockHandler);
        $this->mockHandler = $mockHandler;

        $fileStoreBasePath = self::getContainer()->getParameter('file_store_base_path');
        \assert(is_string($fileStoreBasePath));
        $this->expectedFilePath = $fileStoreBasePath . '/' . self::EXPECTED_FILE_RELATIVE_PATH;

        $applicationClient = self::getContainer()->get(ApplicationClient::class);
        \assert($applicationClient instanceof ApplicationClient);
        $this->applicationClient = $applicationClient;
        $applicationClient->setClient($client);

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

        $this->mockHandler->append(
            new Response(200, [], self::USER_ID),
        );

        $response = $this->applicationClient->makeAuthorizedSourceRequest(
            'POST',
            'add_file',
            self::SOURCE_ID,
            self::CREATE_DATA
        );

        self::assertSame(200, $response->getStatusCode());
        self::assertSame(self::CREATE_DATA['content'], file_get_contents($this->expectedFilePath));
    }

    public function testUpdateAddedFile(): void
    {
        $this->mockHandler->append(
            new Response(200, [], self::USER_ID)
        );

        $response = $this->applicationClient->makeAuthorizedSourceRequest(
            'POST',
            'add_file',
            self::SOURCE_ID,
            self::UPDATE_DATA
        );

        self::assertSame(200, $response->getStatusCode());
        self::assertSame(self::UPDATE_DATA['content'], file_get_contents($this->expectedFilePath));
    }
}
