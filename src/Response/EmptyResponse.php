<?php

declare(strict_types=1);

namespace App\Response;

use Symfony\Component\HttpFoundation\Response;

class EmptyResponse extends Response
{
    public function __construct(int $statusCode = 200)
    {
        parent::__construct(null, $statusCode, ['content-type' => null]);
    }
}
