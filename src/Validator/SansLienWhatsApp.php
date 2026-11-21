<?php

namespace App\Validator;

use Symfony\Component\Validator\Attribute\HasNamedArguments;
use Symfony\Component\Validator\Constraint;

/**
 * Refuse un lien WhatsApp dans un texte affiché aux visiteurs non connectés :
 * des inconnus rejoignaient les groupes du club en le trouvant sur le site.
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD)]
class SansLienWhatsApp extends Constraint
{
    #[HasNamedArguments]
    public function __construct(
        public string $message = 'Les liens WhatsApp sont interdits ici : cette partie est visible sans connexion.',
        ?array $groups = null,
        mixed $payload = null,
    ) {
        parent::__construct(null, $groups, $payload);
    }
}
