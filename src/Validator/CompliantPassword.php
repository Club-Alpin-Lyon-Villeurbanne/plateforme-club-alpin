<?php

namespace App\Validator;

use Rollerworks\Component\PasswordStrength\Validator\Constraints\PasswordRequirements;
use Symfony\Component\Validator\Constraints\Compound;
use Symfony\Component\Validator\Constraints\NotCompromisedPassword;

class CompliantPassword extends Compound
{
    protected function getConstraints(array $options): array
    {
        return [
            new NotCompromisedPassword(),
            new PasswordRequirements([
                'minLength' => 8,
                'requireLetters' => true,
                'requireCaseDiff' => true,
                'requireNumbers' => true,
                'requireSpecialCharacter' => true,
            ]),
        ];
    }
}
