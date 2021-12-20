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

    /**
     * @var array<string, string>
     */
    #[ORM\Column(type: 'simple_array', nullable: true)]
    private array $parameters = [];

    /**
     * @param array<string, string> $parameters
     */
    public function __construct(
        string $id,
        string $userId,
        FileSource|GitSource $parent,
        array $parameters = []
    ) {
        parent::__construct($id, $userId);

        $this->parent = $parent;
        $this->parameters = $parameters;
    }

    public function getParent(): FileSource|GitSource
    {
        return $this->parent;
    }

    /**
     * @return array<string, string>
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * @return array{
     *     "id": string,
     *     "user_id": string,
     *     "type": SourceInterface::TYPE_RUN,
     *     "parent": string,
     *     "parameters": array<string, string>
     * }
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->getUserId(),
            'type' => SourceInterface::TYPE_RUN,
            'parent' => $this->parent->getId(),
            'parameters' => $this->parameters,
        ];
    }
}
