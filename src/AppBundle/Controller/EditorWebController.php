<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use AppBundle\Entity\TbComment;
use AppBundle\Entity\TbCommentVersion;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

header('Content-Type: text/html; charset=utf-8');

/**
 * Description of EditorWebController
 *
 * @author Marilia
 */
class EditorWebController extends Controller {

    public $em;
    public $versaoEscolhida;
    public $versionsActivity;
    public $numComActivity;
    public $numNotices;
    public $formComentarioGeral;
    public $formComentarioEspecifico;
    public $formAuxiliar;
    public $dadosUsuarios;
    public $linkPerfil;
    public $logControle;

    public function __construct() {
        $this->logControle = new LogController();
    }

    public function selecionarVersoes($idVersionActivity, $idActivityStudent) {

        $queryBuilderV = $this->em->createQueryBuilder();
        $queryBuilderV
                ->select('va,a')
                ->from('AppBundle:TbVersionActivity', 'va')
                ->innerJoin('va.idActivityStudent', 'a')
                ->where($queryBuilderV->expr()->eq('va.idActivityStudent', $idActivityStudent))
                ->andWhere($queryBuilderV->expr()->eq('va.idVersionActivity', $idVersionActivity))
                ->orderBy('va.idVersionActivity', 'ASC')
                ->getQuery()
                ->execute();

        return $resultVersao = $queryBuilderV->getQuery()->getArrayResult();
    }

    public function jsonVersaoEscolhida($rowVersao) {
        if (!empty($rowVersao['dtLastAccess'])) {
            $dtLastAccess = $rowVersao['dtLastAccess']->format('Y-m-d H:i:s');
        } else {
            $dtLastAccess = null;
        }
        if (!empty($rowVersao['dtSubmission'])) {
            $dtSubmission = $rowVersao['dtSubmission']->format('Y-m-d H:i:s');
        } else {
            $this->get('session')->set('versaoAtualEditavel', $rowVersao['idVersionActivity']);
            $dtSubmission = null;
        }
        if (!empty($rowVersao['dtVerification'])) {
            $dtVerification = $rowVersao['dtVerification']->format('Y-m-d H:i:s');
        } else {
            $dtVerification = null;
        }
        $textoFormatado = $this->alteraTextoAtividade($rowVersao['txActivity']);
        $this->versaoEscolhida['versaoEscolhida'] = array(
            'idVersionActivity' => $rowVersao['idVersionActivity'],
            'txActivity' => $textoFormatado,
            'dtLastAccess' => $dtLastAccess,
            'dtSubmission' => $dtSubmission,
            'dtVerification' => $dtVerification,
            'idActivityStudent' => $rowVersao['idActivityStudent']['idActivityStudent']
        );
    }

    public function alteraTextoAtividade($textoActivity) {
        $textoSemSlashes = stripslashes($textoActivity);
        $nova = str_replace("video src=", "img src=", $textoSemSlashes);
        return $nova;
    }

    public function selecionarDadosVersaoAtual($idVersionActivity, $idActivityStudent) {
        if ($idVersionActivity > 0) {
            $resultadoVersao = $this->selecionarVersoes($idVersionActivity, $idActivityStudent);
            $totalItensVersao = count($resultadoVersao);
            if ($totalItensVersao > 0) {
                foreach ($resultadoVersao as $rowVersao) {
                    $this->jsonVersaoEscolhida($rowVersao);
                }
                $this->numComActivity = $this->getMaxNumComActvity($idActivityStudent);
            }
        } else {
            //nenhuma versao
            $this->versaoEscolhida = array();
            $this->versionsActivity = array();
            $this->numComActivity = 0;
        }
    }

    public function selecionarDadosVersoes($idActivityStudent, $idVersionActivity) {
        $queryBuilder = $this->em->createQueryBuilder();
        $queryBuilder
                ->select('va,a')
                ->from('AppBundle:TbVersionActivity', 'va')
                ->innerJoin('va.idActivityStudent', 'a')
                ->where($queryBuilder->expr()->eq('va.idActivityStudent', $idActivityStudent))
                ->orderBy('va.idVersionActivity', 'ASC')
                ->getQuery()
                ->execute();

        $results = $queryBuilder->getQuery()->getArrayResult();

        $this->logControle->log("SQL SELECT VERSION : " . print_r($results, true));

        $totalItens = count($results);

        if ($totalItens > 0) {
            foreach ($results as $row) {

                if (!empty($row['dtLastAccess'])) {
                    $dtLastAccess = $row['dtLastAccess']->format('Y-m-d H:i:s');
                } else {
                    $dtLastAccess = null;
                }
                if (!empty($row['dtSubmission'])) {
                    $dtSubmission = $row['dtSubmission']->format('Y-m-d H:i:s');
                } else {
                    $this->get('session')->set('versaoAtualEditavel', $row['idVersionActivity']);
                    $dtSubmission = null;
                }
                if (!empty($row['dtVerification'])) {
                    $dtVerification = $row['dtVerification']->format('Y-m-d H:i:s');
                } else {
                    $dtVerification = null;
                }

                $textoSemSlashes = stripslashes($row['txActivity']);

                $nova = str_replace("video src=", "img src=", $textoSemSlashes);
                $this->versionsActivity['versions'][] = array(
                    'idVersionActivity' => $row['idVersionActivity'],
                    'txActivity' => $nova,
                    'dtLastAccess' => $dtLastAccess,
                    'dtSubmission' => $dtSubmission,
                    'dtVerification' => $dtVerification,
                    'idActivityStudent' => $row['idActivityStudent']['idActivityStudent']
                );
            }
        } else {
            if ($idVersionActivity == 0) {
                $this->versionsActivity = array();
            }
        }
    }

    function gerarFormulariosComentarios($idActivityStudent, $idVersionActivity) {

        $commentGeral = new TbComment();

        $this->formComentarioGeral = $this->createFormBuilder($commentGeral)
                ->add('txComment', TextType::class, array('label' => false))
                ->add('save', SubmitType::class, array('label' => false))
                ->add('idActivityStudent', HiddenType::class, array('data' => $idActivityStudent))
                ->getForm();


        $commentEspecific = new TbCommentVersion;
        $this->formComentarioEspecifico = $this->createFormBuilder($commentGeral)
                ->add('txComment', TextType::class, array('label' => false, 'attr' => array('class' => 'teste')))
                ->add('save', SubmitType::class, array('label' => false))
                ->add('idActivityStudent', HiddenType::class, array('data' => $idActivityStudent))
                ->getForm();

        $this->formAuxiliar = $this->createFormBuilder($commentEspecific)
                ->add('idVersionActivity', HiddenType::class, array('data' => $idVersionActivity))
                ->getForm();
    }

    public function verificarTipoUsuario($idUser, $idPortfolioStudent) {
        $flagPerfil = '';
        $retornoPortfolioStudent = PortfolioStudentController::selecionarPortfolioStudentByIdPortfolioStudent($idPortfolioStudent);
        $retornoVisitante = VisitanteController::verificarVisitante($idUser);
        if (count($retornoVisitante) > 0) {
            if ($retornoPortfolioStudent[0]['idPortfolioStudent']['idPortfolioClass']['idClass']['idClass'] == $retornoVisitante[0]['idClass']['idClass']) {
                if ($flagPerfil != 'T') {
                    $flagPerfil = 'V';
                }
            }
        }

        if ($retornoPortfolioStudent[0]['idPortfolioStudent']['idStudent']['idUser'] == $idUser) {
            $flagPerfil = 'A';
        } else {
            if ($retornoPortfolioStudent[0]['idTutor']['idUser'] == $idUser) {
                $flagPerfil = 'T';
            }
        }


        $this->logControle->logWeb($flagPerfil);
        return $flagPerfil;
    }

    function search($array, $key, $value) {
        $results =array();

        if (is_array($array)) {
            if (isset($array[$key]) && $array[$key] == $value) {
                $results[] = $array;
            }

            foreach ($array as $subarray) {
                $results = array_merge($results,  $this->search($subarray, $key, $value));
            }
        }

        return $results;
    }

    public function selecionarPerfil($idPortfolioStudent, $flagPerfil) {
        $this->dadosUsuarios = array();

        $retornoPortfolioStudent = PortfolioStudentController::selecionarPortfolioStudentByIdPortfolioStudent($idPortfolioStudent);
        foreach ($retornoPortfolioStudent as $array) {
          
           
        
        if ($flagPerfil == 'A') {
            $this->linkPerfil = 'editorTextoAluno.html.twig';
             if ($array['idTutor']['idUser'] != $this->get('session')->get('idUser')) {
                $this->dadosUsuarios[] = array(
                    'nome' => $array['idTutor']['nmUser'],
                    'foto' => UserController::selecionarFotoUsuario($array['idTutor']['idUser']),
                    'idUser' => $array['idTutor']['idUser']
                );
            }
        } else {
             if (empty($this->search($this->dadosUsuarios,'idUser',$array['idPortfolioStudent']['idStudent']['idUser']))) {
                $this->dadosUsuarios[] = array(
                    'nome' => $array['idPortfolioStudent']['idStudent']['nmUser'],
                    'foto' => UserController::selecionarFotoUsuario($array['idPortfolioStudent']['idStudent']['idUser']),
                    'idUser' => $array['idPortfolioStudent']['idStudent']['idUser']
                );
            }
            $this->linkPerfil = 'editorTextoTutor.html.twig';
        }
        }
    }

    /**
     * @Route("/editor/{idActivityStudent}/{idVersionActivity}")
     */
    public function editor(Request $req, $idActivityStudent, $idVersionActivity) {
        $idUser = $this->get('session')->get('idUser');
        $flagVisitante = false;
        $flagCondidadoPermissaoComentarios = false;
        if (empty($idUser)) {
            return $this->redirectToRoute('login');
        }
        $this->em = $this->getDoctrine()->getEntityManager();
        $this->get('session')->set('versaoAtual', $idVersionActivity);
        $this->get('session')->set('atividadeAtual', $idActivityStudent);

        $this->selecionarDadosVersaoAtual($idVersionActivity, $idActivityStudent);

        $this->numNotices = $this->getNumberNotices($idActivityStudent, $idUser);

        $this->selecionarDadosVersoes($idActivityStudent, $idVersionActivity);
        $this->gerarFormulariosComentarios($idActivityStudent, $idVersionActivity);

        $queryBuilderUser = $this->em->createQueryBuilder();
        $queryBuilderUser
                ->select('a,ps, aa')
                ->from('AppBundle:TbActivityStudent', 'a')
                ->innerJoin('a.idPortfolioStudent', 'ps', 'WITH', 'a.idPortfolioStudent = ps.idPortfolioStudent')
                ->innerJoin('a.idActivity', 'aa', 'WITH', 'a.idActivity = aa.idActivity')
                ->where($queryBuilderUser->expr()->eq('a.idActivityStudent ', $idActivityStudent))
                ->getQuery()
                ->execute();
        $portfolio = $queryBuilderUser->getQuery()->getArrayResult();
        if (!empty($portfolio[0]['dtConclusion'])) {
            $dtConclusion = $portfolio[0]['dtConclusion']->format('Y-m-d H:i:s');
        } else {
            $dtConclusion = NULL;
        }
        $idPortfolioStudent = $portfolio[0]['idPortfolioStudent']['idPortfolioStudent'];
        $nomeAtividade = $portfolio[0]['idActivity']['dsTitle'];
        $this->logControle->log("RESULTADO DO PORT1 -seleciona o portfolio da atividadeAtual-: " . print_r($portfolio, true));

        $flagPerfil = $this->verificarTipoUsuario($idUser, $idPortfolioStudent);


        $this->selecionarPerfil($idPortfolioStudent, $flagPerfil);
        if ($flagPerfil == 'V') {

            $flagVisitante = true;
            $flagCondidadoPermissaoComentarios = $this->verificarPermissaoVisitante($idUser);
        }

        return $this->render($this->linkPerfil, array(
                    'form' => $this->formComentarioGeral->createView(), 'form2' => $this->formComentarioEspecifico->createView(),
                    'form3' => $this->formAuxiliar->createView(), 'versions' => $this->versionsActivity,
                    'versaoEscolhida' => $this->versaoEscolhida, 'numComActivity' => $this->numComActivity,
                    'idActivityStudent' => $idActivityStudent, 'dt_conclusion' => $dtConclusion,
                    'dadosUsuarios' => $this->dadosUsuarios,
                    'nomeAtividade' => $nomeAtividade, 'numNotices' => $this->numNotices,
                    'flagVisitante' => $flagVisitante, 'flagPermissaoVisitante' => $flagCondidadoPermissaoComentarios));
    }

    public function verificarPermissaoVisitante($idUser) {

        $idClass = $this->getIdClassByPortfolioClass();

        $queryBuilderUser = $this->em->createQueryBuilder();
        $queryBuilderUser
                ->select('g.flComments')
                ->from('AppBundle:TbGuest', 'g')
                ->innerJoin('g.idUser', 'u', 'WITH', 'g.idUser = u.idUser')
                ->innerJoin('g.idClass', 'c', 'WITH', 'g.idClass = c.idClass')
                ->where($queryBuilderUser->expr()->eq('g.idClass ', $idClass))
                ->andwhere($queryBuilderUser->expr()->eq('g.idUser ', $idUser))
                ->getQuery()
                ->execute();
        $permissao = $queryBuilderUser->getQuery()->getArrayResult();
        $this->logControle->logWeb(print_r($permissao, true));
        if ($permissao[0]['flComments'] == 'S') {
            return true;
        } else {
            return false;
        }
    }

    function getIdClassByPortfolioClass() {
        $idPortfolioClass = $this->get('session')->get('portfolio');

        $objetoPortfolioClass = $this->getDoctrine()
                ->getRepository('AppBundle:TbPortfolioClass')
                ->findOneBy(array('idPortfolioClass' => $idPortfolioClass));

        $objetoIdClass = $objetoPortfolioClass->getIdClass();
        return $objetoIdClass->getIdClass();
    }

    public function getMaxNumComActvity($idActivityStudent) {
        $this->logControle->log("get num com");

        $this->em = $this->getDoctrine()->getEntityManager();
        $numComVersion = 0;
        $queryBuilderV = $this->em->createQueryBuilder();
        $queryBuilderV
                ->select('va,a')
                ->from('AppBundle:TbVersionActivity', 'va')
                ->innerJoin('va.idActivityStudent', 'a')
                ->where($queryBuilderV->expr()->eq('va.idActivityStudent', $idActivityStudent))
                ->getQuery()
                ->execute();

        $this->logControle->log("query");
        $resultVersao = $queryBuilderV->getQuery()->getArrayResult();

        $this->logControle->log("VERSAO ESCOLHIDA : " . print_r($resultVersao, true));

        $totalItensVersao = count($resultVersao);

        if ($totalItensVersao > 0) {
            foreach ($resultVersao as $rowVersao) {
                $queryBuilderCom = $this->em->createQueryBuilder();
                $queryBuilderCom
                        ->select('cv.nuCommentActivity')
                        ->from('AppBundle:TbCommentVersion', 'cv')
                        ->where($queryBuilderCom->expr()->eq('cv.idVersionActivity', $rowVersao['idVersionActivity']))
                        ->getQuery()
                        ->execute();

                $resultCom = $queryBuilderCom->getQuery()->getArrayResult();
                $this->logControle->log("comvers " . print_r($resultCom, true));
                if (count($resultCom) > 0) {
                    foreach ($resultCom as $rowCom) {
                        $this->logControle->log("COMPARANDO " . $rowCom['nuCommentActivity'] . " COM O COMVER " . $numComVersion);

                        if ($numComVersion < $rowCom['nuCommentActivity']) {
                            $numComVersion = $rowCom['nuCommentActivity'];
                        }
                    }
                }
            }
        }
        $this->logControle->log("RETORNO " . $numComVersion);
        return $numComVersion;
    }

    public function getNumberNotices($idActivityStudent, $idUser) {
        $nomeTable = 'tb_comment';
        $em = $this->getDoctrine()->getEntityManager();
        $queryBuilderN = $em->createQueryBuilder();
        $queryBuilderN
                ->select('n.coIdTable as coIdTable')
                ->from('AppBundle:TbNotice', 'n')
                ->innerJoin('n.idDestination', 'u', 'WITH', 'n.idDestination = u.idUser')
                ->innerJoin('n.idActivityStudent', 'a', 'WITH', 'a.idActivityStudent = n.idActivityStudent')
                ->where('n.dtRead is NULL')
                ->andWhere($queryBuilderN->expr()->eq('n.nmTable', "'" . $nomeTable . "'"))
                ->andWhere($queryBuilderN->expr()->eq('n.idActivityStudent', $idActivityStudent))
                ->andWhere($queryBuilderN->expr()->eq('n.idDestination', $idUser))
                ->getQuery()
                ->execute();
        $this->logControle->log($queryBuilderN);
        $totalNotices = $queryBuilderN->getQuery()->getArrayResult();


        if ($totalNotices) {
            foreach ($totalNotices as $var) {
                $queryBuilder = $em->createQueryBuilder();
                $queryBuilder
                        ->select('COUNT(c.idComment) as numNotices')
                        ->from('AppBundle:TbComment', 'c')
                        ->innerJoin('c.idActivityStudent', 'a', 'WITH', 'c.idActivityStudent = a.idActivityStudent')
                        ->innerJoin('c.idAuthor', 'u')
                        ->where($queryBuilder->expr()->eq('c.idComment', $var['coIdTable']))
                        ->andWhere($queryBuilder->expr()->eq('c.tpComment', "'G'"))
                        ->groupBy('c.idComment')
                        ->orderBy('c.idComment', 'ASC')
                        ->getQuery()
                        ->execute();

                $results = $queryBuilder->getQuery()->getArrayResult();
                if ($results) {
                    return $results[0]['numNotices'];
                } else {
                    return 0;
                }
            }
        }
    }

}
