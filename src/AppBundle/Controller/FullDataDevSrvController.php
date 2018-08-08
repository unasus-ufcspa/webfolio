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
use Symfony\Component\Validator\Constraints\DateTime;
use AppBundle\Entity\TbComment;
use AppBundle\Entity\TbCommentVersion;
use AppBundle\Entity\TbVersionActivity;
use AppBundle\Entity\TbAttachment;
use AppBundle\Entity\TbAttachComment;
use AppBundle\Entity\TbReference;
use AppBundle\Entity\TbAttachActivity;
use AppBundle\Controller\AddSyncController;
use AppBundle\Controller\AddNoticeController;
use AppBundle\Entity\TbSync;
use AppBundle\Entity\TbAnnotation;

header('Content-Type: text/html; charset=UTF-8');
mb_internal_encoding("iso-8859-1");
mb_http_output("iso-8859-1");
ob_start("mb_output_handler");

/**
 * Description of FullDataDevSrvController
 *
 * @author Marilia
 */
class FullDataDevSrvController extends Controller {

    public $id_comment_version = array();
    public $error = array();
    public $em;
    public $idsComVersions = array();
    public $anexo;
    public $logControle;

    public function __construct() {
        $this->logControle = new LogController();
    }

    public function addError($flag) {
        $status = array(
            1 => 'Erro no banco!',
            2 => 'Campos obrigatórios vazios!',
            3 => 'Json vazio!',
            4 => 'Nenhum json recebido pelo servidor!',
            5 => 'Usuário não localizado!',
            6 => 'Usuário sem portFolio cadastrado!',
            7 => 'Nenhum dado foi econtrado no Banco de Dados!',
            8 => 'Não há dados para sincronizar!',
            9 => 'IdDevice/tpDevice não pode estar vazio!',
            10 => 'Falha ao atualizar tabela!',
            11 => 'Falha na inserção dos dados no Banco de Dados',
            12 => 'Basic Data ja foi sincronizado!',
            13 => 'First Login ja foi sincronizado!',
            14 => 'Falha na inserção dos dados para sincronismo!'
        );

        $array = array(
            "erro" => $status[$flag]
        );
        return $array;
    }

    /**
     * @Route("/fullDataDevSrv")
     */
    public function fullDataDevSrv(Request $req) {
        $this->em = $this->getDoctrine()->getEntityManager();
        $this->logControle->log('INICIO fullDataSrvDev');

        $this->response = NULL;
        $this->error = NULL;
        if (0 === strpos($req->headers->get('Content-Type'), 'application/json')) {
            $data = json_decode($req->getContent(), true);
            $req->request->replace(is_array($data) ? $data : array());
            $this->logControle->log("REQUEST fullDataDevSrv : " . print_r($data, true));

            if ((!empty($data)) && (!empty($data['fullDataDevSrv_request']))) {

                $data = $data['fullDataDevSrv_request'];

                if ((!empty($data['device']['ds_hash'])) && (!empty($data['device']['id_user']))) {
                    $iduser = $data['device']['id_user'];
                    $ds_hash = $data['device']['ds_hash'];

                    //a versao tem que ir primeiro
                    if (!empty($data['version']['tb_version_activity'])) {
                        $version[] = $data['version']['tb_version_activity'];
                        $this->results['fullDataDevSrv_response']['version']['tb_version_activity'] = $this->addVersionActivity($version, $iduser, $ds_hash);
                    }

                    if (!empty($data['comment']['tb_comment'])) {
                        $comment[] = $data['comment']['tb_comment'];
                        $this->results['fullDataDevSrv_response']['comment']['tb_comment'] = $this->addComment($comment, $iduser, $ds_hash);
                    }

                    if (!empty($data['reference']['tb_reference'])) {
                        $reference[] = $data['reference']['tb_reference'];
                        $this->results['fullDataDevSrv_response']['reference']['tb_reference'] = $this->addReference($reference, $iduser, $ds_hash);
                    }
                    if (!empty($data['annotation']['tb_annotation'])) {
                        $annotation[] = $data['annotation']['tb_annotation'];
                        $this->results['fullDataDevSrv_response']['annotation']['tb_annotation'] = $this->addAnnotation($annotation, $iduser, $ds_hash);
                    }


                    if (!empty($data['user']['tb_user'])) {
                        $user[] = $data['user']['tb_user'];
                        $user_upd = $this->updateUserDevSrv($user, $iduser, $ds_hash);
                        if (!empty($user_upd)) {
                            $erro = $user_upd;
                        }
                    }
                    if (!empty($data['notice']['tb_notice'])) {
                        $notice[] = $data['notice']['tb_notice'];
                        $this->updateReadNotice($notice);
                    }


                    if (!empty($data['activityStudent']['tb_activity_student'])) {
                        $activity[] = $data['activityStudent']['tb_activity_student'];
                        $local = $data['activityStudent']['tb_activity_student'];
                        $this->logControle->log(print_r($activity, true));
                        foreach ($local as $value) {

                            $this->logControle->log(print_r($value, true));

                            if (isset($value['dt_conclusion'])) {
                                $this->logControle->log("existe");
                                $dataConclusion = $value['dt_conclusion'];
                                $id = $value['id_activity_student'];
                                if ($this->finalizaAtividade($dataConclusion, $id)) {
                                    $resultSync = (AddSyncController::addSync($id, $iduser, $ds_hash, "tb_activity_student", $id)); //sync de tb_activity_student é a finalização da atividade
                                    AddNoticeController::addNotice($id, $id, $id, "tb_activity_student", $iduser, $ds_hash);

                                    $array = array(
                                        'id_activity_student' => $id,
                                        'dt_conclusion' => $dataConclusion
                                    );
                                    $this->results['fullDataDevSrv_response']['activityStudent']['tb_activity_student'][] = $array;
                                }
                            } else {
                                $this->logControle->log("nao existe");
                            }
                            $this->logControle->log(print_r($data['activityStudent'], true));
                            if (isset($value['attachment'])) {
                                $this->logControle->log("add actiivty attach");
                                $this->addActivityAttach($activity, $iduser, $ds_hash);
                            }
                        }
                    }

                    if (!empty($this->anexos)) {
                        $this->results['fullDataDevSrv_response']['attachment']['tb_attachment'][] = $this->anexos;
                    }
                } else {
                    $this->logControle->log(" ----  OCORREU UM ERRO NO BANCO --- " . pg_last_error($this->logControle->db));
                    $flag = 9;
                    $erro = $this->addError($flag);
                }
            } else {
                $this->logControle->log(" ----  OCORREU UM ERRO NO BANCO --- " . pg_last_error($this->logControle->db));
                $flag = 3;
                $erro = $this->addError($flag);
            }
        } else {
            $this->logControle->log(" ----  OCORREU UM ERRO NO BANCO --- " . pg_last_error($this->logControle->db));
            $flag = 4;
            $erro = $this->addError($flag);
        }

        if (!empty($erro)) {
            //$this->logControle->log("ENTROU AQUI!");
            $this->response['fullDataDevSrv']['error'] = $erro;
        } else {
            if (!empty($this->results)) {
                $this->response = $this->results;
            }
        }

        $this->logControle->log("RESPONSE fullDataSrvDev " . print_r($this->response, true));
        $this->logControle->log("FIM");
        $this->logControle->log("==============================================================================");
        return new JsonResponse($this->response);
    }

    public function finalizaAtividade($dataConclusion, $idActivityStudent) {
        try {
            $objact = $this->getDoctrine()
                    ->getRepository('AppBundle:TbActivityStudent')
                    ->findOneBy(array('idActivityStudent' => $idActivityStudent));
            $dataConclusionUp = new \DateTime($dataConclusion);
            $objact->setDtConclusion($dataConclusionUp);

            $this->em->persist($objact);
            $this->em->flush();

            return true;
        } catch (Exception $e) {
            echo 'Exceção capturada: ', $e->getMessage(), "\n";
        }
    }

    public function addComment($comentarios, $iduser, $dshash) {
        // $this->logControle->log("Entrou em addComment: " . print_r($comentarios, true));
        foreach ($comentarios as $value) {
            foreach ($value as $comment) {
                // $this->logControle->log("ARRAY: " . print_r($comment, true));
                $id_comment = !empty($comment['id_comment']) ? $comment['id_comment'] : NULL;
                $id_activity_student = !empty($comment['id_activity_student']) ? $comment['id_activity_student'] : NULL;
                $id_author = !empty($comment['id_author']) ? $comment['id_author'] : NULL;
                $id_comment_version = !empty($comment['id_comment_version']) ? $comment['id_comment_version'] : NULL;
                $id_comment_version_srv = !empty($comment['id_comment_version_srv']) ? $comment['id_comment_version_srv'] : NULL;
                $dt_comment = !empty($comment['dt_comment']) ? $comment['dt_comment'] : NULL;
                $tx_comment = !empty($comment['tx_comment']) ? $comment['tx_comment'] : NULL;
                $tp_comment = $comment['tp_comment'];


                if ($id_comment_version == NULL) {
                    //  $this->logControle->log("COMENTARIO GERAL ");
                    $objcom = new TbComment();


                    $objact = $this->getDoctrine()
                            ->getRepository('AppBundle:TbActivityStudent')
                            ->findOneBy(array('idActivityStudent' => $id_activity_student));

                    //   $this->logControle->log(" objeto atividade" . print_r($objact, true));

                    $objuser = $this->getDoctrine()
                            ->getRepository('AppBundle:TbUser')
                            ->findOneBy(array('idUser' => $id_author));

                    $objcom->setIdActivityStudent($objact);
                    $objcom->setIdAuthor($objuser);

                    $dtComment2 = new \DateTime($dt_comment);
                    //$this->logControle->log(" OBJETO DATACOMMENT " . print_R($dtComment2, true));
                    $objcom->setDtComment($dtComment2);


                    $dt_send = new \DateTime();
                    $dt_send->format('Y-m-d H:i:s');
                    $objcom->setDtSend($dt_send);


                    $objcom->setTxComment($tx_comment);
                    $objcom->setTpComment($tp_comment);
                    $this->em->persist($objcom);
                    $id_comment_srv = $objcom->getIdComment();
                    $this->em->flush();

                    $queryBuilderCom = $this->em->createQueryBuilder();
                    $queryBuilderCom
                            ->update('AppBundle:TbComment', 'c')
                            ->set('c.idCommentSrv', $queryBuilderCom->expr()->literal($id_comment_srv))
                            ->where($queryBuilderCom->expr()->eq('c.idComment', $id_comment_srv))
                            ->getQuery()
                            ->execute();

                    $nm_table = "tb_comment";
                    $resultSync = (AddSyncController::addSync($id_comment_srv, $iduser, $dshash, $nm_table, $id_activity_student));

                    AddNoticeController::addNotice($id_comment, $id_comment_srv, $id_activity_student, $nm_table, $iduser, $dshash);
                    if (!empty($comment['attachment'])) {

                        //   $this->logControle->log("COMENTARIO COM ANEXO" . print_r($comment['attachment'], true));

                        $id_attachment_srv = $this->insertCommentAttach($comment['attachment'], $id_comment_srv);

                        $this->anexos[] = array(
                            'id_attachment' => $comment['attachment']['id_attachment'],
                            'id_attachment_srv' => "" . $id_attachment_srv . ""
                        );
                    } else {
                        //   $this->logControle->log("COMENTARIO SEM ANEXO" . $comment['tx_comment']);
                    }

                    $resp[] = array(
                        "id_comment" => $id_comment,
                        "id_comment_srv" => (string) $id_comment_srv,
                        "dt_comment_srv" => $dt_send->format('Y-m-d H:i:s')
                    );
                    // $this->logControle->log("-------------------------COMENTARIO NORMAL---------------------" . $tx_comment);
                } else {
                    if ($id_comment_version_srv == NULL) {
                        $this->logControle->log("COMVERSIONS: " . print_r($this->idsComVersions, true));

                        foreach ($this->idsComVersions as $idsComVersions) {
                            if ($idsComVersions['id_comment_version_mobile'] == $id_comment_version) {
                                $objcom = new TbComment();

                                $objact = $this->getDoctrine()
                                        ->getRepository('AppBundle:TbActivityStudent')
                                        ->findOneBy(array('idActivityStudent' => $id_activity_student));

                                $objuser = $this->getDoctrine()
                                        ->getRepository('AppBundle:TbUser')
                                        ->findOneBy(array('idUser' => $id_author));

                                $objcom->setIdActivityStudent($objact);
                                $objcom->setIdAuthor($objuser);
                                $dtComment2 = new \DateTime($dt_comment);
                                // $this->logControle->log(" OBJETO DATACOMMENT " . print_R($dtComment2, true));
                                $objcom->setDtComment($dtComment2);
                                $objcom->setTxComment($tx_comment);
                                $objcom->setTpComment($tp_comment);

                                $dt_send = new \DateTime();
                                $dt_send->format('Y-m-d H:i:s');
                                $objcom->setDtSend($dt_send);

                                $objCoVer = $this->getDoctrine()
                                        ->getRepository('AppBundle:TbCommentVersion')
                                        ->findOneBy(array('idCommentVersion' => $idsComVersions['id_comment_version_srv']));

                                $objcom->setIdCommentVersion($objCoVer);

                                $this->em->persist($objcom);

                                $id_comment_srv = $objcom->getIdComment();
                                $this->em->flush();

                                $queryBuilderCom = $this->em->createQueryBuilder();
                                $queryBuilderCom
                                        ->update('AppBundle:TbComment', 'c')
                                        ->set('c.idCommentSrv', $queryBuilderCom->expr()->literal($id_comment_srv))
                                        ->where($queryBuilderCom->expr()->eq('c.idComment', $id_comment_srv))
                                        ->getQuery()
                                        ->execute();

                                $nm_table = "tb_comment";
                                $resultSync = (AddSyncController::addSync($id_comment_srv, $iduser, $dshash, $nm_table, $id_activity_student));

                                AddNoticeController::addNotice($id_comment, $id_comment_srv, $id_activity_student, $nm_table, $iduser, $dshash);
                            }
                        }
                        $resp[] = array(
                            "id_comment" => $id_comment,
                            "id_comment_srv" => (string) $id_comment_srv,
                            "dt_comment_srv" => $dt_send->format('Y-m-d H:i:s'),
                            "id_comment_version_srv" => $idsComVersions['id_comment_version_srv']
                        );
                        // $this->logControle->log("-------------------------COMENTARIO DE VERSÃO SÓ QUE VERSÃO NOVA---------------------" . $tx_comment);
                    } else {

                        $objcom = new TbComment();

                        $objact = $this->getDoctrine()
                                ->getRepository('AppBundle:TbActivityStudent')
                                ->findOneBy(array('idActivityStudent' => $id_activity_student));

                        $objuser = $this->getDoctrine()
                                ->getRepository('AppBundle:TbUser')
                                ->findOneBy(array('idUser' => $id_author));

                        $objcom->setIdActivityStudent($objact);
                        $objcom->setIdAuthor($objuser);
                        $dtComment2 = new \DateTime($dt_comment);
                        // $this->logControle->log(" OBJETO DATACOMMENT " . print_R($dtComment2, true));
                        $objcom->setDtComment($dtComment2);
                        $objcom->setTxComment($tx_comment);
                        $objcom->setTpComment($tp_comment);

                        $dt_send = new \DateTime();
                        $dt_send->format('Y-m-d H:i:s');
                        $objcom->setDtSend($dt_send);

                        //   $this->logControle->log("ID COMMENT VERSION SRV AQUI AQUI JUJUBA" . $id_comment_version_srv);

                        $objCoVer = $this->getDoctrine()
                                ->getRepository('AppBundle:TbCommentVersion')
                                ->findOneBy(array('idCommentVersion' => $id_comment_version_srv));

                        $objcom->setIdCommentVersion($objCoVer);


                        $this->em->persist($objcom);
                        $id_comment_srv = $objcom->getIdComment();
                        $this->em->flush();

                        $queryBuilderCom = $this->em->createQueryBuilder();
                        $queryBuilderCom
                                ->update('AppBundle:TbComment', 'c')
                                ->set('c.idCommentSrv', $queryBuilderCom->expr()->literal($id_comment_srv))
                                ->where($queryBuilderCom->expr()->eq('c.idComment', $id_comment_srv))
                                ->getQuery()
                                ->execute();

                        $nm_table = "tb_comment";
                        $resultSync = (AddSyncController::addSync($id_comment_srv, $iduser, $dshash, $nm_table, $id_activity_student));

                        AddNoticeController::addNotice($id_comment, $id_comment_srv, $id_activity_student, $nm_table, $iduser, $dshash);

                        $resp[] = array(
                            "id_comment" => $id_comment,
                            "id_comment_srv" => (string) $id_comment_srv,
                            "dt_comment_srv" => $dt_send->format('Y-m-d H:i:s'),
                            "id_comment_version_srv" => $id_comment_version_srv,
                        );
                        // $this->logControle->log("-------------------------COMENTARIO DE VERSAO VELHA---------------------" . $tx_comment);
                    }
                }
            }
        }
        return $resp;
    }

    public function addVersionActivity($versoes, $iduser, $dshash) {
        foreach ($versoes as $value) {
            $this->logControle->log("add version");
            foreach ($value as $version) {

                // $this->logControle->log("VERSION : " . print_r($version, true));
                $id_version = !empty($version['id_version_activity']) ? $version['id_version_activity'] : NULL;
                $id_activity_student = !empty($version['id_activity_student']) ? $version['id_activity_student'] : NULL;
                $tx_activity = $version['tx_activity'];
                $dt_last_access = !empty($version['dt_last_access']) ? $version['dt_last_access'] : NULL;
                $dt_submission = !empty($version['dt_submission']) ? $version['dt_submission'] : NULL;
                $dt_verification = !empty($version['dt_verification']) ? $version['dt_verification'] : NULL;
                $id_version_activity_srv = $version['id_version_activity_srv'];

                if (!empty($id_version_activity_srv)) {//se a versao ja ta no banco
                    if (isset($version['tb_comment_version'])) {//verifica se há as bolinhas para inserir
                        $versionA = $version['tb_comment_version'];

                        foreach ($versionA as $version2) {
                            $id_comment_version = !empty($version2['id_comment_version']) ? $version2['id_comment_version'] : NULL;
                            $id_version_activity = $version2['id_version_activity'];
                            $tx_reference = !empty($version2['tx_reference']) ? $version2['tx_reference'] : NULL;
                            $nu_comment_activity = !empty($version2['nu_comment_activity']) ? $version2['nu_comment_activity'] : NULL;
                            $nu_initial_pos = !empty($version2['nu_initial_pos']) ? $version2['nu_initial_pos'] : NULL;
                            $nu_size = !empty($version2['nu_size']) ? $version2['nu_size'] : NULL;

                          
                            $objver = $this->getDoctrine()
                                    ->getRepository('AppBundle:TbVersionActivity')
                                    ->findOneBy(array('idVersionActivity' => $id_version_activity_srv));
                      
                            $objComVer = new TbCommentVersion();
                          
                            $objComVer->setIdVersionActivity($objver);
                            $objComVer->setTxReference($tx_reference);
                            $objComVer->setNuCommentActivity($nu_comment_activity);
                            $objComVer->setNuInitialPos($nu_initial_pos);
                            $objComVer->setNuSize($nu_size);
                            $this->em->persist($objComVer);
                            $id_comment_version_srv = $objComVer->getIdCommentVersion();
                            $this->em->flush();

                            $queryBuildervers = $this->em->createQueryBuilder();
                            $queryBuildervers
                                    ->update('AppBundle:TbVersionActivity', 'v')
                                    ->set('v.txActivity', $queryBuildervers->expr()->literal($tx_activity))
                                    ->where($queryBuildervers->expr()->eq('v.idVersionActivity', $id_version_activity_srv))
                                    ->getQuery()
                                    ->execute();


                            $queryBuilder = $this->em->createQueryBuilder();
                            $queryBuilder
                                    ->update('AppBundle:TbCommentVersion', 'c')
                                    ->set('c.idCommentVersionSrv', $queryBuilder->expr()->literal($id_comment_version_srv))
                                    ->where($queryBuilder->expr()->eq('c.idCommentVersion', $id_comment_version_srv))
                                    ->getQuery()
                                    ->execute();
                            // $this->logControle->log("update idcommentversion " . $queryBuilder);


                            $nm_table = "tb_comment_version";
                            $resultSync = (AddSyncController::addSync($id_comment_version_srv, $iduser, $dshash, $nm_table, $id_activity_student));

                            $nm_table_update = "tb_version_activity_update";
                            $resultSyncVersion = (AddSyncController::addSync($id_version_activity_srv, $iduser, $dshash, $nm_table_update, $id_activity_student));

                            //  $resultSync = (AddSyncController::addSync($id_comment_version_srv, $iduser, $dshash, $nm_table, $id_activity_student));
                            //AddNoticeController::addNotice($id_comment_version, $id_comment_version_srv, $id_activity_student, $nm_table, $iduser, $dshash);

                            $this->idsComVersions[] = (array("id_comment_version_srv" => $id_comment_version_srv,
                                "id_comment_version_mobile" => $id_comment_version));
                        }
                        $resp[] = (array
                            ("id_comment_version_srv" => (string) $id_comment_version_srv,
                            "id_comment_version_mobile" => $id_comment_version));
                    }
                } else {//se a versao é nova
                    //  $this->logControle->log("versao nova!!! ");
                    $this->logControle->log("versao nova!!! ");
                    //verifica se ela ja foi inserida sem response/com algum problema  e o dispositivo esta tentando inserir de novo
                    $queryBuilderV = $this->em->createQueryBuilder();
                    $queryBuilderV
                            ->select('v')
                            ->from('AppBundle:TbVersionActivity', 'v')
                            ->innerJoin('v.idActivityStudent', 'a', 'WITH', 'v.idActivityStudent = a.idActivityStudent')
                            ->where($queryBuilderV->expr()->eq('v.idVersionActivitySrv', $id_version))
                            ->andWhere(($queryBuilderV->expr()->eq('v.idActivityStudent', $id_activity_student)))
                            ->andWhere($queryBuilderV->expr()->eq('v.txActivity', "'" . $tx_activity . "'"))
                            ->getQuery()
                            ->execute();

                    $this->logControle->log($queryBuilderV);
                    $retorno = $queryBuilderV->getQuery()->getArrayResult();
                    $this->logControle->log(" testando versao " . print_r($retorno, true));
                    $total = count($retorno);
                    if ($total > 0) {

                        $resp[] = (array
                            ("id_version_activity" => $id_version,
                            "id_version_activity_srv" => (string) $retorno[0]['idVersionActivity'],
                            "dt_submission" => !empty( $retorno[0]['dtSubmission']) ? (string) $retorno[0]['dtSubmission']->format('Y-m-d H:i:s'): NULL));
                    } else {



                        $objact = $this->getDoctrine()
                                ->getRepository('AppBundle:TbActivityStudent')
                                ->findOneBy(array('idActivityStudent' => $id_activity_student));


                        $objdtVer = new \DateTime($dt_verification);
                        $this->logControle->log("dtsubmission: " . $dt_submission);
                        if ($dt_submission == NULL) {
                            $this->logControle->log("submission null");
                            $objnewver = new TbVersionActivity();

                            $objnewver->setIdVersionActivitySrv($id_version);
                            $objnewver->setIdActivityStudent($objact);
                            $objnewver->setTxActivity($tx_activity);
                            $dt_submission = new \DateTime();
                            $dt_submission->format('Y-m-d H:i:s');
                            $objnewver->setDtSubmission($dt_submission);
                            $objnewver->setDtVerification($objdtVer);

                            $objdtlast = new \DateTime($dt_last_access);
                            $objnewver->setDtLastAccess($objdtlast);
                            $this->em->persist($objnewver);
                            $id_version_activity_srv = $objnewver->getIdVersionActivity();
                            $this->atualizaVersaoAtual($id_activity_student, $tx_activity);
                            $this->em->flush();
                            $nm_table = "tb_version_activity";
                            $resultSync = (AddSyncController::addSync($id_version_activity_srv, $iduser, $dshash, $nm_table, $id_activity_student));

                            AddNoticeController::addNotice($id_version, $id_version_activity_srv, $id_activity_student, $nm_table, $iduser, $dshash);
                        } else {
                            if ($dt_submission == "0000-00-00 00:00:00") {
                                $this->logControle->log("000000");
                                $queryBuilderVersaoAtual = $this->em->createQueryBuilder();
                                $queryBuilderVersaoAtual
                                        ->select('va.idVersionActivity as idVersionActivityAtual')
                                        ->from('AppBundle:TbVersionActivity', 'va')
                                        ->innerJoin('va.idActivityStudent', 'a')
                                        ->where($queryBuilderVersaoAtual->expr()->eq('va.idActivityStudent', $id_activity_student))
                                        ->andWhere($queryBuilderVersaoAtual->expr()->isNull('va.dtSubmission'))
                                        ->getQuery()
                                        ->execute();

                                $this->logControle->log($queryBuilderVersaoAtual);
                                $resultsVersaoAtual = $queryBuilderVersaoAtual->getQuery()->getArrayResult();

                                $this->logControle->log("procurando versao atual" . print_r($resultsVersaoAtual, true));

                                if (count($resultsVersaoAtual) > 0) {
                                    $queryBuilder = $this->em->createQueryBuilder();
                                    $queryBuilder
                                            ->update('AppBundle:TbVersionActivity', 'v')
                                            ->set('v.idVersionActivitySrv', $queryBuilder->expr()->literal($resultsVersaoAtual[0]['idVersionActivityAtual']))
                                            ->set('v.txActivity', $queryBuilder->expr()->literal($tx_activity))
                                            ->where($queryBuilder->expr()->eq('v.idVersionActivity', $resultsVersaoAtual[0]['idVersionActivityAtual']))
                                            ->getQuery()
                                            ->execute();
                                    $id_version_activity_srv = $resultsVersaoAtual[0]['idVersionActivityAtual'];
                                    $this->logControle->log("atualizando versao atual" . $id_version_activity_srv);
                                } else {
                                    $objnewver = new TbVersionActivity();
                                    $objnewver->setIdVersionActivitySrv($id_version);
                                    $objnewver->setTxActivity($tx_activity);
                                    $objnewver->setIdActivityStudent($objact);
                                    $this->em->persist($objnewver);
                                    $id_version_activity_srv = $objnewver->getIdVersionActivity();

                                    $this->em->flush();
                                }
                                $nm_table = "tb_version_activity";
                                $resultSync = (AddSyncController::addSyncVersaoAtual($id_version_activity_srv, $iduser, $dshash, $nm_table, $id_activity_student));
                            }
                        }




//                    $queryBuilder = $this->em->createQueryBuilder();
//                    $queryBuilder
//                            ->update('AppBundle:TbVersionActivity', 'v')
//                            ->set('v.idVersionActivitySrv', $queryBuilder->expr()->literal($id_version_activity_srv))
//                            ->where($queryBuilder->expr()->eq('v.idVersionActivity', $id_version))
//                            ->getQuery()
//                            ->execute();

                        if (!empty($version['tb_comment_version'])) {//verifica se há as bolinhas para inserir
                            // $this->logControle->log("VERSAO NOVA E COMENTARIO DE VERSAO NOVO");
                            $versionA = $version['tb_comment_version'];

                            // $this->logControle->log("o que tem dentro do comment version : " . print_r($versionA, true));

                            foreach ($versionA as $version2) {
                                $id_comment_version = !empty($version2['id_comment_version']) ? $version2['id_comment_version'] : 'NULL';
                                $id_version_activity = $version2['id_version_activity'];
                                $tx_reference = !empty($version2['tx_reference']) ? $version2['tx_reference'] : 'NULL';
                                $nu_comment_activity = !empty($version2['nu_comment_activity']) ? $version2['nu_comment_activity'] : 'NULL';
                                $nu_initial_pos = !empty($version2['nu_initial_pos']) ? $version2['nu_initial_pos'] : 'NULL';
                                $nu_size = !empty($version2['nu_size']) ? $version2['nu_size'] : 'NULL';


                                //  $this->logControle->log("versao id servidor:" . $id_version_activity_srv);
                                // $this->logControle->log("  --- objeto:" . print_r($objnewver, true)); 
//                              $objver = $this->getDoctrine()
//                                        ->getRepository('AppBundle:TbVersionActivity')
//                                        ->findOneBy(array('idVersionActivitySrv' => $id_version_activity_srv));
//                             $query = $this->em->createQuery('
//                                        SELECT v
//                                        FROM AppBundle:TbVersionActivity v
//                                        where v.idVersionActivity = 1
//                                    ');
                                //$this->logControle->log(" objeto versao " . print_r($objver, true));

                                $objComVer = new TbCommentVersion();

                                $objComVer->setIdVersionActivity($id_version_activity_srv);
                                $objComVer->setTxReference($tx_reference);
                                $objComVer->setNuCommentActivity($nu_comment_activity);
                                $objComVer->setNuInitialPos($nu_initial_pos);
                                $objComVer->setNuSize($nu_size);

                                // $this->logControle->log(" objeto comment versao " . print_r($objComVer, true));


                                $this->em->persist($objComVer);
                                $id_comment_version_srv = $objComVer->getIdCommentVersion();

                                //$this->logControle->log(" idcommentversion srv  " . $id_comment_version_srv);

                                $this->em->flush();
                                $nm_table = "tb_comment_version";

                                $queryBuilder = $this->em->createQueryBuilder();
                                $queryBuilder
                                        ->update('AppBundle:TbCommentVersion', 'c')
                                        ->set('c.idCommentVersionSrv', $queryBuilder->expr()->literal($id_comment_version_srv))
                                        ->where($queryBuilder->expr()->eq('c.idCommentVersion', $id_comment_version_srv))
                                        ->getQuery()
                                        ->execute();
                                //  $this->logControle->log("update idcommentversion " . $queryBuilder);


                                $resultSync = (AddSyncController::addSync($id_comment_version_srv, $iduser, $dshash, $nm_table, $id_activity_student));

                                //     AddNoticeController::addNotice($id_comment_version, $id_comment_version_srv, $id_activity_student, $nm_table, $iduser, $dshash);

                                $this->idsComVersions[] = (array
                                    ("id_comment_version_srv" => $id_comment_version_srv,
                                    "id_comment_version_mobile" => $id_comment_version));

                                if ($dt_submission == "0000-00-00 00:00:00") {
                                    $resp[] = (array
                                        ("id_version_activity" => $id_version,
                                        "id_version_activity_srv" => (string) $id_version_activity_srv,
                                        "id_comment_version_srv" => (string) $id_comment_version_srv,
                                        "id_comment_version_mobile" => $id_comment_version,
                                        "dt_submission" => $dt_submission));
                                }
                            }
                        } else {
                            if (is_object($dt_submission)) {
                                $this->logControle->log(" é objeto");
                                $data = $dt_submission->format('Y-m-d H:i:s');
                            } else {
                                $data = $dt_submission;
                            }
                            $resp[] = (array
                                ("id_version_activity" => $id_version,
                                "id_version_activity_srv" => (string) $id_version_activity_srv,
                                "dt_submission" => $data));
                        }
                    }
                }
            }
        }
        return $resp;
    }

    public function insertCommentAttach($anexo, $id_comment_srv) {
        $this->logControle->log("insert comment atttach");
        $this->logControle->log(print_r($anexo, true));
        $tpAttachment = $anexo['tp_attachment'];
        $nmFile = $anexo['nm_file'];
        $nmSystem = $anexo['nm_system'];

        $objAttach = new TbAttachment();
        $objAttach->setTpAttachment($tpAttachment);
        $objAttach->setNmFile($nmFile);
        $objAttach->setNmSystem($nmSystem);

        $this->em->persist($objAttach);
        $idAttachSrv = $objAttach->getIdAttachment();
        $this->em->flush();

        $queryBuilder = $this->em->createQueryBuilder();
        $queryBuilder
                ->update('AppBundle:TbAttachment', 'a')
                ->set('a.idAttachmentSrv', $queryBuilder->expr()->literal($idAttachSrv))
                ->where($queryBuilder->expr()->eq('a.idAttachment', $idAttachSrv))
                ->getQuery()
                ->execute();
        //$this->logControle->log($queryBuilder);

        $objAttach2 = $this->getDoctrine()
                ->getRepository('AppBundle:TbAttachment')
                ->findOneBy(array('idAttachment' => $idAttachSrv));

        $objComm = $this->getDoctrine()
                ->getRepository('AppBundle:TbComment')
                ->findOneBy(array('idComment' => $id_comment_srv));
        $objAttachComm = new TbAttachComment();
        $objAttachComm->setIdAttachment($objAttach2);
        $objAttachComm->setIdComment($objComm);

        $this->em->persist($objAttachComm);

        $this->em->flush();

        return $idAttachSrv;
    }

    public function addReference($referencias, $iduser, $dshash) {
        //  $this->logControle->log(print_r($referencias, true));
        foreach ($referencias as $value) {
            foreach ($value as $reference) {
                $id_reference = !empty($reference['id_reference']) ? $reference['id_reference'] : NULL;
                $id_activity_student = !empty($reference['id_activity_student']) ? $reference['id_activity_student'] : NULL;
                $ds_url = !empty($reference['ds_url']) ? "'" . $reference['ds_url'] . "'" : NULL;


                $objAct = $this->getDoctrine()
                        ->getRepository('AppBundle:TbActivityStudent')
                        ->findOneBy(array('idActivityStudent' => $id_activity_student));

                $objRef = new TbReference();
                $objRef->setIdActivityStudent($objAct);
                $objRef->setDsUrl($ds_url);

                $this->em->persist($objRef);
                $idRefSrv = $objRef->getIdReference();
                $this->em->flush();


                $resp[] = array(
                    'id_reference' => $id_reference,
                    'id_reference_srv' => (string) $idRefSrv
                );


                $queryBuilder = $this->em->createQueryBuilder();
                $queryBuilder
                        ->update('AppBundle:TbReference', 'r')
                        ->set('r.idReferenceSrv', $queryBuilder->expr()->literal($idRefSrv))
                        ->where($queryBuilder->expr()->eq('r.idReference', $idRefSrv))
                        ->getQuery()
                        ->execute();

                $nm_table = "tb_reference";
                $resultSync = (AddSyncController::addSync($idRefSrv, $iduser, $dshash, $nm_table, $id_activity_student));

                AddNoticeController::addNotice($id_reference, $idRefSrv, $id_activity_student, $nm_table, $iduser, $dshash);
            }
        }

        return $resp;
    }

    public function updateUserDevSrv($usuarios, $iduser, $ds_hash) {
        foreach ($usuarios as $user) {
            // $this->logControle->log("UPDATE: " . print_r($user, true));

            $photo = $user['im_photo'];
            //$this->logControle->log("PHOTO GIGANTE: " . $user['im_photo']);
            $nm_user = $user['nm_user'];
            $nu_identification = $user['nu_identification'];
            $ds_email = $user['ds_email'];
            $nu_cellphone = $user['nu_cellphone'];

            $sql_update = " UPDATE 
                                tb_user
                            SET 
                                im_photo='" . pg_escape_bytea($photo) . "',
                                nm_user =  '$nm_user',
                                nu_identification = '$nu_identification',
                                ds_email = '$ds_email',
                                nu_cellphone = '$nu_cellphone'
                           WHERE 
                               id_user = " . $user['id_user'] . "";

            // $this->logControle->log("SQL BASE62 - BLOB " . $sql_update);
            $res_update = pg_query($this->logControle->db, $sql_update);

            error_reporting(0);
            if (!$res_update) {
                $this->logControle->log(" ----  OCORREU UM ERRO NO BANCO --- " . $sql . " \nERRO: " . pg_last_error($this->logControle->db));

                $flag = 10;
                $this->error[] = $this->addError($flag);
                $resp['error'] = $this->error;
                break;
            } else {
                $sql_view = "select distinct
                                    id_tutor as id
                                from
                                    vw_activity
                                where
                                     id_student = " . $iduser . "
                                union
                                select 
                                    id_student 
                                from
                                    vw_activity
                                where 
                                    id_tutor= " . $iduser . ""; //seleciona os alunos do tutor ou os tutores do aluno para pegar as atualizações dos perfis
                // $this->logControle->log("sql_view: " . $sql_view);
                error_reporting(0);

                $ret_view = pg_query($this->logControle->db, $sql_view);

                error_reporting(0);
                if (!$ret_view) {
                    $this->logControle->log(" ----  OCORREU UM ERRO NO BANCO --- \n ERRO: " . pg_last_error($this->logControle->db));

                    $flag = 1;
                    $this->error[] = $this->addError($flag);
                    $resp['error'] = $this->error;
                    break;
                } else {
                    if (pg_affected_rows($ret_view) > 0) {
                        while ($row_view = pg_fetch_assoc($ret_view)) {
                            $idPar = $row_view['id'];

                            $queryBuilder = $this->em->createQueryBuilder();
                            $queryBuilder
                                    ->select("d, u")
                                    ->from('AppBundle:TbDevice', 'd')
                                    ->innerJoin('d.idUser', 'u', 'WITH', 'd.idUser = u.idUser')
                                    ->where($queryBuilder->expr()->eq('d.idUser', $idPar))
                                    ->getQuery()
                                    ->execute();

                            //  $this->logControle->log($queryBuilder);
                            $results = $queryBuilder->getQuery()->getArrayResult();
                            $objSync = new TbSync();

                            $idAuthor = $this->getDoctrine()
                                    ->getRepository('AppBundle:TbUser')
                                    ->findOneBy(array('idUser' => $iduser));
                            $objSync->setIdAuthor($idAuthor);


                            $objSync->setIdDestination($idAuthor);

                            $objSync->setNmTable("tb_user");
                            $objSync->setCoIdTable($iduser);

                            $dtSync = new \DateTime();
                            $dtSync->format('H:i:s \O\n Y-m-d');
                            $objSync->setDtSync($dtSync);
                            $this->em->persist($objSync);
                            $idsync = $objSync->getIdSync();
                            $this->em->flush();
                            foreach ($results as $devices) {
                                // $this->logControle->log("================================ " . print_r($devices, true));
                                $objSync = new TbSync();

                                $idAuthor = $this->getDoctrine()
                                        ->getRepository('AppBundle:TbUser')
                                        ->findOneBy(array('idUser' => $iduser));
                                $objSync->setIdAuthor($idAuthor);

                                $idDestino = $devices['idUser']['idUser'];

                                $objdestino = $this->getDoctrine()
                                        ->getRepository('AppBundle:TbUser')
                                        ->findOneBy(array('idUser' => $idDestino));
                                $objSync->setIdDestination($objdestino);

                                $objSync->setNmTable("tb_user");
                                $objSync->setCoIdTable($iduser);

                                $dtSync = new \DateTime();
                                $dtSync->format('H:i:s \O\n Y-m-d');
                                $objSync->setDtSync($dtSync);
                                $this->em->persist($objSync);
                                $idsync = $objSync->getIdSync();
                                $this->em->flush();

                                AddSyncController::addSyncDeviceDestino($idDestino, $idsync);

                                AddSyncController::addSyncDeviceAuthor($iduser, $ds_hash, $idsync);
                            }
                        }
                    }
                }
            }
        }



        return $resp;
    }

    public function addActivityAttach($activity, $iduser, $dshash) {
        foreach ($activity as $value) {
            foreach ($value as $ativ) {
                if (!empty($ativ['id_activity_student'])) {
                    $id_activity = $ativ['id_activity_student'];
                    if (isset($ativ['attachment'])) {
                        foreach ($ativ['attachment'] as $anexos) {
                            $tp_attachment = !empty($anexos['tp_attachment']) ? $anexos['tp_attachment'] : 'NULL';
                            $nm_file = !empty($anexos['nm_file']) ? $anexos['nm_file'] : 'NULL';
                            $nm_system = !empty($anexos['nm_system']) ? $anexos['nm_system'] : 'NULL';

                            $objAttach = new TbAttachment();
                            $objAttach->setTpAttachment($tp_attachment);
                            $objAttach->setNmFile($nm_file);
                            $objAttach->setNmSystem($nm_system);

                            $idUserObj = $this->getDoctrine()
                                    ->getRepository('AppBundle:TbUser')
                                    ->findOneBy(array('idUser' => $iduser));

                            $objAttach->setIdAuthor($idUserObj);

                            $this->em->persist($objAttach);
                            $idAttach = $objAttach->getIdAttachment();
                            $this->em->flush();
                            $this->anexos[] = array(
                                'id_attachment' => $anexos['id_attachment'],
                                'id_attachment_srv' => (string) $idAttach
                            );

                            $queryBuilder = $this->em->createQueryBuilder();
                            $queryBuilder
                                    ->update('AppBundle:TbAttachment', 'a')
                                    ->set('a.idAttachmentSrv', $queryBuilder->expr()->literal($idAttach))
                                    ->where($queryBuilder->expr()->eq('a.idAttachment', $idAttach))
                                    ->getQuery()
                                    ->execute();


                            $objActivity = $this->getDoctrine()
                                    ->getRepository('AppBundle:TbActivityStudent')
                                    ->findOneBy(array('idActivityStudent' => $id_activity));

                            $objAttachment = $this->getDoctrine()
                                    ->getRepository('AppBundle:TbAttachment')
                                    ->findOneBy(array('idAttachment' => $idAttach));

                            $objAttachAct = new TbAttachActivity();
                            $objAttachAct->setIdAttachment($objAttachment);
                            $objAttachAct->setIdActivityStudent($objActivity);

                            $this->em->persist($objAttachAct);
                            $idAttachActivity = $objAttachAct->getIdAttachActivity();
                            $this->em->flush();

                            $nm_table = "tb_attach_activity";

                            AddSyncController::addSync($idAttachActivity, $iduser, $dshash, $nm_table, $id_activity);

                            AddNoticeController::addNotice($anexos['id_attachment'], $idAttachActivity, $id_activity, $nm_table, $iduser, $dshash);
                        }
                    }
                }
            }
        }
    }

    public function updateReadNotice($notices) {
        foreach ($notices as $value) {
            foreach ($value as $notice) {
                $dt_read = date('Y-m-d H:i:s');
                $idnotice = $notice['id_notice'];
                $queryBuilder = $this->em->createQueryBuilder();
                $queryBuilder
                        ->update('AppBundle:TbNotice', 'n')
                        ->set('n.dtRead', $queryBuilder->expr()->literal($dt_read))
                        ->where($queryBuilder->expr()->eq('n.idNotice', $idnotice))
                        ->getQuery()
                        ->execute();
                // $this->logControle->log($queryBuilder);

                $this->logControle->log("Tb_notice: dt_read atualizada com sucesso!");
            }
        }
    }

    public function atualizaVersaoAtual($id_activity_student, $tx_activity) {

        $queryBuilder = $this->em->createQueryBuilder();
        $queryBuilder
                ->update('AppBundle:TbVersionActivity', 'v')
                ->set('v.txActivity', $queryBuilder->expr()->literal($tx_activity))
                ->where($queryBuilder->expr()->isNull('v.dtSubmission'))
                ->andWhere($queryBuilder->expr()->eq('v.idActivityStudent', $id_activity_student))
                ->getQuery()
                ->execute();
    }

    public function addAnnotation($annotation, $iduser, $ds_hash) {
       
        foreach ($annotation as $arrayAnotacao) {
            foreach ($arrayAnotacao as $anotacao) {
                $this->em = $this->getDoctrine()->getEntityManager();
                $this->logControle->log(print_r($anotacao, true));
                $objetoUser = $this->getDoctrine()
                        ->getRepository('AppBundle:TbUser')
                        ->findOneBy(array('idUser' => $iduser));
                $objAnnotation = new TbAnnotation();
                $objAnnotation->setDsAnnotation($anotacao['ds_annotation']);
                $objAnnotation->setIdUser($objetoUser);
                $this->em->persist($objAnnotation);
                $idAnnotation = $objAnnotation->getIdAnnotation();
                $objAnnotation->setIdAnnotationSrv($idAnnotation);
                $idSrv = $objAnnotation->getIdAnnotationSrv();
                $this->em->flush();
                
                $resp[] = array(
                    'id_annotation_srv' => $idSrv,
                     'id_annotation'=>$anotacao['id_annotation']   
                      
                );
                $nm_table = "tb_annotation";
                (AddSyncController::addSync($idSrv, $iduser, $ds_hash, $nm_table, -1));
            }
        }
        return $resp;
    }

}
