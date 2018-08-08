<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use AppBundle\Entity\TbVersionActivity;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use AppBundle\Controller\PortfolioStudentController;

/**
 * Description of AtividadesController
 *
 * @author Marilia
 */
class AtividadesController extends Controller {

    public $em;
    public $logControle;

    public function __construct() {
        $this->logControle = new LogController();
    }

    /**
     * @Route("/ultimoPortfolio/0")
     */
    public function ultimoPortfolio() {
        $idUser = $this->get('session')->get('idUser');
        if (empty($idUser)) {
            return $this->redirectToRoute('login');
        }

        $id = $this->get('session')->get('portfolio'); //class
        $this->logControle->logWeb(" ---- SESSAO -- -- - " . $id);
        $this->logControle->logWeb(" ---- idUser -- -- - " . $idUser);

        $retornoPortfolio = PortfolioStudentController::selecionarPortfolioStudentByPortfolioClass($id);
        $this->logControle->logWeb(print_r($retornoPortfolio, true));
                
        $isGuest = true;
        foreach ($retornoPortfolio as $a) {

            if ($a['idTutor']['idUser'] == $idUser) {
                $this->logControle->logWeb("é tutor");
                $isGuest = false;
                return $this->atividadesTutor($id);
            } else {
                if ($a['idPortfolioStudent']['idStudent']['idUser'] == $idUser) {
                    $this->logControle->logWeb("é aluno");
                    $isGuest = false;
                    return $this->atividadesAluno($id);
                }
            }
        }
        if ($isGuest) {

            return $this->atividadesVisitante($id);
        }
    }

    /**
     * @Route("/atividadesVisitante/{id}")
     */
    public function atividadesVisitante($id) {
        $this->em = $this->getDoctrine()->getEntityManager();
        $idUser = $this->get('session')->get('idUser');
        if (empty($idUser)) {
            return $this->redirectToRoute('login');
        }
        $this->get('session')->set('portfolio', $id);
        $id = $this->get('session')->get('portfolio');
        $idUser = $this->get('session')->get('idUser');
        $this->logControle->log(" ---- SESSAO -- -- - " . $id);

        $queryBuilder = $this->em->createQueryBuilder();
        $queryBuilder
                ->select('ps, u')
                ->from('AppBundle:TbPortfolioStudent', 'ps')
                ->innerJoin('ps.idStudent', 'u', 'WITH', 'ps.idStudent= u.idUser')
                ->Where($queryBuilder->expr()->eq('ps.idPortfolioClass', $id))
                ->getQuery()
                ->execute();

        $resultadoPortfolioStudent = $queryBuilder->getQuery()->getArrayResult();

        foreach ($resultadoPortfolioStudent as $a) {
            $photo = $this->selecionarImagemPerfil($a['idStudent']['idUser']);

            $resultadoActivityStudent = $this->selecionarAtividades($a['idPortfolioStudent']);
            foreach ($resultadoActivityStudent as $row) {

                $idVersãoAtual = $this->selecionarVersaoAtual($row['idActivityStudent']);
                if (!empty($row['dtConclusion'])) {
                    $dtConclusion = $row['dtConclusion']->format('Y-m-d H:i:s');
                } else {
                    $dtConclusion = null;
                }

                $resp[$a['idStudent']['idUser']][] = array(
                    'id_activity_student' => $row['idActivityStudent'],
                    'idPortfolioStudent' => $row['idPortfolioStudent']['idPortfolioStudent'],
                    'dsTitle' => $row['idActivity']['dsTitle'],
                    'dsDescription' => $row['idActivity']['dsDescription'],
                    'nmUser' => $a['idStudent']['nmUser'],
                    'idVersionActivity' => ((!empty($idVersãoAtual)) ? $idVersãoAtual : -1),
                    'notice' => 0,
                    'foto' => $photo,
                    'dtConclusion' => $dtConclusion
                );
            }
        }

        $this->logControle->log("RETORNO : " . print_r($resp, true));

        return $this->render('atividades.html.twig', array('atividades' => $resp, 'id' => $id));
    }

    /**
     * @Route("/atividadesTutor/{id}")
     */
    public function atividadesTutor($id) {
        $this->em = $this->getDoctrine()->getEntityManager();
        $idUser = $this->get('session')->get('idUser');
        if (empty($idUser)) {
            return $this->redirectToRoute('login');
        }
        $this->get('session')->set('portfolio', $id);

       $retornoPortfolioStudent_tutorPortfolioByClassTutor = PortfolioStudentController::selecionarPortfolioStudentByPortfolioClassTutor($idUser, $id);

        foreach ($retornoPortfolioStudent_tutorPortfolioByClassTutor as $a) {
            $photo = $this->selecionarImagemPerfil($a['idPortfolioStudent']['idStudent']['idUser']);

            $resultadoActivityStudent = $this->selecionarAtividades($a['idPortfolioStudent']['idPortfolioStudent']);

            foreach ($resultadoActivityStudent as $row) {
                $totalNotices = $this->selecionarQuantidadeNotificacoes($row['idActivityStudent'], $idUser);

                $idVersãoAtual = $this->selecionarVersaoAtual($row['idActivityStudent']);

                if (!empty($row['dtConclusion'])) {
                    $dtConclusion = $row['dtConclusion']->format('Y-m-d H:i:s');
                } else {
                    $dtConclusion = null;
                }
                $resp[$a['idPortfolioStudent']['idStudent']['idUser']][] = array(
                    'id_activity_student' => $row['idActivityStudent'],
                    'idPortfolioStudent' => $row['idPortfolioStudent']['idPortfolioStudent'],
                    'dsTitle' => $row['idActivity']['dsTitle'],
                    'dsDescription' => $row['idActivity']['dsDescription'],
                    'nmUser' => $a['idPortfolioStudent']['idStudent']['nmUser'],
                    'idVersionActivity' => ((!empty($idVersãoAtual)) ? $idVersãoAtual : -1),
                    'notice' => $totalNotices,
                    'foto' => $photo,
                    'dtConclusion' => $dtConclusion
                );
            }
        }


        return $this->render('atividades.html.twig', array('atividades' => $resp, 'id' => $id));
    }

    public function selecionarImagemPerfil($idUsuarioImagem) {
        $photo = null;
        $select = "SELECT 
                            encode(im_photo::bytea, 'escape') as photo 
                        FROM 
                            tb_user
                        WHERE
                            id_user = " . $idUsuarioImagem;

        $this->logControle->log("selecct user: " . $select);
        $resultado = pg_query($this->logControle->db, $select);
        if (pg_affected_rows($resultado) > 0) {
            while ($row = pg_fetch_assoc($resultado)) {
                $photo = $row['photo'];
            }
        }
        return $photo;
    }

    public function selecionarAtividades($idPortfolioStudent) {
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
        $resultadoActivityStudent = $queryBuilder->getQuery()->getArrayResult();
        return $resultadoActivityStudent;
    }

    public function selecionarQuantidadeNotificacoes($idActivityStudent, $idUser) {
        $queryBuilderN = $this->em->createQueryBuilder();
        $queryBuilderN
                ->select('COUNT(n.dtNotice) as numNotices')
                ->from('AppBundle:TbNotice', 'n')
                ->innerJoin('n.idDestination', 'u', 'WITH', 'n.idDestination = u.idUser')
                ->innerJoin('n.idActivityStudent', 'a', 'WITH', 'a.idActivityStudent = n.idActivityStudent')
                ->where('n.dtRead is NULL')
                ->andWhere($queryBuilderN->expr()->eq('n.idActivityStudent', $idActivityStudent))
                ->andWhere($queryBuilderN->expr()->eq('n.idDestination', $idUser))
                ->getQuery()
                ->execute();
        $this->logControle->log($queryBuilderN);
        $totalNotices = $queryBuilderN->getQuery()->getArrayResult();

        return $totalNotices[0]['numNotices'];
    }

    public function selecionarVersaoAtual($idActivityStudent) {
        $queryBuilder = $this->em->createQueryBuilder();
        $queryBuilder
                ->select('MAX(va.idVersionActivity) as idVersionActivityMAX ')
                ->from('AppBundle:TbVersionActivity', 'va')
                ->innerJoin('va.idActivityStudent', 'a')
                ->where($queryBuilder->expr()->eq('va.idActivityStudent', $idActivityStudent))
                ->andWhere($queryBuilder->expr()->isNotNull('va.dtSubmission'))
                ->getQuery()
                ->execute();

        $this->logControle->log($queryBuilder);
        $results2 = $queryBuilder->getQuery()->getArrayResult();

        return $results2[0]['idVersionActivityMAX'];
    }

    /**
     * @Route("/atividadesAluno/{id}")
     */
    public function atividadesAluno($id) {
        $this->em = $this->getDoctrine()->getEntityManager();
        $idUser = $this->get('session')->get('idUser');
        if (empty($idUser)) {
            return $this->redirectToRoute('login');
        }
        $this->get('session')->set('portfolio', $id);


        $queryBuilder = $this->em->createQueryBuilder();
        $queryBuilder
                ->select('ps, u')
                ->from('AppBundle:TbPortfolioStudent', 'ps')
                ->innerJoin('ps.idStudent', 'u', 'WITH', 'ps.idStudent= u.idUser')
                ->Where($queryBuilder->expr()->eq('ps.idPortfolioClass', $id))
                ->andWhere($queryBuilder->expr()->eq('ps.idStudent', $idUser))
                ->getQuery()
                ->execute();

        $resultadoPortfolioStudent = $queryBuilder->getQuery()->getArrayResult();

        foreach ($resultadoPortfolioStudent as $a) {
            $photo = $this->selecionarImagemPerfil($a['idStudent']['idUser']);

            $resultadoActivityStudent = $this->selecionarAtividades($a['idPortfolioStudent']);

            foreach ($resultadoActivityStudent as $row) {

                $totalNotices = $this->selecionarQuantidadeNotificacoes($row['idActivityStudent'], $idUser);

                $queryBuilderVersaoAtual = $this->em->createQueryBuilder();
                $queryBuilderVersaoAtual
                        ->select('va.idVersionActivity as idVersionActivityAtual')
                        ->from('AppBundle:TbVersionActivity', 'va')
                        ->innerJoin('va.idActivityStudent', 'a')
                        ->where($queryBuilderVersaoAtual->expr()->eq('va.idActivityStudent', $row['idActivityStudent']))
                        ->andWhere($queryBuilderVersaoAtual->expr()->isNull('va.dtSubmission'))
                        ->getQuery()
                        ->execute();

                $resultsVersaoAtual = $queryBuilderVersaoAtual->getQuery()->getArrayResult();


                if (!empty($row['dtConclusion'])) {
                    $dtConclusion = $row['dtConclusion']->format('Y-m-d H:i:s');
                } else {
                    $dtConclusion = null;
                }

                if (count($resultsVersaoAtual) > 0) {
                    $resp[$a['idStudent']['idUser']][] = array(
                        'id_activity_student' => $row['idActivityStudent'],
                        'idPortfolioStudent' => $row['idPortfolioStudent']['idPortfolioStudent'],
                        'dsTitle' => $row['idActivity']['dsTitle'],
                        'dsDescription' => $row['idActivity']['dsDescription'],
                        'nmUser' => $a['idStudent']['nmUser'],
                        'idVersionActivity' => $resultsVersaoAtual[0]['idVersionActivityAtual'],
                        'notice' => $totalNotices,
                        'foto' => $photo,
                        'dtConclusion' => $dtConclusion
                    );
                } else {
                    $queryBuilder = $this->em->createQueryBuilder();
                    $queryBuilder
                            ->select('MAX(va.idVersionActivity) as idVer, va.txActivity as txActivityMax')
                            ->from('AppBundle:TbVersionActivity', 'va')
                            ->innerJoin('va.idActivityStudent', 'a')
                            ->where($queryBuilder->expr()->eq('va.idActivityStudent', $row['idActivityStudent']))
                            ->andWhere($queryBuilderVersaoAtual->expr()->isNotNull('va.dtSubmission'))
                            ->groupBy('a.idActivityStudent, va.idVersionActivity')
                            ->getQuery()
                            ->execute();

                    $this->logControle->log($queryBuilder);
                    $results2 = $queryBuilder->getQuery()->getArrayResult();

                    if (count($results2) > 0) {
                        $objVersionAtual = new TbVersionActivity();

                        $objact = $this->getDoctrine()
                                ->getRepository('AppBundle:TbActivityStudent')
                                ->findOneBy(array('idActivityStudent' => $row['idActivityStudent']));
                        $objVersionAtual->setIdActivityStudent($objact);
                        $objVersionAtual->setTxActivity($results2[0]['txActivityMax']);
                        $em->persist($objVersionAtual);
                        $id_version_atual = $objVersionAtual->getIdVersionActivity();
                        $objVersionAtual->setIdVersionActivitySrv($id_version_atual);
                        $em->flush();

                        $resultSync = (AddSyncWebController::addSync($id_version_atual, $idUser, "tb_version_activity", $row['idActivityStudent']));


                        $resp[$a['idStudent']['idUser']][] = array(
                            'id_activity_student' => $row['idActivityStudent'],
                            'idPortfolioStudent' => $row['idPortfolioStudent']['idPortfolioStudent'],
                            'dsTitle' => $row['idActivity']['dsTitle'],
                            'dsDescription' => $row['idActivity']['dsDescription'],
                            'idVersionActivity' => $id_version_atual,
                            'nmUser' => $a['idStudent']['nmUser'],
                            'foto' => $photo,
                            'notice' => $totalNotices,
                            'dtConclusion' => $dtConclusion
                        );
                    } else {
                        $this->logControle->log("criando versao atual para " . $row['idActivityStudent']);
                        //nao tem nenhuma versao
                        $objVersionAtual = new TbVersionActivity();

                        $objact = $this->getDoctrine()
                                ->getRepository('AppBundle:TbActivityStudent')
                                ->findOneBy(array('idActivityStudent' => $row['idActivityStudent']));
                        $objVersionAtual->setIdActivityStudent($objact);
                        $objVersionAtual->setTxActivity('');
                        $this->em->persist($objVersionAtual);
                        $id_version_atual = $objVersionAtual->getIdVersionActivity();
                        $objVersionAtual->setIdVersionActivitySrv($id_version_atual);
                        $this->em->flush();

                        $resultSync = (AddSyncWebController::addSync($id_version_atual, $idUser, "tb_version_activity", $row['idActivityStudent']));
                        $resp[$a['idStudent']['idUser']][] = array(
                            'id_activity_student' => $row['idActivityStudent'],
                            'idPortfolioStudent' => $row['idPortfolioStudent']['idPortfolioStudent'],
                            'dsTitle' => $row['idActivity']['dsTitle'],
                            'dsDescription' => $row['idActivity']['dsDescription'],
                            'nmUser' => $a['idStudent']['nmUser'],
                            'idVersionActivity' => $id_version_atual,
                            'notice' => $totalNotices,
                            'foto' => $photo,
                            'dtConclusion' => $dtConclusion
                        );
                    }
                }
            }
        }
        $this->logControle->log("RETORNO : " . print_r($resp, true));

        return $this->render('atividades.html.twig', array('atividades' => $resp, 'id' => $id));
    }

    /**
     * @Route("/finalizaAtividadeWeb")
     */
    public function finalizaAtividadeWeb() {
        $em = $this->getDoctrine()->getEntityManager();
        $this->logControle->log("finaliza Atividade na web");
        $idActivityStudent = $_POST['idActivityStudent'];
        $dataConclusion = new \DateTime();
        $dataConclusion->format('Y-m-d H:i:s');

        $objact = $this->getDoctrine()
                ->getRepository('AppBundle:TbActivityStudent')
                ->findOneBy(array('idActivityStudent' => $idActivityStudent));

        $objact->setDtConclusion($dataConclusion);

        $em->persist($objact);
        $em->flush();

        $idUser = $this->get('session')->get('idUser');
        $resultSync = (AddSyncWebController::addSync($idActivityStudent, $idUser, "tb_activity_student", $idActivityStudent));
        AddNoticeControllerWeb::addNoticeWeb($idActivityStudent, $idActivityStudent, $idActivityStudent, "tb_activity_student", $idUser);
        return new JsonResponse();
    }

}
