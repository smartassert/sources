<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\SourceInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class UserSourceAccessChecker
{
    public function __construct(
        private AuthorizationCheckerInterface $authorizationChecker,
    ) {
    }

    public function denyAccessUnlessGranted(SourceInterface $source): void
    {
        $attribute = SourceAccessVoter::ACCESS;

        if (false === $this->authorizationChecker->isGranted($attribute, $source)) {
            $exception = new AccessDeniedException('Access Denied.');
            $exception->setAttributes($attribute);
            $exception->setSubject($source);

            throw $exception;
        }
    }
}
