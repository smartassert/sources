<?php

declare(strict_types=1);

namespace App\Exception;

use App\Request\LabelledObjectRequestInterface;
use App\Request\ObjectRequestInterface;

class DuplicateEntityLabelException extends \Exception
{
    public function __construct(
        public readonly LabelledObjectRequestInterface&ObjectRequestInterface $request,
    ) {
        parent::__construct();
    }
}
