<?php

declare(strict_types=1);

namespace App\Response;

use Symfony\Component\HttpFoundation\Response;

class YamlResponse extends Response
{
    public function __construct(string $content = '')
    {
        parent::__construct($content, 200, [
            'content-type' => 'text/x-yaml; charset=utf-8',
        ]);
    }
}
