<?php

declare(strict_types=1);

namespace App\ResponseBody;

use App\Exception\Storage\StorageExceptionInterface;

class FileExceptionResponse implements ErrorInterface
{
    public function __construct(
        private StorageExceptionInterface $exception
    ) {
    }

    public function getType(): string
    {
        return sprintf('source_%s_exception', $this->exception->getAction());
    }

    public function getPayload(): array
    {
        return [
            'file' => $this->exception->getPath(),
            'message' => $this->exception->getMessage(),
        ];
    }
}
