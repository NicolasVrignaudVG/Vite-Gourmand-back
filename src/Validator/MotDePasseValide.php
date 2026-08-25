<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * Règle de robustesse des mots de passe de l'application :
 * 10 caractères minimum, une majuscule, une minuscule, un chiffre, un caractère spécial.
 */
#[\Attribute]
class MotDePasseValide extends Constraint
{
    public string $message = 'Le mot de passe doit contenir au minimum 10 caractères, une majuscule, une minuscule, un chiffre et un caractère spécial.';
}