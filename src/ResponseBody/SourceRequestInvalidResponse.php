<?php

declare(strict_types=1);

namespace App\ResponseBody;

use App\Request\SourceRequestInterface;

class SourceRequestInvalidResponse implements ErrorInterface
{
    public function __construct(
        private SourceRequestInterface $request
    ) {
    }

    public function getType(): string
    {
        return 'invalid_source_request';
    }

    public function getPayload(): array
    {
        return [
            'source_type' => $this->request->getType(),
            'missing_required_fields' => $this->request->getMissingRequiredFields(),
        ];
    }
}
