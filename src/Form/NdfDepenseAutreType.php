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

use App\Entity\NdfDepenseAutre;



class NdfDepenseAutreType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('montant', MoneyType::class, [
                'label' => 'Montant',
                'required' => FALSE,
            ])
            ->add('ordre', HiddenType::class, [
                'data' => 0,
                'required' => FALSE,
            ])
            ->add('commentaire', TextType::class, [
                'label' => 'Descriptif',
                'required' => FALSE,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => NdfDepenseAutre::class
        ));
    }
}
