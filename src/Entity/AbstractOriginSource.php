<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\MappedSuperclass]
abstract class AbstractOriginSource extends AbstractSource implements \JsonSerializable
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
     */
    public function __construct(string $id, string $userId)
    {
        parent::__construct($id, $userId);
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
     *     "label": non-empty-string
     * }
     */
    public function jsonSerialize(): array
    {
        return array_merge(parent::jsonSerialize(), ['label' => $this->getLabel()]);
    }
}
