<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\SourceRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\MappedSuperclass]
#[ORM\Table(name: 'source')]
#[ORM\Entity(repositoryClass: SourceRepository::class)]
#[ORM\InheritanceType('JOINED')]
#[ORM\DiscriminatorColumn(name: 'type', type: 'string', length: self::TYPE_DISCRIMINATOR_LENGTH)]
#[ORM\DiscriminatorMap([
    SourceTypeInterface::TYPE_GIT => GitSource::class,
    SourceTypeInterface::TYPE_FILE => FileSource::class,
])]
abstract class AbstractSource
{
    public const ID_LENGTH = 32;
    public const TYPE_DISCRIMINATOR_LENGTH = 32;

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: self::ID_LENGTH, unique: true)]
    protected string $id;

    #[ORM\Column(type: 'string', length: 32)]
    private string $userId;

    public function __construct(string $id, string $userId)
    {
        $this->id = $id;
        $this->userId = $userId;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }
}
