<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Tests\Services\ApplicationClient;
use GuzzleHttp\Handler\MockHandler;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class AbstractSourceControllerTest extends WebTestCase
{
    protected MockHandler $mockHandler;
    protected ApplicationClient $applicationClient;

    protected function setUp(): void
    {
        parent::setUp();

        $client = static::createClient();

        $applicationClient = self::getContainer()->get(ApplicationClient::class);
        \assert($applicationClient instanceof ApplicationClient);
        $this->applicationClient = $applicationClient;
        $applicationClient->setClient($client);

        $mockHandler = self::getContainer()->get(MockHandler::class);
        \assert($mockHandler instanceof MockHandler);
        $this->mockHandler = $mockHandler;
    }
}
