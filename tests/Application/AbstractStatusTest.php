<?php

declare(strict_types=1);

namespace App\Tests\Application;

abstract class AbstractStatusTest extends AbstractApplicationTest
{
    public function testGetStatus(): void
    {
        $response = $this->applicationClient->makeGetStatusRequest();

        $this->responseAsserter->assertSuccessfulJsonResponse(
            $response,
            [
                'idle' => true,
                'ready' => $this->getExpectedReadyValue(),
            ]
        );
    }

    abstract protected function getExpectedReadyValue(): bool;
}
