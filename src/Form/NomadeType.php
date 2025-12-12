<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class NomadeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /* @var User $user */
        $builder
            ->add('id_user', ChoiceType::class, [
                'label' => 'Choisir un non-adhérent',
                'required' => true,
                'mapped' => false,
                'placeholder' => '--',
                'choices' => $options['existing_users'],
                'choice_label' => fn ($user) => sprintf(
                    '%s %s - %s - licence découverte valide jusqu\'au %s',
                    strtoupper($user->getLastname()),
                    ucfirst($user->getFirstname()),
                    $user->getCafnum(),
                    $user->getDiscoveryEndDatetime()?->format('d/m/Y H:i:s')
                ),
                'choice_value' => fn ($user) => $user?->getId(),
                'attr' => [
                    'class' => 'type1',
                    'style' => 'width: 100%',
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
