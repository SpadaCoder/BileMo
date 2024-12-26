<?php

namespace App\Security\Voter;

use App\Entity\AppUser;
use App\Entity\Customer;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

final class UserVoter extends Voter
{
    public const DELETE = 'USER_DELETE';
    public const VIEW = 'USER_VIEW';
    public const CREATE = 'USER_CREATE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        // Vérifie que l'attribut est géré par ce voter
        return in_array($attribute, [self::DELETE, self::VIEW, self::CREATE])
            && ($subject instanceof AppUser || $subject instanceof Customer);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $customer = $token->getUser();

        // Vérifie si l'utilisateur connecté est un Customer
        if (!$customer instanceof UserInterface) {
            return false;
        }

        switch ($attribute) {
            case self::DELETE:
                // Vérifie si le Customer peut supprimer un AppUser
                if ($subject instanceof AppUser) {
                    return $subject->getCustomer() === $customer;
                }
                break;

            case self::VIEW:
                // Vérifie si le Customer peut voir un AppUser
                if ($subject instanceof AppUser) {
                    return $subject->getCustomer() === $customer;
                }
                break;

            case self::CREATE:
                // Tous les Customers peuvent créer des utilisateurs
                return true;
        }

        return false;
    }
}
