<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Model\EntityId;
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
     *
     * @param array<string, int|string> $routeParameters
     */
    public function testRequestForUnauthorizedUser(string $method, string $routeName, array $routeParameters): void
    {
        $this->setUserServiceUnauthorizedResponse();

        $url = $this->generateUrl($routeName, $routeParameters);

        $response = $this->applicationClient->makeAuthorizedRequest($method, $url);

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
                'routeName' => 'source_create',
                'routeParameters' => [],
            ],
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
            'list sources' => [
                'method' => 'GET',
                'routeName' => 'source_list',
                'routeParameters' => [],
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
