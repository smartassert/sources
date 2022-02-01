<?php

declare(strict_types=1);

namespace App\Entity;

use App\Model\FileLocatorInterface;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class RunSource extends AbstractSource implements FileLocatorInterface, \JsonSerializable
{
    use UserSourceFileLocatorTrait;

    #[ORM\ManyToOne(targetEntity: AbstractSource::class, cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: true)]
    private FileSource|GitSource|null $parent;

    /**
     * @var array<string, string>
     */
    #[ORM\Column(type: 'simple_array', nullable: true)]
    private array $parameters;

    /**
     * @param array<string, string> $parameters
     */
    public function __construct(FileSource|GitSource $parent, array $parameters = [])
    {
        parent::__construct($parent->getUserId());

        $this->parent = $parent;
        $this->parameters = $parameters;
        ksort($this->parameters);
    }

    public function getParent(): FileSource|GitSource|null
    {
        return $this->parent;
    }

    public function unsetParent(): void
    {
        $this->parent = null;
    }

    /**
     * @return array<string, string>
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * @return SourceInterface::TYPE_RUN
     */
    public function getType(): string
    {
        return SourceInterface::TYPE_RUN;
    }

    /**
     * @return array{
     *     "id": string,
     *     "user_id": string,
     *     "type": SourceInterface::TYPE_RUN,
     *     "parent": string|null,
     *     "parameters": array<string, string>
     * }
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->getUserId(),
            'type' => $this->getType(),
            'parent' => $this->parent?->getId(),
            'parameters' => $this->parameters,
        ];
    }
}
