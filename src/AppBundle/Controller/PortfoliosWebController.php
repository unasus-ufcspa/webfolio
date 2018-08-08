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
use AppBundle\Entity\TbPolicyUser;
use AppBundle\Controller\PortfolioStudentController;

header('Content-Type: text/html; charset=utf-8');

/**
 * Description of PortfoliosWebController
 *
 * @author Marilia
 */
class PortfoliosWebController extends Controller {

    public $em;
    public $logControle;

    public function __construct() {
        $this->logControle = new LogController();
    }

    /**
     * @Route("/portfolios")
     */
    public function portfolios() {
        $this->em = $this->getDoctrine()->getEntityManager();
        $idUser = $this->get('session')->get('idUser');
        $retornoPolicies = $this->selectTbPolicy($idUser);
        if (empty($idUser)) {
            return $this->redirectToRoute('login');
        } else {
            $idClasses = VisitanteController::verificarVisitante($idUser);
            if ($idClasses) {

                $responsePortfolios[] = $this->carregarPortfoliosVisitante($idClasses);
            }
            $responsePortfolios[] = $this->carregarPortfolios($idUser);


            $retorno = $this->render('portfolios.html.twig', array('portfoliosResponse' => $responsePortfolios, 'id' => $idUser, 'policies' => $retornoPolicies));

            $this->logControle->logWeb(" Response portfolios : " . print_r($responsePortfolios, true));
        }
        if (isset($retorno)) {
            return $retorno;
        } else {
            return new JsonResponse();
        }
    }

    public function carregarPortfoliosVisitante($idClasses) {
        foreach ($idClasses as $idClass) {
            $queryBuilderPortfolioGuest = $this->em->createQueryBuilder();
            $queryBuilderPortfolioGuest
                    ->select('c, pc,p ')
                    ->from('AppBundle:TbPortfolioClass', 'pc')
                    ->innerJoin('pc.idClass', 'c', 'WITH', 'c.idClass = pc.idClass')
                    ->innerJoin('pc.idPortfolio', 'p', 'WITH', 'p.idPortfolio  = pc.idPortfolio')
                    ->where($queryBuilderPortfolioGuest->expr()->eq('pc.idClass ', $idClass['idClass']['idClass']))
                    ->getQuery()
                    ->execute();

            $resultadoPortfolioTbGuest = $queryBuilderPortfolioGuest->getQuery()->getArrayResult();
            $this->logControle->logWeb(">>Retorno  PORTFOLIO CLASS GUEST : " . print_r($resultadoPortfolioTbGuest, true));

            foreach ($resultadoPortfolioTbGuest as $rowPortfolioClass) {

                $resp['visitante'][] = array(
                    'idPortfolioClass' => $rowPortfolioClass['idPortfolioClass'],
                    'idPortfolio' => $rowPortfolioClass['idPortfolio']['idPortfolio'],
                    'dsTitle' => $rowPortfolioClass['idPortfolio']['dsTitle'],
                    'dsDescription' => $rowPortfolioClass['idPortfolio']['dsDescription'],
                    'dsCode' => $rowPortfolioClass['idClass']['dsCode']
                );
            }
        }
        if (empty($resp)) {
            $resp = array();
        }
        return $resp;
    }

    public function carregarPortfolios($idUser) {
        $queryBuilderUser = $this->em->createQueryBuilder();
        $queryBuilderUser
                ->select('ps, pcl, u')
                ->from('AppBundle:TbPortfolioStudent', 'ps')
                ->innerJoin('ps.idStudent', 'u')
                ->innerJoin('ps.idPortfolioClass', 'pcl', 'WITH', 'pcl.idPortfolioClass = ps.idPortfolioClass')
                ->where($queryBuilderUser->expr()->eq('ps.idStudent ', $idUser))
                ->getQuery()
                ->execute();

        $resultadoTbPortfolioStudentAluno = $queryBuilderUser->getQuery()->getArrayResult();


        $countResultadoAluno = count($resultadoTbPortfolioStudentAluno);
        $this->logControle->logWeb(">>Retorno  TB PORTFOLIO STUDENT : " . print_r($resultadoTbPortfolioStudentAluno, true));

        if ($countResultadoAluno > 0) {
            $resp = $this->carregarPortfoliosAluno($resultadoTbPortfolioStudentAluno, $idUser);
        }

        $retornoPortfolioStudent_tutorPortfolioByTutor = PortfolioStudentController::selecionarPortfolioStudentByTutor($idUser);


        $totalItens = count($retornoPortfolioStudent_tutorPortfolioByTutor);



        if ($totalItens > 0) {
            foreach ($retornoPortfolioStudent_tutorPortfolioByTutor as $row) {
                $em = $this->getDoctrine()->getEntityManager();
                $queryBuilder = $em->createQueryBuilder();
                $queryBuilder
                        ->select('ae, ps,a, pcl')
                        ->from('AppBundle:TbActivityStudent', 'ae')
                        ->innerJoin('ae.idPortfolioStudent', 'ps', 'WITH', 'ae.idPortfolioStudent = ps.idPortfolioStudent')
                        ->innerJoin('ae.idActivity', 'a', 'WITH', 'a.idActivity = ae.idActivity')
                        ->innerJoin('ps.idPortfolioClass', 'pcl', 'WITH', 'pcl.idPortfolioClass = ps.idPortfolioClass')
                        ->Where($queryBuilder->expr()->eq('ae.idPortfolioStudent', $row['idPortfolioStudent']['idPortfolioStudent']))
                        ->getQuery()
                        ->execute();
                $atividades = $queryBuilder->getQuery()->getArrayResult();
                $totalN = 0;

                foreach ($atividades as $at) {
                    $queryBuilderN = $em->createQueryBuilder();
                    $queryBuilderN
                            ->select('COUNT(n.dtNotice) as numNotices')
                            ->from('AppBundle:TbNotice', 'n')
                            ->innerJoin('n.idDestination', 'u', 'WITH', 'n.idDestination = u.idUser')
                            ->where('n.dtRead is NULL')
                            ->andWhere($queryBuilderN->expr()->eq('n.idActivityStudent', $at['idActivityStudent']))
                            ->andWhere($queryBuilderN->expr()->eq('n.idDestination', $idUser))
                            ->getQuery()
                            ->execute();

                    $totalNotices = $queryBuilderN->getQuery()->getArrayResult();
                    $totalN +=$totalNotices[0]['numNotices'];
                }
                if (isset($notices[$at['idPortfolioStudent']['idPortfolioClass']['idPortfolioClass']])) {
                    $atual = $notices[$at['idPortfolioStudent']['idPortfolioClass']['idPortfolioClass']];
                    $notices[$at['idPortfolioStudent']['idPortfolioClass']['idPortfolioClass']] = $atual + $totalN;
                } else {
                    $notices[$at['idPortfolioStudent']['idPortfolioClass']['idPortfolioClass']] = $totalN;
                }
            }
            foreach ($retornoPortfolioStudent_tutorPortfolioByTutor as $row) {
                $rowPortfolioStudent = $row['idPortfolioStudent'];
                $rowPortfolioClass = $rowPortfolioStudent['idPortfolioClass'];
                $rowPortfolio = $rowPortfolioClass['idPortfolio'];
                $rowClass = $rowPortfolioClass['idClass'];
                if (!isset($idPortfoliosUsados) || !in_array($rowPortfolioClass['idPortfolioClass'], $idPortfoliosUsados)) {
                    $resp['tutor'][] = array(
                        'idPortfolioStudent' => $rowPortfolioStudent['idPortfolioStudent'],
                        'idPortfolioClass' => $rowPortfolioClass['idPortfolioClass'],
                        'idPortfolio' => $rowPortfolio['idPortfolio'],
                        'dsTitle' => $rowPortfolio['dsTitle'],
                        'dsDescription' => $rowPortfolio['dsDescription'],
                        'dsCode' => $rowClass['dsCode'],
                        'notices' => $notices[$rowPortfolioClass['idPortfolioClass']]
                    );
                    $idPortfoliosUsados[] = $rowPortfolioClass['idPortfolioClass'];
                }
            }
        }
        if (empty($resp)) {
            $resp = array();
        }
        return $resp;
    }

    public function carregarPortfoliosAluno($resultado, $idUser) {
        foreach ($resultado as $row) {
            $queryBuilderUser = $this->em->createQueryBuilder();
            $queryBuilderUser
                    ->select('pcl, p, c')
                    ->from('AppBundle:TbPortfolioClass', 'pcl')
                    ->innerJoin('pcl.idPortfolio', 'p', 'WITH', 'pcl.idPortfolio = p.idPortfolio')
                    ->innerJoin('pcl.idClass', 'c', 'WITH', 'pcl.idClass = c.idClass')
                    ->where($queryBuilderUser->expr()->eq('pcl.idPortfolioClass ', $row['idPortfolioClass']['idPortfolioClass']))
                    ->orderBy('c.dsCode', 'ASC')
                    ->getQuery()
                    ->execute();

            $this->logControle->logWeb($queryBuilderUser);
            $resultado = $queryBuilderUser->getQuery()->getArrayResult();

            $this->logControle->logWeb("RESULTADO TB PORFOLIO CLASS/PORTFOLIO ====== " . print_r($resultado, true));

            foreach ($resultado as $rowPcl) {

                $em = $this->getDoctrine()->getEntityManager();
                $queryBuilder = $em->createQueryBuilder();
                $queryBuilder
                        ->select('ae, ps')
                        ->from('AppBundle:TbActivityStudent', 'ae')
                        ->innerJoin('ae.idPortfolioStudent', 'ps', 'WITH', 'ae.idPortfolioStudent = ps.idPortfolioStudent')
                        ->Where($queryBuilder->expr()->eq('ae.idPortfolioStudent', $row['idPortfolioStudent']))
                        ->getQuery()
                        ->execute();
                $atividades = $queryBuilder->getQuery()->getArrayResult();
                $this->logControle->logWeb("atividades : " . print_r($atividades, true));
                $totalN = 0;


                foreach ($atividades as $at) {
                    $queryBuilderN = $em->createQueryBuilder();
                    $queryBuilderN
                            ->select('COUNT(n.dtNotice) as numNotices')
                            ->from('AppBundle:TbNotice', 'n')
                            ->innerJoin('n.idDestination', 'u', 'WITH', 'n.idDestination = u.idUser')
                            ->innerJoin('n.idActivityStudent', 'a', 'WITH', 'a.idActivityStudent = n.idActivityStudent')
                            ->where('n.dtRead is NULL')
                            ->andWhere($queryBuilderN->expr()->eq('n.idActivityStudent', $at['idActivityStudent']))
                            ->andWhere($queryBuilderN->expr()->eq('n.idDestination', $idUser))
                            ->getQuery()
                            ->execute();
                    $this->logControle->logWeb($queryBuilderN);
                    $totalNotices = $queryBuilderN->getQuery()->getArrayResult();
                    $this->logControle->logWeb("notices" . print_r($totalNotices, true));
                    $totalN +=$totalNotices[0]['numNotices'];
                    $this->logControle->logWeb($totalN);
                }

                $resp['student'][] = array(
                    'idPortfolioStudent' => $row['idPortfolioStudent'],
                    'idPortfolioClass' => $rowPcl['idPortfolioClass'],
                    'idPortfolio' => $rowPcl['idPortfolio']['idPortfolio'],
                    'dsTitle' => $rowPcl['idPortfolio']['dsTitle'],
                    'dsDescription' => $rowPcl['idPortfolio']['dsDescription'],
                    'dsCode' => $rowPcl['idClass']['dsCode'],
                    'notices' => $totalN
                );
                return $resp;
            }
        }
    }

    public function selectTbPolicy($idUser) {
        $totalItens = 0;
        $queryBuilder = $this->em->createQueryBuilder();
        $queryBuilder
                ->select('pu, p, u')
                ->from('AppBundle:TbPolicyUser', 'pu')
                ->innerJoin('pu.idPolicy', 'p', 'WITH', 'pu.idPolicy = p.idPolicy')
                ->innerJoin('pu.idUser', 'u', 'WITH', 'pu.idUser = u.idUser')
                ->Where($queryBuilder->expr()->eq('pu.idUser', $idUser))
                ->andWhere($queryBuilder->expr()->isNull('pu.flAccept'))
                ->getQuery()
                ->execute();

        //  $this->logControle->logWeb($queryBuilder);
        $results = $queryBuilder->getQuery()->getArrayResult();

        $totalItens = count($results);
        if ($totalItens > 0) {
            foreach ($results as $row) {
                $result[] = array(
                    'idPolicyUser' => (string) $row['idPolicyUser'],
                    'idUser' => (string) $row['idUser']['idUser'],
                    'flAccept' => $row['flAccept'],
                    'idPolicy' => (string) $row['idPolicy']['idPolicy'],
                    'txPolicy' => $row['idPolicy']['txPolicy']
                );
            }
        } else {
            $result = array();
        }
        $this->logControle->logWeb("TB POLICY : " . print_r($result, true));
        return $result;
    }

    /**
     * @Route("/aceitarTermoUso")
     */
    public function aceitarTermoUso() {
        $this->em = $this->getDoctrine()->getEntityManager();
        $idPolicyUser = $_POST['idPolicyUser'];


        $objetoPolicyUser = $this->getDoctrine()
                ->getRepository('AppBundle:TbPolicyUser')
                ->findOneBy(array('idPolicyUser' => $idPolicyUser));


        $objetoPolicyUser->setFlAccept('S');

        $this->em->persist($objetoPolicyUser);
        $this->em->flush();
        return new JsonResponse();
    }

}
