<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\Source\Type;
use App\Model\DirectoryLocatorInterface;
use App\Model\SourceRepositoryInterface;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class FileSource extends AbstractSource implements
    SourceOriginInterface,
    DirectoryLocatorInterface,
    SourceRepositoryInterface,
    \JsonSerializable
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

    public function getType(): Type
    {
        return Type::FILE;
    }

    public function getRunParameterNames(): array
    {
        return [];
    }

    public function getRepositoryPath(): string
    {
        return $this->getDirectoryPath() . '/';
    }

    /**
     * @return array{
     *     "id": string,
     *     "user_id": non-empty-string,
     *     "type": 'file',
     *     "label": string
     * }
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->getUserId(),
            'type' => Type::FILE->value,
            'label' => $this->label
        ];
    }

    public function getDirectoryPath(): string
    {
        return sprintf(
            '%s/%s',
            $this->getUserId(),
            $this->getId(),
        );
    }
}
