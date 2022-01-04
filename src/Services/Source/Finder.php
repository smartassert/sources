<?php

declare(strict_types=1);

namespace App\Services\Source;

use App\Entity\SourceInterface;

class Finder
{
    /**
     * @var TypeFinderInterface[]
     */
    private array $typeFinders;

    /**
     * @param TypeFinderInterface[] $typeFinders
     */
    public function __construct(array $typeFinders)
    {
        $this->typeFinders = array_filter($typeFinders, function ($item) {
            return $item instanceof TypeFinderInterface;
        });
    }

    public function find(SourceInterface $source): ?SourceInterface
    {
        foreach ($this->typeFinders as $typeFinder) {
            if ($typeFinder->supports($source->getType())) {
                return $typeFinder->find($source);
            }
        }

        return null;
    }
}
