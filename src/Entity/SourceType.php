<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=SourceTypeRepository::class)
 */
class SourceType
{
    public const TYPE_GIT = 'git';

    public const ALL = [
        self::TYPE_GIT,
    ];

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private ?int $id;

    /**
     * @var SourceType::TYPE_*
     *
     * @ORM\Column
     */
    private string $name;

    /**
     * @param SourceType::TYPE_* $name
     */
    public function __construct(string $name)
    {
        $this->id = null;
        $this->name = $name;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return SourceType::TYPE_*
     */
    public function getName(): string
    {
        return $this->name;
    }
}
