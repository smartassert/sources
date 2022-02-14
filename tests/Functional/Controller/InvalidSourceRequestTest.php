<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\FileSource;
use App\Model\EntityId;
use App\Services\Source\Store;
use App\Tests\Model\Route;
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
     */
    public function testRequestSourceNotFound(string $method, Route $route): void
    {
        $this->setUserServiceAuthorizedResponse(UserId::create());

        $response = $this->applicationClient->makeAuthorizedRequest($method, $route);

        self::assertSame(404, $response->getStatusCode());
    }

    /**
     * @dataProvider requestSourceDataProvider
     */
    public function testRequestInvalidSourceUser(string $method, Route $route): void
    {
        $sourceUserId = UserId::create();
        $requestUserId = UserId::create();
        $label = 'source label';

        $source = new FileSource($sourceUserId, $label);
        $sourceId = $source->getId();
        $this->store->add($source);

        $this->setUserServiceAuthorizedResponse($requestUserId);

        $routeWithSourceId = new Route(
            $route->name,
            array_merge($route->parameters, ['sourceId' => $sourceId])
        );

        $response = $this->applicationClient->makeAuthorizedRequest($method, $routeWithSourceId);

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
                'route' => new Route('get', $sourceRouteParameters),
            ],
            'update source' => [
                'method' => 'PUT',
                'route' => new Route('update', $sourceRouteParameters),
            ],
            'delete source' => [
                'method' => 'DELETE',
                'route' => new Route('delete', $sourceRouteParameters),
            ],
            'prepare source' => [
                'method' => 'POST',
                'route' => new Route('prepare', $sourceRouteParameters),
            ],
            'add file' => [
                'method' => 'POST',
                'route' => new Route('add_file', array_merge(
                    $sourceRouteParameters,
                    [
                        'filename' => 'filename.yaml',
                    ]
                )),
            ],
            'remove file' => [
                'method' => 'POST',
                'route' => new Route('remove_file', array_merge(
                    $sourceRouteParameters,
                    [
                        'filename' => 'filename.yaml',
                    ]
                )),
            ],
        ];
    }
}
