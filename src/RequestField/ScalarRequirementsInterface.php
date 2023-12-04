<?php

declare(strict_types=1);

namespace App\RequestField;

use App\ErrorResponse\SizeInterface;

interface ScalarRequirementsInterface extends RequirementsInterface
{
    /**
     * @return 'bool'|'float'|'int'|'string'
     */
    public function getDataType(): string;

    public function getSize(): ?SizeInterface;
}