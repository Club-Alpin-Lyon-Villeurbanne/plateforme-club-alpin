<?php

namespace App\Validator;

use App\Security\RecaptchaValidator as Client;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class RecaptchaValidator extends ConstraintValidator
{
    private Client $client;
    private RequestStack $requestStack;

    public function __construct(Client $client, RequestStack $requestStack)
    {
        $this->client = $client;
        $this->requestStack = $requestStack;
    }

    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof Recaptcha) {
            throw new UnexpectedTypeException($constraint, __NAMESPACE__ . '\Recaptcha');
        }

        if (!$this->client->isValid($value ?? '', $this->requestStack->getCurrentRequest()->getClientIp(), $constraint->action)) {
            $this->context->buildViolation('Le token recaptcha est invalide.')
                ->addViolation();
        }
    }
}
