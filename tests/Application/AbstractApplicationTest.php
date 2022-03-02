<?php

declare(strict_types=1);

namespace App\Tests\Application;

use App\Tests\Services\ApplicationClient\AdapterInterface;
use App\Tests\Services\ApplicationClient\Client;
use App\Tests\Services\ApplicationClient\ClientFactory;
use App\Tests\Services\AuthenticationConfiguration;
use App\Tests\Services\ResponseAsserter;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class AbstractApplicationTest extends WebTestCase
{
    protected AuthenticationConfiguration $authenticationConfiguration;
    protected ResponseAsserter $responseAsserter;
    protected KernelBrowser $kernelBrowser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->kernelBrowser = self::createClient();

        $authenticationConfiguration = self::getContainer()->get(AuthenticationConfiguration::class);
        \assert($authenticationConfiguration instanceof AuthenticationConfiguration);
        $this->authenticationConfiguration = $authenticationConfiguration;

        $responseAsserter = self::getContainer()->get(ResponseAsserter::class);
        \assert($responseAsserter instanceof ResponseAsserter);
        $this->responseAsserter = $responseAsserter;
    }

    protected function getApplicationClient(): Client
    {
        $factory = self::getContainer()->get(ClientFactory::class);
        \assert($factory instanceof ClientFactory);

        return $factory->create($this->getClientAdapter());
    }

    abstract protected function getClientAdapter(): AdapterInterface;
}
