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
use AppBundle\Entity\TbPortfolioStudent;
use AppBundle\Entity\TbTutorPortfolio;

/**
 * Description of PortfolioStudentController
 *
 * @author Marilia
 */
class PortfolioStudentController extends Controller {

    public $logControle;
    public $em;

    public function __construct() {
        $this->logControle = new LogController();
         $this->em = $this->getDoctrine()->getEntityManager();
    }

    function selecionarPortfolioStudent($idUser) {
        $this->em = $this->getDoctrine()->getEntityManager();

        $queryBuilder = $this->em->createQueryBuilder();
        $queryBuilder
                ->select('t, p, t, ut, u, pc')
                ->from('AppBundle:TbTutorPortfolio', 't')
                ->innerJoin('t.idPortfolioStudent', 'p', 'WITH', 't.idPortfolioStudent = p.idPortfolioStudent')
                ->innerJoin('p.idPortfolioClass', 'pc', 'WITH', 'pc.idPortfolioClass = p.idPortfolioClass')
                ->innerJoin('p.idStudent', 'u', 'WITH', 'u.idUser = p.idStudent')
                ->innerJoin('t.idTutor', 'ut')
                ->where($queryBuilder->expr()->eq('p.idStudent', $idUser))
                ->orWhere($queryBuilder->expr()->eq('t.idTutor', $idUser))
                ->getQuery()
                ->execute();
        $results = $queryBuilder->getQuery()->getArrayResult();
        $this->logControle->log("TbTutorPortfolio : " . print_r($results, true));

        return $results;
    }

    function selecionarPortfolioStudentByTutor($idUser) {
        $this->em = $this->getDoctrine()->getEntityManager();

        $queryBuilder = $this->em->createQueryBuilder();
        $queryBuilder
                ->select('t, ps, p, ut, u, pcl,c')
                ->from('AppBundle:TbTutorPortfolio', 't')
                ->innerJoin('t.idPortfolioStudent', 'ps', 'WITH', 't.idPortfolioStudent = ps.idPortfolioStudent')
                ->innerJoin('ps.idPortfolioClass', 'pcl', 'WITH', 'pcl.idPortfolioClass = ps.idPortfolioClass')
                ->innerJoin('pcl.idPortfolio', 'p', 'WITH', 'pcl.idPortfolio = p.idPortfolio')
                ->innerJoin('pcl.idClass', 'c', 'WITH', 'pcl.idClass = c.idClass')
                ->innerJoin('ps.idStudent', 'u', 'WITH', 'u.idUser = ps.idStudent')
                ->innerJoin('t.idTutor', 'ut')
                ->where($queryBuilder->expr()->eq('t.idTutor', $idUser))
                ->getQuery()
                ->execute();
        $results = $queryBuilder->getQuery()->getArrayResult();
      

        return $results;
    }

      function selecionarPortfolioStudentByStudent($idUser) {
        $this->em = $this->getDoctrine()->getEntityManager();

        $queryBuilder = $this->em->createQueryBuilder();
        $queryBuilder
                ->select('t, ps, p, ut, u, pcl,c')
                ->from('AppBundle:TbTutorPortfolio', 't')
                ->innerJoin('t.idPortfolioStudent', 'ps', 'WITH', 't.idPortfolioStudent = ps.idPortfolioStudent')
                ->innerJoin('ps.idPortfolioClass', 'pcl', 'WITH', 'pcl.idPortfolioClass = ps.idPortfolioClass')
                ->innerJoin('pcl.idPortfolio', 'p', 'WITH', 'pcl.idPortfolio = p.idPortfolio')
                ->innerJoin('pcl.idClass', 'c', 'WITH', 'pcl.idClass = c.idClass')
                ->innerJoin('ps.idStudent', 'u', 'WITH', 'u.idUser = ps.idStudent')
                ->innerJoin('t.idTutor', 'ut')
                ->where($queryBuilder->expr()->eq('ps.idStudent', $idUser))
                ->getQuery()
                ->execute();
        $results = $queryBuilder->getQuery()->getArrayResult();
      

        return $results;
    }
    function selecionarPortfolioStudentByPortfolioClass($idPortfolioClass) {
        $this->em = $this->getDoctrine()->getEntityManager();

        $queryBuilder = $this->em->createQueryBuilder();
        $queryBuilder
                ->select('t, p, t, ut, u, pc,po,c')
                ->from('AppBundle:TbTutorPortfolio', 't')
                ->innerJoin('t.idPortfolioStudent', 'p', 'WITH', 't.idPortfolioStudent = p.idPortfolioStudent')
                ->innerJoin('p.idPortfolioClass', 'pc', 'WITH', 'pc.idPortfolioClass = p.idPortfolioClass')
                  ->innerJoin('pc.idPortfolio', 'po', 'WITH', 'pc.idPortfolio = po.idPortfolio')
                    ->innerJoin('pc.idClass', 'c', 'WITH', 'pc.idClass = c.idClass')
                ->innerJoin('p.idStudent', 'u')
                ->innerJoin('t.idTutor', 'ut')
                ->where($queryBuilder->expr()->eq('p.idPortfolioClass', $idPortfolioClass))
                ->getQuery()
                ->execute();
        $results = $queryBuilder->getQuery()->getArrayResult();
        $this->logControle->logWeb("TbTutorPortfolio com student by portfolio class : " . print_r($results, true));

        return $results;
    }

    function selecionarPortfolioStudentByPortfolioClassTutor($idUser, $idPortfolioClass) {
        $this->em = $this->getDoctrine()->getEntityManager();

        $queryBuilder = $this->em->createQueryBuilder();
        $queryBuilder
                ->select('t, p, t, ut, u, pc')
                ->from('AppBundle:TbTutorPortfolio', 't')
                ->innerJoin('t.idPortfolioStudent', 'p', 'WITH', 't.idPortfolioStudent = p.idPortfolioStudent')
                ->innerJoin('p.idPortfolioClass', 'pc', 'WITH', 'pc.idPortfolioClass = p.idPortfolioClass')
                ->innerJoin('p.idStudent', 'u', 'WITH', 'u.idUser = p.idStudent')
                ->innerJoin('t.idTutor', 'ut')
                ->where($queryBuilder->expr()->eq('p.idPortfolioClass', $idPortfolioClass))
                ->andWhere($queryBuilder->expr()->eq('t.idTutor', $idUser))
                ->getQuery()
                ->execute();
        $results = $queryBuilder->getQuery()->getArrayResult();
        $this->logControle->log("TbTutorPortfolio com student by portfolio class : " . print_r($results, true));

        return $results;
    }

      function  selecionarPortfolioStudentByIdPortfolioStudent($idPortfolioStudent) {
        $this->em =$this->getDoctrine()->getManager();
 $this->logControle->logWeb(" id portfolio student : " .$idPortfolioStudent);
        $queryBuilder = $this->em->createQueryBuilder();
        $queryBuilder
                ->select('t, p, t, ut, u, pc,c')
                ->from('AppBundle:TbTutorPortfolio', 't')
                ->innerJoin('t.idPortfolioStudent', 'p', 'WITH', 't.idPortfolioStudent = p.idPortfolioStudent')
                ->innerJoin('p.idPortfolioClass', 'pc', 'WITH', 'pc.idPortfolioClass = p.idPortfolioClass')
                ->innerJoin('pc.idClass', 'c', 'WITH', 'pc.idClass = c.idClass')
                ->innerJoin('p.idStudent', 'u', 'WITH', 'u.idUser = p.idStudent')
                ->innerJoin('t.idTutor', 'ut')
                ->where($queryBuilder->expr()->eq('p.idPortfolioStudent', $idPortfolioStudent))
                ->getQuery()
                ->execute();
        $results = $queryBuilder->getQuery()->getArrayResult();
       $this->logControle->logWeb("TbTutorPortfolio com student by id portfolio student : " . print_r($results, true));

        return ($results);
    }
}
