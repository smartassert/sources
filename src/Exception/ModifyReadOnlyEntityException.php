<?php

declare(strict_types=1);

namespace App\Exception;

use App\ErrorResponse\ModifyReadOnlyEntityErrorInterface;

class ModifyReadOnlyEntityException extends ErrorResponseException
{
    public function __construct(ModifyReadOnlyEntityErrorInterface $error)
    {
        parent::__construct($error, 405);
    }
}
