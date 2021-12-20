<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\SourceRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\MappedSuperclass]
#[ORM\Table(name: 'source')]
#[ORM\Entity(repositoryClass: SourceRepository::class)]
#[ORM\InheritanceType('JOINED')]
#[ORM\DiscriminatorColumn(name: 'type', type: 'string')]
#[ORM\DiscriminatorMap([
    SourceType::TYPE_GIT => GitSource::class,
])]
abstract class AbstractSource
{
    public const ID_LENGTH = 32;

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: self::ID_LENGTH, unique: true)]
    protected string $id;

    #[ORM\Column(type: 'string', length: 32)]
    private string $userId;

    #[ORM\ManyToOne(targetEntity: SourceType::class)]
    #[ORM\JoinColumn(nullable: false)]
    private SourceType $type;

    public function __construct(string $id, string $userId, SourceType $type)
    {
        $this->id = $id;
        $this->userId = $userId;
        $this->type = $type;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getType(): SourceType
    {
        return $this->type;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }
}
