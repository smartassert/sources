<?php

declare(strict_types=1);

namespace App\Tests\Application;

abstract class AbstractHealthCheckTest extends AbstractApplicationTest
{
    public function testGetHealthCheck(): void
    {
        $response = $this->applicationClient->makeGetHealthCheckRequest();

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('application/json', $response->getHeaderLine('content-type'));
        self::assertJsonStringEqualsJsonString(
            (string) json_encode([
                'database_connection' => true,
                'database_entities' => true,
                'message_queue' => true,
            ]),
            $response->getBody()->getContents()
        );
    }
}
