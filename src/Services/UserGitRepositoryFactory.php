<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\GitSource;
use App\Model\UserGitRepository;

class UserGitRepositoryFactory
{
    public function create(GitSource $source): UserGitRepository
    {
        return new UserGitRepository($source);
    }
}
