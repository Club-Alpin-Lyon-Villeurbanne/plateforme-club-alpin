<?php

namespace App\Form;

use App\Validator\Recaptcha;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;

class ResetPasswordType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => ' ',
                'attr' => ['class' => 'type1', 'placeholder' => 'Mon adresse e-mail'],
                'required' => true,
            ])
            ->add('recaptcha', HiddenType::class, [
                'constraints' => new Recaptcha('resetPassword'),
            ])
        ;
    }
}
