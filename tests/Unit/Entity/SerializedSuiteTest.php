<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\SerializedSuite;
use App\Entity\Suite;
use App\Enum\SerializedSuite\FailureReason;
use App\Enum\SerializedSuite\State;
use PHPUnit\Framework\TestCase;

class SerializedSuiteTest extends TestCase
{
    public function testSetPreparationFailedIsIdempotent(): void
    {
        $serializedSuite = new SerializedSuite(
            md5((string) rand()),
            new Suite(md5((string) rand())),
            []
        );

        self::assertSame(State::REQUESTED, $serializedSuite->getState());

        $serializedSuiteData = $serializedSuite->jsonSerialize();
        self::assertArrayNotHasKey('failure_reason', $serializedSuiteData);
        self::assertArrayNotHasKey('failure_message', $serializedSuiteData);

        $failureReason = FailureReason::GIT_CHECKOUT;
        $failureMessage = md5((string) rand());

        $serializedSuite->setPreparationFailed($failureReason, $failureMessage);

        self::assertSame(State::FAILED, $serializedSuite->getState());
        $serializedSuiteData = $serializedSuite->jsonSerialize();
        self::assertSame($failureReason->value, $serializedSuiteData['failure_reason'] ?? null);
        self::assertSame($failureMessage, $serializedSuiteData['failure_message'] ?? null);

        $serializedSuite->setPreparationFailed(FailureReason::GIT_CLONE, $failureMessage);

        self::assertSame(State::FAILED, $serializedSuite->getState());
        $serializedSuiteData = $serializedSuite->jsonSerialize();
        self::assertSame($failureReason->value, $serializedSuiteData['failure_reason'] ?? null);
        self::assertSame($failureMessage, $serializedSuiteData['failure_message'] ?? null);
    }
}
