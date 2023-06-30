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
use App\Entity\NdfDepenseVoiture;

class NdfDepenseVoitureType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('nbre_km', IntegerType::class, [
                'label' => 'Nombre de kms (aller/retour)',
                'required' => false,
            ])
            ->add('commentaire', TextareaType::class, [
                'label' => 'Descriptif trajet',
                'required' => false,
            ])
            ->add('frais_peage', MoneyType::class, [
                'label' => 'Frais de péage',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => NdfDepenseVoiture::class
        ));
    }
}
