<?php

namespace App\Form;

use App\Entity\Partenaire;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class PartnerType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom',
            ])
            ->add('order', IntegerType::class, [
                'label' => 'Ordre d\'affichage',
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
            ])
            ->add('url', UrlType::class, [
                'label' => 'URL',
            ])
            ->add('imageFile', FileType::class, [
                'label' => 'Image PNG (250 x 100, transparente)',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '1024k',
                        'mimeTypes' => [
                            'image/png',
                        ],
                        'mimeTypesMessage' => 'Veuillez télécharger une image PNG valide',
                    ])
                ],
            ])
            ->add('type', ChoiceType::class, [
                'choices' => [
                    'Privé' => 1,
                    'Public' => 2,
                ],
                'expanded' => true,
                'multiple' => false,
            ])
            ->add('enabled', ChoiceType::class, [
                'choices' => [
                    'Activé' => true,
                    'Désactivé' => false,
                ],
                'expanded' => true,
                'multiple' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Partenaire::class,
        ]);
    }
}