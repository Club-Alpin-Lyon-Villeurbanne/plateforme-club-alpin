<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class UserFullType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if (!$options['is_edit']) {
            $builder
                ->add('firstname', TextType::class, [
                    'label' => 'Prénom',
                    'required' => true,
                    'attr' => [
                        'class' => 'type1',
                    ],
                    'constraints' => [
                        new NotBlank(),
                        new Length([
                            'min' => 3,
                        ]),
                    ],
                ])
                ->add('lastname', TextType::class, [
                    'label' => 'Nom',
                    'required' => true,
                    'attr' => [
                        'class' => 'type1',
                    ],
                    'constraints' => [
                        new NotBlank(),
                        new Length([
                            'min' => 3,
                        ]),
                    ],
                ])
            ;
        }
        $builder->add('email', EmailType::class, [
            'label' => 'E-mail',
            'required' => true,
            'attr' => [
                'class' => 'type1',
            ],
            'constraints' => [
                new NotBlank(),
                new Email(),
            ],
        ]);
        if (!$options['is_edit']) {
            $builder
                ->add('cafnum', TextType::class, [
                    'label' => 'Numéro de licence',
                    'required' => true,
                    'attr' => [
                        'class' => 'type1',
                    ],
                    'help' => 'Ne peut pas être laissé vide et doit être unique ; si l\'utilisateur n\'a pas de numéro de licence FFCAM, donnez une indication de son rôle (ex : formateur_psc1) mais évitez toute donnée personnelle',
                    'help_attr' => [
                        'class' => 'mini',
                    ],
                    'constraints' => [
                        new NotBlank(),
                    ],
                ])
                ->add('birthdate', DateType::class, [
                    'label' => 'Date de naissance',
                    'required' => true,
                    'widget' => 'single_text',
                    'format' => 'dd/MM/yyyy',
                    'html5' => false,
                    'attr' => [
                        'class' => 'type1',
                        'autocomplete' => 'off',
                    ],
                    'help' => 'Format : jj/mm/aaaa',
                    'help_attr' => [
                        'class' => 'mini',
                    ],
                ])
                ->add('tel', TextType::class, [
                    'label' => 'Numéro de téléphone personnel',
                    'required' => false,
                    'attr' => [
                        'class' => 'type1',
                    ],
                ])
                ->add('tel2', TextType::class, [
                    'label' => 'Numéro de téléphone de secours',
                    'required' => false,
                    'attr' => [
                        'class' => 'type1',
                    ],
                ])
                ->add('adresse', TextType::class, [
                    'label' => false,
                    'required' => false,
                    'attr' => [
                        'class' => 'type1',
                        'placeholder' => 'Numéro, rue...',
                    ],
                ])
                ->add('cp', TextType::class, [
                    'label' => false,
                    'required' => false,
                    'attr' => [
                        'class' => 'type1',
                        'style' => 'width: 100px',
                        'placeholder' => 'Code postal',
                    ],
                ])
                ->add('ville', TextType::class, [
                    'label' => false,
                    'required' => false,
                    'attr' => [
                        'class' => 'type1',
                        'placeholder' => 'Ville',
                    ],
                ])
                ->add('pays', TextType::class, [
                    'label' => false,
                    'required' => false,
                    'attr' => [
                        'class' => 'type1',
                        'placeholder' => 'Pays',
                    ],
                ])
            ;
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'is_edit' => false,
            'csrf_protection' => true,
        ]);
    }
}
