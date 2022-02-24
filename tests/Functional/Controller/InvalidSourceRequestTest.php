<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\FileSource;
use App\Model\EntityId;
use App\Services\Source\Store;
use App\Tests\Model\UserId;
use App\Tests\Services\EntityRemover;

class InvalidSourceRequestTest extends AbstractSourceControllerTest
{
    private Store $store;

    protected function setUp(): void
    {
        parent::setUp();

        $store = self::getContainer()->get(Store::class);
        \assert($store instanceof Store);
        $this->store = $store;

        $entityRemover = self::getContainer()->get(EntityRemover::class);
        if ($entityRemover instanceof EntityRemover) {
            $entityRemover->removeAll();
        }
    }

    /**
     * @dataProvider requestSourceDataProvider
     *
     * @param array<string, int|string> $routeParameters
     */
    public function testRequestSourceNotFound(string $method, string $routeName, array $routeParameters): void
    {
        $this->setUserServiceAuthorizedResponse(UserId::create());

        $url = $this->generateUrl($routeName, $routeParameters);

        $response = $this->applicationClient->makeAuthorizedRequest($method, $url);

        self::assertSame(404, $response->getStatusCode());
    }

    /**
     * @dataProvider requestSourceDataProvider
     *
     * @param array<string, int|string> $routeParameters
     */
    public function testRequestInvalidSourceUser(string $method, string $routeName, array $routeParameters): void
    {
        $sourceUserId = UserId::create();
        $requestUserId = UserId::create();
        $label = 'source label';

        $source = new FileSource($sourceUserId, $label);
        $sourceId = $source->getId();
        $this->store->add($source);

        $this->setUserServiceAuthorizedResponse($requestUserId);

        $url = $this->generateUrl($routeName, array_merge($routeParameters, ['sourceId' => $sourceId]));

        $response = $this->applicationClient->makeAuthorizedRequest($method, $url);

        self::assertSame(403, $response->getStatusCode());
    }

    /**
     * @return array<mixed>
     */
    public function requestSourceDataProvider(): array
    {
        $sourceRouteParameters = ['sourceId' => EntityId::create()];

        return [
            'get source' => [
                'method' => 'GET',
                'routeName' => 'user_source_get',
                'routeParameters' => $sourceRouteParameters,
            ],
            'update source' => [
                'method' => 'PUT',
                'routeName' => 'user_source_update',
                'routeParameters' => $sourceRouteParameters,
            ],
            'delete source' => [
                'method' => 'DELETE',
                'routeName' => 'user_source_delete',
                'routeParameters' => $sourceRouteParameters,
            ],
            'prepare source' => [
                'method' => 'POST',
                'routeName' => 'user_source_prepare',
                'routeParameters' => $sourceRouteParameters,
            ],
            'add file' => [
                'method' => 'POST',
                'routeName' => 'file_source_file_add',
                'routeParameters' => array_merge(
                    $sourceRouteParameters,
                    [
                        'filename' => 'filename.yaml',
                    ]
                ),
            ],
            'remove file' => [
                'method' => 'POST',
                'routeName' => 'file_source_file_remove',
                'routeParameters' => array_merge(
                    $sourceRouteParameters,
                    [
                        'filename' => 'filename.yaml',
                    ]
                ),
            ],
        ];
    }
}
