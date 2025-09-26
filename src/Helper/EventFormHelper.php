<?php

namespace App\Helper;

use App\Entity\Commission;
use App\Service\ParticipantService;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Length;

class EventFormHelper
{
    public function __construct(protected ParticipantService $participantService)
    {
    }

    public function encadrementFields(FormBuilderInterface $builder, ?Commission $commission): FormBuilderInterface
    {
        $this->participantService->buildManagersLists($commission, null);

        $builder
            ->add('encadrants', ChoiceType::class, [
                'label' => false,
                'choices' => array_flip($this->participantService->getEncadrants()),
                'mapped' => false,
                'multiple' => true,
                'expanded' => true,
            ])
            ->add('coencadrants', ChoiceType::class, [
                'label' => false,
                'choices' => array_flip($this->participantService->getCoencadrants()),
                'mapped' => false,
                'multiple' => true,
                'expanded' => true,
            ])
            ->add('initiateurs', ChoiceType::class, [
                'label' => false,
                'choices' => array_flip($this->participantService->getInitiateurs()),
                'mapped' => false,
                'multiple' => true,
                'expanded' => true,
            ])
            ->add('benevoles', ChoiceType::class, [
                'label' => false,
                'choices' => array_flip($this->participantService->getBenevoles()),
                'mapped' => false,
                'multiple' => true,
                'expanded' => true,
            ])
        ;

        return $builder;
    }

    public function specificMandatoryFields(FormBuilderInterface $builder, ?Commission $commission): FormBuilderInterface
    {
        $mandatoryFields = [];
        if ($commission instanceof Commission) {
            $mandatoryFields = $commission->getMandatoryFields();
        }

        $difficulteRequired = false;
        $deniveleRequired = false;
        $distanceRequired = false;
        if (\in_array('difficulte', $mandatoryFields, true)) {
            $difficulteRequired = true;
        }
        if (\in_array('denivele', $mandatoryFields, true)) {
            $deniveleRequired = true;
        }
        if (\in_array('distance', $mandatoryFields, true)) {
            $distanceRequired = true;
        }

        $builder
            ->add('difficulte', TextType::class, [
                'label' => 'Difficulté, niveau',
                'required' => $difficulteRequired,
                'attr' => [
                    'placeholder' => 'ex : PD, 5d+, exposé, ...',
                    'maxlength' => 50,
                    'class' => 'type2',
                ],
                'constraints' => [
                    new Length([
                        'max' => 50,
                    ]),
                ],
            ])
            ->add('distance', TextType::class, [
                'label' => 'Distance',
                'required' => $distanceRequired,
                'attr' => [
                    'placeholder' => 'ex : 13.50',
                    'maxlength' => 50,
                    'class' => 'type2',
                ],
                'help' => 'km',
                'help_attr' => [
                    'class' => 'mini',
                ],
                'constraints' => [
                    new Length([
                        'max' => 50,
                    ]),
                ],
            ])
            ->add('denivele', TextType::class, [
                'label' => 'Dénivelé positif',
                'required' => $deniveleRequired,
                'attr' => [
                    'placeholder' => 'ex : 1200',
                    'maxlength' => 50,
                    'class' => 'type2',
                ],
                'help' => 'm',
                'help_attr' => [
                    'class' => 'mini',
                ],
                'constraints' => [
                    new Length([
                        'max' => 50,
                    ]),
                ],
            ])
        ;

        return $builder;
    }
}
