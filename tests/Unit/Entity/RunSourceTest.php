<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\FileSource;
use App\Entity\RunSource;
use App\Entity\SourceInterface;
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

        $withoutParent = new RunSource($parent);
        $withoutParent->unsetParent();

        $withoutParentWithParameters = new RunSource($parent, $parameters);
        $withoutParentWithParameters->unsetParent();

        $withoutParameters = new RunSource($parent);
        $withParameters = new RunSource($parent, $parameters);

        return [
            'no parent, no parameters' => [
                'source' => $withoutParent,
                'expected' => [
                    'id' => $withoutParent->getId(),
                    'user_id' => $withoutParent->getUserId(),
                    'type' => SourceInterface::TYPE_RUN,
                    'parent' => null,
                    'parameters' => [],
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
                ],
            ],
        ];
    }
}
