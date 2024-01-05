<?php

declare(strict_types=1);

namespace App\Exception;

use App\ErrorResponse\ModifyReadOnlyEntityErrorInterface;

/**
 * @phpstan-import-type SerializedModifyReadOnlyEntityError from ModifyReadOnlyEntityErrorInterface
 */
class ModifyReadOnlyEntityException extends FooException
{
    public function __construct(ModifyReadOnlyEntityErrorInterface $error)
    {
        parent::__construct($error, 405);
    }
}
