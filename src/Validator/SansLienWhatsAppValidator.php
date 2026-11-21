<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class SansLienWhatsAppValidator extends ConstraintValidator
{
    /**
     * Domaines de WhatsApp (chat.whatsapp.com, wa.me, api.whatsapp.com…) et schéma whatsapp://,
     * en texte comme dans un href. Le nom seul (« groupe WhatsApp ») reste permis.
     * Le domaine ne doit pas prolonger un autre nom d'hôte : « java.me » ne compte pas.
     */
    private const string MOTIF = '~(?<![a-z0-9.-])(?:[a-z0-9-]+\.)*(?:whatsapp\.com|wa\.me)(?![a-z0-9-])|\bwhatsapp://~i';

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof SansLienWhatsApp) {
            throw new UnexpectedTypeException($constraint, SansLienWhatsApp::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        if (!\is_string($value)) {
            throw new UnexpectedValueException($value, 'string');
        }

        if (preg_match(self::MOTIF, $value)) {
            $this->context->buildViolation($constraint->message)->addViolation();
        }
    }
}
