<?php

namespace App\Controller;

use App\Entity\Commission;
use App\Entity\Evt;
use App\Helper\MonthHelper;
use App\Repository\EvtRepository;
use App\Service\ParticipantService;
use App\UserRights;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CommissionController extends AbstractController
{
    #[Route('/encadrement-par-commission', name: 'participants_by_commission')]
    public function participantsByCommission(
        Request $request,
        ManagerRegistry $doctrine,
        ParticipantService $participantService,
        FormFactoryInterface $formFactory
    ): Response {
        $commissionId = $request->query->get('commission');
        $commission = $doctrine->getRepository(Commission::class)->find($commissionId);
        $participantService->buildManagersLists($commission, null);

        $form = $formFactory->createBuilder()
            ->add('encadrants', ChoiceType::class, [
                'label' => false,
                'choices' => array_flip($participantService->getEncadrants()),
                'mapped' => false,
                'multiple' => true,
                'expanded' => true,
            ])
            ->add('coencadrants', ChoiceType::class, [
                'label' => false,
                'choices' => array_flip($participantService->getCoencadrants()),
                'mapped' => false,
                'multiple' => true,
                'expanded' => true,
            ])
            ->add('initiateurs', ChoiceType::class, [
                'label' => false,
                'choices' => array_flip($participantService->getInitiateurs()),
                'mapped' => false,
                'multiple' => true,
                'expanded' => true,
            ])
            ->add('benevoles', ChoiceType::class, [
                'label' => false,
                'choices' => array_flip($participantService->getBenevoles()),
                'mapped' => false,
                'multiple' => true,
                'expanded' => true,
            ])
            ->getForm()
        ;

        return $this->render('form/field_participants.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/sorties-par-commission', name: 'sorties_by_commission')]
    public function eventsByCommission(
        Request $request,
        ManagerRegistry $doctrine,
        EvtRepository $eventRepository,
        UserRights $userRights,
        MonthHelper $monthHelper,
        FormFactoryInterface $formFactory
    ): Response {
        $commissionId = $request->query->get('commission');
        $commission = $doctrine->getRepository(Commission::class)->find($commissionId);

        $form = $formFactory->createBuilder()
            ->add('evt', EntityType::class, [
                'class' => Evt::class,
                'choices' => array_filter(
                    $eventRepository->getRecentPastEvents($commission),
                    fn (Evt $event) => $userRights->allowedOnCommission('evt_create', $event->getCommission())
                ),
                'choice_label' => function (Evt $evt) use ($monthHelper) {
                    return date('d', $evt->getTsp()) . ' ' .
                       $monthHelper->getMonthName(date('m', $evt->getTsp())) . ' ' .
                       date('Y', $evt->getTsp()) . ' | ' .
                       $evt->getCommission()->getTitle() . ' | ' .
                       $evt->getTitre()
                    ;
                },
                'placeholder' => 'Sélectionner',
                'required' => true,
                'label' => 'Lier cet article à une sortie',
                'attr' => [
                    'class' => 'type1 wide',
                    'style' => 'width: 95%',
                ],
                'help' => 'Champ obligatoire pour un compte rendu de sortie.',
                'help_attr' => [
                    'class' => 'mini',
                ],
            ])
            ->getForm()
        ;

        return $this->render('form/field_events.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
