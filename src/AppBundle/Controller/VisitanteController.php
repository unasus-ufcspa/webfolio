<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Description of VisitanteController
 *
 * @author Marilia
 */
class VisitanteController extends Controller {

    public $logControle;
    public $em;

    public function __construct() {
        $this->logControle = new LogController();
    }

    public function verificarVisitante($idUser) {
        $this->em = $this->getDoctrine()->getEntityManager();
        $queryBuilderUserGuest = $this->em->createQueryBuilder();
        $queryBuilderUserGuest
                ->select('g,u,c')
                ->from('AppBundle:TbGuest', 'g')
                ->innerJoin('g.idUser', 'u', 'WITH', 'u.idUser = g.idUser')
                ->innerJoin('g.idClass', 'c', 'WITH', 'c.idClass = g.idClass')
                ->where($queryBuilderUserGuest->expr()->eq('g.idUser ', $idUser))
                ->getQuery()
                ->execute();

        $resultadoTbGuest = $queryBuilderUserGuest->getQuery()->getArrayResult();
        $this->logControle->log(">>Retorno TB GUEST : " . print_r($resultadoTbGuest, true));

        if (count($resultadoTbGuest) > 0) {
            return $resultadoTbGuest;
        } else {
            return 0;
        }
    }

    public function carregarUsuariosTurmas($visitante) {
        $this->logControle->logWeb("carregar usuarios turmas");
        $idUsuariosTurma = array();
        $idsPortfolioClass = VisitanteController::getIdPortfolioClassByClass($visitante['idClass']['idClass']);
        foreach ($idsPortfolioClass as $idPortfolioClass) {
            $resultadoPortfolioStudent = PortfolioStudentController::selecionarPortfolioStudentByPortfolioClass($idPortfolioClass['idPortfolioClass']);

            foreach ($resultadoPortfolioStudent as $valueArray) {
                if (!in_array($valueArray['idTutor']['idUser'], $idUsuariosTurma)) {
                    $idUsuariosTurma[] = $valueArray['idTutor']['idUser'];
                }
                if (!in_array($valueArray['idPortfolioStudent']['idStudent']['idUser'], $idUsuariosTurma)) {
                    $idUsuariosTurma[] = $valueArray['idPortfolioStudent']['idStudent']['idUser'];
                }
            }
        }
        $this->logControle->logWeb(print_r($idUsuariosTurma, true));
        return $idUsuariosTurma;
    }

    function getIdPortfolioClassByClass($idClass) {
        $this->em = $this->getDoctrine()->getEntityManager();
        $queryBuilderPortfolioClass = $this->em->createQueryBuilder();
        $queryBuilderPortfolioClass
                ->select('pc')
                ->from('AppBundle:TbPortfolioClass', 'pc')
                ->where($queryBuilderPortfolioClass->expr()->eq('pc.idClass ', $idClass))
                ->getQuery()
                ->execute();

        $idsPortfolioClass = $queryBuilderPortfolioClass->getQuery()->getArrayResult();
        $this->logControle->logWeb(">>Retorno portfolio class : " . print_r($idsPortfolioClass, true));

        return $idsPortfolioClass;
    }

    function verificarVisitantePortfolioStudent() {

        $this->em = $this->getDoctrine()->getEntityManager();
        $queryBuilderPortfolioStudent = $this->em->createQueryBuilder();
        $queryBuilderPortfolioStudent
                ->select('ps, u, ut')
                ->from('AppBundle:TbPortfolioClass', 'pc')
                ->innerJoin('pc.idPortfolioStudent', 'ps', 'WITH', 'ps.idPortfolioStudent = pc.idPortfolioStudent')
                ->innerJoin('pc.idClass', 'c', 'WITH', 'c.idClass = ps.idClass')
                ->innerJoin('g.idGuest', 'g', 'WITH', 'c.idClass = g.idClass')
                ->getQuery()
                ->execute();

        $resultadoPortfolioStudent = $queryBuilderPortfolioStudent->getQuery()->getArrayResult();

        $this->logControle->log(">>Retorno TB GUEST : " . print_r($resultadoPortfolioStudent, true));
    }

}
