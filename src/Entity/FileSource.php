<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\EntityType;
use App\Enum\Source\Type;
use App\Model\DirectoryLocatorInterface;
use App\Model\SourceRepositoryInterface;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class FileSource extends AbstractSource implements DirectoryLocatorInterface, SourceRepositoryInterface
{
    public function getRunParameterNames(): array
    {
        return [];
    }

    public function getRepositoryPath(): string
    {
        return $this->getDirectoryPath() . '/';
    }

    public function getDirectoryPath(): string
    {
        return sprintf(
            '%s/%s',
            $this->getUserId(),
            $this->getId(),
        );
    }

    public function getType(): Type
    {
        return Type::FILE;
    }

    public function getEntityType(): EntityType
    {
        return EntityType::FILE_SOURCE;
    }
}
