<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;

class UserContactType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('objet', TextType::class, [
                'label' => 'Objet',
                'required' => true,
                'mapped' => false,
                'attr' => [
                    'class' => 'type1',
                    'style' => 'width:95%',
                    'minlength' => 4,
                ],
                'data' => $options['default_object'],
                'constraints' => [
                    new Length([
                        'min' => 4,
                    ]),
                ],
            ])
            ->add('message', TextareaType::class, [
                'label' => 'Message',
                'required' => true,
                'mapped' => false,
                'attr' => [
                    'class' => 'type1 wide',
                    'style' => 'width:95%; height:150px',
                ],
                'constraints' => [
                    new Length([
                        'min' => 10,
                    ]),
                ],
            ])
            ->add('id_user', HiddenType::class, [
                'label' => false,
                'required' => true,
                'mapped' => false,
                'data' => $options['user_id'],
            ])
            ->add('id_article', HiddenType::class, [
                'label' => false,
                'required' => false,
                'mapped' => false,
                'data' => $options['article_id'],
            ])
            ->add('id_event', HiddenType::class, [
                'label' => false,
                'required' => false,
                'mapped' => false,
                'data' => $options['event_id'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'default_object' => '',
            'user_id' => 0,
            'article_id' => 0,
            'event_id' => 0,
            'csrf_protection' => true,
        ]);
    }
}
