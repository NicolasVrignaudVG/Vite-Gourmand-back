<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class MotDePasseValideValidator extends ConstraintValidator
{
    private const PATTERN = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{10,}$/';

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof MotDePasseValide) {
            throw new UnexpectedTypeException($constraint, MotDePasseValide::class);
        }

        if (!is_string($value) || !preg_match(self::PATTERN, $value)) {
            $this->context->buildViolation($constraint->message)->addViolation();
        }
    }
}