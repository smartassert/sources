<?php

declare(strict_types=1);

namespace App\Request;

use App\Enum\Source\Type;
use Symfony\Component\HttpFoundation\Request;

class FileSourceRequest implements SourceRequestInterface
{
    public const PARAMETER_LABEL = 'label';

    private string $label;

    public function __construct(Request $request)
    {
        $payload = $request->request;

        $this->label = trim((string) $payload->get(self::PARAMETER_LABEL));
    }

    public function getType(): string
    {
        return Type::FILE->value;
    }

    public function getLabel(): string
    {
        return $this->label;
    }
}
