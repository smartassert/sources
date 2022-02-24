<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\AbstractSource;
use App\Entity\SourceInterface;
use App\Tests\Services\ApplicationClient;
use App\Tests\Services\AuthenticationConfiguration;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\RouterInterface;
use webignition\ObjectReflector\ObjectReflector;

abstract class AbstractSourceControllerTest extends WebTestCase
{
    protected const AUTHENTICATED_USER_ID_PLACEHOLDER = '{{ authenticated_user_id }}';

    protected ApplicationClient $applicationClient;
    protected string $authenticatedUserId = '';
    private RouterInterface $router;

    protected function setUp(): void
    {
        parent::setUp();

        $client = static::createClient();

        $applicationClient = self::getContainer()->get(ApplicationClient::class);
        \assert($applicationClient instanceof ApplicationClient);
        $this->applicationClient = $applicationClient;
        $applicationClient->setClient($client);

        $router = self::getContainer()->get(RouterInterface::class);
        \assert($router instanceof RouterInterface);
        $this->router = $router;

        $authenticationConfiguration = self::getContainer()->get(AuthenticationConfiguration::class);
        \assert($authenticationConfiguration instanceof AuthenticationConfiguration);
        $this->authenticatedUserId = $authenticationConfiguration->authenticatedUserId;
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
                $this->authenticatedUserId
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
            $sourceData['user_id'] = $this->authenticatedUserId;
        }

        return $sourceData;
    }
}
