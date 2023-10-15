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
use App\Form\NdfDepenseHebergementType;
use App\Entity\NdfDemande;

class NdfDemandeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('remboursement', ChoiceType::class, [
                'label' => 'Remboursement',
                'choices' => [
                    'Je fais don de cette note de frais au Club et recevrai en fin d\'année un reçu fiscal.' => false,
                    'Je demande le remboursement de cette note de frais.' => true,
                ],
                'multiple' => false,
                'expanded' => true,
                'required' => true
            ])
            ->add('type_transport', ChoiceType::class, [
                'label' => 'Transport utilisé',
                'choices' => [
                    'Aucun' => '',
                    'Minibus du Club' => 'minibus_club',
                    'Minibus de location' => 'minibus_loc',
                    'Voiture personnelle' => 'voiture',
                    'Transport en commun' => 'commun'
                ],
                'multiple' => false,
                'expanded' => false,
                'required' => true,
            ])
            ->add('ndf_depenses_minibus_club', CollectionType::class, [
                'label' => false,
                'entry_type' => NdfDepenseMinibusClubType::class,
                'allow_add' => true,
                'allow_delete' => false,
                'required' => false,
                'mapped' => true,
            ])
            ->add('ndf_depenses_minibus_loc', CollectionType::class, [
                'label' => false,
                'entry_type' => NdfDepenseMinibusLocType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'required' => false,
                'mapped' => true,
            ])
            ->add('ndf_depenses_voiture', CollectionType::class, [
                'label' => false,
                'entry_type' => NdfDepenseVoitureType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'required' => false,
                'mapped' => true,
            ])
            ->add('ndf_depenses_commun', CollectionType::class, [
                'label' => false,
                'entry_type' => NdfDepenseCommunType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'required' => false,
                'mapped' => true,
            ])
            ->add('ndf_depenses_hebergement', CollectionType::class, [
                'label' => 'Hébergement',
                'entry_type' => NdfDepenseHebergementType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'required' => false,
                'mapped' => true,
            ])
            ->add('ndf_depenses_autre', CollectionType::class, [
                'label' => 'Autre',
                'entry_type' => NdfDepenseAutreType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'required' => false,
                'mapped' => true,
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
