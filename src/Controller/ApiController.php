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
    public function getNdfDemandes(NdfDemandeRepository $repository)
    {
        $demandes = $repository->findAll();

        $formatted = [];
        foreach ($demandes as $demande) {
            $formatted[] = [
               'id' => $demande->getId(),
               'demandeur' => $demande->getDemandeur()->getFirstName()." ".$demande->getDemandeur()->getLastName(),
               'sortie' => $demande->getSortie()->getTitre(),
               'remboursement' => $demande->getRemboursement(),
               'statut' => $demande->getStatut(),
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

        $sorties = $repository->findBy([],[], $limit, ($page-1)*$limit);
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
        $view = $this->view($formatted, 200);
        $view->setFormat('json');

        return $this->handleView($view);
    }


}
