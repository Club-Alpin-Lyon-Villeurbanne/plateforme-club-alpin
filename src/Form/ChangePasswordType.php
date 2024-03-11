<?php

namespace App\Form;

use App\Validator\CompliantPassword;
use App\Validator\Recaptcha;
use App\Validator\UserPasswordValidator;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Security\Core\Validator\Constraints\UserPassword;

class ChangePasswordType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('current_password', PasswordType::class, [
                'constraints' => new UserPassword(null, null, UserPasswordValidator::class),
                'label' => ' ',
                'attr' => ['autocomplete' => 'new-password', 'class' => 'type1', 'placeholder' => 'Mot de passe actuel'],
            ])
            ->add('password', RepeatedType::class, [
                'type' => PasswordType::class,
                'invalid_message' => 'Les mots de passe ne correspondent pas.',
                'options' => ['attr' => ['class' => 'password-field']],
                'required' => true,
                'first_options' => [
                    'label' => ' ',
                    'constraints' => new CompliantPassword(),
                    'attr' => ['autocomplete' => 'new-password', 'class' => 'type1', 'placeholder' => 'Nouveau mot de passe'],
                ],
                'second_options' => [
                    'label' => ' ',
                    'attr' => ['autocomplete' => 'new-password', 'class' => 'type1', 'placeholder' => 'Confirmation du nouveau mot de passe'],
                ],
            ])
            ->add('recaptcha', HiddenType::class, [
                'constraints' => new Recaptcha('changePassword'),
            ])
        ;
    }
}
