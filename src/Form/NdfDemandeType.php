<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Form\NdfDepenseAutreType;
use App\Form\NdfDepenseCommunType;
use App\Form\NdfDepenseVoitureType;
use App\Form\NdfDepenseMinibusClubType;
use App\Form\NdfDepenseMinibusLocType;

class NdfDemandeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('remboursement', ChoiceType::class, [
                'label' => 'Remboursement',
                'choices' => [
                    'Je renonce au remboursement de cette note de frais' => FALSE,
                    'Je demande le remboursement de cette note de frais' => TRUE,
                ],
                'multiple' => FALSE,
                'expanded' => TRUE,
                'required' => TRUE,
            ])
            ->add('type_transport', ChoiceType::class, [
                'label' => 'Transport utilisé',
                'choices' => [
                    'Aucun' => NULL,
                    'Minibus du Club' => 'minibus_club',
                    'Minibus de location' => 'minibus_loc',
                    'Voiture personnelle' => 'voiture',
                    'Transport en commun' => 'commun'
                ],
                'multiple' => FALSE,
                'expanded' => TRUE,
                'required' => TRUE,
            ])
            ->add('depenses_minibus_club', CollectionType::class, [
                'entry_type' => NdfDepenseMinibusClubType::class,
                'allow_add' => TRUE,
                'allow_delete' => TRUE,
                'required' => FALSE,
                'mapped' => TRUE,
            ])
            ->add('depenses_minibus_loc', CollectionType::class, [
                'entry_type' => NdfDepenseMinibusLocType::class,
                'allow_add' => TRUE,
                'allow_delete' => TRUE,
                'required' => FALSE,
                'mapped' => TRUE,
            ])
            ->add('depenses_voiture', CollectionType::class, [
                'entry_type' => NdfDepenseVoitureType::class,
                'allow_add' => TRUE,
                'allow_delete' => TRUE,
                'required' => FALSE,
                'mapped' => TRUE,
            ])
            ->add('depenses_commun', CollectionType::class, [
                'entry_type' => NdfDepenseCommunType::class,
                'allow_add' => TRUE,
                'allow_delete' => TRUE,
                'required' => FALSE,
                'mapped' => TRUE,
            ])
            ->add('depenses_hebergement', CollectionType::class, [
                'entry_type' => NdfDepenseHebergementType::class,
                'allow_add' => TRUE,
                'allow_delete' => TRUE,
                'required' => FALSE,
                'mapped' => TRUE,
            ])
            ->add('depenses_autre', CollectionType::class, [
                'entry_type' => NdfDepenseAutreType::class,
                'allow_add' => TRUE,
                'allow_delete' => TRUE,
                'required' => FALSE,
                'mapped' => TRUE,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => NdfDemande::class
        ]);
    }
}
