<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use AppBundle\Entity\TbReference;
use Symfony\Component\HttpFoundation\JsonResponse;
use AppBundle\Controller\AddSyncController;
use AppBundle\Controller\AddNoticeController;

header('Content-Type: text/html; charset=utf-8');

/**
 * Description of ReferenciasController
 *
 * @author Marilia
 */
class ReferenciasController extends Controller {

    public $em;
    public $logControle ;

    public function __construct() {
          $this->logControle= new LogController(); 
    }

  

    /**
     * @Route("/referencias")
     */
    public function referencias() {

        $this->em = $this->getDoctrine()->getEntityManager();
          $this->logControle->logWeb("----referencias-----");

        $queryBuilder = $this->em->createQueryBuilder();
        $queryBuilder
                ->select('r, ac')
                ->from('AppBundle:TbReference', 'r')
                ->innerJoin('r.idActivityStudent', 'ac', 'WITH', 'ac.idActivityStudent = r.idActivityStudent')
//                ->Where($queryBuilder->expr()->eq('d.idUser', $destino))
//                ->andWhere($queryBuilder->expr()->isNull('d.dtLogout'))
                ->getQuery()
                ->execute();
 
          $this->logControle->logWeb($queryBuilder);
        $results = $queryBuilder->getQuery()->getArrayResult();
         $this->logControle->logWeb(print_r($results, true));
          $this->logControle->logWeb("selecionando referencias: " . print_r($results, true));
       $totalItens = count($results);
        if ($totalItens >0){
        foreach ($results as $ref) {
            $referencias[] = array(
                'ds_url' => $ref['dsUrl'],
                'id_activity_student' => $ref['idActivityStudent']['idActivityStudent'],
                'id_reference_src' => $ref['idReferenceSrv']
            );
        }
       
        }else{
           $referencias=array(); 
        }
        
        return $this->render('referencias.html.twig', array('referencias' => $referencias));
    }

    /**
     * @Route("/salvaReferencia")
     */
    public function salvaReferencia() {
        $resp = array();
        $referencia = $_POST['referencia'];
        $idActivityStudent = $_POST['idActivityStudent'];
        $this->em = $this->getDoctrine()->getEntityManager();
        if (!empty($referencia) && (!empty($idActivityStudent))) {

            $objact = $this->getDoctrine()
                    ->getRepository('AppBundle:TbActivityStudent')
                    ->findOneBy(array('idActivityStudent' => $idActivityStudent));
            $objRef = new TbReference();
              $this->logControle->logWeb("depois");
            $objRef->setDsUrl($referencia);
            $objRef->setIdActivityStudent($objact);

            $this->em->persist($objRef);
            $idRef = $objRef->getIdReference();
              $this->logControle->logWeb($idRef);
            //   $this->logControle->logWeb(print_r($objRef, true));
            $objRef->setIdReferenceSrv($idRef);
            $this->em->flush();
            $resp = array(
                'id_reference_srv' => $idRef
            );
            $idUser  = $this->get('session')->get('idUser');

            $nm_table = "tb_reference";
            $resultSync = (AddSyncWebController::addSync($idRef, $idUser, $nm_table, $idActivityStudent));
            AddNoticeControllerWeb::addNoticeWeb($idRef, $idRef, $idActivityStudent, $nm_table, $idUser);
        } else {
            $resp = array();
        }
        return new JsonResponse($resp);
    }

}
