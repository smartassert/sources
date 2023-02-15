<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\FileSource;
use App\Entity\RunSource;
use App\Enum\RunSource\FailureReason;
use App\Enum\RunSource\State;
use App\Enum\Source\Type;
use App\Services\EntityIdFactory;
use App\Tests\Model\UserId;
use PHPUnit\Framework\TestCase;

class RunSourceTest extends TestCase
{
    /**
     * @dataProvider jsonSerializeDataProvider
     *
     * @param array<mixed> $expected
     */
    public function testJsonSerialize(RunSource $source, array $expected): void
    {
        self::assertSame($expected, $source->jsonSerialize());
    }

    /**
     * @return array<mixed>
     */
    public function jsonSerializeDataProvider(): array
    {
        $idFactory = new EntityIdFactory();

        $userId = UserId::create();
        $parameters = [
            'key1' => 'value1',
            'key2' => 'value2',
        ];

        $parent = new FileSource($idFactory->create(), $userId, 'file source label');

        $withoutParameters = new RunSource($idFactory->create(), $parent);
        $withParameters = new RunSource($idFactory->create(), $parent, $parameters);

        $withNonDefaultState = (new RunSource($idFactory->create(), $parent))->setState(State::PREPARED);

        $failureMessage = 'fatal: repository \'http://example.com/repository.git\' not found';
        $hasPreparationFailed = (new RunSource($idFactory->create(), $parent))->setPreparationFailed(
            FailureReason::GIT_CLONE,
            $failureMessage
        );

        return [
            'has parent, no parameters' => [
                'source' => $withoutParameters,
                'expected' => [
                    'id' => $withoutParameters->getId(),
                    'user_id' => $withoutParameters->getUserId(),
                    'type' => Type::RUN->value,
                    'parent' => $withoutParameters->getParent()->getId(),
                    'parameters' => [],
                    'state' => State::REQUESTED->value,
                ],
            ],
            'has parent, has parameters' => [
                'source' => $withParameters,
                'expected' => [
                    'id' => $withParameters->getId(),
                    'user_id' => $withParameters->getUserId(),
                    'type' => Type::RUN->value,
                    'parent' => $withParameters->getParent()->getId(),
                    'parameters' => $parameters,
                    'state' => State::REQUESTED->value,
                ],
            ],
            'non-default state' => [
                'source' => $withNonDefaultState,
                'expected' => [
                    'id' => $withNonDefaultState->getId(),
                    'user_id' => $withNonDefaultState->getUserId(),
                    'type' => Type::RUN->value,
                    'parent' => $withParameters->getParent()->getId(),
                    'parameters' => [],
                    'state' => State::PREPARED->value,
                ],
            ],
            'preparation failed' => [
                'source' => $hasPreparationFailed,
                'expected' => [
                    'id' => $hasPreparationFailed->getId(),
                    'user_id' => $hasPreparationFailed->getUserId(),
                    'type' => Type::RUN->value,
                    'parent' => $withParameters->getParent()->getId(),
                    'parameters' => [],
                    'state' => State::FAILED->value,
                    'failure_reason' => FailureReason::GIT_CLONE->value,
                    'failure_message' => $failureMessage,
                ],
            ],
        ];
    }
}
