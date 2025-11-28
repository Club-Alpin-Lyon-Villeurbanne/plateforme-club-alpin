<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('gdriveEmail', TextType::class, [
                'label' => 'E-mail secondaire :',
                'required' => false,
                'attr' => [
                    'class' => 'type1 wide',
                    'style' => 'width: 95%',
                ],
            ])
            ->add('mediaUploadId', HiddenType::class, [
                'mapped' => false,
                'required' => false,
            ])
            ->add('save', SubmitType::class, [
                'label' => '<span class="bleucaf">&gt;</span> ENREGISTRER MES INFORMATIONS',
                'label_html' => true,
                'attr' => [
                    'class' => 'biglink',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
