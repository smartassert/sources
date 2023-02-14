<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\MappedSuperclass]
abstract class AbstractOriginSource extends AbstractSource
{
    public const LABEL_MAX_LENGTH = 255;

    #[ORM\Column(type: 'string', length: self::LABEL_MAX_LENGTH)]
    private string $label;

    /**
     * @param non-empty-string $userId
     */
    public function __construct(string $userId, string $label)
    {
        parent::__construct($userId);

        $this->label = $label;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setLabel(string $label): self
    {
        $this->label = $label;

        return $this;
    }
}
