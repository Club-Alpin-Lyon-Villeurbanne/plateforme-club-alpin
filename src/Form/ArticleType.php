<?php

namespace App\Form;

use App\Entity\Article;
use App\Entity\Commission;
use App\Entity\Evt;
use App\Helper\MonthHelper;
use App\Repository\CommissionRepository;
use App\Repository\EvtRepository;
use App\UserRights;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ArticleType extends AbstractType
{
    public function __construct(
        private readonly CommissionRepository $commissionRepository,
        private readonly EvtRepository $eventRepository,
        private readonly UserRights $userRights,
        private readonly MonthHelper $monthHelper
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $article = $options['data'] ?? null;
        $defaultArticleType = '';

        if ($article && $article->getEvt()) {
            $defaultArticleType = 'cr';
        } elseif ($article && $article->getCommission()) {
            $defaultArticleType = 'article';
        }

        $builder
            ->add('commission', EntityType::class, [
                'class' => Commission::class,
                'choices' => array_filter(
                    iterator_to_array($this->commissionRepository->findVisible()),
                    fn (Commission $commission) => $this->userRights->allowedOnCommission('article_create', $commission),
                ),
                'label' => 'Commission',
                'label_attr' => [
                    'class' => 'title-header',
                ],
                'placeholder' => 'Choisissez une commission',
                'required' => true,
                'attr' => [
                    'class' => 'type1 wide',
                    'style' => 'width: 95%',
                ],
            ])
            ->add('articleType', ChoiceType::class, [
                'mapped' => false,
                'label' => 'Je rédige',
                'label_attr' => [
                    'class' => 'title-header',
                ],
                'choices' => [
                    '📋 un compte rendu de sortie' => 'cr',
                    '📖 un article' => 'article',
                ],
                'expanded' => true,
                'multiple' => false,
                'required' => true,
                'data' => $defaultArticleType,
            ])
            ->add('evt', EntityType::class, [
                'class' => Evt::class,
                'choices' => array_filter(
                    $this->eventRepository->getRecentPastEvents(),
                    fn (Evt $event) => ($this->userRights->allowedOnCommission('article_create', $event->getCommission()) || $this->userRights->allowedOnCommission('evt_create', $event->getCommission()))
                ),
                'choice_label' => function (Evt $evt) {
                    return date('d', $evt->getTsp()) . ' ' .
                           $this->monthHelper->getMonthName(date('m', $evt->getTsp())) . ' ' .
                           date('Y', $evt->getTsp()) . ' | ' .
                           $evt->getCommission()->getTitle() . ' | ' .
                           $evt->getTitre();
                },
                'placeholder' => 'Sélectionner',
                'required' => false,
                'label' => 'Lier cet article à une sortie',
                'attr' => [
                    'class' => 'type1 wide',
                    'style' => 'width: 95%',
                ],
                'help' => 'Champ obligatoire pour un compte rendu de sortie.',
                'help_attr' => [
                    'class' => 'mini',
                ],
            ])
            ->add('titre', TextType::class, [
                'label' => 'Titre',
                'label_attr' => [
                    'class' => 'title-header',
                ],
                'required' => true,
                'attr' => [
                    'placeholder' => 'ex : Escalade du Grand Som, une sortie bien gaillarde !',
                    'class' => 'type1 wide',
                    'style' => 'width: 95%',
                ],
            ])
            ->add('une', CheckboxType::class, [
                'label' => 'Placer cet article à la Une ?',
                'required' => false,
                'attr' => [
                    'class' => 'custom',
                ],
                'help' => 'À utiliser avec parcimonie. Ceci place l\'article au sommet de la page d\'accueil, dans les actualités défilantes. Il reste affiché là jusqu\'à ce qu\'un autre article à la Une vienne l\'en déloger. Utile pour une actualité qui dure dans le temps, ou une alerte à mettre en valeur. La photo est alors obligatoire.',
            ])
            ->add('cont', TextareaType::class, [
                'label' => 'Contenu',
                'required' => true,
                'attr' => [
                    'class' => 'type1 wide tinymce',
                    'rows' => 15,
                    'style' => 'width: 615px; min-height:300px',
                ],
            ])
            ->add('mediaUploadId', HiddenType::class, [
                'mapped' => false,
                'required' => false,
            ])
            ->add('agreeEdito', CheckboxType::class, [
                'label' => 'Je certifie que j\'ai pris connaissance de la <a href="' . $options['editoLineLink'] . '" target="_blank" rel="noopener">ligne éditoriale du club</a> avant de poster mon article',
                'label_html' => true,
                'required' => true,
            ])
            ->add('imagesAuthorized', CheckboxType::class, [
                'label' => 'Je certifie que j\'ai l\'autorisation des propriétaires de chaque image et chaque photo présente dans cet article sinon le club se risque à des amendes, <a href="' . $options['imageRightLink'] . '" target="_blank" rel="noopener">voici l\'explication de cas déjà passés dans notre club</a>.',
                'label_html' => true,
                'required' => true,
                'help' => 'Vous n\'êtes pas autorisé à utiliser des photos d\'internet, sauf si elles proviennent des plateformes : <a href="https://www.pexels.com/fr-fr/" target="_blank" rel="noopener">Pexels</a>, <a href="https://pixabay.com/fr/" target="_blank" rel="noopener">Pixabay</a>, <a href="https://unsplash.com/fr" target="_blank" rel="noopener">Unsplash</a>',
                'help_html' => true,
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
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Article::class,
            'editoLineLink' => '',
            'imageRightLink' => '',
        ]);
    }
}
