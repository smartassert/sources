<?php

declare(strict_types=1);

namespace App\Request;

use Symfony\Component\HttpFoundation\Request;
use webignition\EncapsulatingRequestResolverBundle\Model\EncapsulatingRequestInterface;

class FileSourceRequest implements EncapsulatingRequestInterface
{
    public const KEY_POST_LABEL = 'label';

    public function __construct(
        private string $label,
    ) {
    }

    public static function create(Request $request): FileSourceRequest
    {
        return new FileSourceRequest((string) $request->request->get(self::KEY_POST_LABEL));
    }

    public function getLabel(): string
    {
        return $this->label;
    }
}
