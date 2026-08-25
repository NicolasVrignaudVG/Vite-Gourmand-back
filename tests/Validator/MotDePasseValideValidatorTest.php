<?php

namespace App\Tests\Validator;

use App\Validator\MotDePasseValide;
use App\Validator\MotDePasseValideValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class MotDePasseValideValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): MotDePasseValideValidator
    {
        return new MotDePasseValideValidator();
    }

    #[DataProvider('motsDePasseValides')]
    public function testMotDePasseValide(string $password): void
    {
        $this->validator->validate($password, new MotDePasseValide());
        $this->assertNoViolation();
    }

    public static function motsDePasseValides(): array
    {
        return [['MonMotDePasse@1'], ['Admin@1234!'], ['Azerty123#456']];
    }

    #[DataProvider('motsDePasseInvalides')]
    public function testMotDePasseInvalide(string $password): void
    {
        $constraint = new MotDePasseValide();
        $this->validator->validate($password, $constraint);
        $this->buildViolation($constraint->message)->assertRaised();
    }

    public static function motsDePasseInvalides(): array
    {
        return [
            ['Court@1'],
            ['motdepasse@1'],
            ['MOTDEPASSE@1'],
            ['MonMotDePasse@'],
            ['MonMotDePasse1'],
        ];
    }
}