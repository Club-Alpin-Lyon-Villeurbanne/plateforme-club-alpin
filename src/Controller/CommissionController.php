<?php

namespace App\Controller;

use App\Entity\Commission;
use App\Entity\Evt;
use App\Helper\MonthHelper;
use App\Repository\CommissionRepository;
use App\Repository\EvtRepository;
use App\Service\ParticipantService;
use App\UserRights;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints\Length;

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

    #[Route('/champs-parametrables-par-commission', name: 'specific_fields_by_commission')]
    public function fieldsByCommission(
        Request $request,
        ManagerRegistry $doctrine,
        FormFactoryInterface $formFactory
    ): Response {
        $mandatoryFields = [];
        $commissionId = $request->query->get('commission');
        $commission = $doctrine->getRepository(Commission::class)->find($commissionId);
        if ($commission instanceof Commission) {
            $mandatoryFields = explode(',', $commission->getMandatoryFields());
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

        $form = $formFactory->createBuilder()
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
            ->getForm()
        ;

        return $this->render('form/commission_specific_fields.html.twig', [
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
                    fn (Evt $event) => ($userRights->allowedOnCommission('article_create', $event->getCommission()) || $userRights->allowedOnCommission('evt_create', $event->getCommission()))
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
                'required' => false,
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

    #[Route('/commissions/liste', name: 'commission_index')]
    #[Template('commission/index.html.twig')]
    public function index(UserRights $userRights, CommissionRepository $commissionRepository): array
    {
        if (!$userRights->allowed('commission_list')) {
            throw new AccessDeniedHttpException('Not allowed');
        }

        $myCommissionsCodes = $userRights->getCommissionListForRight('commission_config');

        return [
            'commissions' => $commissionRepository->findBy(['code' => $myCommissionsCodes], ['title' => 'ASC']),
        ];
    }

    #[Route('/commission/{id}/configuration', name: 'commission_configuration', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    #[Template('commission/configuration.html.twig')]
    public function configuration(Commission $commission, Request $request, EntityManagerInterface $entityManager): array|RedirectResponse
    {
        if (!$this->isGranted('COMMISSION_CONFIG', $commission)) {
            throw new AccessDeniedHttpException('Not allowed');
        }

        $configurableFields = explode(',', Evt::CONFIGURABLE_FIELDS);

        if ('POST' === $request->getMethod() && !$this->isCsrfTokenValid('commission_configuration', $request->request->get('csrf_token'))) {
            $this->addFlash('error', 'Jeton de validation invalide.');

            return $this->redirectToRoute('commission_configuration', ['id' => $commission->getId()]);
        }

        if ('POST' === $request->getMethod()) {
            $data = $request->request->all();

            $commissionMandatoryFields = [];
            foreach ($configurableFields as $fieldName) {
                if (isset($data[$fieldName]) && 'on' === $data[$fieldName]) {
                    $commissionMandatoryFields[] = $fieldName;
                }
            }

            $commission->setMandatoryFields($commissionMandatoryFields ? implode(',', $commissionMandatoryFields) : '');
            $entityManager->persist($commission);
            $entityManager->flush();

            $this->addFlash('success', 'Les champs obligatoires ont bien été enregistrés pour ' . $commission->getTitle() . '.');

            return $this->redirectToRoute('commission_configuration', ['id' => $commission->getId()]);
        }

        return [
            'commission' => $commission,
            'checked_fields' => explode(',', $commission->getMandatoryFields()),
            'fields' => $configurableFields,
        ];
    }
}
