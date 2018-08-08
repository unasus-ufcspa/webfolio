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
use AppBundle\Entity\TbVersionActivity;
use AppBundle\Controller\PortfolioStudentController;

header('Content-Type: text/html; charset=utf-8');

/**
 * Description of ReferenciasController
 *
 * @author Marilia
 */
class RelatorioController extends Controller {

    public $em;
    public $listaPortfoliosFinalizados;
    public $logControle;

    public function __construct() {
        $this->logControle = new LogController();
    }

    /**
     * @Route("/relatoriosTurmas")
     */
    public function relatoriosTurmas() {

        $resp = array();
        $this->em = $this->getDoctrine()->getEntityManager();


        $idUser = $this->get('session')->get('idUser');

        if (empty($idUser)) {
            return $this->redirectToRoute('login');
        } else {
            $idClasses = VisitanteController::verificarVisitante($idUser);
            if ($idClasses) {
                $this->carregarPortfoliosVisitante($idClasses);
            }
            $this->carregarPortfolios($idUser);


            $retorno = $this->render('relatoriosTurmas.html.twig', array('portfolios' => $this->listaPortfoliosFinalizados, 'id' => $idUser));
        }
        if (isset($retorno)) {
            return $retorno;
        } else {
            return new JsonResponse();
        }
    }

    public function carregarPortfoliosVisitante($idClasses) {
        foreach ($idClasses as $visitante) {

            $this->logControle->logWeb(print_r($visitante, true));
            $idsPortfolioClass = VisitanteController::getIdPortfolioClassByClass($visitante['idClass']['idClass']);
            foreach ($idsPortfolioClass as $idPortfolioClass) {
                $resultadoTbPortfolioStudent = PortfolioStudentController::selecionarPortfolioStudentByPortfolioClass($idPortfolioClass['idPortfolioClass']);
                $countResultado = count($resultadoTbPortfolioStudent);


                if ($countResultado > 0) {
                    $this->portfoliosFinalizados($resultadoTbPortfolioStudent, "visitante");
                }
            }
        }
    }

    public function carregarPortfolios($idUser) {

        $resultadoTbPortfolioStudentAluno = PortfolioStudentController::selecionarPortfolioStudentByStudent($idUser);
        $countResultadoAluno = count($resultadoTbPortfolioStudentAluno);


        if ($countResultadoAluno > 0) {
            $this->portfoliosFinalizados($resultadoTbPortfolioStudentAluno, "student");
        }



        $resultadoPortfolioStudentTutor = PortfolioStudentController::selecionarPortfolioStudentByTutor($idUser);
        $this->logControle->logWeb(">>Retorno  TB PORTFOLIO STUDENT tutor : " . print_r($resultadoPortfolioStudentTutor, true));

        if (count($resultadoPortfolioStudentTutor) > 0) {
            $this->portfoliosFinalizados($resultadoPortfolioStudentTutor, "tutor");
        }
    }

    public function portfoliosFinalizados($resultadoPortfolioStudent, $stringNomeArray) {
        $idPortfoliosUsados = array();

        $this->logControle->logWeb(">>Retorno portfolios finalizados : " . print_r($resultadoPortfolioStudent, true));
        foreach ($resultadoPortfolioStudent as $row) {

            if ($this->verificarAtividadesFinalizadas($row['idPortfolioStudent']['idPortfolioStudent'])) {

                if (!empty($idPortfoliosUsados)) {
                    if (!in_array($row['idPortfolioStudent']['idPortfolioClass']['idPortfolioClass'], $idPortfoliosUsados)) {
                        $this->gerarArrayDados($row, $stringNomeArray);

                        $idPortfoliosUsados[] = $row['idPortfolioStudent']['idPortfolioClass']['idPortfolioClass'];
                    }
                } else {
                    $this->gerarArrayDados($row, $stringNomeArray);
                    $idPortfoliosUsados[] = $row['idPortfolioStudent']['idPortfolioClass']['idPortfolioClass'];
                }
            }
        }
    }

    function verificarAtividadesFinalizadas($idPortfolioStudent) {
        $this->em = $this->getDoctrine()->getEntityManager();
        $atividades = $this->selecionarAtividadesByPortfolioStudent($idPortfolioStudent);
        $totalAtividades = count($atividades);

        $this->em = $this->getDoctrine()->getEntityManager();
        $queryBuilderFinal = $this->em->createQueryBuilder();
        $queryBuilderFinal
                ->select('ae, ps,a')
                ->from('AppBundle:TbActivityStudent', 'ae')
                ->innerJoin('ae.idPortfolioStudent', 'ps', 'WITH', 'ae.idPortfolioStudent = ps.idPortfolioStudent')
                ->innerJoin('ae.idActivity', 'a', 'WITH', 'a.idActivity = ae.idActivity')
                ->Where($queryBuilderFinal->expr()->eq('ae.idPortfolioStudent', $idPortfolioStudent))
                ->andWhere($queryBuilderFinal->expr()->isNotNull('ae.dtConclusion'))
                ->getQuery()
                ->execute();
        $atividadesFinais = $queryBuilderFinal->getQuery()->getArrayResult();
        //$this->logControle->logWeb("atividades : " . print_r($atividadesFinais, true));

        $totalAtividadesFinalizadas = count($atividadesFinais);
        $this->logControle->logWeb("total atividades= " . $totalAtividades . "atividades finalizadas = " . $totalAtividadesFinalizadas);
        if ($totalAtividades == $totalAtividadesFinalizadas) {
            $this->logControle->logWeb("verdade");

            return true;
        } else {
            return false;
        }
    }

    function gerarArrayDados($row, $stringNomeArray) {
        if (empty($this->search($this->listaPortfoliosFinalizados, 'idPortfolioStudent', $row['idPortfolioStudent']['idPortfolioStudent']))) {
            $this->listaPortfoliosFinalizados[$stringNomeArray][] = array(
                'idPortfolioStudent' => $row['idPortfolioStudent']['idPortfolioStudent'],
                'idPortfolioClass' => $row['idPortfolioStudent']['idPortfolioClass']['idPortfolioClass'],
                'idPortfolio' => $row['idPortfolioStudent']['idPortfolioClass']['idPortfolio']['idPortfolio'],
                'dsTitle' => $row['idPortfolioStudent']['idPortfolioClass']['idPortfolio']['dsTitle'],
                'dsDescription' => $row['idPortfolioStudent']['idPortfolioClass']['idPortfolio']['dsDescription'],
                'dsCode' => $row['idPortfolioStudent']['idPortfolioClass']['idClass']['dsCode']
            );
        }
        $this->logControle->logWeb(print_r($this->listaPortfoliosFinalizados, true));
    }

    function search($array, $key, $value) {
        $results = array();

        if (is_array($array)) {
            if (isset($array[$key]) && $array[$key] == $value) {
                $results[] = $array;
            }

            foreach ($array as $subarray) {
                $results = array_merge($results, $this->search($subarray, $key, $value));
            }
        }

        return $results;
    }

    /**
     * @Route("/relatoriosAlunosVisitante/{id}")
     */
    public function relatoriosAlunosVisitante($id) {
        $idUser = $this->get('session')->get('idUser');
        $this->get('session')->set('portfolio', $id);
        if (empty($idUser)) {
            return $this->redirectToRoute('login');
        }

        $resultadoPortfolioStudent = PortfolioStudentController::selecionarPortfolioStudentByPortfolioClass($id);
        $resp = $this->gerarJsonRelatorio($resultadoPortfolioStudent);


        return $this->render('relatoriosAlunos.html.twig', array('alunos' => $resp, 'visitantes' => true));
    }

    public function gerarJsonRelatorio($resultadoPortfolioStudent) {
        $resp = array();
        foreach ($resultadoPortfolioStudent as $a) {
            $this->logControle->logWeb(print_r($a, true));
            if ($this->verificarAtividadesFinalizadas($a['idPortfolioStudent']['idPortfolioStudent'])) {

                $this->logControle->logWeb("teste " + $a['idPortfolioStudent']['idStudent']['idUser']);
                $photo = UserController::selecionarFotoUsuario($a['idPortfolioStudent']['idStudent']['idUser']);

                if (empty($this->search($resp, 'idPortfolioStudent', $a['idPortfolioStudent']['idPortfolioStudent']))) {
                    $resp[] = array(
                        'idPortfolioStudent' => $a['idPortfolioStudent']['idPortfolioStudent'],
                        'nmUser' => $a['idPortfolioStudent']['idStudent']['nmUser'],
                        'idVersionActivity' => -1,
                        'idUser' => $a['idPortfolioStudent']['idStudent']['idUser'],
                        'foto' => $photo
                    );
                }
            }
        }
        return $resp;
    }

    /**
     * @Route("/relatoriosAlunos/{id}")
     */
    public function relatoriosAlunos($id) {

        $idUser = $this->get('session')->get('idUser');
        $this->get('session')->set('portfolio', $id);
        if (empty($idUser)) {
            return $this->redirectToRoute('login');
        }

        $resultadoPortfolioStudent = PortfolioStudentController::selecionarPortfolioStudentByPortfolioClassTutor($idUser, $id);
        $resp = $this->gerarJsonRelatorio($resultadoPortfolioStudent);

        return $this->render('relatoriosAlunos.html.twig', array('alunos' => $resp, 'visitantes' => false));
    }

    function selecionarAtividadesByPortfolioStudent($idPortfolioStudent) {
        $queryBuilder = $this->em->createQueryBuilder();
        $queryBuilder
                ->select('ae, ps,a, pcl')
                ->from('AppBundle:TbActivityStudent', 'ae')
                ->innerJoin('ae.idPortfolioStudent', 'ps', 'WITH', 'ae.idPortfolioStudent = ps.idPortfolioStudent')
                ->innerJoin('ae.idActivity', 'a', 'WITH', 'a.idActivity = ae.idActivity')
                ->innerJoin('ps.idPortfolioClass', 'pcl', 'WITH', 'pcl.idPortfolioClass = ps.idPortfolioClass')
                ->Where($queryBuilder->expr()->eq('ae.idPortfolioStudent', $idPortfolioStudent))
                ->getQuery()
                ->execute();
        $atividades = $queryBuilder->getQuery()->getArrayResult();
        return $atividades;
    }

    /**
     * @Route("/relatoriosFinaisTutor/{idUser}")
     */
    public function relatoriosFinaisTutor($idUser) {
        $resp = array();

        $id = $this->get('session')->get('portfolio');

        $this->em = $this->getDoctrine()->getEntityManager();
        $queryBuilder = $this->em->createQueryBuilder();
        $queryBuilder
                ->select('ps, u')
                ->from('AppBundle:TbPortfolioStudent', 'ps')
                ->innerJoin('ps.idStudent', 'u', 'WITH', 'ps.idStudent= u.idUser')
                ->where($queryBuilder->expr()->eq('ps.idPortfolioClass', $id))
                ->andWhere($queryBuilder->expr()->eq('ps.idStudent', $idUser))
                ->getQuery()
                ->execute();

        $this->logControle->logWeb($queryBuilder);
        $results = $queryBuilder->getQuery()->getArrayResult();
        $this->logControle->logWeb("portfolio -----------: " . print_r($results, true));


        foreach ($results as $a) {
            $this->em = $this->getDoctrine()->getEntityManager();
            $queryBuilder = $this->em->createQueryBuilder();
            $queryBuilder
                    ->select('ae, ps,a')
                    ->from('AppBundle:TbActivityStudent', 'ae')
                    ->innerJoin('ae.idPortfolioStudent', 'ps', 'WITH', 'ae.idPortfolioStudent = ps.idPortfolioStudent')
                    ->innerJoin('ae.idActivity', 'a', 'WITH', 'a.idActivity = ae.idActivity')
                    ->Where($queryBuilder->expr()->eq('ae.idPortfolioStudent', $a['idPortfolioStudent']))
                    ->orderBy('a.nuOrder', 'ASC')
                    ->getQuery()
                    ->execute();

            $this->logControle->logWeb($queryBuilder);
            $results = $queryBuilder->getQuery()->getArrayResult();
            $this->logControle->logWeb("atividades : " . print_r($results, true));
            $contAtiv = 0;
            foreach ($results as $row) {

                $queryBuilderVersaoAtual = $this->em->createQueryBuilder();
                $queryBuilderVersaoAtual
                        ->select('MAX(va.idVersionActivity) as idVer, va.txActivity as txActivityMax')
                        ->from('AppBundle:TbVersionActivity', 'va')
                        ->innerJoin('va.idActivityStudent', 'a')
                        ->where($queryBuilderVersaoAtual->expr()->eq('va.idActivityStudent', $row['idActivityStudent']))
                        ->andWhere($queryBuilderVersaoAtual->expr()->isNotNull('va.dtSubmission'))
                        ->groupBy('a.idActivityStudent, va.idVersionActivity')
                        ->orderBy('va.idVersionActivity', 'DESC')
                        ->getQuery()
                        ->execute();


                $this->logControle->logWeb($queryBuilderVersaoAtual);
                $resultsVersaoAtual = $queryBuilderVersaoAtual->getQuery()->getArrayResult();

                $this->logControle->logWeb("versao final" . print_r($resultsVersaoAtual, true));

                if (count($resultsVersaoAtual) > 0) {
                    $textoFinal = stripcslashes($resultsVersaoAtual[0]['txActivityMax']);

                    $textoFinal = preg_replace('/(src=["\'])([^"\']+)(["\'])/', 'src="/webfolio/uploads/$2"', $textoFinal);
                    $textoFinal = str_replace("style=\"background-color:#d9fce6\"", '', $textoFinal);
                    $textoFinal = str_replace("style=\"background-color:#70e7d0\"", '', $textoFinal);
                    //só retorna a versao atual
                    $resp[] = array(
                        'id_activity_student' => $row['idActivityStudent'],
                        'idPortfolioStudent' => $row['idPortfolioStudent']['idPortfolioStudent'],
                        'dsTitle' => $row['idActivity']['dsTitle'],
                        'dsDescription' => $row['idActivity']['dsDescription'],
                        'nmUser' => $a['idStudent']['nmUser'],
                        'idVersionActivity' => $resultsVersaoAtual[0]['idVer'],
                        'textoFinal' => ($textoFinal),
                    );
                } else {

                    $queryBuilderVersaoAtual = $this->em->createQueryBuilder();
                    $queryBuilderVersaoAtual
                            ->select('MAX(va.idVersionActivity) as idVer, va.txActivity as txActivityMax')
                            ->from('AppBundle:TbVersionActivity', 'va')
                            ->innerJoin('va.idActivityStudent', 'a')
                            ->where($queryBuilderVersaoAtual->expr()->eq('va.idActivityStudent', $row['idActivityStudent']))
                            ->groupBy('a.idActivityStudent, va.idVersionActivity')
                            ->orderBy('va.idVersionActivity', 'DESC')
                            ->getQuery()
                            ->execute();
                    $resultsSemFim = $queryBuilderVersaoAtual->getQuery()->getArrayResult();

                    $this->logControle->logWeb("SQL SELECT VERSION atividades : " . print_r($resultsSemFim, true));

                    if (count($resultsSemFim) > 0) {
                        //criar versao atual

                        $textoFinal = stripcslashes($resultsSemFim[0]['txActivityMax']);
                        $textoFinal = preg_replace('/(src=["\'])([^"\']+)(["\'])/', 'src="/webfolio/uploads/$2"', $textoFinal);

                        $textoFinal = str_replace("style=\"background-color:#d9fce6\"", '', $textoFinal);
                        $textoFinal = str_replace("style=\"background-color:#70e7d0\"", '', $textoFinal);
                        $resp[] = array(
                            'id_activity_student' => $row['idActivityStudent'],
                            'idPortfolioStudent' => $row['idPortfolioStudent']['idPortfolioStudent'],
                            'dsTitle' => $row['idActivity']['dsTitle'],
                            'dsDescription' => $row['idActivity']['dsDescription'],
                            'idVersionActivity' => $resultsSemFim[0]['idVer'],
                            'textoFinal' => ($textoFinal),
                            'nmUser' => $a['idStudent']['nmUser']
                        );
                    } else {
                        $resp[] = array(
                            'id_activity_student' => $row['idActivityStudent'],
                            'idPortfolioStudent' => $row['idPortfolioStudent']['idPortfolioStudent'],
                            'dsTitle' => $row['idActivity']['dsTitle'],
                            'dsDescription' => $row['idActivity']['dsDescription'],
                            'idVersionActivity' => '',
                            'textoFinal' => '',
                            'nmUser' => $a['idStudent']['nmUser']
                        );
                    }
                }
            }
        }




        return $this->render('relatoriosFinaisTutor.html.twig', array('atividades' => $resp));
    }

    /**
     * @Route("/relatoriosFinaisAluno/{id}")
     */
    public function relatoriosFinaisAluno($id) {

        $idUser = $this->get('session')->get('idUser');
        if (empty($idUser)) {
            return $this->redirectToRoute('login');
        }
        $this->get('session')->set('portfolio', $id);
        $id = $this->get('session')->get('portfolio');


        $this->logControle->logWeb(" ---- SESSAO -- -- - " . $idUser);


        $this->em = $this->getDoctrine()->getEntityManager();
        $queryBuilder = $this->em->createQueryBuilder();
        $queryBuilder
                ->select('ps, u')
                ->from('AppBundle:TbPortfolioStudent', 'ps')
                ->innerJoin('ps.idStudent', 'u', 'WITH', 'ps.idStudent= u.idUser')
                ->Where($queryBuilder->expr()->eq('ps.idPortfolioClass', $id))
                ->andWhere($queryBuilder->expr()->eq('ps.idStudent', $idUser))
                ->getQuery()
                ->execute();

        $this->logControle->logWeb($queryBuilder);
        $results = $queryBuilder->getQuery()->getArrayResult();
        $this->logControle->logWeb("portfolio -----------: " . print_r($results, true));


        foreach ($results as $a) {
            $this->em = $this->getDoctrine()->getEntityManager();
            $queryBuilder = $this->em->createQueryBuilder();
            $queryBuilder
                    ->select('ae, ps,a')
                    ->from('AppBundle:TbActivityStudent', 'ae')
                    ->innerJoin('ae.idPortfolioStudent', 'ps', 'WITH', 'ae.idPortfolioStudent = ps.idPortfolioStudent')
                    ->innerJoin('ae.idActivity', 'a', 'WITH', 'a.idActivity = ae.idActivity')
                    ->Where($queryBuilder->expr()->eq('ae.idPortfolioStudent', $a['idPortfolioStudent']))
                    ->orderBy('a.nuOrder', 'ASC')
                    ->getQuery()
                    ->execute();

            $this->logControle->logWeb($queryBuilder);
            $results = $queryBuilder->getQuery()->getArrayResult();
            $this->logControle->logWeb("atividades : " . print_r($results, true));
            $contAtiv = 0;
            foreach ($results as $row) {

                $queryBuilderVersaoAtual = $this->em->createQueryBuilder();
                $queryBuilderVersaoAtual
                        ->select('MAX(va.idVersionActivity) as idVer, va.txActivity as txActivityMax')
                        ->from('AppBundle:TbVersionActivity', 'va')
                        ->innerJoin('va.idActivityStudent', 'a')
                        ->where($queryBuilderVersaoAtual->expr()->eq('va.idActivityStudent', $row['idActivityStudent']))
                        ->andWhere($queryBuilderVersaoAtual->expr()->isNotNull('va.dtSubmission'))
                        ->groupBy('a.idActivityStudent, va.idVersionActivity')
                        ->orderBy('va.idVersionActivity', 'DESC')
                        ->getQuery()
                        ->execute();

                $this->logControle->logWeb($queryBuilderVersaoAtual);
                $resultsVersaoAtual = $queryBuilderVersaoAtual->getQuery()->getArrayResult();
                $this->logControle->logWeb("versao final" . print_r($resultsVersaoAtual, true));

                if (count($resultsVersaoAtual) > 0) {
                    //só retorna a versao atual
                    $textoFinal = stripcslashes($resultsVersaoAtual[0]['txActivityMax']);
                    $textoFinal = preg_replace('/(src=["\'])([^"\']+)(["\'])/', 'src="/webfolio/uploads/$2"', $textoFinal);

                    $textoFinal = str_replace("style=\"background-color:#d9fce6\"", '', $textoFinal);
                    $resp[] = array(
                        'id_activity_student' => $row['idActivityStudent'],
                        'idPortfolioStudent' => $row['idPortfolioStudent']['idPortfolioStudent'],
                        'dsTitle' => $row['idActivity']['dsTitle'],
                        'dsDescription' => $row['idActivity']['dsDescription'],
                        'nmUser' => $a['idStudent']['nmUser'],
                        'idVersionActivity' => $resultsVersaoAtual[0]['idVer'],
                        'textoFinal' => ($textoFinal),
                    );
                } else {

                    $queryBuilderVersaoAtual = $this->em->createQueryBuilder();
                    $queryBuilderVersaoAtual
                            ->select('MAX(va.idVersionActivity) as idVer, va.txActivity as txActivityMax')
                            ->from('AppBundle:TbVersionActivity', 'va')
                            ->innerJoin('va.idActivityStudent', 'a')
                            ->where($queryBuilderVersaoAtual->expr()->eq('va.idActivityStudent', $row['idActivityStudent']))
                            ->groupBy('a.idActivityStudent, va.idVersionActivity')
                            ->orderBy('va.idVersionActivity', 'DESC')
                            ->getQuery()
                            ->execute();

                    $this->logControle->logWeb($queryBuilderVersaoAtual);
                    $resultsSemFim = $queryBuilderVersaoAtual->getQuery()->getArrayResult();

                    $this->logControle->logWeb("SQL SELECT VERSION atividades : " . print_r($resultsSemFim, true));

                    if (count($resultsSemFim) > 0) {
                        //criar versao atual

                        $textoFinal = stripcslashes($resultsSemFim[0]['txActivityMax']);
                        $textoFinal = preg_replace('/(src=["\'])([^"\']+)(["\'])/', 'src="/webfolio/uploads/$2"', $textoFinal);

                        $textoFinal = str_replace("style=\"background-color:#d9fce6\"", '', $textoFinal);
                        $resp[] = array(
                            'id_activity_student' => $row['idActivityStudent'],
                            'idPortfolioStudent' => $row['idPortfolioStudent']['idPortfolioStudent'],
                            'dsTitle' => $row['idActivity']['dsTitle'],
                            'dsDescription' => $row['idActivity']['dsDescription'],
                            'idVersionActivity' => $resultsSemFim[0]['idVer'],
                            'textoFinal' => ($textoFinal),
                            'nmUser' => $a['idStudent']['nmUser']
                        );
                    } else {
                        $resp[] = array(
                            'id_activity_student' => $row['idActivityStudent'],
                            'idPortfolioStudent' => $row['idPortfolioStudent']['idPortfolioStudent'],
                            'dsTitle' => $row['idActivity']['dsTitle'],
                            'dsDescription' => $row['idActivity']['dsDescription'],
                            'idVersionActivity' => '',
                            'textoFinal' => '',
                            'nmUser' => $a['idStudent']['nmUser']
                        );
                    }
                }
            }
        }




        return $this->render('relatoriosFinais.html.twig', array('atividades' => $resp));
    }

    /**
     * @Route("/relatoriosFinaisAlunoPDF")
     */
    public function relatoriosFinaisAlunoPDF() {

        $idUser = $this->get('session')->get('idUser');
        if (empty($idUser)) {
            return $this->redirectToRoute('login');
        }
        $id = $this->get('session')->get('portfolio');


        $this->logControle->logWeb(" ---- ID USER -- -- - " . $idUser . " id portfolio " . $id);


        $this->em = $this->getDoctrine()->getEntityManager();
        $queryBuilder = $this->em->createQueryBuilder();
        $queryBuilder
                ->select('ps, u')
                ->from('AppBundle:TbPortfolioStudent', 'ps')
                ->innerJoin('ps.idStudent', 'u', 'WITH', 'ps.idStudent= u.idUser')
                ->Where($queryBuilder->expr()->eq('ps.idPortfolioClass', $id))
                ->andWhere($queryBuilder->expr()->eq('ps.idStudent', $idUser))
                ->getQuery()
                ->execute();

        $this->logControle->logWeb($queryBuilder);
        $results = $queryBuilder->getQuery()->getArrayResult();
        $this->logControle->logWeb("portfolio -----------: " . print_r($results, true));

        $responsePdf = $this->gerarResponseAtividadesPdf($results);
        $this->logControle->logWeb("RESPONSE PDF  ALUNO : " . print_r($responsePdf, true));



        $this->returnPDFResponseFromHTML($responsePdf);
    }

    /**
     * @Route("/relatoriosFinaisTutorPDF/{idPorfolioStudent}")
     */
    public function relatoriosFinaisTutorPDF($idPorfolioStudent) {

        $idUser = $this->get('session')->get('idUser');
        if (empty($idUser)) {
            return $this->redirectToRoute('login');
        }


        $resultadoPortfolioStudent = PortfolioStudentController::selecionarPortfolioStudentByIdPortfolioStudent($idPorfolioStudent);


        $responsePdf = $this->gerarResponseAtividadesPdf($resultadoPortfolioStudent);
        $this->logControle->logWeb("RESPONSE PDF : " . print_r($responsePdf, true));

        $this->returnPDFResponseFromHTML($responsePdf);
    }

    public function gerarResponseAtividadesPdf($portfoliosStudent) {
        $resp = array();
        foreach ($portfoliosStudent as $a) {
            if (isset($a['idPortfolioStudent']['idPortfolioStudent'])) {
                $idPortfolioStudent = $a['idPortfolioStudent']['idPortfolioStudent'];
                $student = $a['idPortfolioStudent']['idStudent'];
            } else {
                $idPortfolioStudent = $a['idPortfolioStudent'];
                $student = $a['idStudent'];
            }
            $this->em = $this->getDoctrine()->getEntityManager();
            $queryBuilder = $this->em->createQueryBuilder();
            $queryBuilder
                    ->select('ae, ps,a')
                    ->from('AppBundle:TbActivityStudent', 'ae')
                    ->innerJoin('ae.idPortfolioStudent', 'ps', 'WITH', 'ae.idPortfolioStudent = ps.idPortfolioStudent')
                    ->innerJoin('ae.idActivity', 'a', 'WITH', 'a.idActivity = ae.idActivity')
                    ->Where($queryBuilder->expr()->eq('ae.idPortfolioStudent', $idPortfolioStudent))
                    ->orderBy('a.nuOrder', 'ASC')
                    ->getQuery()
                    ->execute();

            $this->logControle->logWeb($queryBuilder);
            $results = $queryBuilder->getQuery()->getArrayResult();
            $this->logControle->logWeb("atividades : " . print_r($results, true));
            $contAtiv = 0;
            foreach ($results as $row) {

                $queryBuilderVersaoAtual = $this->em->createQueryBuilder();
                $queryBuilderVersaoAtual
                        ->select('MAX(va.idVersionActivity) as idVer, va.txActivity as txActivityMax')
                        ->from('AppBundle:TbVersionActivity', 'va')
                        ->innerJoin('va.idActivityStudent', 'a')
                        ->where($queryBuilderVersaoAtual->expr()->eq('va.idActivityStudent', $row['idActivityStudent']))
                        ->andWhere($queryBuilderVersaoAtual->expr()->isNotNull('va.dtSubmission'))
                        ->groupBy('a.idActivityStudent, va.idVersionActivity')
                        ->orderBy('va.idVersionActivity', 'DESC')
                        ->getQuery()
                        ->execute();


                $this->logControle->logWeb($queryBuilderVersaoAtual);
                $resultsVersaoAtual = $queryBuilderVersaoAtual->getQuery()->getArrayResult();

                $this->logControle->logWeb("versao final" . print_r($resultsVersaoAtual, true));

                if (count($resultsVersaoAtual) > 0) {
                    $textoFinal = stripcslashes($resultsVersaoAtual[0]['txActivityMax']);

                    $textoFinal = preg_replace('/(src=["\'])([^"\']+)(["\'])/', 'src="/webfolio/uploads/$2"', $textoFinal);
                    $textoFinal = str_replace("style=\"background-color:#d9fce6\"", '', $textoFinal);
                    //só retorna a versao atual
                    $resp[] = array(
                        'id_activity_student' => $row['idActivityStudent'],
                        'idPortfolioStudent' => $row['idPortfolioStudent']['idPortfolioStudent'],
                        'dsTitle' => $row['idActivity']['dsTitle'],
                        'dsDescription' => $row['idActivity']['dsDescription'],
                        'nmUser' => $a['idPortfolioStudent']['idStudent']['nmUser'],
                        'idVersionActivity' => $resultsVersaoAtual[0]['idVer'],
                        'textoFinal' => ($textoFinal),
                    );
                } else {

                    $queryBuilderVersaoAtual = $this->em->createQueryBuilder();
                    $queryBuilderVersaoAtual
                            ->select('MAX(va.idVersionActivity) as idVer, va.txActivity as txActivityMax')
                            ->from('AppBundle:TbVersionActivity', 'va')
                            ->innerJoin('va.idActivityStudent', 'a')
                            ->where($queryBuilderVersaoAtual->expr()->eq('va.idActivityStudent', $row['idActivityStudent']))
                            ->groupBy('a.idActivityStudent, va.idVersionActivity')
                            ->orderBy('va.idVersionActivity', 'DESC')
                            ->getQuery()
                            ->execute();
                    $resultsSemFim = $queryBuilderVersaoAtual->getQuery()->getArrayResult();

                    $this->logControle->logWeb("SQL SELECT VERSION atividades : " . print_r($resultsSemFim, true));

                    if (count($resultsSemFim) > 0) {
                        //criar versao atual

                        $textoFinal = stripcslashes($resultsSemFim[0]['txActivityMax']);
                        $textoFinal = preg_replace('/(src=["\'])([^"\']+)(["\'])/', 'src="/webfolio/uploads/$2"', $textoFinal);

                        $textoFinal = str_replace("style=\"background-color:#d9fce6\"", '', $textoFinal);
                        $resp[] = array(
                            'id_activity_student' => $row['idActivityStudent'],
                            'idPortfolioStudent' => $row['idPortfolioStudent']['idPortfolioStudent'],
                            'dsTitle' => $row['idActivity']['dsTitle'],
                            'dsDescription' => $row['idActivity']['dsDescription'],
                            'idVersionActivity' => $resultsSemFim[0]['idVer'],
                            'textoFinal' => ($textoFinal),
                            'nmUser' => $a['idPortfolioStudent']['idStudent']['nmUser']
                        );
                    } else {
                        $resp[] = array(
                            'id_activity_student' => $row['idActivityStudent'],
                            'idPortfolioStudent' => $row['idPortfolioStudent']['idPortfolioStudent'],
                            'dsTitle' => $row['idActivity']['dsTitle'],
                            'dsDescription' => $row['idActivity']['dsDescription'],
                            'idVersionActivity' => '',
                            'textoFinal' => '',
                            'nmUser' => $a['idPortfolioStudent']['idStudent']['nmUser']
                        );
                    }
                }
            }
        }

        return $resp;
    }

    public function returnPDFResponseFromHTML($html) {
        //set_time_limit(30); uncomment this line according to your needs
        // If you are not in a controller, retrieve of some way the service container and then retrieve it
        //$pdf = $this->container->get("white_october.tcpdf")->create('vertical', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        //if you are in a controlller use :
        $pdf = $this->get("white_october.tcpdf")->create('vertical', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->SetAuthor('Our Code World');
        $pdf->SetTitle(('Our Code World Title'));
        $pdf->SetSubject('Our Code World Subject');
        $pdf->setFontSubsetting(true);
        $pdf->SetAutoPageBreak(true, 10);
        //  $pdf->SetFont('helvetica', '', 11, '', true);
        $pdf->SetMargins(20, 30, 20, true);


        $pdf->setPrintFooter(false);
        // $filename = 'ourcodeworld_pdf_demo';
        foreach ($html as $array) {
            $this->logControle->logWeb(print_r($array, true));
            $pdf->SetAuthor($array['nmUser']);
            $pdf->SetTitle(($array['dsTitle']));
            $pdf->SetSubject($array['dsTitle']);
            $filename = 'portfolio_' . $array['nmUser'] . '_' . $array['idPortfolioStudent'];
            $pdf->resetHeaderTemplate();

            $pdf->SetHeaderData('', 0, $array['dsTitle'], '');
            $this->logControle->logWeb($array['dsTitle']);
// set header and footer fonts
            $pdf->setHeaderFont(Array('freeserif', '', 18));
            $pdf->SetHeaderMargin(10);
            $pdf->setPrintHeader(true);
            $pdf->AddPage();

            $texto = stripcslashes($array['textoFinal']);
            $texto = preg_replace('/(src=["\'])([^"\']+)(["\'])/', 'src="' . $this->getParameter('web_dir') . '/web/uploads/$2"', $texto);


            $pdf->writeHTMLCell($w = 0, $h = 0, $x = '', $y = '', $texto, $border = 0, $ln = 1, $fill = 0, $reseth = true, $align = '', $autopadding = true);
            $pdf->endPage(); //do end of page
            $pdf->lastPage(); //set cursor at last page, because autopagebreak not do it
        }

        $pdf->Output($filename . ".pdf", 'I'); // This will output the PDF as a response directly
    }

    /**
     * @Route("/relatorioPdf/{id}")
     */
    public function relatorioPdf($id) {
        // You can send the html as you want
        //$html = '<h1>Plain HTML</h1>';
        $version = $this->getDoctrine()
                ->getRepository('AppBundle:TbVersionActivity')
                ->findOneBy(array('idVersionActivity' => $id));
        $texto = $version->getTxActivity();
        $texto = stripcslashes($texto);
        $texto = preg_replace('#<img.+?src=[\'"]([^\'"]+)[\'"].*>#i', '<img src="' . $this->getParameter('web_dir') . '/web/uploads/$1">', $texto);
        // but in this case we will render a symfony view !
        // We are in a controller and we can use renderView function which retrieves the html from a view
        // then we send that html to the user.
//    $html = $this->renderView(
//         'default/index.html.twig',
//         array(
//          'someDataToView' => 'Something'
//         )
//    );
        //   C:/Apache24/htdocs_sfny/webfolio/web/uploads/
        $this->returnPDFResponseFromHTML($texto);
    }

}
