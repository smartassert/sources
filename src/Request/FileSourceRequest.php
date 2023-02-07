<?php

declare(strict_types=1);

namespace App\Request;

use App\Entity\FileSource;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;

class FileSourceRequest
{
    public const PARAMETER_LABEL = 'label';

    #[Assert\Length(null, 1, FileSource::LABEL_MAX_LENGTH)]
    private string $label;

    public function __construct(Request $request)
    {
        $payload = $request->request;

        $this->label = trim((string) $payload->get(self::PARAMETER_LABEL));
    }

    public function getLabel(): string
    {
        return $this->label;
    }
}
