<?php

declare(strict_types=1);

namespace App\ResponseBody;

use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperationFailed;

class FilesystemExceptionResponse implements ErrorInterface
{
    public function __construct(
        private FilesystemException $exception
    ) {
    }

    public function getType(): string
    {
        $type = 'unknown';
        if ($this->exception instanceof FilesystemOperationFailed) {
            $type = strtolower($this->exception->operation());
        }

        return sprintf('source_%s_exception', $type);
    }

    public function getPayload(): array
    {
        $path = '';
        if (method_exists($this->exception, 'location')) {
            $path = $this->exception->location();
        }

        return [
            'file' => $path,
            'message' => $this->exception->getMessage(),
        ];
    }
}
