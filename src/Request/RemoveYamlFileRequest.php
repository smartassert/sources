<?php

declare(strict_types=1);

namespace App\Request;

use Symfony\Component\HttpFoundation\Request;
use webignition\EncapsulatingRequestResolverBundle\Model\EncapsulatingRequestInterface;

class RemoveYamlFileRequest extends AbstractYamlFileRequest implements EncapsulatingRequestInterface
{
    public static function create(Request $request): self
    {
        return new self(self::createFilenameFromRequest($request));
    }
}
