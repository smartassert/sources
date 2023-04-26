<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Enum\SerializedSuite\State;

trait WaitUntilSerializedSuiteStateTrait
{
    private function waitUntilSuiteStateIs(
        string $serializedSuiteId,
        State $expectedState
    ): void {
        $timeout = 30000;
        $duration = 0;
        $period = 1000;
        $state = null;

        while ($expectedState->value !== $state) {
            $getResponse = $this->applicationClient->makeGetSerializedSuiteRequest(
                self::$apiTokens->get(self::USER_1_EMAIL),
                $serializedSuiteId
            );

            if (200 === $getResponse->getStatusCode()) {
                $responseData = json_decode($getResponse->getBody()->getContents(), true);

                if (is_array($responseData)) {
                    $state = $responseData['state'] ?? null;
                }

                if ($expectedState->value !== $state) {
                    $duration += $period;

                    if ($duration >= $timeout) {
                        throw new \RuntimeException('Timed out waiting for "' . $serializedSuiteId . '" to prepare');
                    }

                    usleep($period);
                }
            }
        }
    }
}
