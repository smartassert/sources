<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\FileSource;
use App\Entity\RunSource;
use App\Entity\SourceInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Ulid;

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
        $parentSourceId = (string) new Ulid();
        $runSourceId = (string) new Ulid();
        $userId = (string) new Ulid();
        $parameters = [
            'key1' => 'value1',
            'key2' => 'value2',
        ];

        $parent = new FileSource($parentSourceId, $userId, 'file source label');

        return [
            'no parent, no parameters' => [
                'source' => (function () use ($runSourceId, $parent) {
                    $source = new RunSource($runSourceId, $parent);
                    $source->unsetParent();

                    return $source;
                })(),
                'expected' => [
                    'id' => $runSourceId,
                    'user_id' => $userId,
                    'type' => SourceInterface::TYPE_RUN,
                    'parent' => null,
                    'parameters' => [],
                ],
            ],
            'no parent, has parameters' => [
                'source' => (function () use ($runSourceId, $parent, $parameters) {
                    $source = new RunSource($runSourceId, $parent, $parameters);
                    $source->unsetParent();

                    return $source;
                })(),
                'expected' => [
                    'id' => $runSourceId,
                    'user_id' => $userId,
                    'type' => SourceInterface::TYPE_RUN,
                    'parent' => null,
                    'parameters' => $parameters,
                ],
            ],
            'has parent, no parameters' => [
                'source' => new RunSource($runSourceId, $parent),
                'expected' => [
                    'id' => $runSourceId,
                    'user_id' => $userId,
                    'type' => SourceInterface::TYPE_RUN,
                    'parent' => $parentSourceId,
                    'parameters' => [],
                ],
            ],
            'has parent, has parameters' => [
                'source' => new RunSource($runSourceId, $parent, $parameters),
                'expected' => [
                    'id' => $runSourceId,
                    'user_id' => $userId,
                    'type' => SourceInterface::TYPE_RUN,
                    'parent' => $parentSourceId,
                    'parameters' => $parameters,
                ],
            ],
        ];
    }

    public function testGetPathEqualsToString(): void
    {
        $id = (string) new Ulid();
        $userId = (string) new Ulid();

        $source = new RunSource(
            $id,
            new FileSource((string) new Ulid(), $userId, 'file source label')
        );

        $expectedPath = sprintf('%s/%s', $userId, $id);

        self::assertSame($expectedPath, $source->getPath());
        self::assertSame($expectedPath, (string) $source);
        self::assertSame($source->getPath(), (string) $source);
    }
}
