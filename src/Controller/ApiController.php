<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

use App\Entity\NdfDemande;
use App\Repository\NdfDemandeRepository;

use App\Utils\NoteDeFrais;
use App\Utils\ApiKeywords;

use App\Entity\Evt;
use App\Repository\EvtRepository;


/**
 * @Route("/api")
 */
class ApiController extends AbstractController
{

    public function __construct()
    {
    }



    /**
     * @Route(
     *     name="api_ndf_list",
     *     path="/ndf",
     *     methods={"GET"}
     * )
     */


    public function getNdfDemandes(NdfDemandeRepository $repository, Request $request)
    {
        $page = null !== $request->get('page') ? $request->get('page') : 1;
        $limit = null !== $request->get('limit') ? $request->get('limit') : 50;

        $filters = $request->get('filters')??[];
        $filtersRequest = [];
        $addDetails = false;

        if(array_key_exists('sortie', $filters) && preg_match('/^[0-9]+/',$filters['sortie'])) {
            $filtersRequest['sortie'] = $filters['sortie'];
        }
        if(array_key_exists('details', $filters) && $filters['details'] == 1) {
            $addDetails = true;
        }


        $demandes = $repository->findBy($filtersRequest,['id'=>'DESC'], $limit, ($page-1)*$limit);

        $formatted = [];
        foreach ($demandes as $demande) {

            $depensesDetails = 'Ajoutez le filter "details" pour en savoir plus';
            
            //On va aller chercher les infos des ndfs
            if($addDetails) {
                $depensesHebergements = $demande->getNdfDepensesHebergement();
                $depenseHebergement = [];

                foreach ($depensesHebergements as $depense) {
                    $depenseHebergement[] = [
                        ApiKeywords::ORDRE => $depense->getOrdre(),
                        ApiKeywords::MONTANT => $depense->getMontant(),
                        ApiKeywords::COMMENT => $depense->getCommentaire()
                    ];
                }

                //depenseAutre
                $depensesAutres = $demande->getNdfDepensesAutre();
                $depenseAutre = [];
                foreach ($depensesAutres as $depense) {
                    $depenseAutre[] = [
                        ApiKeywords::ORDRE => $depense->getOrdre(),
                        ApiKeywords::MONTANT => $depense->getMontant(),
                        ApiKeywords::COMMENT => $depense->getCommentaire()
                    ];
                }

                switch ($demande->getTypeTransport()) {
                    case 'voiture':
                        $depensesVoiture = $demande->getNdfDepensesVoiture();
                        $depenseTransport = [
                            ApiKeywords::TYPE => 'voiture'
                        ];
                        foreach ($depensesVoiture as $depense) {
                            $depenseTransport[] = [
                                ApiKeywords::NBRE_KMS => $depense->getNbreKm(),
                                ApiKeywords::FRAIS_PEAGE => $depense->getFraisPeage(),
                                ApiKeywords::COMMENT => $depense->getCommentaire()
                            ];
                        }
                        break;
                    case 'minibus_loc':
                        $depensesMinibusLoc = $demande->getNdfDepensesMinibusLoc();
                        $depenseTransport = [
                            ApiKeywords::TYPE => 'minibus_loc'
                        ];
                        foreach ($depensesMinibusLoc as $depense) {
                            $depenseTransport[] = [
                                ApiKeywords::NBRE_KMS => $depense->getNbreKm(),
                                ApiKeywords::PRIX_LOC_KM => $depense->getPrixLocKm(),
                                ApiKeywords::FRAIS_PEAGE => $depense->getFraisPeage(),
                                ApiKeywords::ESSENCE => $depense->getCoutEssence(),
                                ApiKeywords::NBRE_PASSAGER => $depense->getNbrePassager()
                            ];
                        }
                        break;
                    case 'minibus_club':
                        $depensesMinibusClub = $demande->getNdfDepensesMinibusClub();
                        $depenseTransport = [
                            ApiKeywords::TYPE => 'minibus_club'
                        ];
                        foreach ($depensesMinibusClub as $depense) {
                            $depenseTransport[] = [
                                ApiKeywords::NBRE_KMS => $depense->getNbreKm(),
                                ApiKeywords::FRAIS_PEAGE => $depense->getFraisPeage(),
                                ApiKeywords::ESSENCE => $depense->getCoutEssence(),
                                ApiKeywords::NBRE_PASSAGER => $depense->getNbrePassager()
                            ];
                        }
                        break;
                    case 'commun':
                        //depenseCommun
                        $depensesCommuns = $demande->getNdfDepensesCommun();
                        $depenseTransport = [
                            ApiKeywords::TYPE => 'commun'
                        ];
                        foreach ($depensesCommuns as $depense) {
                            $depenseTransport[] = [
                                ApiKeywords::ORDRE => $depense->getOrdre(),
                                ApiKeywords::MONTANT => $depense->getMontant(),
                                ApiKeywords::COMMENT => $depense->getCommentaire()
                            ];
                        }
                        break;
                    default:
                        //pas de transport
                        $depenseTransport = [];
                }
                $depensesDetails = [
                    ApiKeywords::DEPENSE_TRANSPORT => $depenseTransport,
                    ApiKeywords::DEPENSE_HEBERGEMENT => $depenseHebergement,
                    ApiKeywords::DEPENSE_AUTRE => $depenseAutre
               ];
            }

            $formatted[] = [
               ApiKeywords::ID => $demande->getId(),
               ApiKeywords::DEMANDEUR => $demande->getDemandeur()->getFirstName()." ".$demande->getDemandeur()->getLastName(),
               ApiKeywords::SORTIE => [
                    ApiKeywords::ID => $demande->getSortie()->getId(),
                    ApiKeywords::TITRE => $demande->getSortie()->getTitre()
               ],
               ApiKeywords::REMBOURSEMENT => $demande->getRemboursement(),
               ApiKeywords::TAUX_REMBOURSEMENT_KM => NoteDeFrais::getTauxKms(new \DateTime('@' . $demande->getSortie()->getTsp())),
               ApiKeywords::PLAFOND_REMBOURSEMENT_HEBERGEMENT => NoteDeFrais::getPlafondHebergement(new \DateTime('@' . $demande->getSortie()->getTsp())),
               ApiKeywords::STATUT => $demande->getStatut(),
               ApiKeywords::DEPENSES => $depensesDetails
            ];
        }
        $data = [ApiKeywords::DATA => $formatted];
        return $this->json($data, 200);
       
    }


    /**
     * @Route(
     *     name="api_ndf",
     *     path="/ndf/{id}",
     *     methods={"GET"}
     * )
     */
    public function getNdfDemande($id, NdfDemandeRepository $repository)
    {
        $demande = $repository->findOneById($id);

        if(!empty($demande)) {
            $formatted = [
               ApiKeywords::ID => $demande->getId(),
               ApiKeywords::DEMANDEUR => $demande->getDemandeur()->getFirstName()." ".$demande->getDemandeur()->getLastName(),
               ApiKeywords::SORTIE => $demande->getSortie()->getTitre(),
               ApiKeywords::REMBOURSEMENT => $demande->getRemboursement(),
               ApiKeywords::STATUT => $demande->getStatut(),
            ];

            $data = [ApiKeywords::DATA => $formatted];

            $view = $this->json($data, 200);
        } else {
            $view = $this->json([],404);
        }

        return $view;
    }

    /**
     * @Route(
     *     name="api_sorties",
     *     path="/sorties",
     *     methods={"GET"}
     * )
     */
    public function getSorties(EvtRepository $repository, Request $request)
    {
        $page = null !== $request->get('page') ? $request->get('page') : 1;
        $limit = null !== $request->get('limit') ? $request->get('limit') : 50;

        $filters = $request->get('filters')??[];
        $filtersRequest = [];

        if(array_key_exists('statut_ndf', $filters) && in_array($filters['statut_ndf'], ["en_attente", "complete", "valide", "non_applicable"])) {
            $filtersRequest['ndfStatut'] = $filters['statut_ndf'];
        }

        $sorties = $repository->findBy($filtersRequest,['id'=>'DESC'], $limit, ($page-1)*$limit);
        $formatted = [];
        foreach ($sorties as $sortie) {
            $formatted[] = [
                ApiKeywords::ID => $sortie->getId(),
                ApiKeywords::COMMISSION => $sortie->getCommission()->getTitle(),
                ApiKeywords::TITRE => $sortie->getTitre(),
                ApiKeywords::DATE_DEBUT => $sortie->getTsp(),
                ApiKeywords::DATE_FIN => $sortie->getTspEnd(),
                ApiKeywords::STATUT => $sortie->getNdfStatut(),
                ApiKeywords::LIEU => $sortie->getPlace(),
                ApiKeywords::NBRE_PARTICIPANTS => count($sortie->getParticipants())
            ];
        
        }
        $data = [ApiKeywords::DATA => $formatted];
        return $this->json($data, 200);
    }

    /**
     * @Route(
     *     name="api_ndf_modify",
     *     path="/ndf",
     *     methods={"PATCH"}
     * )
     */
    public function modifyNdf($id, NdfDemandeRepository $repository)
    {
        return $this->json([], 501);
    }
}
