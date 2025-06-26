<?php

namespace App\Form;

use App\Entity\Article;
use App\Entity\Evt;
use App\Repository\CommissionRepository;
use App\Repository\EvtRepository;
use App\UserRights;
use FOS\CKEditorBundle\Form\Type\CKEditorType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ArticleType extends AbstractType
{
    public function __construct(
        private readonly CommissionRepository $commissionRepository,
        private readonly EvtRepository $eventRepository,
        private readonly UserRights $userRights)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Récupérer toutes les commissions visibles et les convertir en tableau
        $commissions = $this->commissionRepository->findVisible();
        // S'assurer que $commissions est un tableau et non un générateur
        $commissions = \is_array($commissions) ? $commissions : iterator_to_array($commissions);

        // Créer les choix pour les commissions
        $commissionChoices = [];
        foreach ($commissions as $commission) {
            if ($this->userRights->allowedOnCommission('article_create', $commission)) {
                $commissionChoices['Actualité « ' . $commission->getTitle() . ' »'] = (string) $commission->getId();
            }
        }

        $article = $options['data'] ?? null;
        $defaultArticleType = '';
        $isCompteRendu = false;

        if ($article && $article->getCommission()) {
            $defaultArticleType = $article->getCommission()->getId();
        } elseif ($article && $article->getEvt()) {
            $defaultArticleType = '-1'; // Compte rendu de sortie
            $isCompteRendu = true;
        }

        $builder
            ->add('articleType', ChoiceType::class, [
                'mapped' => false,
                'label' => "Type d'article",
                'choices' => [
                    'Compte rendu de sortie' => '-1',
                ] + $commissionChoices,
                'group_by' => function ($choice, $key, $value) {
                    if (is_numeric($value) && (int) $value > 0) {
                        return 'Article lié à une commission :';
                    }

                    return null;
                },
                'required' => true,
                'data' => $defaultArticleType,
                'placeholder' => '- Choisissez le type d\'article',
            ])
            ->add('isCompteRendu', HiddenType::class, [
                'mapped' => false,
                'data' => $isCompteRendu,
            ])
            ->add('evt', EntityType::class, [
                'class' => Evt::class,
                'choices' => array_filter(
                    $this->eventRepository->getRecentPastEvents(),
                    fn (Evt $event) => $this->userRights->allowedOnCommission('evt_create', $event->getCommission())
                ),
                'choice_label' => function (Evt $evt) {
                    return date('d', $evt->getTsp()) . ' ' .
                           $this->getMonthName(date('m', $evt->getTsp())) . ' ' .
                           date('Y', $evt->getTsp()) . ' | ' .
                           $evt->getCommission()->getTitle() . ' | ' .
                           $evt->getTitre();
                },
                'placeholder' => '--',
                'required' => false,
                'label' => 'Lier cet article à une sortie',
            ])
            ->add('titre', TextType::class, [
                'label' => 'Titre',
                'attr' => [
                    'placeholder' => 'ex : Escalade du Grand Som, une sortie bien gaillarde !',
                ],
            ])
            ->add('une', CheckboxType::class, [
                'label' => 'Placer cet article à la Une ?',
                'required' => false,
                'help' => 'À utiliser avec parcimonie. Ceci place l\'article au sommet de la page d\'accueil, dans les actualités défilantes. Il reste affiché là jusqu\'à ce qu\'un autre article à la Une vienne l\'en déloger. Utile pour une actualité qui dure dans le temps, ou une alerte à mettre en valeur. La photo est alors obligatoire.',
            ])
            ->add('cont', CKEditorType::class, [
                'label' => 'Contenu',
                'required' => true,
                'attr' => [
                    'class' => 'type1 wide ckeditor',
                    'rows' => 15,
                    'style' => 'width: 97%; min-height:300px',
                ],
            ])
            ->add('mediaUploadId', HiddenType::class, [
                'mapped' => false,
                'required' => false,
            ])
            ->add('commission', HiddenType::class, [
                'mapped' => false,
                'required' => false,
            ])
            ->add('articleDraftSave', SubmitType::class, [
                'label' => '<span class="bleucaf">&gt;</span> ENREGISTRER COMME BROUILLON',
                'label_html' => true,
                'attr' => [
                    'class' => 'mediumlink',
                ],
            ])
            ->add('articleSave', SubmitType::class, [
                'label' => '<span class="blanc">&gt;</span> ENREGISTRER ET DEMANDER LA PUBLICATION',
                'label_html' => true,
                'attr' => [
                    'class' => 'mediumlink btn-blue blanc',
                ],
            ])

            // Gestion des événements du formulaire pour la logique conditionnelle
            ->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
                $data = $event->getData();
                $form = $event->getForm();

                // Gérer la conversion de l'ID de commission en objet Commission
                if (isset($data['articleType'])) {
                    if ('0' === $data['articleType'] || '-1' === $data['articleType'] || '' === $data['articleType']) {
                        $data['commission'] = null;
                    } else {
                        $data['commission'] = (int) $data['articleType'];
                    }
                }

                if (isset($data['articleType']) && '-1' === $data['articleType']) {
                    $data['isCompteRendu'] = true;
                } else {
                    $data['isCompteRendu'] = false;
                }

                $event->setData($data);
            })
            ->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
                $form = $event->getForm();
                $article = $event->getData();

                if ($form->has('commission') && $form->get('commission')->getData()) {
                    $commissionId = $form->get('commission')->getData();
                    $commission = $this->commissionRepository->find($commissionId);
                    if ($commission) {
                        $article->setCommission($commission);
                    }
                }
            });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Article::class,
        ]);
    }

    private function getMonthName(string $month): string
    {
        $months = [
            '01' => 'janvier',
            '02' => 'février',
            '03' => 'mars',
            '04' => 'avril',
            '05' => 'mai',
            '06' => 'juin',
            '07' => 'juillet',
            '08' => 'août',
            '09' => 'septembre',
            '10' => 'octobre',
            '11' => 'novembre',
            '12' => 'décembre',
        ];

        return $months[$month] ?? $month;
    }
}
