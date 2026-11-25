<?php

namespace App\Tests\Form;

use App\Entity\Article;
use App\Form\ArticleType;
use App\Tests\WebTestCase;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;

class ArticleTypeTest extends WebTestCase
{
    private function buildForm(Article $article): FormInterface
    {
        return self::getContainer()->get(FormFactoryInterface::class)->create(ArticleType::class, $article, [
            'editoLineLink' => '',
            'imageRightLink' => '',
            'csrf_protection' => false,
        ]);
    }

    public function testUnLienWhatsAppEstRefuseDansLeTitreEtLeContenu(): void
    {
        $this->signup();
        $form = $this->buildForm(new Article());

        $form->submit([
            'titre' => 'Sortie au Grand Som wa.me/33612345678',
            'cont' => '<p>Rejoignez le <a href="https://chat.whatsapp.com/AbCdEf123456">groupe</a></p>',
        ], false);

        self::assertFalse($form->isValid());
        foreach (['titre', 'cont'] as $champ) {
            self::assertStringContainsString('liens WhatsApp sont interdits dans un article', (string) $form->get($champ)->getErrors(), $champ);
        }
    }

    public function testUnArticleSansLienWhatsAppNeDeclenchePasLErreur(): void
    {
        $this->signup();
        $form = $this->buildForm(new Article());

        $form->submit([
            'titre' => 'Sortie au Grand Som',
            'cont' => '<p>Le groupe WhatsApp de la sortie a bien fonctionné.</p>',
        ], false);

        self::assertStringNotContainsString('WhatsApp', (string) $form->getErrors(true));
    }
}
