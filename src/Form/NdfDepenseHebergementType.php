<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\OptionsResolver\OptionsResolver;

use App\Entity\NdfDepenseHebergement;

class NdfDepenseHebergementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('date', TextType::class, [
                'label' => 'Nuit du',
                'required' => TRUE,
            ])
            ->add('montant', MoneyType::class, [
                'label' => 'Montant',
                'required' => TRUE,
            ])
            ->add('commentaire', TextType::class, [
                'label' => 'Descriptif',
                'required' => FALSE,
            ])
            ->add('ordre', HiddenType::class, [
                'data' => 0,
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
