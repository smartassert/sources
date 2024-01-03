<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\EntityType;
use App\Repository\SuiteRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SuiteRepository::class)]
class Suite implements \JsonSerializable, UserHeldEntityInterface, EntityIdentifierInterface
{
    public const ID_LENGTH = 32;
    public const LABEL_MAX_LENGTH = 255;

    /**
     * @var non-empty-string
     */
    #[ORM\Id]
    #[ORM\Column(type: Types::STRING, length: self::ID_LENGTH, unique: true)]
    public readonly string $id;

    #[ORM\ManyToOne(targetEntity: AbstractSource::class, cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: false)]
    private SourceInterface $source;

    /**
     * @var non-empty-string
     */
    #[ORM\Column(type: Types::STRING, length: self::LABEL_MAX_LENGTH)]
    private string $label;

    /**
     * @var array<int, non-empty-string>
     */
    #[ORM\Column(type: Types::SIMPLE_ARRAY, nullable: true)]
    private array $tests;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $deletedAt = null;

    /**
     * @param non-empty-string $id
     */
    public function __construct(string $id)
    {
        $this->id = $id;
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

    public function getUserId(): string
    {
        return $this->source->getUserId();
    }

    public function getDeletedAt(): ?\DateTimeImmutable
    {
        return $this->deletedAt;
    }

    public function setDeletedAt(\DateTimeImmutable $deletedAt): void
    {
        if (null === $this->deletedAt) {
            $this->deletedAt = $deletedAt;
        }
    }

    public function setSource(SourceInterface $source): void
    {
        $this->source = $source;
    }

    public function getSource(): SourceInterface
    {
        return $this->source;
    }

    /**
     * @return array<int, non-empty-string>
     */
    public function getTests(): array
    {
        return $this->tests;
    }

    /**
     * @return array{
     *     id: non-empty-string,
     *     source_id: non-empty-string,
     *     label: non-empty-string,
     *     tests: array<int, non-empty-string>,
     *     deleted_at?: positive-int
     * }
     */
    public function jsonSerialize(): array
    {
        $data = [
            'id' => $this->id,
            'source_id' => $this->source->getId(),
            'label' => $this->label,
            'tests' => $this->tests,
        ];

        if ($this->getDeletedAt() instanceof \DateTimeInterface) {
            $deletedAtTimestamp = (int) $this->getDeletedAt()->format('U');

            if ($deletedAtTimestamp >= 1) {
                $data['deleted_at'] = $deletedAtTimestamp;
            }
        }

        return $data;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getEntityType(): string
    {
        return EntityType::SUITE->value;
    }
}
