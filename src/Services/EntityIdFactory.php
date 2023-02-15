<?php

declare(strict_types=1);

namespace App\Services;

use App\Exception\EmptyEntityIdException;
use Symfony\Component\Uid\Ulid;

class EntityIdFactory
{
    /**
     * @return non-empty-string
     *
     * @throws EmptyEntityIdException
     */
    public function create(): string
    {
        $id = (string) new Ulid();
        if ('' === $id) {
            throw new EmptyEntityIdException();
        }

        return $id;
    }
}
