<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Model\EntityId;
use App\Tests\Model\Route;
use App\Tests\Services\AuthorizationRequestAsserter;

class UnauthorizedRequestTest extends AbstractSourceControllerTest
{
    private AuthorizationRequestAsserter $authorizationRequestAsserter;

    protected function setUp(): void
    {
        parent::setUp();

        $authorizationRequestAsserter = self::getContainer()->get(AuthorizationRequestAsserter::class);
        \assert($authorizationRequestAsserter instanceof AuthorizationRequestAsserter);
        $this->authorizationRequestAsserter = $authorizationRequestAsserter;
    }

    /**
     * @dataProvider requestForUnauthorizedUserDataProvider
     */
    public function testRequestForUnauthorizedUser(string $method, Route $route): void
    {
        $this->setUserServiceUnauthorizedResponse();

        $response = $this->applicationClient->makeAuthorizedRequest($method, $route);

        self::assertSame(401, $response->getStatusCode());
        $this->authorizationRequestAsserter->assertAuthorizationRequestIsMade();
    }

    /**
     * @return array<mixed>
     */
    public function requestForUnauthorizedUserDataProvider(): array
    {
        $sourceRouteParameters = ['sourceId' => EntityId::create()];

        return [
            'create source' => [
                'method' => 'POST',
                'route' => new Route('source_create'),
            ],
            'get source' => [
                'method' => 'GET',
                'route' => new Route('user_source_get', $sourceRouteParameters),
            ],
            'update source' => [
                'method' => 'PUT',
                'route' => new Route('user_source_update', $sourceRouteParameters),
            ],
            'delete source' => [
                'method' => 'DELETE',
                'route' => new Route('user_source_delete', $sourceRouteParameters),
            ],
            'list sources' => [
                'method' => 'GET',
                'route' => new Route('source_list'),
            ],
            'prepare source' => [
                'method' => 'POST',
                'route' => new Route('user_source_prepare', $sourceRouteParameters),
            ],
            'add file' => [
                'method' => 'POST',
                'route' => new Route('file_source_file_add', array_merge(
                    $sourceRouteParameters,
                    [
                        'filename' => 'filename.yaml',
                    ]
                )),
            ],
            'remove file' => [
                'method' => 'POST',
                'route' => new Route('file_source_file_remove', array_merge(
                    $sourceRouteParameters,
                    [
                        'filename' => 'filename.yaml',
                    ]
                )),
            ],
        ];
    }
}