<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use AppBundle\Entity\TbAnnotation;

/**
 * Description of AnnotationsController
 *
 * @author Marilia
 */
class AnnotationsController extends Controller {

    public $logControle;
    public $em;

    public function __construct() {
        $this->logControle = new LogController();
    }

    /**
     * @Route("/anotacoes")
     */
    public function anotacoes() {
        $idUser = $this->get('session')->get('idUser');
        if (empty($idUser)) {
            return $this->redirectToRoute('login');
        }
        $anotacoes = $this->selecionarAnotacoes($idUser);
        return $this->render('anotacoes.html.twig', array('referencias' => $anotacoes));
    }

    public function selecionarAnotacoes($idUser) {
        $this->em = $this->getDoctrine()->getEntityManager();


        $queryBuilder = $this->em->createQueryBuilder();
        $queryBuilder
                ->select('a,u')
                ->from('AppBundle:TbAnnotation', 'a')
                ->innerJoin('a.idUser', 'u', 'WITH', 'a.idUser = u.idUser')
                ->Where($queryBuilder->expr()->eq('a.idUser', $idUser))
                ->getQuery()
                ->execute();


        $results = $queryBuilder->getQuery()->getArrayResult();

        $totalItens = count($results);
        if ($totalItens > 0) {
            foreach ($results as $ref) {
                $anotacoes[] = array(
                    'ds_annotation' => $ref['dsAnnotation'],
                    'id_annotation_srv' => $ref['idAnnotationSrv']
                );
            }
        } else {
            $anotacoes = array();
        }
        return $anotacoes;
    }

    /**
     * @Route("/salvarAnotacao")
     */
    public function salvarAnotacao() {
        $resp = array();
        $referencia = $_POST['annotation'];
        $idUser = $this->get('session')->get('idUser');
        $this->em = $this->getDoctrine()->getEntityManager();
        if (!empty($referencia)) {
            $objetoUser = $this->getDoctrine()
                    ->getRepository('AppBundle:TbUser')
                    ->findOneBy(array('idUser' => $idUser));
            $objAnnotation = new TbAnnotation();
            $objAnnotation->setDsAnnotation($referencia);
            $objAnnotation->setIdUser($objetoUser);
            $this->em->persist($objAnnotation);
            $idAnnotation = $objAnnotation->getIdAnnotation();
            $objAnnotation->setIdAnnotationSrv($idAnnotation);
            $idSrv = $objAnnotation->getIdAnnotationSrv();
            $this->em->flush();
            $resp = array(
                'id_annotation_srv' => $idSrv
            );
            $nm_table = "tb_annotation";
            $resultSync = (AddSyncWebController::addSync($idSrv, $idUser, $nm_table, -1));
        }
        return new JsonResponse($resp);
    }

}
