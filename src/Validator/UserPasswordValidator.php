<?php

namespace App\Validator;

use App\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Validator\Constraints\UserPassword;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class UserPasswordValidator extends ConstraintValidator
{
    private Security $security;
    private PasswordHasherFactoryInterface $hasherFactory;

    public function __construct(
        Security $security,
        PasswordHasherFactoryInterface $hasherFactory
    ) {
        $this->security = $security;
        $this->hasherFactory = $hasherFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function validate($password, Constraint $constraint): void
    {
        if (!$constraint instanceof UserPassword) {
            throw new UnexpectedTypeException($constraint, UserPassword::class);
        }

        if (null === $password || '' === $password) {
            $this->context->addViolation($constraint->message);

            return;
        }

        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new ConstraintDefinitionException(sprintf('The User object must implement "%s".', User::class));
        }

        if (!$this->hasherFactory->getPasswordHasher('login_form')->verify($user->getMdp(), $password, $user->getSalt())) {
            $this->context->addViolation($constraint->message);
        }
    }
}
