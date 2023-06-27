<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\ViewHandler;
use FOS\RestBundle\View\View;

use App\Entity\NdfDemande;
use App\Repository\NdfDemandeRepository;

use App\Entity\Evt;
use App\Repository\EvtRepository;


/**
 * @Route("/api")
 */
class ApiController extends AbstractFOSRestController
{

    public function __construct()
    {
    }

    /**
     * @Rest\Get("/ndf")
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
            
            //On va aller chercher les infos des ndfs
            if($addDetails) {
                $depensesHebergements = $demande->getNdfDepenseHebergement();
                $depenseHebergement = [];
                foreach ($depensesHebergements as $depense) {
                    $depenseHebergement[] = [
                        'ordre' => $depense->getOrdre(),
                        'montant' => $depense->getMontant(),
                        'commentaire' => $depense->getCommentaire()
                    ];
                }

                //depenseAutre
                $depensesAutres = $demande->getNdfDepenseAutre();
                $depenseAutre = [];
                foreach ($depensesAutres as $depense) {
                    $depenseAutre[] = [
                        'ordre' => $depense->getOrdre(),
                        'montant' => $depense->getMontant(),
                        'commentaire' => $depense->getCommentaire()
                    ];
                }

                switch ($demande->typeTransport) {
                    case 'voiture':
                        $depensesVoiture = $demande->getNdfDepenseVoiture();
                        $depenseTransport = [
                            'type' => 'voiture',
                            'nbre_kms' => $depensesVoiture->getNbreKm(),
                            'frais_peage' => $depensesVoiture->getFraisPeage(),
                            'commentaire' => $depensesVoiture->getCommentaire()
                        ];
                        break;
                    case 'minibus_loc':
                        $depensesMinibusLoc = $demande->getNdfDepenseMinibusLoc();
                        $depenseTransport = [
                            'type' => 'minibus_loc',
                            'nbre_kms' => $depensesMinibusLoc->getNbreKm(),
                            'prix_loc_km' => $depensesMinibusLoc->getPrixLocKm(),
                            'frais_peage' => $depensesMinibusLoc->getFraisPeage(),
                            'cout_essence' => $depensesMinibusLoc->getCoutEssence(),
                            'nbre_passager' => $depensesMinibusLoc->getNbrePassager()
                        ];
                        break;
                    case 'minibus_club':
                        $depensesMinibusClub = $demande->getNdfDepenseMinibusClub();
                        $depenseTransport = [
                            'type' => 'minibus_club',
                            'nbre_kms' => $depensesMinibusClub->getNbreKm(),
                            'frais_peage' => $depensesMinibusClub->getFraisPeage(),
                            'cout_essence' => $depensesMinibusClub->getCoutEssence(),
                            'nbre_passager' => $depensesMinibusClub->getNbrePassager()
                        ];
                        break;
                    case 'commun':
                        //depenseCommun
                        $depensesCommuns = $demande->getNdfDepenseCommun();
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
            }

            $formatted[] = [
               'id' => $demande->getId(),
               'demandeur' => $demande->getDemandeur()->getFirstName()." ".$demande->getDemandeur()->getLastName(),
               'sortie' => $demande->getSortie()->getTitre(),
               'remboursement' => $demande->getRemboursement(),
               'statut' => $demande->getStatut(),
               'depenses' => [
                    'depense_transport' => $depenseTransport,
                    'depense_hebergement' => $depenseHebergement,
                    'depense_autre' => $depenseAutre
               ]
            ];
        }

        $view = $this->view($formatted, 200);
        $view->setFormat('json');

        return $this->handleView($view);
    }


    /**
     * @Rest\Get("/ndf/{id}")
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

            $view = $this->view($formatted, 200);
        } else {
            $view = $this->view(404);
        }
        
        $view->setFormat('json');

        return $this->handleView($view);
    }

    /**
     * @Rest\Get("/sorties")
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
        $view = $this->view($data, 200);
        $view->setFormat('json');

        return $this->handleView($view);
    }


}
