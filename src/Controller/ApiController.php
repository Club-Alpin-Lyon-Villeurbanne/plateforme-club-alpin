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

use App\Utils\Ndf;

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
                        'ordre' => $depense->getOrdre(),
                        'montant' => $depense->getMontant(),
                        'commentaire' => $depense->getCommentaire()
                    ];
                }

                //depenseAutre
                $depensesAutres = $demande->getNdfDepensesAutre();
                $depenseAutre = [];
                foreach ($depensesAutres as $depense) {
                    $depenseAutre[] = [
                        'ordre' => $depense->getOrdre(),
                        'montant' => $depense->getMontant(),
                        'commentaire' => $depense->getCommentaire()
                    ];
                }

                switch ($demande->getTypeTransport()) {
                    case 'voiture':
                        $depensesVoiture = $demande->getNdfDepensesVoiture();
                        $depenseTransport = [
                            'type' => 'voiture'
                        ];
                        foreach ($depensesVoiture as $depense) {
                            $depenseTransport[] = [
                                'nbre_kms' => $depense->getNbreKm(),
                                'frais_peage' => $depense->getFraisPeage(),
                                'commentaire' => $depense->getCommentaire()
                            ];
                        }
                        break;
                    case 'minibus_loc':
                        $depensesMinibusLoc = $demande->getNdfDepensesMinibusLoc();
                        $depenseTransport = [
                            'type' => 'minibus_loc'
                        ];
                        foreach ($depensesMinibusLoc as $depense) {
                            $depenseTransport[] = [
                                'nbre_kms' => $depense->getNbreKm(),
                                'prix_loc_km' => $depense->getPrixLocKm(),
                                'frais_peage' => $depense->getFraisPeage(),
                                'cout_essence' => $depense->getCoutEssence(),
                                'nbre_passager' => $depense->getNbrePassager()
                            ];
                        }
                        break;
                    case 'minibus_club':
                        $depensesMinibusClub = $demande->getNdfDepensesMinibusClub();
                        $depenseTransport = [
                            'type' => 'minibus_club'
                        ];
                        foreach ($depensesMinibusClub as $depense) {
                            $depenseTransport[] = [
                                'nbre_kms' => $depense->getNbreKm(),
                                'frais_peage' => $depense->getFraisPeage(),
                                'cout_essence' => $depense->getCoutEssence(),
                                'nbre_passager' => $depense->getNbrePassager()
                            ];
                        }
                        break;
                    case 'commun':
                        //depenseCommun
                        $depensesCommuns = $demande->getNdfDepensesCommun();
                        $depenseTransport = [
                            'type' => 'commun'
                        ];
                        foreach ($depensesCommuns as $depense) {
                            $depenseTransport[] = [
                                'ordre' => $depense->getOrdre(),
                                'montant' => $depense->getMontant(),
                                'commentaire' => $depense->getCommentaire()
                            ];
                        }
                        break;
                    default:
                        //pas de transport
                        $depenseTransport = [];
                }
                $depensesDetails = [
                    'depense_transport' => $depenseTransport,
                    'depense_hebergement' => $depenseHebergement,
                    'depense_autre' => $depenseAutre
               ];
            }

            $formatted[] = [
               'id' => $demande->getId(),
               'demandeur' => $demande->getDemandeur()->getFirstName()." ".$demande->getDemandeur()->getLastName(),
               'sortie' => [
                    'id' => $demande->getSortie()->getId(),
                    'titre' => $demande->getSortie()->getTitre()
               ],
               'remboursement' => $demande->getRemboursement(),
               'taux_remboursement_kms' => Ndf::getTauxKms(new \DateTime('@' . $demande->getSortie()->getTsp())),
               'plafond_remboursement_hebergement' => Ndf::getPlafondHebergement(new \DateTime('@' . $demande->getSortie()->getTsp())),
               'statut' => $demande->getStatut(),
               'depenses' => $depensesDetails
            ];
        }
        $data = ['data' => $formatted];
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
               'id' => $demande->getId(),
               'demandeur' => $demande->getDemandeur()->getFirstName()." ".$demande->getDemandeur()->getLastName(),
               'sortie' => $demande->getSortie()->getTitre(),
               'remboursement' => $demande->getRemboursement(),
               'statut' => $demande->getStatut(),
            ];

            $data = ['data' => $formatted];

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
                'id' => $sortie->getId(),
                'commission' => $sortie->getCommission()->getTitle(),
                'titre' => $sortie->getTitre(),
                'date_debut' => $sortie->getTsp(),
                'date_fin' => $sortie->getTspEnd(),
                'statut_ndf' => $sortie->getNdfStatut(),
                'lieu' => $sortie->getPlace(),
                'nb_participants' => count($sortie->getParticipants())
            ];
        
        }
        $data = ['data' => $formatted];
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

    }
}
