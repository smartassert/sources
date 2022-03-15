<?php

declare(strict_types=1);

namespace App\Request;

use App\Validator\YamlFileConstraint;
use SmartAssert\YamlFile\YamlFile;
use Symfony\Component\HttpFoundation\Request;
use webignition\EncapsulatingRequestResolverBundle\Model\EncapsulatingRequestInterface;

class AddYamlFileRequest extends AbstractYamlFileRequest implements EncapsulatingRequestInterface
{
    public function __construct(
        #[YamlFileConstraint]
        private YamlFile $file,
    ) {
        parent::__construct($file->name);
    }

    public static function create(Request $request): self
    {
        return new self(
            new YamlFile(
                self::createFilenameFromRequest($request),
                trim($request->getContent())
            )
        );
    }

    public function getYamlFile(): YamlFile
    {
        return $this->file;
    }
}
