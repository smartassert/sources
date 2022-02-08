<?php

declare(strict_types=1);

namespace App\Response;

use App\Exception\File\ReadException;

class SourceReadExceptionResponse implements ErrorResponseInterface
{
    public function __construct(
        private ReadException $exception
    ) {
    }

    public function getType(): string
    {
        return 'source_read_exception';
    }

    public function getPayload(): array
    {
        return [
            'file' => $this->exception->getPath(),
            'message' => $this->exception->getMessage(),
        ];
    }
}
