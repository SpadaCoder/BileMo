<?php

namespace App\Security\Voter;

use App\Entity\AppUser;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

final class UserVoter extends Voter
{
    public const APPUSER_DELETE = 'AppUserDelete';
    public const APPUSER_VIEW = 'AppUserView';
    // public const APPUSER_CREATE = 'AppUserCreate';

    protected function supports(string $attribute, mixed $subject): bool
    {
        // Vérifie que l'attribut est géré par ce voter
        return in_array($attribute, [self::APPUSER_DELETE, self::APPUSER_VIEW])
            && $subject instanceof AppUser;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $customer = $token->getUser();

        // Vérifie si l'utilisateur connecté est un Customer
        if (!$customer instanceof UserInterface) {
            return false;
        }

        switch ($attribute) {
            case self::APPUSER_DELETE:
            case self::APPUSER_VIEW:
                if ($subject instanceof AppUser) {
                    return $this->isOwner($subject, $customer);
                }
                break;

            // case self::APPUSER_CREATE:
            //     // Tous les Customers peuvent créer des utilisateurs
            //     return true;
        }

        return false;

    }

    private function isOwner(AppUser $appUser, UserInterface $customer): bool
    {
        // Retourne vrai si le Customer est propriétaire de l'AppUser
        return $appUser->getCustomer() === $customer;
    }
}
