<?php

namespace App\Controller;

use App\Entity\Commission;
use App\Service\ParticipantService;
use Doctrine\Persistence\ManagerRegistry;
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
}
