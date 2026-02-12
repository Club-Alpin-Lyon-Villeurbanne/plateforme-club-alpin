<?php

namespace App\Controller;

use App\Entity\Commission;
use App\Entity\User;
use App\Entity\UserAttr;
use App\Entity\Usertype;
use App\Repository\UserAttrRepository;
use App\Repository\UserRightRepository;
use App\Repository\UsertypeAttrRepository;
use App\Repository\UsertypeRepository;
use App\Security\SecurityConstants;
use App\Service\UserRightService;
use App\UserRights;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;

class UserRightController extends AbstractController
{
    public function __construct(
        protected EntityManagerInterface $manager,
        protected LoggerInterface $logger,
        protected UserRights $userRights,
        protected UserRightService $userRightService,
        protected bool $cleanInferiorRights = true,
    ) {
    }

    #[Route(path: '/gerer-responsabilites/{user}', name: 'user_right_manage', requirements: ['user' => '\d+'], methods: ['GET', 'POST'])]
    #[Template('user-attr/manage-attr.html.twig')]
    public function manageRights(
        User $user,
        UsertypeRepository $usertypeRepository,
    ): array {
        if (
            !$this->isGranted(SecurityConstants::ROLE_ADMIN)
            && !$this->userRights->allowed('user_giveright_1')
            && !$this->userRights->allowed('user_giveright_2')
            && !$this->userRights->allowed('user_giveright_3')
            && !$this->userRights->allowed('user_givepresidence')
            && !$this->userRights->allowed('comm_delier_encadrant')
            && !$this->userRights->allowed('comm_delier_responsable')
        ) {
            throw new AccessDeniedHttpException('Vos droits ne sont pas assez élevés pour accéder à cette page');
        }

        $commissions = $this->manager->getRepository(Commission::class)->findBy(['vis' => true], ['ordre' => 'asc']);
        $availableCommissions = array_filter(
            $commissions,
            fn (Commission $commission) => $this->userRights->allowedOnCommission('user_giveright_1', $commission)
                || $this->userRights->allowedOnCommission('user_giveright_3', $commission)
                || $this->userRights->allowedOnCommission('comm_lier_encadrant', $commission),
        );
        if ($this->isGranted(SecurityConstants::ROLE_ADMIN) || $this->userRights->allowed('user_givepresidence')) {
            $availableCommissions = $commissions;
        }

        return [
            'user' => $user,
            'user_rights' => $user->getAttributes(),
            'usertypes' => $usertypeRepository->findAllManageable(),
            'commissions' => $availableCommissions,
        ];
    }

    #[Route(path: '/ajouter-responsabilite/{user}', name: 'user_right_add', requirements: ['user' => '\d+'], methods: ['GET', 'POST'])]
    public function addRight(
        User $user,
        Request $request,
        UsertypeRepository $usertypeRepository,
        UserAttrRepository $userAttrRepository,
    ): RedirectResponse {
        if (!$this->isCsrfTokenValid('user_right_add', $request->request->get('csrf_token'))) {
            throw new BadRequestException('Jeton de validation invalide.');
        }

        $data = $request->request->all();
        $usertype = $this->manager->getRepository(Usertype::class)->find($data['id_usertype']);
        if (!$usertype instanceof Usertype || !$this->isCurrentUserAllowed($usertype)) {
            throw new AccessDeniedHttpException('Vous n\'avez pas le droit d\'accéder à cette fonctionnalité');
        }

        if ($usertype->getLimitedToComm() && !empty($data['commission'])) {
            $lowerResps = $usertypeRepository->findLowerResps($usertype->getHierarchie(), $usertype->getLimitedToComm());

            foreach ($data['commission'] as $commission) {
                $commissionCode = str_replace('commission:', '', $commission);
                $user->addAttribute($usertype, $commission, $data['description_user_attr']);

                // enlever les responsabilités inférieures
                if ($this->cleanInferiorRights) {
                    foreach ($lowerResps as $lowerResp) {
                        // on n'enlève pas "encadrant" si on ajoute "responsable de commission"
                        if (
                            UserAttr::RESPONSABLE_COMMISSION === $usertype->getCode()
                            && UserAttr::ENCADRANT === $lowerResp->getCode()
                        ) {
                            continue;
                        }
                        $userAttrRepository->deleteByUser($user, $lowerResp, $commissionCode);
                    }
                }
            }
        } elseif (!$usertype->getLimitedToComm()) {
            $user->addAttribute($usertype, null, $data['description_user_attr']);
        }
        $this->manager->persist($user);
        $this->manager->flush();

        return $this->redirectToRoute('user_right_manage', ['user' => $user->getId()]);
    }

    #[Route(path: '/retirer-responsabilite/{user}/{type}/{commission}', name: 'user_right_remove', requirements: ['user' => '\d+', 'type' => '[a-z0-9-_]+'], methods: ['GET', 'POST'])]
    public function userRemoveRight(
        User $user,
        #[MapEntity(mapping: ['type' => 'code'])]
        Usertype $type,
        #[MapEntity(mapping: ['commission' => 'code'])]
        ?Commission $commission = null,
    ): RedirectResponse {
        if (!$this->isCurrentUserAllowed($type, $commission)) {
            throw new AccessDeniedHttpException('Vous n\'avez pas le droit d\'accéder à cette fonctionnalité');
        }

        $this->removeRight($user, $type, $commission);

        return $this->redirectToRoute('user_right_manage', ['user' => $user->getId()]);
    }

    #[Route(path: '/retirer-responsabilite/auto/{type}/{commission}', name: 'user_right_auto_remove', requirements: ['type' => '[a-z0-9-_]+'], methods: ['GET', 'POST'])]
    public function autoRemoveRight(
        #[MapEntity(mapping: ['type' => 'code'])]
        Usertype $type,
        #[MapEntity(mapping: ['commission' => 'code'])]
        ?Commission $commission = null,
    ): RedirectResponse {
        if (!$this->getUser()) {
            throw new AccessDeniedHttpException('Seuls les adhérents connectés peuvent effectuer cette action');
        }

        /** @var User $user */
        $user = $this->getUser();
        if ($this->removeRight($user, $type, $commission)) {
            $this->addFlash('success', 'La responsabilité ' . $type->getTitle() . ' vous a bien été retirée' . ($commission ? ' pour la commission ' . $commission->getTitle() : '') . '.');
        } else {
            $this->addFlash('error', 'Vous n\'avez pas la responsabilité ' . $type->getTitle() . ' ' . ($commission ? 'pour la commission ' . $commission->getTitle() : '') . ', vous ne pouvez donc pas la retirer.');
        }

        return $this->redirectToRoute('my_profile');
    }

    #[Route('/admin-matrice-droits.html', name: 'admin_user_right_matrix', methods: ['GET', 'POST'], priority: 10)]
    #[Template('admin/user_right_matrix.html.twig')]
    public function matrix(
        Request $request,
        UsertypeRepository $usertypeRepository,
        UserRightRepository $userRightRepository,
        UsertypeAttrRepository $usertypeAttrRepository,
    ): array|RedirectResponse {
        $this->denyAccessUnlessGranted(SecurityConstants::ROLE_ADMIN);

        $usertypes = $usertypeRepository->findAllOrdered();
        $userrights = $userRightRepository->findAllOrdered();
        $attributions = $usertypeAttrRepository->findAllPairs();

        // Groupement des droits par parent
        $grouped_rights = [];
        foreach ($userrights as $right) {
            $parent = $right->getParent();
            $grouped_rights[$parent][] = $right;
        }

        if ('POST' === $request->getMethod()) {
            if (!$this->isCsrfTokenValid('right_matrix_update', $request->request->get('csrf_token'))) {
                throw new BadRequestException('Jeton de validation invalide.');
            }

            $data = $request->request->all();
            $usertypeAttrRepository->replaceAll($data['usertype_attr']);

            $this->addFlash('success', 'La matrice des droits a bien été sauvegardée.');

            return $this->redirectToRoute('admin_user_right_matrix');
        }

        return [
            'usertypes' => $usertypes,
            'userrights' => $userrights,
            'attributions' => $attributions,
            'grouped_rights' => $grouped_rights,
            'is_admin' => true,
            'admin_page' => 'admin_user_right_matrix',
        ];
    }

    private function removeRight(User $user, Usertype $type, ?Commission $commission = null): bool
    {
        $result = false;

        $userRight = $user->getAttribute($type->getCode(), $commission?->getCode() ?? null);
        if ($userRight instanceof UserAttr) {
            $this->manager->remove($userRight);
            $this->manager->flush();
            try {
                $this->userRightService->notify($userRight, 'suppression', $this->getUser());
            } catch (\Exception $exception) {
                $this->logger->error('Impossible de notifier le retrait d\'une responsabilité');
                $this->logger->error($exception->getMessage());
            }

            $result = true;
        }

        return $result;
    }

    private function isCurrentUserAllowed(Usertype $type, ?Commission $commission = null): bool
    {
        $allowed = false;

        if ($this->isGranted(SecurityConstants::ROLE_ADMIN)) {
            return true;
        }

        $params = '';
        if ($commission) {
            $params = 'commission:' . $commission->getCode();
        }

        switch ($type->getCode()) {
            case UserAttr::ENCADRANT:
            case UserAttr::STAGIAIRE:
                $allowed = $this->userRights->allowed('comm_lier_encadrant', $params) || $this->userRights->allowed('comm_delier_encadrant', $params);
                break;

            case UserAttr::COENCADRANT:
            case UserAttr::BENEVOLE:
            case UserAttr::REDACTEUR:
                $allowed = $this->userRights->allowed('user_giveright_1', $params);
                break;

            case UserAttr::RESPONSABLE_COMMISSION:
                $allowed = $this->userRights->allowed('user_giveright_3', $params) || $this->userRights->allowed('comm_delier_responsable', $params);
                break;

            case UserAttr::SALARIE:
                $allowed = $this->userRights->allowed('user_giveright_2');
                break;

            case UserAttr::PRESIDENT:
            case UserAttr::VICE_PRESIDENT:
                $allowed = $this->userRights->allowed('user_givepresidence');
                break;
        }

        return $allowed;
    }
}
