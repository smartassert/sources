<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\SuiteRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SuiteRepository::class)]
class Suite implements \JsonSerializable
{
    public const ID_LENGTH = 32;

    /**
     * @var non-empty-string
     */
    #[ORM\Id]
    #[ORM\Column(type: Types::STRING, length: self::ID_LENGTH, unique: true)]
    private readonly string $id;

    /**
     * @var non-empty-string
     */
    #[ORM\Column(type: Types::STRING, length: self::ID_LENGTH)]
    private readonly string $userId;

    #[ORM\ManyToOne(targetEntity: AbstractSource::class, cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: false)]
    private SourceOriginInterface $source;

    /**
     * @var non-empty-string
     */
    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $label;

    /**
     * @var array<int, non-empty-string>
     */
    #[ORM\Column(type: Types::SIMPLE_ARRAY)]
    private array $tests;

    /**
     * @param non-empty-string             $id
     * @param non-empty-string             $id
     * @param non-empty-string             $userId
     * @param non-empty-string             $label
     * @param array<int, non-empty-string> $tests
     */
    public function __construct(
        string $id,
        string $userId,
        SourceOriginInterface $source,
        string $label,
        array $tests = []
    ) {
        $this->id = $id;
        $this->userId = $userId;
        $this->source = $source;
        $this->label = $label;
        $this->tests = $tests;
    }

    /**
     * @param non-empty-string $label
     */
    public function setLabel(string $label): self
    {
        $this->label = $label;

        return $this;
    }

    /**
     * @param non-empty-string[] $tests
     */
    public function setTests(array $tests): self
    {
        $this->tests = $tests;

        return $this;
    }

    /**
     * @return non-empty-string
     */
    public function getUserId(): string
    {
        return $this->userId;
    }

    /**
     * @return array{
     *     id: non-empty-string,
     *     source_id: non-empty-string,
     *     label: non-empty-string,
     *     tests: array<int, non-empty-string>
     * }
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'source_id' => $this->source->getId(),
            'label' => $this->label,
            'tests' => $this->tests,
        ];
    }
}
