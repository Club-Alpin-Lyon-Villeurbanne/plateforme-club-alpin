<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;

class NomadeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /* @var User $user */
        $builder
            ->add('id_user', ChoiceType::class, [
                'label' => '- Reprendre un non-adhérent déjà créé',
                'required' => false,
                'mapped' => false,
                'placeholder' => '- Non merci, créer un nouveau non-adhérent',
                'choices' => $options['existing_users'],
                'choice_label' => fn ($user) => sprintf(
                    '%s %s - %s - le %s',
                    strtoupper($user->getLastname()),
                    ucfirst($user->getFirstname()),
                    $user->getCafnum(),
                    $user->getCreatedAt()?->format('d/m/Y')
                ),
                'choice_value' => fn ($user) => $user?->getId(),
                'attr' => [
                    'class' => 'type1',
                    'style' => 'width: 40%',
                ],
            ])

            ->add('cafnum', TextType::class, [
                'label' => 'Numéro de licence FFCAM',
                'required' => true,
                'attr' => [
                    'class' => 'type1',
                    'placeholder' => 'Requis',
                    'maxlength' => 20,
                ],
                'constraints' => [
                    new Length([
                        'max' => 20,
                    ]),
                ],
            ])
            ->add('lastname', TextType::class, [
                'label' => 'Nom',
                'required' => true,
                'attr' => [
                    'class' => 'type1',
                    'placeholder' => 'Requis',
                ],
            ])
            ->add('firstname', TextType::class, [
                'label' => 'Prénom',
                'required' => true,
                'attr' => [
                    'class' => 'type1',
                    'placeholder' => 'Requis',
                ],
            ])
            ->add('tel', TelType::class, [
                'label' => 'Téléphone',
                'required' => false,
                'attr' => [
                    'class' => 'type1',
                    'placeholder' => 'Facultatif',
                ],
            ])
            ->add('birthdate', DateType::class, [
                'label' => 'Date de naissance',
                'required' => true,
                'mapped' => false,
                'widget' => 'single_text',
                'format' => 'dd/MM/yyyy',
                'html5' => false,
                'attr' => [
                    'class' => 'type1',
                    'placeholder' => 'jj/mm/aaaa',
                    'autocomplete' => 'off',
                ],
            ])
            ->add('tel2', TelType::class, [
                'label' => 'Téléphone de secours',
                'required' => true,
                'attr' => [
                    'class' => 'type1',
                    'placeholder' => 'Requis',
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Adresse e-mail',
                'required' => false,
                'attr' => [
                    'class' => 'type1',
                    'placeholder' => 'Facultatif',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'existing_users' => [],
            'csrf_protection' => true,
        ]);
    }
}
