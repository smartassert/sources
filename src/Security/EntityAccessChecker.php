<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\UserHeldEntityInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class EntityAccessChecker
{
    public function __construct(
        private readonly AuthorizationCheckerInterface $authorizationChecker,
    ) {}

    /**
     * @throws AccessDeniedException
     */
    public function denyAccessUnlessGranted(UserHeldEntityInterface $entity): void
    {
        $attribute = EntityAccessVoter::ACCESS;

        if (false === $this->authorizationChecker->isGranted($attribute, $entity)) {
            $exception = new AccessDeniedException('Access Denied.');
            $exception->setAttributes($attribute);
            $exception->setSubject($entity);

            throw $exception;
        }
    }
}
