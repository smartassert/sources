<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\SourceRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\MappedSuperclass]
#[ORM\Table(name: 'source')]
#[ORM\Entity(repositoryClass: SourceRepository::class)]
#[ORM\InheritanceType('JOINED')]
#[ORM\DiscriminatorColumn(name: 'type_discriminator', type: 'string', length: self::TYPE_DISCRIMINATOR_LENGTH)]
#[ORM\DiscriminatorMap([
    SourceTypeInterface::TYPE_GIT => GitSource::class,
])]
#[ORM\Index(columns: ['type'], name: 'type_idx')]
abstract class AbstractSource
{
    public const ID_LENGTH = 32;
    public const TYPE_DISCRIMINATOR_LENGTH = 32;

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: self::ID_LENGTH, unique: true)]
    protected string $id;

    #[ORM\Column(type: 'string', length: 32)]
    private string $userId;

    /**
     * @var SourceTypeInterface::TYPE_*
     */
    #[ORM\Column(name: 'type', type: 'string', length: self::TYPE_DISCRIMINATOR_LENGTH)]
    private string $type;

    /**
     * @param SourceTypeInterface::TYPE_* $type
     */
    public function __construct(string $id, string $userId, string $type)
    {
        $this->id = $id;
        $this->userId = $userId;
        $this->type = $type;
    }

    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return SourceTypeInterface::TYPE_* $type
     */
    public function getType(): string
    {
        return $this->type;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }
}
