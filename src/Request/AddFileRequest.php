<?php

declare(strict_types=1);

namespace App\Request;

use App\Model\Filename;
use App\Model\YamlFile;
use App\Validator\YamlFileConstraint;
use Symfony\Component\HttpFoundation\Request;
use webignition\EncapsulatingRequestResolverBundle\Model\EncapsulatingRequestInterface;

class AddFileRequest implements EncapsulatingRequestInterface
{
    public const KEY_POST_NAME = 'name';
    public const KEY_POST_CONTENT = 'content';

    public function __construct(
        #[YamlFileConstraint]
        private YamlFile $file,
    ) {
    }

    public static function create(Request $request): self
    {
        return new self(
            new YamlFile(
                new Filename(trim((string) $request->request->get(self::KEY_POST_NAME))),
                trim((string) $request->request->get(self::KEY_POST_CONTENT))
            )
        );
    }

    public function getYamlFile(): YamlFile
    {
        return $this->file;
    }
}
