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
use AppBundle\Entity\TbNoticeDevice;
use AppBundle\Entity\TbDevice;
use AppBundle\Entity\TbUser;
use Symfony\Component\Validator\Constraints\Date;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\QueryBuilder;
use AppBundle\Controller\idDeviceSeqController;
use AppBundle\Controller\FindNoticeController;

header('Content-Type: text/html; charset=UTF-8');
mb_internal_encoding("iso-8859-1");
mb_http_output("iso-8859-1");
ob_start("mb_output_handler");

/**
 * Description of FirstSyncController
 *
 * @author Marilia
 */
class FirstSyncController extends Controller {

    public $id_comment_version = array();
    public $error = array();
    public $em;
    public $idUser;
    public $logControle;

    public function __construct() {
        $this->logControle = new LogController();
    }

    public function addError($flag) {
        $status = array(
            1 => 'Erro no banco!',
            2 => 'Campos obrigatÃ³rios vazios!',
            3 => 'Json vazio!',
            4 => 'Nenhum json recebido pelo servidor!',
            5 => 'UsuÃ¡rio nÃ£o localizado!',
            6 => 'UsuÃ¡rio sem portFolio cadastrado!',
            7 => 'Nenhum dado foi econtrado no Banco de Dados!',
            8 => 'NÃ£o hÃ¡ dados para sincronizar!',
            9 => 'IdDevice/tpDevice nÃ£o pode estar vazio!',
            10 => 'Falha ao atualizar tabela!',
            11 => 'Falha na inserÃ§Ã£o dos dados no Banco de Dados',
            12 => 'Basic Data ja foi sincronizado!',
            13 => 'First Login ja foi sincronizado!',
            14 => 'Falha na inserÃ§Ã£o dos dados para sincronismo!'
        );

        $array = array(
            "erro" => $status[$flag]
        );
        return $array;
    }

    /**
     * @Route("/firstSync")
     */
    public function firstSync(Request $req) {
        $this->em = $this->getDoctrine()->getEntityManager();
        $this->logControle->log('INICIO firstSync');

        $this->response = NULL;
        $this->error = NULL;
        if (0 === strpos($req->headers->get('Content-Type'), 'application/json')) {
            $data = json_decode($req->getContent(), true);
            $req->request->replace(is_array($data) ? $data : array());
            $this->logControle->log("REQUEST firstSync: " . print_r($data, true));

            if ((!empty($data)) && (!empty($data['firstSync_request']))) {

                $device = $data['firstSync_request'];

                if ((!empty($device['ds_hash'])) && (!empty($device['id_user']))) {
                    $iduser = $device['id_user'];
                    $dshash = $device['ds_hash'];

                    $this->idUser = $iduser;
                    $queryBuilder = $this->em->createQueryBuilder();
                    $queryBuilder
                            ->select('s, a')
                            ->from('AppBundle:TbSync', 's')
                            ->innerJoin('s.idActivityStudent', 'a')
                            ->where($queryBuilder->expr()->eq('s.idAuthor', $iduser))
                            ->orWhere($queryBuilder->expr()->eq('s.idDestination', $iduser))
                            ->orderBy('s.coIdTable', 'ASC')
                            ->getQuery()
                            ->execute();

                    //$this->log($queryBuilder);
                    $results = $queryBuilder->getQuery()->getResult(\Doctrine\ORM\Query::HYDRATE_ARRAY);

                    $this->results['firstSync_response']['notice']['tb_notice'] = FindNoticeController::findNotice($iduser, $device['ds_hash']);
                    $id_device = (IdDeviceSeqController::getIdDeviceSeq($dshash, $iduser));
                    $this->logControle->log("------ device ---  ");
                    $this->logControle->log("RESULT SYNC " . print_r($results, true));
                    if ($results) {
                        foreach ($results as $row) {
                            $dtDevice = date('Y-m-d H:i:s');
                            $queryBuilder = $this->em->createQueryBuilder();
                            $queryBuilder
                                    ->update('AppBundle:TbSyncDevice', 'sd')
                                    ->set('sd.dtDevice', $queryBuilder->expr()->literal($dtDevice))
                                    ->where($queryBuilder->expr()->eq('sd.idDevice', $id_device))
                                    ->andWhere($queryBuilder->expr()->eq('sd.idSync', "'" . $row['idSync'] . "'"))
                                    ->getQuery()
                                    ->execute();
                            $this->logControle->log($queryBuilder);

                            $this->logControle->log("---atualizando dtDevice para eliminar do fullData---");
                            //$this->log($queryBuilder);
                            //$this->logControle->log("ROW_SYNC AQUI : " . print_r($row, true));
                            $nomeTab = $row['nmTable'];
                            $nomeFunc = "select_" . $nomeTab;

                            //$this->logControle->log("nome da funcao:  ---------->" . $nomeFunc);

                            if ($nomeTab == 'TbUser') {
                                //$this->logControle->log("DENTRO DO IF NOME TAB == TB_USER");
                                if ((!in_array($row['coIdTable'], $ids_users))) {

                                    $ids_users[] = $row['coIdTable'];

                                    $retorno = $this->$nomeFunc($row);
                                    if (!empty($retorno)) {
                                        $arr_data[substr($nomeTab, 3)][$nomeTab][] = $retorno;
                                    }
                                }
                            } else {

                                $retorno = $this->$nomeFunc($row);
                                if (!empty($retorno)) {
                                    $arr_data[substr($nomeTab, 3)][$nomeTab][] = $retorno;
                                }
                            }
                        }
                        $arr_data["annotation"]["tb_annotation"] = AnnotationsController::selecionarAnotacoes($this->idUser);
                        //$this->logControle->log("ARR_DATA :  " . print_r($arr_data, true));
                        $this->results['firstSync_response']['data'] = $arr_data;
                    } else {
                        $this->logControle->log(" ----  OCORREU UM ERRO NO BANCO --- " . pg_last_error($this->logControle->db));
                        $flag = 8;
                        $this->error = $this->addError($flag);
                        $this->results['firstSync_response']['data']['error'] = $this->error;
                    }
                } else {
                    $this->logControle->log(" ----  OCORREU UM ERRO NO BANCO --- " . pg_last_error($this->logControle->db));
                    $flag = 9;
                    $this->error = $this->addError($flag);
                    $this->results['firstSync_response']['data']['error'] = $this->error;
                }
            } else {
                $this->logControle->log(" ----  OCORREU UM ERRO NO BANCO --- " . pg_last_error($this->logControle->db));
                $flag = 3;
                $this->error = $this->addError($flag);
                $this->results['firstSync_response']['data']['error'] = $this->error;
            }
        } else {
            $this->logControle->log(" ----  OCORREU UM ERRO NO BANCO --- " . pg_last_error($this->logControle->db));
            $flag = 4;
            $this->error = $this->addError($flag);
            $this->results['firstSync_response']['data']['error'] = $this->error;
        }
        $this->response = $this->results;

        $this->logControle->log("RESPONSE firstSync" . print_r($this->response, true));
        $this->logControle->log("FIM");
        $this->logControle->log("==============================================================================");
        return new JsonResponse($this->response);
    }

    public function select_tb_comment($row_sync) {
        $totalItens = 0;
        $result = array();
        $idcomment = $row_sync['coIdTable'];
        $em = $this->getDoctrine()->getEntityManager();


        $queryBuilder = $em->createQueryBuilder();
        $queryBuilder
                ->select('c, u, a')
                ->from('AppBundle:TbComment', 'c')
                ->innerJoin('c.idActivityStudent', 'a')
                ->innerJoin('c.idAuthor', 'u')
//                            ->innerJoin('c.idCommentVersion', 'v')
                ->where($queryBuilder->expr()->eq('c.idComment', $idcomment))
                ->getQuery()
                ->execute();

        // $this->logControle->log($queryBuilder);
        $results = $queryBuilder->getQuery()->getArrayResult();


        //
        //  
        //  $this->logControle->log("SQL SELECT_TB_COMMENT: " . print_r($results, true));

        $totalItens = count($results);

        if ($totalItens > 0) {
            foreach ($results as $row) {
                if ($row['tpComment'] == 'O') {
                    $queryBuilder = $em->createQueryBuilder();
                    $queryBuilder
                            ->select('c, v')
                            ->from('AppBundle:TbComment', 'c')
                            ->innerJoin('c.idCommentVersion', 'v', 'WITH', 'v.idCommentVersion = c.idCommentVersion')
                            ->where($queryBuilder->expr()->eq('c.idComment', $idcomment))
                            ->orderBy('c.idComment', 'ASC')
                            ->getQuery()
                            ->execute();

                    //$this->logControle->log($queryBuilder);
                    $results = $queryBuilder->getQuery()->getArrayResult();
                    //$this->log("--------result tbcomment ---- : " . print_r($results, true));
                    $totalItens = count($results);

                    if ($totalItens > 0) {
                        $idCommentVersion = $results[0]['idCommentVersion']['idCommentVersion'];
                    } else {
                        $idCommentVersion = null;
                        $flag = 1;
                        $this->error = $this->addError($flag);
                        $result = $this->error;
                    }

//                     if ((!in_array($idCommentVersion, $this->id_comment_version)) && (!empty($idCommentVersion))) {
//                        $this->id_comment_version[] = $idCommentVersion;
//                    }
                } else {
                    $idCommentVersion = null;
                }
                $result = array(
                    'id_comment' => (string) $row['idComment'],
                    'id_activity_student' => (string) $row['idActivityStudent']['idActivityStudent'],
                    'id_author' => (string) $row['idAuthor']['idUser'],
                    'tx_comment' => $row['txComment'],
                    'tx_comment' => $row['txComment'],
                    'tp_comment' => $row['tpComment'],
                    'dt_comment' => $row['dtComment']->format('Y-m-d H:i:s'),
                    'id_comment_version' => (string) $idCommentVersion
                );
                //$this->logControle->log("RESULT  : " . print_r($result, true));
//
                $result['attachment'] = FirstSyncController::verifAttach_comment($row['idComment']);
            }
        }
        return $result;
    }

    public function verifAttach_comment($id_comment) {
        $this->em = $this->getDoctrine()->getEntityManager();
        $queryBuilder = $this->em->createQueryBuilder();
        $queryBuilder
                ->select('ac, a')
                ->from('AppBundle:TbAttachComment', 'ac')
                ->innerJoin('ac.idComment', 'c', 'WITH', 'c.idComment = ac.idComment')
                ->innerJoin('ac.idAttachment', 'a', 'WITH', 'ac.idAttachment = a.idAttachment')
                ->where($queryBuilder->expr()->eq('c.idComment', $id_comment))
                ->getQuery()
                ->execute();

        $results = $queryBuilder->getQuery()->getArrayResult();
        if (count($results) > 0) {
            foreach ($results as $row_attach) {
                $result = array(
                    'id_attachment' => (string) $row_attach['idAttachment']['idAttachment'],
                    'tp_attachment' => $row_attach['idAttachment']['tpAttachment'],
                    'nm_file' => $row_attach['idAttachment']['nmFile'],
                    'nm_system' => $row_attach['idAttachment']['nmSystem']
                );
            }
        } else {
            $this->logControle->log(" ----  OCORREU UM ERRO NO BANCO --- " . $queryBuilder . " \nERRO: " . pg_last_error($this->logControle->db));
            $flag = 7;
            $this->error = $this->addError($flag);
            $result = $this->error;
        }


        return $result;
    }

    public function select_tb_version_activity($row_sync) {
        $totalItens = 0;
        $result = array();
        $idversion = $row_sync['coIdTable'];

        $queryBuilder = $this->em->createQueryBuilder();
        $queryBuilder
                ->select('v, a')
                ->from('AppBundle:TbVersionActivity', 'v')
                ->innerJoin('v.idActivityStudent', 'a', 'WITH', 'v.idActivityStudent = a.idActivityStudent')
                ->where($queryBuilder->expr()->eq('v.idVersionActivity', $idversion))
                ->getQuery()
                ->execute();

        // $this->logControle->log($queryBuilder);
        $results = $queryBuilder->getQuery()->getArrayResult();
        //$this->logControle->log("TB  VERSION ACTIVITY : " . print_r($results, true));

        $totalItens = count($results);

        if ($totalItens > 0) {
            foreach ($results as $row) {


                if (empty($row['dtVerification'])) {
                    $dtVerification = null;
                } else {
                    $dtVerification = $row['dtVerification']->format('Y-m-d H:i:s');
                }


                if (empty($row['dtSubmission'])) {
                    $dtSubmission = null;
                } else {
                    $dtSubmission = $row['dtSubmission']->format('Y-m-d H:i:s');
                }

                if (empty($row['dtLastAccess'])) {
                    $dtLastAccess = null;
                } else {
                    $dtLastAccess = $row['dtLastAccess']->format('Y-m-d H:i:s');
                }

                $result = array(
                    'id_version_activity' => (string) $row['idVersionActivity'],
                    'id_activity_student' => (string) $row['idActivityStudent']['idActivityStudent'],
                    'tx_activity' => $row['txActivity'],
                    'dt_last_access' => $dtLastAccess,
                    'dt_submission' => $dtSubmission,
                    'dt_verification' => $dtVerification
                );
            }
        }
        return $result;
    }

    public function select_tb_comment_version($row_sync) {
        $totalItens = 0;
        //$this->logControle->log("COMENTARIO VERSAO IDS : " . print_r($row_sync, true));
        $result = array();
        $id_comment_version = $row_sync['coIdTable'];

        $queryBuilder = $this->em->createQueryBuilder();
        $queryBuilder
                ->select('c, v')
                ->from('AppBundle:TbCommentVersion', 'c')
                ->innerJoin('c.idVersionActivity', 'v')
                ->where($queryBuilder->expr()->eq('c.idCommentVersion', $id_comment_version))
                ->getQuery()
                ->execute();

        $results = $queryBuilder->getQuery()->getArrayResult();

        $totalItens = count($results);

        if ($totalItens > 0) {
            foreach ($results as $row) {

                $result = array(
                    'id_comment' => $row['idCommentVersion'],
                    'tx_reference' => $row['txReference'],
                    'nu_comment_activity' => (string) $row['nuCommentActivity'],
                    'nu_initial_pos' => (string) $row['nuInitialPos'],
                    'nu_size' => (string) $row['nuSize'],
                    'id_version_activity' => $row['idVersionActivity']['idVersionActivity']
                );
            }
        }
        return $result;
    }

    public function select_tb_reference($row_sync) {
        $totalItens = 0;
        //$this->logControle->log("COMENTARIO VERSAO IDS : " . print_r($row_sync, true));
        $result = array();
        $idreference = $row_sync['coIdTable'];

        $queryBuilder = $this->em->createQueryBuilder();
        $queryBuilder
                ->select('r,a')
                ->from('AppBundle:TbReference', 'r')
                ->innerJoin('r.idActivityStudent', 'a', 'WITH', 'r.idActivityStudent = a.idActivityStudent')
                ->where($queryBuilder->expr()->eq('r.idReference', $idreference))
                ->getQuery()
                ->execute();

        //$this->logControle->log($queryBuilder);
        $results = $queryBuilder->getQuery()->getArrayResult();
        //$this->log("TB REFERENCE : " . print_r($results, true));


        $totalItens = count($results);

        if ($totalItens > 0) {
            foreach ($results as $row) {

                $result = array(
                    'id_reference' => (string) $row['idReference'],
                    'ds_url' => $row['dsUrl'],
                    'id_reference_srv' => (string) $row['idReferenceSrv'],
                    'id_activity_student' => (string) $row['idActivityStudent']['idActivityStudent']
                );
            }
        }
        return $result;
    }

    public function select_tb_attach_activity($row_sync) {
        $totalItens = 0;
        $result = array();
        //$this->logControle->log('INICIO select_tb_activity_student');


        $idanexo = $row_sync['coIdTable'];

        $queryBuilder = $this->em->createQueryBuilder();
        $queryBuilder
                ->select('a,t,ac, u')
                ->from('AppBundle:TbAttachActivity', 'a')
                ->innerJoin('a.idAttachment', 't', 'WITH', 'a.idAttachment = t.idAttachment')
                ->innerJoin('a.idActivityStudent', 'ac', 'WITH', 'a.idActivityStudent = ac.idActivityStudent')
                ->innerJoin('t.idAuthor', 'u', 'WITH', 'u.idUser = t.idAuthor')
                ->where($queryBuilder->expr()->eq('a.idAttachActivity', $idanexo))
                ->getQuery()
                ->execute();

        //$this->log($queryBuilder);
        $results = $queryBuilder->getQuery()->getArrayResult();
        $this->logControle->log("TB select_tb_activity_student : " . print_r($results, true));


        $totalItens = count($results);

        if ($totalItens > 0) {
            foreach ($results as $row) {

                $result = array(
                    'id_attach_activity' => (string) $row['idAttachActivity'],
                    'id_activity_student' => (string) $row['idActivityStudent']['idActivityStudent'],
                    'id_attachment' => (string) $row['idAttachment']['idAttachment']
                );
                $result['attachment'] = array(
                    'tp_attachment' => $row['idAttachment']['tpAttachment'],
                    'id_author' => $row['idAttachment']['idAuthor']['idUser'],
                    'nm_file' => $row['idAttachment']['nmFile'],
                    'nm_system' => $row['idAttachment']['nmSystem']
                );
            }
        }

        return $result;
    }

    public function select_tb_version_activity_update($row_sync) {
        $totalItens = 0;
        $result = array();
        $idversion = $row_sync['coIdTable'];

        $queryBuilder = $this->em->createQueryBuilder();
        $queryBuilder
                ->select('v, a')
                ->from('AppBundle:TbVersionActivity', 'v')
                ->innerJoin('v.idActivityStudent', 'a', 'WITH', 'v.idActivityStudent = a.idActivityStudent')
                ->where($queryBuilder->expr()->eq('v.idVersionActivity', $idversion))
                ->getQuery()
                ->execute();

        //$this->logControle->log($queryBuilder);
        $results = $queryBuilder->getQuery()->getArrayResult();
        // $this->logControle->log("TB  VERSION ACTIVITY UPDATE : " . print_r($results, true));

        $totalItens = count($results);

        if ($totalItens > 0) {
            foreach ($results as $row) {

                if (empty($row['dtVerification'])) {
                    $dtVerification = null;
                } else {
                    $dtVerification = $row['dtVerification']->format('Y-m-d H:i:s');
                }


                if (empty($row['dtSubmission'])) {
                    $dtSubmission = null;
                } else {
                    $dtSubmission = $row['dtSubmission']->format('Y-m-d H:i:s');
                }

                if (empty($row['dtLastAccess'])) {
                    $dtLastAccess = null;
                } else {
                    $dtLastAccess = $row['dtLastAccess']->format('Y-m-d H:i:s');
                }

                $result = array(
                    'id_version_activity' => (string) $row['idVersionActivity'],
                    'id_activity_student' => (string) $row['idActivityStudent']['idActivityStudent'],
                    'tx_activity' => $row['txActivity'],
                    'dt_last_access' => $dtLastAccess,
                    'dt_submission' => $dtSubmission,
                    'dt_verification' => $dtVerification
                );
            }
        } else {
            $flag = 7;
            $this->error = $this->addError($flag);
            $result = $this->error;
        }
        return $result;
    }

//
//     public function select_tb_activity_student($row_sync) {
//        $totalItens = 0;
//        $result = array();
//       $this->logControle->log('INICIO select_tb_activity_student');
//
//
//        $idActivity = $row_sync['coIdTable'];
//
//       $this->logControle->log("id " . $idActivity);
//        $queryBuilder = $this->em->createQueryBuilder();
//        $queryBuilder
//                ->select('at,ps,a')
//                ->from('AppBundle:TbActivityStudent', 'at')
//                ->innerJoin('at.idPortfolioStudent', 'ps', 'WITH', 'at.idPortfolioStudent = ps.idPortfolioStudent')
//                ->innerJoin('at.idActivity', 'a', 'WITH', 'at.idActivity = a.idActivity')
//                ->where($queryBuilder->expr()->eq('at.idActivityStudent', $idActivity))
//                ->getQuery()
//                ->execute();
//
//       $this->logControle->log($queryBuilder);
//        $results = $queryBuilder->getQuery()->getArrayResult();
//       $this->logControle->log("TB select_tb_activity_student : " . print_r($results, true));
//
//
//        $totalItens = count($results);
//
//
//        if ($totalItens > 0) {
//            foreach ($results as $row) {
//
//                $result = array(
//                    'id_activity_student' => (string) $row['idActivityStudent'],
//                    'id_portfolio_student' => (string) $row['idPortfolioStudent']['idPortfolioStudent'],
//                    'id_activity' => (string) $row['idActivity']['idActivity'],
//                    'dt_conclusion' => $row['dtConclusion']->format('Y-m-d H:i:s')
//                );
//
//            }
//        }
//
//
//
//        return $result;
//    }
    public function select_tb_user($row_sync) {
        $totalItens = 0;
        //seleciona atualizaÃ§Ãµes dos usuarios conforme a tbsync
        //$this->logControle->log("select_tb_user : " . print_r($row_sync, true));

        $iduser = $row_sync['coIdTable'];

        $queryBuilder = $this->em->createQueryBuilder();
        $queryBuilder
                ->select("u")
                ->from('AppBundle:TbUser', 'u')
                ->where($queryBuilder->expr()->eq('u.idUser', $iduser))
                ->getQuery()
                ->execute();

        //$this->logControle->log($queryBuilder);
        $results = $queryBuilder->getQuery()->getArrayResult();
        //$this->logControle->log("TB USER : " . print_r($results, true));


        $totalItens = count($results);
        //NÃ£o encontrei uma forma de transformar as imagens pelo doctrine, entao usei do jeito normal//
        $select = "SELECT 
                            encode(im_photo::bytea, 'escape') as photo 
                        FROM 
                            tb_user
                        WHERE
                            id_user = " . $iduser;

        //$this->logControle->log("selecct user: " . $select);
        $resultado = pg_query($this->logControle->db, $select);
        if (pg_affected_rows($resultado) > 0) {
            while ($row = pg_fetch_assoc($resultado)) {
                $photo = $row['photo'];
            }
        }

        if ($totalItens > 0) {
            foreach ($results as $row) {
                $result[] = array(
                    'id_user' => (string) $row['idUser'],
                    'nm_user' => $row['nmUser'],
                    'nu_identification' => (string) $row['nuIdentification'],
                    'nu_cellphone' => $row['nuCellphone'],
                    'im_photo' => $photo,
                    'ds_email' => $row['dsEmail'],
                    'ds_email' => $row['dsEmail'],
                );
            }
        }
        return $result;
    }

    public function select_tb_activity_student($row_sync) {
        $totalItens = 0;
        $result = array();
        $this->logControle->log('INICIO select_tb_activity_student');


        $idActivity = $row_sync['coIdTable'];

        $this->logControle->log("id " . $idActivity);
        $queryBuilder = $this->em->createQueryBuilder();
        $queryBuilder
                ->select('at,ps,a')
                ->from('AppBundle:TbActivityStudent', 'at')
                ->innerJoin('at.idPortfolioStudent', 'ps', 'WITH', 'at.idPortfolioStudent = ps.idPortfolioStudent')
                ->innerJoin('at.idActivity', 'a', 'WITH', 'at.idActivity = a.idActivity')
                ->where($queryBuilder->expr()->eq('at.idActivityStudent', $idActivity))
                ->getQuery()
                ->execute();

        $this->logControle->log($queryBuilder);
        $results = $queryBuilder->getQuery()->getArrayResult();
        $this->logControle->log("TB select_tb_activity_student : " . print_r($results, true));


        $totalItens = count($results);


        if ($totalItens > 0) {
            foreach ($results as $row) {
                if (empty($row['dtConclusion'])) {
                    $dtConclusion = null;
                } else {
                    $dtConclusion = $row['dtConclusion']->format('Y-m-d H:i:s');
                }

                $result = array(
                    'id_activity_student' => (string) $row['idActivityStudent'],
                    'id_portfolio_student' => (string) $row['idPortfolioStudent']['idPortfolioStudent'],
                    'id_activity' => (string) $row['idActivity']['idActivity'],
                    'dt_conclusion' => $dtConclusion
                );
            }
        }



        return $result;
    }

}
