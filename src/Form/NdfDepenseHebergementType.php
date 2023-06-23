<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\OptionsResolver\OptionsResolver;

use App\Entity\NdfDepenseHebergement;

class NdfDepenseHebergementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('nbre_km', IntegerType::class, [
                'label' => 'Nombre de kms (aller/retour)',
                'required' => FALSE,
            ])
            ->add('montant', MoneyType::class, [
                'label' => 'Montant',
                'required' => FALSE,
            ])
            ->add('commentaire', TextareaType::class, [
                'label' => 'Descriptif trajet',
                'required' => FALSE,
            ])
            ->add('ordre', HiddenType::class, [
                'label' => 'Ordre',
                'required' => FALSE,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => NdfDepenseHebergement::class
        ]);
    }
}
