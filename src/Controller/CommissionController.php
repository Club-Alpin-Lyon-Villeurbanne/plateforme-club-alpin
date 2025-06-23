<?php

namespace App\Controller;

use App\Entity\Commission;
use App\Entity\Groupe;
use App\Service\ParticipantService;
use Doctrine\ORM\EntityRepository;
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
    #[Route('/groupes-par-commission', name: 'groups_by_commission')]
    public function groupsByCommission(
        Request $request,
        ManagerRegistry $doctrine,
        FormFactoryInterface $formFactory
    ): Response {
        $commissionId = $request->query->get('commission');
        $commission = $doctrine->getRepository(Commission::class)->find($commissionId);

        $form = $formFactory->createBuilder()
            ->add('groupe', EntityType::class, [
                'class' => Groupe::class,
                'query_builder' => function (EntityRepository $er) use ($commission) {
                    return $er->createQueryBuilder('g')
                              ->where('g.actif = 1')
                              ->andWhere('g.idCommission = :commission')
                              ->setParameters(['commission' => $commission])
                              ->orderBy('g.nom', 'ASC')
                    ;
                },
                'label' => 'Groupe concerné par cette sortie',
                'required' => false,
                'attr' => [
                    'class' => 'type1 wide',
                ],
            ])
            ->getForm()
        ;

        return $this->render('form/field_group.html.twig', [
            'form' => $form->createView(),
        ]);
    }

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
