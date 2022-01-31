<?php

declare(strict_types=1);

namespace App\Entity;

use App\Model\SourceOriginInterface;
use App\Model\UserFileLocatorInterface;
use App\Model\UserSourceFileLocatorTrait;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class FileSource extends AbstractSource implements UserFileLocatorInterface, \JsonSerializable, SourceOriginInterface
{
    use UserSourceFileLocatorTrait;

    #[ORM\Column(type: 'string', length: 255)]
    private string $label;

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

    /**
     * @return SourceInterface::TYPE_FILE
     */
    public function getType(): string
    {
        return SourceInterface::TYPE_FILE;
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
            'type' => $this->getType(),
            'label' => $this->label
        ];
    }
}
