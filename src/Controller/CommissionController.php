<?php

namespace App\Controller;

use App\Entity\Commission;
use App\Entity\Evt;
use App\Entity\UserAttr;
use App\Helper\EventFormHelper;
use App\Helper\MonthHelper;
use App\Legacy\LegacyContainer;
use App\Mailer\Mailer;
use App\Repository\CommissionRepository;
use App\Repository\EvtRepository;
use App\Repository\UserAttrRepository;
use App\UserRights;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class CommissionController extends AbstractController
{
    #[Route('/encadrement-par-commission', name: 'participants_by_commission')]
    public function participantsByCommission(
        Request $request,
        ManagerRegistry $doctrine,
        FormFactoryInterface $formFactory,
        EventFormHelper $eventFormHelper,
    ): Response {
        $commissionId = $request->query->get('commission');
        $commission = $doctrine->getRepository(Commission::class)->find($commissionId);

        $builder = $formFactory->createBuilder();
        $builder = $eventFormHelper->encadrementFields($builder, $commission);

        return $this->render('form/field_participants.html.twig', [
            'form' => $builder->getForm()->createView(),
        ]);
    }

    #[Route('/champs-parametrables-par-commission', name: 'specific_fields_by_commission')]
    public function fieldsByCommission(
        Request $request,
        ManagerRegistry $doctrine,
        FormFactoryInterface $formFactory,
        EventFormHelper $eventFormHelper,
    ): Response {
        $commissionId = $request->query->get('commission');
        $commission = $doctrine->getRepository(Commission::class)->find($commissionId);

        $builder = $formFactory->createBuilder();
        $builder = $eventFormHelper->specificMandatoryFields($builder, $commission);

        return $this->render('form/commission_specific_fields.html.twig', [
            'form' => $builder->getForm()->createView(),
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

    #[Route('/commissions', name: 'commission_index')]
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

    #[Route('/commissions/{id}/configuration', name: 'commission_configuration', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    #[Template('commission/configuration.html.twig')]
    public function configuration(
        Commission $commission,
        Request $request,
        EntityManagerInterface $entityManager,
        UserAttrRepository $userAttrRepository,
        Mailer $mailer,
    ): array|RedirectResponse {
        if (!$this->isGranted('COMMISSION_CONFIG', $commission)) {
            throw new AccessDeniedHttpException('Not allowed');
        }

        $configurableFields = Commission::CONFIGURABLE_FIELDS;

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

            $commission->setMandatoryFields($commissionMandatoryFields);
            $entityManager->persist($commission);
            $entityManager->flush();

            $this->addFlash('success', 'Les champs obligatoires ont bien été enregistrés pour ' . $commission->getTitle() . '.');

            // prévenir les resp. de comm
            $responsables = $userAttrRepository->getResponsablesByCommission($commission);
            /** @var UserAttr $responsable */
            foreach ($responsables as $responsable) {
                if ($this->getUser() !== $responsable->getUser()) {
                    $mailer->send(
                        $responsable->getUser(),
                        'transactional/commission-champs-obligatoires-modifies',
                        [
                            'commission' => $commission->getTitle(),
                            'user' => $this->getUser()->getFullName(),
                            'profile_url' => LegacyContainer::get('legacy_router')->generate('legacy_root', [], UrlGeneratorInterface::ABSOLUTE_URL) . 'user-full/' . $this->getUser()->getId() . '.html',
                        ]
                    );
                }
            }

            return $this->redirectToRoute('commission_configuration', ['id' => $commission->getId()]);
        }

        return [
            'commission' => $commission,
            'checked_fields' => $commission->getMandatoryFields(),
            'fields' => $configurableFields,
        ];
    }
}
