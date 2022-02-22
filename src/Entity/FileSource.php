<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\Source\Type as SourceType;
use App\Model\SerializableSourceInterface;
use App\Model\UserFileLocatorInterface;
use App\Model\UserSourceFileLocatorTrait;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class FileSource extends AbstractSource implements
    OriginSourceInterface,
    UserFileLocatorInterface,
    SerializableSourceInterface,
    \JsonSerializable
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

    public function getType(): SourceType
    {
        return SourceType::FILE;
    }

    public function getRunParameterNames(): array
    {
        return [];
    }

    public function getSerializableSourcePath(): string
    {
        return '/';
    }

    /**
     * @return array{
     *     "id": string,
     *     "user_id": string,
     *     "type": 'file',
     *     "label": string
     * }
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->getUserId(),
            'type' => SourceType::FILE->value,
            'label' => $this->label
        ];
    }
}
