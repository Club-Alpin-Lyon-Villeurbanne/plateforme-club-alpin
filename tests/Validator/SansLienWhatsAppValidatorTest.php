<?php

namespace App\Tests\Validator;

use App\Validator\SansLienWhatsApp;
use App\Validator\SansLienWhatsAppValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @extends ConstraintValidatorTestCase<SansLienWhatsAppValidator>
 */
class SansLienWhatsAppValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): SansLienWhatsAppValidator
    {
        return new SansLienWhatsAppValidator();
    }

    #[DataProvider('textesAvecLienWhatsApp')]
    public function testUnLienWhatsAppEstRefuse(string $texte): void
    {
        $this->validator->validate($texte, new SansLienWhatsApp(message: 'interdit'));

        $this->buildViolation('interdit')->assertRaised();
    }

    public static function textesAvecLienWhatsApp(): iterable
    {
        yield 'invitation à un groupe' => ['Rejoignez le groupe : https://chat.whatsapp.com/AbCdEf123456'];
        yield 'sans https' => ['groupe : chat.whatsapp.com/AbCdEf123456'];
        yield 'lien court' => ['écrivez-moi sur https://wa.me/33612345678'];
        yield 'lien court sans https' => ['wa.me/33612345678'];
        yield 'chaîne' => ['https://www.whatsapp.com/channel/0029VaAbCdEf'];
        yield 'api' => ['https://api.whatsapp.com/send?phone=33612345678'];
        yield 'majuscules' => ['HTTPS://CHAT.WHATSAPP.COM/ABCDEF'];
        yield 'dans un href, texte neutre' => ['<p>Le <a href="https://chat.whatsapp.com/AbCdEf">groupe de la sortie</a></p>'];
        yield 'schéma de l\'application' => ['<a href="whatsapp://send?text=bonjour">écrire</a>'];
        yield 'entité décimale dans un href' => ['<a href="https://chat.whats&#97;pp.com/AbCdEf">groupe</a>'];
        yield 'entité hexadécimale' => ['https://wa&#x2E;me/33612345678'];
        yield 'entité sans point-virgule' => ['<a href="https://chat.whats&#97pp.com/AbCdEf">groupe</a>'];
        yield 'entité nommée' => ['https://wa&period;me/33612345678'];
        yield 'encodage d\'URL' => ['<a href="https://chat.whats%61pp.com/AbCdEf">groupe</a>'];
    }

    #[DataProvider('textesSansLienWhatsApp')]
    public function testUnTexteSansLienWhatsAppEstAccepte(?string $texte): void
    {
        $this->validator->validate($texte, new SansLienWhatsApp());

        $this->assertNoViolation();
    }

    public static function textesSansLienWhatsApp(): iterable
    {
        yield 'vide' => [''];
        yield 'nul' => [null];
        yield 'nom seul' => ['Un groupe WhatsApp sera créé pour le covoiturage.'];
        yield 'autre lien' => ['<a href="https://www.clubalpinlyon.fr/materiel">Gestion du matériel</a>'];
        yield 'domaine qui finit par wa.me' => ['https://java.me/doc'];
        yield 'domaine qui commence par wa.me' => ['https://wa.mexico.example/'];
        yield 'domaine qui contient whatsapp.com' => ['https://notwhatsapp.com/page'];
        yield 'entités sans lien' => ['<p>Parents &amp; enfants&nbsp;: groupe WhatsApp &#224; venir</p>'];
        yield 'domaine encodé qui finit par wa.me' => ['https://jav&#97;.me/doc'];
    }
}
