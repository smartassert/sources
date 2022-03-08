<?php

declare(strict_types=1);

namespace App\Tests\Application;

abstract class AbstractHealthCheckTest extends AbstractApplicationTest
{
    public function testGetHealthCheck(): void
    {
        $response = $this->applicationClient->makeGetHealthCheckRequest();

        $this->responseAsserter->assertSuccessfulJsonResponse(
            $response,
            [
                'database_connection' => true,
                'database_entities' => true,
                'message_queue' => true,
            ]
        );
    }
}
