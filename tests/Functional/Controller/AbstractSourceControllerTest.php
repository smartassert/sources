<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\AbstractSource;
use App\Entity\SourceInterface;
use App\Tests\Services\ApplicationClient\Client;
use App\Tests\Services\ApplicationClient\SymfonyAdapter;
use App\Tests\Services\AuthenticationConfiguration;
use App\Tests\Services\RequestAsserter;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\RouterInterface;
use webignition\ObjectReflector\ObjectReflector;

abstract class AbstractSourceControllerTest extends WebTestCase
{
    protected const AUTHENTICATED_USER_ID_PLACEHOLDER = '{{ authenticated_user_id }}';

    protected RequestAsserter $requestAsserter;
    protected AuthenticationConfiguration $authenticationConfiguration;
    protected string $validToken;
    protected string $invalidToken;
    protected Client $application;
    private RouterInterface $router;

    protected function setUp(): void
    {
        parent::setUp();

        $client = static::createClient();

        $router = self::getContainer()->get(RouterInterface::class);
        \assert($router instanceof RouterInterface);
        $this->router = $router;

        $application = self::getContainer()->get('app.tests.services.application.client.functional');
        \assert($application instanceof Client);

        $symfonyClient = self::getContainer()->get(SymfonyAdapter::class);
        \assert($symfonyClient instanceof SymfonyAdapter);
        $symfonyClient->setKernelBrowser($client);

        $this->application = $application;

        $requestAsserter = self::getContainer()->get(RequestAsserter::class);
        \assert($requestAsserter instanceof RequestAsserter);
        $this->requestAsserter = $requestAsserter;

        $authenticationConfiguration = self::getContainer()->get(AuthenticationConfiguration::class);
        \assert($authenticationConfiguration instanceof AuthenticationConfiguration);
        $this->authenticationConfiguration = $authenticationConfiguration;

        $this->validToken = $authenticationConfiguration->validToken;
        $this->invalidToken = $authenticationConfiguration->invalidToken;
    }

    /**
     * @param array<string, int|string> $routeParameters
     */
    protected function generateUrl(string $routeName, array $routeParameters = []): string
    {
        return $this->router->generate($routeName, $routeParameters);
    }

    protected function setSourceUserIdToAuthenticatedUserId(SourceInterface $source): SourceInterface
    {
        if ($source instanceof AbstractSource && self::AUTHENTICATED_USER_ID_PLACEHOLDER == $source->getUserId()) {
            ObjectReflector::setProperty(
                $source,
                AbstractSource::class,
                'userId',
                $this->authenticationConfiguration->authenticatedUserId
            );
        }

        return $source;
    }

    /**
     * @param array<int, array<mixed>> $sourceDataCollection
     *
     * @return array<int, array<mixed>>
     */
    protected function replaceAuthenticatedUserIdInSourceDataCollection(array $sourceDataCollection): array
    {
        foreach ($sourceDataCollection as $sourceIndex => $sourceData) {
            $sourceDataCollection[$sourceIndex] = $this->replaceAuthenticatedUserIdInSourceData($sourceData);
        }

        return $sourceDataCollection;
    }

    /**
     * @param array<mixed> $sourceData
     *
     * @return array<mixed>
     */
    protected function replaceAuthenticatedUserIdInSourceData(array $sourceData): array
    {
        if (
            array_key_exists('user_id', $sourceData)
            && self::AUTHENTICATED_USER_ID_PLACEHOLDER == $sourceData['user_id']
        ) {
            $sourceData['user_id'] = $this->authenticationConfiguration->authenticatedUserId;
        }

        return $sourceData;
    }
}
