<?php

declare(strict_types=1);

namespace App\Request;

use App\Model\YamlFile;
use App\Validator\YamlFileConstraint;
use Symfony\Component\HttpFoundation\Request;
use webignition\EncapsulatingRequestResolverBundle\Model\EncapsulatingRequestInterface;

class AddYamlFileRequest extends AbstractYamlFileRequest implements EncapsulatingRequestInterface
{
    public const KEY_POST_CONTENT = 'content';

    public function __construct(
        #[YamlFileConstraint]
        private YamlFile $file,
    ) {
        parent::__construct($file->name);
    }

    public static function create(Request $request): self
    {
        $filename = self::createFilenameFromRequest($request);

        return new self(
            new YamlFile(
                $filename,
                trim((string) $request->request->get(self::KEY_POST_CONTENT))
            )
        );
    }

    public function getYamlFile(): YamlFile
    {
        return $this->file;
    }
}
