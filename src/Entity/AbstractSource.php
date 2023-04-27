<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\SourceRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\MappedSuperclass]
#[ORM\Table(name: 'source')]
#[ORM\Entity(repositoryClass: SourceRepository::class)]
#[ORM\InheritanceType('JOINED')]
#[ORM\DiscriminatorColumn(name: 'type', type: 'string', length: self::TYPE_DISCRIMINATOR_LENGTH)]
#[ORM\DiscriminatorMap([
    'git' => GitSource::class,
    'file' => FileSource::class,
])]
abstract class AbstractSource implements SourceInterface, \JsonSerializable
{
    public const ID_LENGTH = 32;
    public const TYPE_DISCRIMINATOR_LENGTH = 32;
    public const LABEL_MAX_LENGTH = 255;

    /**
     * @var non-empty-string
     */
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: self::ID_LENGTH, unique: true)]
    protected string $id;

    /**
     * @var non-empty-string
     */
    #[ORM\Column(type: 'string', length: 32)]
    private string $userId;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $deletedAt = null;

    /**
     * @var non-empty-string
     */
    #[ORM\Column(type: 'string', length: self::LABEL_MAX_LENGTH)]
    private string $label;

    /**
     * @param non-empty-string $id
     * @param non-empty-string $userId
     */
    public function __construct(string $id, string $userId)
    {
        $this->id = $id;
        $this->userId = $userId;
    }

    /**
     * @return non-empty-string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return non-empty-string
     */
    public function getUserId(): string
    {
        return $this->userId;
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

    /**
     * @return non-empty-string
     */
    public function getLabel(): string
    {
        return $this->label;
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
     * @return array{
     *     "id": string,
     *     "user_id": non-empty-string,
     *     "type": non-empty-string,
     *     "label": non-empty-string,
     *     "deleted_at"?: positive-int
     * }
     */
    public function jsonSerialize(): array
    {
        $data = [
            'id' => $this->id,
            'user_id' => $this->getUserId(),
            'type' => $this->getType()->value,
            'label' => $this->getLabel(),
        ];

        if ($this->getDeletedAt() instanceof \DateTimeInterface) {
            $deletedAtTimestamp = (int) $this->getDeletedAt()->format('U');

            if ($deletedAtTimestamp >= 1) {
                $data['deleted_at'] = $deletedAtTimestamp;
            }
        }

        return $data;
    }
}
