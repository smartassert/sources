<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\MappedSuperclass]
abstract class AbstractOriginSource extends AbstractSource
{
    public const LABEL_MAX_LENGTH = 255;

    /**
     * @var non-empty-string
     */
    #[ORM\Column(type: 'string', length: self::LABEL_MAX_LENGTH)]
    private string $label;

    /**
     * @param non-empty-string $id
     * @param non-empty-string $userId
     * @param non-empty-string $label
     */
    public function __construct(string $id, string $userId, string $label)
    {
        parent::__construct($id, $userId);

        $this->label = $label;
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
}
