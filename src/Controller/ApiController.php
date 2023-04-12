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

}
