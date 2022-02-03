<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\FileSource;
use App\Entity\RunSource;
use App\Entity\SourceInterface;
use App\Enum\RunSource\FailureReason;
use App\Enum\RunSource\State;
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
        $userId = UserId::create();
        $parameters = [
            'key1' => 'value1',
            'key2' => 'value2',
        ];

        $parent = new FileSource($userId, 'file source label');

        $withoutParent = (new RunSource($parent))->unsetParent();

        $withoutParentWithParameters = (new RunSource($parent, $parameters))->unsetParent();

        $withoutParameters = new RunSource($parent);
        $withParameters = new RunSource($parent, $parameters);

        $withNonDefaultState = (new RunSource($parent))->setState(State::PREPARED);

        $failureMessage = 'fatal: repository \'http://example.com/repository.git\' not found';
        $hasPreparationFailed = (new RunSource($parent))->setPreparationFailed(
            FailureReason::GIT_CLONE,
            $failureMessage
        );

        return [
            'no parent, no parameters' => [
                'source' => $withoutParent,
                'expected' => [
                    'id' => $withoutParent->getId(),
                    'user_id' => $withoutParent->getUserId(),
                    'type' => SourceInterface::TYPE_RUN,
                    'parent' => null,
                    'parameters' => [],
                    'state' => State::REQUESTED->value,
                ],
            ],
            'no parent, has parameters' => [
                'source' => $withoutParentWithParameters,
                'expected' => [
                    'id' => $withoutParentWithParameters->getId(),
                    'user_id' => $withoutParentWithParameters->getUserId(),
                    'type' => SourceInterface::TYPE_RUN,
                    'parent' => null,
                    'parameters' => $withoutParentWithParameters->getParameters(),
                    'state' => State::REQUESTED->value,
                ],
            ],
            'has parent, no parameters' => [
                'source' => $withoutParameters,
                'expected' => [
                    'id' => $withoutParameters->getId(),
                    'user_id' => $withoutParameters->getUserId(),
                    'type' => SourceInterface::TYPE_RUN,
                    'parent' => $withoutParameters->getParent()?->getId(),
                    'parameters' => [],
                    'state' => State::REQUESTED->value,
                ],
            ],
            'has parent, has parameters' => [
                'source' => $withParameters,
                'expected' => [
                    'id' => $withParameters->getId(),
                    'user_id' => $withParameters->getUserId(),
                    'type' => SourceInterface::TYPE_RUN,
                    'parent' => $withParameters->getParent()?->getId(),
                    'parameters' => $parameters,
                    'state' => State::REQUESTED->value,
                ],
            ],
            'non-default state' => [
                'source' => $withNonDefaultState,
                'expected' => [
                    'id' => $withNonDefaultState->getId(),
                    'user_id' => $withNonDefaultState->getUserId(),
                    'type' => SourceInterface::TYPE_RUN,
                    'parent' => $withParameters->getParent()?->getId(),
                    'parameters' => [],
                    'state' => State::PREPARED->value,
                ],
            ],
            'preparation failed' => [
                'source' => $hasPreparationFailed,
                'expected' => [
                    'id' => $hasPreparationFailed->getId(),
                    'user_id' => $hasPreparationFailed->getUserId(),
                    'type' => SourceInterface::TYPE_RUN,
                    'parent' => $withParameters->getParent()?->getId(),
                    'parameters' => [],
                    'state' => State::FAILED->value,
                    'failure_reason' => FailureReason::GIT_CLONE->value,
                    'failure_message' => $failureMessage,
                ],
            ],
        ];
    }

    public function testGetPathEqualsToString(): void
    {
        $userId = UserId::create();
        $source = new RunSource(
            new FileSource($userId, 'file source label')
        );
        $expectedPath = sprintf('%s/%s', $userId, $source->getId());

        self::assertSame($expectedPath, $source->getPath());
        self::assertSame($expectedPath, (string) $source);
        self::assertSame($source->getPath(), (string) $source);
    }
}
