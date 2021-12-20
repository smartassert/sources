<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class RunSource extends AbstractSource implements \JsonSerializable
{
    #[ORM\OneToOne(targetEntity: AbstractSource::class, cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private FileSource|GitSource $parent;

    public function __construct(string $id, string $userId, FileSource|GitSource $parent)
    {
        parent::__construct($id, $userId);

        $this->parent = $parent;
    }

    public function getParent(): FileSource|GitSource
    {
        return $this->parent;
    }

    /**
     * @return array{
     *     "id": string,
     *     "user_id": string,
     *     "type": SourceTypeInterface::TYPE_RUN,
     *     "parent": string
     * }
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->getUserId(),
            'type' => SourceTypeInterface::TYPE_RUN,
            'parent' => $this->parent->getId(),
        ];
    }
}
