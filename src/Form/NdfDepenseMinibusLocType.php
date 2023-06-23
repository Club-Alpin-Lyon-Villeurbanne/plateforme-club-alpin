<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\OptionsResolver\OptionsResolver;

use App\Entity\NdfDepenseMinibusLoc;

class NdfDepenseMinibusLocType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('nbre_passager', IntegerType::class, [
                'label' => 'Nombre de passagers',
                'required' => FALSE,
            ])
            ->add('nbre_km', IntegerType::class, [
                'label' => 'Nombre de kms (aller/retour)',
                'required' => FALSE,
            ])
            ->add('cout_essence', MoneyType::class, [
                'label' => 'Coût essence',
                'required' => FALSE,
            ])
            ->add('prix_loc_km', MoneyType::class, [
                'label' => 'Prix de location par km',
                'required' => FALSE,
            ])
            ->add('frais_peage', MoneyType::class, [
                'label' => 'Frais de péage',
                'required' => FALSE,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => NdfDepenseMinibusLoc::class
        ]);
    }
}
