<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\SourceInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class SourceAccessVoter extends Voter
{
    public const ACCESS = 'access';

    protected function supports(string $attribute, mixed $subject): bool
    {
        if (self::ACCESS !== $attribute) {
            return false;
        }

        if (!$subject instanceof SourceInterface) {
            return false;
        }

        return true;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof UserInterface) {
            return false;
        }

        return $subject instanceof SourceInterface && $subject->getUserId() === $user->getUserIdentifier();
    }
}
