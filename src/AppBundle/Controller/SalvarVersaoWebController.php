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
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use AppBundle\Entity\TbComment;
use AppBundle\Entity\TbCommentVersion;
use Symfony\Component\HttpFoundation\Response;
use AppBundle\Entity\TbUser;
use AppBundle\Form\Type\TbActivityStudentType;
use AppBundle\Form\Type\TbUserType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use AppBundle\Entity\TbVersionActivity;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\EntityManager;

/**
 * Description of AtividadesController
 *
 * @author Marilia
 */
class SalvarVersaoWebController extends Controller {

    public $em;
public $logControle ;

    public function __construct() {
          $this->logControle= new LogController(); 
    }

  


    /**
     * @Route("/salvarVersao")
     */
    public function salvarVersao() {
        $idUser = $this->get('session')->get('idUser');
        if (empty($idUser)) {
            return $this->redirectToRoute('login');
        }
        $texto = $_POST["texto"];
        $this->logControle->log($texto);
        $bodytagFi = str_replace("\"background-color: #d9fce6;\"", '"' . 'background-color:#d9fce6' . '"', $texto);
        $bodytag = str_replace("class=\"mce-object mce-object-video\"", '', $bodytagFi);

        // explode("'background-color: #70e7d0;'").
        $bodytag = addslashes($bodytag);

        $idActivity = $_POST['atividade'];
        $this->logControle->log("-------salvando texto ---------" . $bodytag);
        $this->em = $this->getDoctrine()->getEntityManager();
        $newversion = new TbVersionActivity();

        $activity = $this->getDoctrine()
                ->getRepository('AppBundle:TbActivityStudent')
                ->findOneBy(array('idActivityStudent' => $idActivity));

        $dt_last_access = new \DateTime();
        $dt_last_access->format('Y-m-d H:i:s');
        $dt_submission = new \DateTime();
        $dt_submission->format('Y-m-d H:i:s');

        $newversion->setDtLastAccess($dt_last_access);
        $newversion->setDtSubmission($dt_submission);
        $newversion->setIdActivityStudent($activity);
        $newversion->setTxActivity($bodytag);
        $this->logControle->log("criamos a versao");

        $this->em->persist($newversion);
        $idversionactivitySrv = $newversion->getIdVersionActivity();
        $nm_table = "tb_version_activity";
        $this->em->flush();
        $idUser = $this->get('session')->get('idUser');
        $resultSync = (AddSyncWebController::addSync($idversionactivitySrv, $idUser, $nm_table, $idActivity));
        AddNoticeControllerWeb::addNoticeWeb($idversionactivitySrv, $idversionactivitySrv, $idActivity, $nm_table, $idUser);
        $queryBuilder = $this->em->createQueryBuilder();
        $queryBuilder
                ->select('va.idVersionActivity as id')
                ->from('AppBundle:TbVersionActivity', 'va')
                ->innerJoin('va.idActivityStudent', 'a')
                ->where($queryBuilder->expr()->eq('va.idActivityStudent', $idActivity))
                ->andWhere($queryBuilder->expr()->isNull('va.dtSubmission'))
                ->getQuery()
                ->execute();
        $resultVersao = $queryBuilder->getQuery()->getArrayResult();
        $this->logControle->log("versao " . $resultVersao[0]['id']);
        $vers = $this->getDoctrine()
                ->getRepository('AppBundle:TbVersionActivity')
                ->findOneBy(array('idVersionActivity' => $resultVersao[0]['id']));
        $vers->setTxActivity($bodytag);
        $this->em->persist($vers);
        $this->em->flush();

//        try {
//            $queryBuilder = $this->em->createQueryBuilder();
//            $queryBuilder
//                    ->update('AppBundle:TbVersionActivity', 'v')
//                    ->set('v.txActivity', $queryBuilder->expr()->literal($bodytag))
//                    ->where($queryBuilder->expr()->isNull('v.dtSubmission'))
//                    ->andWhere($queryBuilder->expr()->eq('v.idActivityStudent', $idActivity))
//                    ->getQuery()
//                    ->execute();
//        } catch (Exception $e) {
//            $this->logControle->log("nao deu certo do outro jeito, vamos tentar outro");
//
//            $queryBuilder = $this->em->createQueryBuilder();
//            $queryBuilder
//                    ->update('AppBundle:TbVersionActivity', 'v')
//                    ->set('v.txActivity', $queryBuilder->expr()->literal("" . $bodytag . ""))
//                    ->where($queryBuilder->expr()->isNull('v.dtSubmission'))
//                    ->andWhere($queryBuilder->expr()->eq('v.idActivityStudent', $idActivity))
//                    ->getQuery()
//                    ->execute();
//        } finally {
//            $this->logControle->log("nao deu certo do outro jeito, vamos tentar outro");
//
//            $queryBuilder = $this->em->createQueryBuilder();
//            $queryBuilder
//                    ->update('AppBundle:TbVersionActivity', 'v')
//                    ->set('v.txActivity', $queryBuilder->expr()->literal("" . $bodytag . ""))
//                    ->where($queryBuilder->expr()->isNull('v.dtSubmission'))
//                    ->andWhere($queryBuilder->expr()->eq('v.idActivityStudent', $idActivity))
//                    ->getQuery()
//                    ->execute();
//        }
        $this->logControle->log("atualiza atual");
        $this->logControle->log($queryBuilder);
        $this->logControle->log('Exceção capturada: ', $this->em->getConnection()->errorInfo());

        return new JsonResponse($dt_submission);
    }

    /**
     * @Route("/updateVersaoAtual")
     */
    public function updateVersaoAtual() {
        $idUser = $this->get('session')->get('idUser');
        if (empty($idUser)) {
            return $this->redirectToRoute('login');
        }
        $texto = $_POST["texto"];

        $bodytagFi = str_replace("\"background-color: #d9fce6;\"", '"' . 'background-color:#d9fce6' . '"', $texto);
        $bodytag = str_replace("class=\"mce-object mce-object-video\"", '', $bodytagFi);
        $bodytag = addslashes($bodytag);
        $idActivity = $_POST['atividade'];
        $this->logControle->log("-------salvando texto ---------" . $bodytag);
        $this->em = $this->getDoctrine()->getEntityManager();
        $this->logControle->log("primeiro");
        $queryBuilder = $this->em->createQueryBuilder();
        $queryBuilder
                ->select('va.idVersionActivity as id')
                ->from('AppBundle:TbVersionActivity', 'va')
                ->innerJoin('va.idActivityStudent', 'a')
                ->where($queryBuilder->expr()->eq('va.idActivityStudent', $idActivity))
                ->andWhere($queryBuilder->expr()->isNull('va.dtSubmission'))
                ->getQuery()
                ->execute();
        $resultVersao = $queryBuilder->getQuery()->getArrayResult();
        $this->logControle->log("versao " . $resultVersao[0]['id']);
        $vers = $this->getDoctrine()
                ->getRepository('AppBundle:TbVersionActivity')
                ->findOneBy(array('idVersionActivity' => $resultVersao[0]['id']));
        $vers->setTxActivity($bodytag);

        $dt_last_access = new \DateTime();
        $dt_last_access->format('Y-m-d H:i:s');

        $vers->setDtLastAccess($dt_last_access);
        $this->em->persist($vers);
        $this->em->flush();



//        try {
//            $queryBuilder = $this->em->createQueryBuilder();
//            $queryBuilder
//                    ->update('AppBundle:TbVersionActivity', 'v')
//                    ->set('v.txActivity', $queryBuilder->expr()->literal($bodytag))
//                    ->where($queryBuilder->expr()->isNull('v.dtSubmission'))
//                    ->andWhere($queryBuilder->expr()->eq('v.idActivityStudent', $idActivity))
//                    ->getQuery()
//                    ->execute();
//        } catch (Exception $e) {
//            $this->logControle->log("nao deu certo antes, vamos ver agora");
//            $queryBuilder = $this->em->createQueryBuilder();
//            $queryBuilder
//                    ->update('AppBundle:TbVersionActivity', 'v')
//                    ->set('v.txActivity', $queryBuilder->expr()->literal("" . $bodytag . ""))
//                    ->where($queryBuilder->expr()->isNull('v.dtSubmission'))
//                    ->andWhere($queryBuilder->expr()->eq('v.idActivityStudent', $idActivity))
//                    ->getQuery()
//                    ->execute();
//        } finally {
//            $this->logControle->log("nao deu certo antes, vamos ver agora");
//            $queryBuilder = $this->em->createQueryBuilder();
//            $queryBuilder
//                    ->update('AppBundle:TbVersionActivity', 'v')
//                    ->set('v.txActivity', $queryBuilder->expr()->literal($bodytag))
//                    ->where($queryBuilder->expr()->isNull('v.dtSubmission'))
//                    ->andWhere($queryBuilder->expr()->eq('v.idActivityStudent', $idActivity))
//                    ->getQuery()
//                    ->execute();
//        }
//        if (!$queryBuilder) {
//            $this->logControle->log("nao deu certo antes, vamos ver agora");
//            $queryBuilder = $this->em->createQueryBuilder();
//            $queryBuilder
//                    ->update('AppBundle:TbVersionActivity', 'v')
//                    ->set('v.txActivity', $queryBuilder->expr()->literal($bodytag))
//                    ->where($queryBuilder->expr()->isNull('v.dtSubmission'))
//                    ->andWhere($queryBuilder->expr()->eq('v.idActivityStudent', $idActivity))
//                    ->getQuery()
//                    ->execute();
//        }
        $this->logControle->log("log");

        $this->logControle->log($queryBuilder);
        return new JsonResponse();
    }

    /**
     * @Route("/carregaMenuVersoes")
     */
    public function carregaMenuVersoes(Request $req) {
        $this->logControle->log("versoes");
        if ($req->getSession()) {
//$this->logControle->log(print_r($req->getSession(), true));
            $session = $req->getSession();
            $idUser = $session->get('idUser');
            $this->logControle->log($idUser);
        } else {
            $this->logControle->log("nao rolou");
        }
        $idActivityStudent = $_POST['atividade'];
        $codUltimaVersao = 0;

        $this->logControle->log("atividade " . $idActivityStudent);
        $this->em = $this->getDoctrine()->getEntityManager();
        if ($codUltimaVersao == 0) {
            $queryBuilder = $this->em->createQueryBuilder();
            $queryBuilder
                    ->select('va,a')
                    ->from('AppBundle:TbVersionActivity', 'va')
                    ->innerJoin('va.idActivityStudent', 'a')
                    ->where($queryBuilder->expr()->eq('va.idActivityStudent', $idActivityStudent))
                    ->orderBy('va.idVersionActivity', 'ASC')
                    ->getQuery()
                    ->execute();
        }
        $this->logControle->log($queryBuilder);
        $results = $queryBuilder->getQuery()->getArrayResult();
        $this->logControle->log("codigo ultima versao " . $codUltimaVersao);
        $this->logControle->log("SQL SELECT VERSION : " . print_r($results, true));

        $totalItens = count($results);

        if ($totalItens > 0) {
            foreach ($results as $row) {
                $this->logControle->log("id version para selecioncar notificacoes" . $row['idVersionActivity']);
                $totalNoticeVersion = $this->noticeVersion($idActivityStudent, $row['idVersionActivity']);

                if (!empty($row['dtLastAccess'])) {
                    $dtLastAccess = $row['dtLastAccess']->format('Y-m-d H:i:s');
                } else {
                    $dtLastAccess = null;
                }
                if (!empty($row['dtSubmission'])) {
                    $dtSubmission = $row['dtSubmission']->format('Y-m-d H:i:s');
                } else {
                    $dtSubmission = null;
                }
                if (!empty($row['dtVerification'])) {
                    $dtVerification = $row['dtVerification']->format('Y-m-d H:i:s');
                } else {
                    $dtVerification = null;
                }

                $versionsActivity[] = array(
                    'idVersionActivity' => $row['idVersionActivity'],
                    'txActivity' => $row['txActivity'],
                    'dtLastAccess' => $dtLastAccess,
                    'dtSubmission' => $dtSubmission,
                    'dtVerification' => $dtVerification,
                    'idActivityStudent' => $row['idActivityStudent']['idActivityStudent'],
                    'totalNotices' => $totalNoticeVersion
                );

                $this->logControle->log(print_r($versionsActivity, true));
            }
        }
        if (!isset($versionsActivity)) {
            $versionsActivity = array();
        }
        $this->logControle->log("CARREGA MENU result " . print_r($versionsActivity, true));
        return new JsonResponse($versionsActivity);
    }

    public function noticeVersion($idActivityStudent, $idVersion) {


        $this->logControle->log("notice version");
        $idUser = $this->get('session')->get('idUser');

        $this->logControle->log("idUser" . $idUser);
        $this->em = $this->getDoctrine()->getEntityManager();
        $queryBuilderN = $this->em->createQueryBuilder();
        $queryBuilderN
                ->select('n.idNotice, n.coIdTableSrv')
                ->from('AppBundle:TbNotice', 'n')
                ->innerJoin('n.idDestination', 'u', 'WITH', 'u.idUser = n.idDestination')
                ->innerJoin('n.idAuthor', 'us', 'WITH', 'us.idUser = n.idAuthor')
                ->innerJoin('n.idActivityStudent', 'ac', 'WITH', 'ac.idActivityStudent = n.idActivityStudent')
                ->Where($queryBuilderN->expr()->isNull('n.dtRead'))
                ->andWhere($queryBuilderN->expr()->eq('n.idActivityStudent', "" . $idActivityStudent . ""))
                ->andWhere($queryBuilderN->expr()->eq('n.nmTable', $queryBuilderN->expr()->literal('tb_comment')))

                //  ->andWhere($queryBuilderN->expr()->eq('n.nmTable', $queryBuilderN->expr()->literal("tb_comment")))
                ->andWhere($queryBuilderN->expr()->eq('n.idDestination', $idUser))
                ->getQuery()
                ->execute();
        $this->logControle->log("query notificacao " . $queryBuilderN);
        $retornoNotice = $queryBuilderN->getQuery()->getArrayResult();

        $this->logControle->log("notificacao " . print_r($retornoNotice, true));
        $totalNotices = count($retornoNotice);
        if ($totalNotices > 0) {
            $idComment = $retornoNotice[0]['coIdTableSrv'];

            $queryBuilderCV = $this->em->createQueryBuilder();
            $queryBuilderCV
                    ->select('c')
                    ->from('AppBundle:TbComment', 'c')
                    ->innerJoin('c.idCommentVersion', 'cv', 'WITH', 'c.idCommentVersion = cv.idCommentVersion')
                    ->where($queryBuilderCV->expr()->eq('c.tpComment', $queryBuilderCV->expr()->literal("O")))
                    ->andWhere($queryBuilderCV->expr()->eq('c.idComment', "" . $idComment . ""))
                    ->andWhere($queryBuilderCV->expr()->eq('cv.idVersionActivity', "" . $idVersion . ""))
                    ->getQuery()
                    ->execute();

            $retorno = $queryBuilderCV->getQuery()->getArrayResult();
            $this->logControle->log("retorno das notificacoes na web " . print_r($retorno, true));
            $total = count($retorno);
            if ($total > 0) {
                return $totalNotices;
            } else {
                return $totalNotices = 0;
            }
        }
        return $totalNotices;
    }

    /**
     * @Route("/getNuCommentActivityAction")
     */
    public function getNuCommentActivityAction($versao) {
        $idUser = $this->get('session')->get('idUser');
        if (empty($idUser)) {
            return $this->redirectToRoute('login');
        }
        $this->em = $this->getDoctrine()->getEntityManager();
        $queryBuilder = $this->em->createQueryBuilder();
        $queryBuilder
                ->select('cv')
                ->from('AppBundle:TbCommentVersion', 'cv')
                //->innerJoin('cv.idVersionActivity', 'v', 'WITH', 'cv.idVersionActivity = v.idVersionActivity')
                ->Where($queryBuilder->expr()->eq('cv.idVersionActivity', $versao))
                ->getQuery()
                ->execute();
        //  $this->logControle->log($queryBuilder);
        $results = $queryBuilder->getQuery()->getArrayResult();
        $totalItens = count($results);
        if ($totalItens > 0) {
            // $this->logControle->log("getNuCommentActivityAction  : " . print_r($results[0]));
            $valor = $results[0]["idCommentVersion"];
            $results = array(
                "idCommentVersion" => $valor + 1
            );
            $response = $results;
        } else {
            $response = 1;
        }

        // $this->logControle->log(print_r($response, true));
        $this->logControle->log("FIM");
        $this->logControle->log("==============================================================================");
        return new JsonResponse($response);
    }

}
