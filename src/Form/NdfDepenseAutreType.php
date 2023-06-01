<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class NdfDemandeAutreType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('montant', MoneyType::class, [
                'label' => 'Montant',
                'required' => FALSE,
            ])
            ->add('ordre', IntegerType::class, [
                'label' => 'Ordre',
                'required' => FALSE,
            ])
            ->add('commentaire', TextareaType::class, [
                'label' => 'Descriptif trajet',
                'required' => FALSE,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => NdfDemandeAutre::class
        ));
    }
}
