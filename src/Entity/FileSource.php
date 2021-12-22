<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class FileSource extends AbstractSource implements \JsonSerializable
{
    #[ORM\Column(type: 'string', length: 255)]
    private string $label;

    public function __construct(
        string $id,
        string $userId,
        string $label,
    ) {
        parent::__construct($id, $userId);

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

    /**
     * @return array{
     *     "id": string,
     *     "user_id": string,
     *     "type": SourceInterface::TYPE_FILE,
     *     "label": string
     * }
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->getUserId(),
            'type' => SourceInterface::TYPE_FILE,
            'label' => $this->label
        ];
    }
}
