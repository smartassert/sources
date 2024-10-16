<?php

declare(strict_types=1);

namespace App\Tests\Application;

abstract class AbstractStatusTest extends AbstractApplicationTest
{
    public function testGetStatus(): void
    {
        $response = $this->applicationClient->makeGetStatusRequest();

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('application/json', $response->getHeaderLine('content-type'));
        self::assertJsonStringEqualsJsonString(
            (string) json_encode([
                'ready' => $this->getExpectedReadyValue(),
            ]),
            $response->getBody()->getContents()
        );
    }

    abstract protected function getExpectedReadyValue(): bool;
}
