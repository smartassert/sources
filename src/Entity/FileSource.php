<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\Source\Type;
use App\Model\DirectoryLocatorInterface;
use App\Model\SourceRepositoryInterface;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class FileSource extends AbstractOriginSource implements DirectoryLocatorInterface, SourceRepositoryInterface
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

    protected function getType(): Type
    {
        return Type::FILE;
    }
}
