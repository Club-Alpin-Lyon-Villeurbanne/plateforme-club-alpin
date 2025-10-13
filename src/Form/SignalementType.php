<?php

namespace App\Form;

use App\Validator\Recaptcha;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SignalementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('contact_status', ChoiceType::class, [
                'label' => 'Je suis',
                'required' => true,
                'mapped' => false,
                'choices' => [
                    'Témoin' => 'témoin',
                    'Victime' => 'victime',
                ],
                'attr' => [
                    'class' => 'type1',
                ],
                'placeholder' => '--',
            ])
            ->add('object', ChoiceType::class, [
                'label' => 'L\'objet de mon signalement',
                'required' => true,
                'mapped' => false,
                'choices' => [
                    'VSS (violences sexistes et sexuelles)' => 'vss',
                    'Autres formes de violence' => 'autres',
                ],
                'attr' => [
                    'class' => 'type1',
                ],
                'placeholder' => '--',
            ])
            ->add('content', TextareaType::class, [
                'label' => 'Mon témoignage',
                'required' => true,
                'mapped' => false,
                'attr' => [
                    'class' => 'type1 wide tinymce ckeditor',
                    'rows' => 15,
                    'style' => 'width: 615px; min-height:300px',
                ],
            ])
            ->add('contact_phone', TelType::class, [
                'label' => 'Téléphone',
                'required' => false,
                'mapped' => false,
                'attr' => [
                    'class' => 'type1',
                ],
            ])
            ->add('contact_email', EmailType::class, [
                'label' => 'Adresse e-mail',
                'required' => false,
                'mapped' => false,
                'attr' => [
                    'class' => 'type1',
                ],
            ])
            // captcha pour éviter le spam (formulaire public)
            ->add('recaptcha', HiddenType::class, [
                'constraints' => new Recaptcha('signalement'),
            ])
            ->add('send', SubmitType::class, [
                'label' => '<span class="blanc">&gt;</span> ENVOYER MON SIGNALEMENT',
                'label_html' => true,
                'attr' => [
                    'class' => 'biglink btn-blue blanc',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => true,
        ]);
    }
}
