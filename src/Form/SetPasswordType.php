<?php

namespace App\Form;

use App\Validator\CompliantPassword;
use App\Validator\Recaptcha;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;

class SetPasswordType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('password', RepeatedType::class, [
                'type' => PasswordType::class,
                'invalid_message' => 'Les mots de passe ne correspondent pas.',
                'options' => ['attr' => ['class' => 'password-field']],
                'required' => true,
                'first_options' => [
                    'label' => ' ',
                    'constraints' => new CompliantPassword(),
                    'attr' => ['autocomplete' => 'new-password', 'class' => 'type1', 'placeholder' => 'Mot de passe'],
                ],
                'second_options' => [
                    'label' => ' ',
                    'attr' => ['autocomplete' => 'new-password', 'class' => 'type1', 'placeholder' => 'Confirmation du mot de passe'],
                ],
            ])
            ->add('recaptcha', HiddenType::class, [
                'constraints' => new Recaptcha('setPassword'),
            ])
        ;
    }
}
