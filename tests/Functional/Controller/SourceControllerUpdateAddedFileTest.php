<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\AbstractSource;
use App\Entity\FileSource;
use App\Entity\GitSource;
use App\Entity\RunSource;
use App\Entity\SourceInterface;
use App\Enum\RunSource\FailureReason;
use App\Enum\RunSource\State;
use App\Enum\Source\Type;
use App\Model\EntityId;
use App\Repository\RunSourceRepository;
use App\Repository\SourceRepository;
use App\Request\AddFileRequest;
use App\Request\FileSourceRequest;
use App\Request\GitSourceRequest;
use App\Request\InvalidSourceTypeRequest;
use App\Request\SourceRequestInterface;
use App\Services\FileStoreManager;
use App\Services\RunSourceSerializer;
use App\Services\Source\Store;
use App\Tests\Model\Route;
use App\Tests\Model\UserId;
use App\Tests\Services\EntityRemover;
use App\Tests\Services\FileStoreFixtureCreator;
use App\Tests\Services\FixtureLoader;
use App\Tests\Services\ApplicationClient;
use App\Validator\YamlFileConstraint;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use SmartAssert\UsersClient\Routes;
use SmartAssert\UsersSecurityBundle\Security\AuthorizationProperties;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\Routing\RouterInterface;
use webignition\HttpHistoryContainer\Container as HttpHistoryContainer;
use webignition\ObjectReflector\ObjectReflector;

class SourceControllerUpdateAddedFileTest extends WebTestCase
{
    private const AUTHORIZATION_TOKEN = 'authorization-token';
    private const USER_ID = '01FVHKTM3V53JVCW1HPN1125NF';
    private const SOURCE_ID = '01FVHM0XGXGAD463JTW05CN2TF';
    private const EXPECTED_FILE_RELATIVE_PATH = self::USER_ID . '/' . self::SOURCE_ID . '/' . self::FILENAME;

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

    private KernelBrowser $client;
    private MockHandler $mockHandler;
    private HttpHistoryContainer $httpHistoryContainer;
    private SourceRepository $sourceRepository;
    private RunSourceRepository $runSourceRepository;
    private Store $store;
    private RouterInterface $router;
    private FileStoreFixtureCreator $fixtureCreator;
    private FixtureLoader $fixtureLoader;

    private ApplicationClient $applicationClient;

    private string $expectedFilePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient();

        $mockHandler = self::getContainer()->get(MockHandler::class);
        \assert($mockHandler instanceof MockHandler);
        $this->mockHandler = $mockHandler;

        $httpHistoryContainer = self::getContainer()->get(HttpHistoryContainer::class);
        \assert($httpHistoryContainer instanceof HttpHistoryContainer);
        $this->httpHistoryContainer = $httpHistoryContainer;

        $handlerStack = self::getContainer()->get(HandlerStack::class);
        \assert($handlerStack instanceof HandlerStack);
        $handlerStack->push(Middleware::history($this->httpHistoryContainer), 'history');

        $store = self::getContainer()->get(Store::class);
        \assert($store instanceof Store);
        $this->store = $store;

        $sourceRepository = self::getContainer()->get(SourceRepository::class);
        \assert($sourceRepository instanceof SourceRepository);
        $this->sourceRepository = $sourceRepository;

        $runSourceRepository = self::getContainer()->get(RunSourceRepository::class);
        \assert($runSourceRepository instanceof RunSourceRepository);
        $this->runSourceRepository = $runSourceRepository;

        $router = self::getContainer()->get(RouterInterface::class);
        \assert($router instanceof RouterInterface);
        $this->router = $router;

        $fixtureCreator = self::getContainer()->get(FileStoreFixtureCreator::class);
        \assert($fixtureCreator instanceof FileStoreFixtureCreator);
        $this->fixtureCreator = $fixtureCreator;

        $fixtureLoader = self::getContainer()->get(FixtureLoader::class);
        \assert($fixtureLoader instanceof FixtureLoader);
        $this->fixtureLoader = $fixtureLoader;

        $fileStoreBasePath = self::getContainer()->getParameter('file_store_base_path');
        \assert(is_string($fileStoreBasePath));
        $this->expectedFilePath = $fileStoreBasePath . '/' . self::EXPECTED_FILE_RELATIVE_PATH;

        $applicationClient = self::getContainer()->get(ApplicationClient::class);
        \assert($applicationClient instanceof ApplicationClient);
        $this->applicationClient = $applicationClient;
        $applicationClient->setClient($this->client);

        $entityRemover = self::getContainer()->get(EntityRemover::class);
        if ($entityRemover instanceof EntityRemover) {
            $entityRemover->removeAll();
        }
    }

    public function testAddFileSuccess(): void
    {
        $source = new FileSource(self::USER_ID, 'file source label');

        ObjectReflector::setProperty(
            $source,
            AbstractSource::class,
            'id',
            self::SOURCE_ID
        );

        $this->store->add($source);

        $fileStoreManager = self::getContainer()->get(FileStoreManager::class);
        \assert($fileStoreManager instanceof FileStoreManager);
        $fileStoreManager->remove((string) $source);

        self::assertDirectoryDoesNotExist((string) $source);

        $this->mockHandler->append(
            new Response(200, [], self::USER_ID),
        );

        $response = $this->applicationClient->makeAuthorizedSourceRequest(
            'POST',
            'add_file',
            $source->getId(),
            self::CREATE_DATA
        );

        self::assertSame(200, $response->getStatusCode());
        self::assertSame(self::CREATE_DATA['content'], file_get_contents($this->expectedFilePath));
    }

    public function testAddFileSuccessAgain(): void
    {
        $source = new FileSource(self::USER_ID, 'file source label');

        ObjectReflector::setProperty(
            $source,
            AbstractSource::class,
            'id',
            self::SOURCE_ID
        );

        $this->store->add($source);

        $this->mockHandler->append(
            new Response(200, [], self::USER_ID)
        );

        $response = $this->applicationClient->makeAuthorizedSourceRequest(
            'POST',
            'add_file',
            $source->getId(),
            self::UPDATE_DATA
        );

        self::assertSame(200, $response->getStatusCode());
        self::assertSame(self::UPDATE_DATA['content'], file_get_contents($this->expectedFilePath));

//        $this->assertAuthorizationRequestIsMade();
//
//        $fileStoreBasePath = self::getContainer()->getParameter('file_store_base_path');
//        self::assertIsString($fileStoreBasePath);
//
//        $requestFileName = $requestData[AddFileRequest::KEY_POST_NAME] ?? null;
//        $requestFileName = is_string($requestFileName) ? $requestFileName : null;
//        self::assertIsString($requestFileName);
//
//        $expectedFilePath = $fileStoreBasePath . '/' . $source . '/' . $requestFileName;
//        self::assertFileExists($expectedFilePath);
//
//        $requestContent = $requestData[AddFileRequest::KEY_POST_CONTENT] ?? null;
//        $requestContent = is_string($requestContent) ? $requestContent : null;
//        self::assertIsString($requestContent);
//
//        self::assertSame($requestContent, file_get_contents($expectedFilePath));
    }
}
