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
use AppBundle\Controller\IdDeviceSeqController;
use AppBundle\Entity\TbUser;
use AppBundle\Entity\TbClass;
use AppBundle\Entity\TbPortfolio;
use AppBundle\Entity\TbActivity;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use AppBundle\Controller\PortfolioStudentController;
use AppBundle\Entity\TbTutorPortfolio;

/**
 * Description of BasicDataController
 *
 * @author Marilia
 */
class BasicDataController extends Controller {

    public $em;
    private $response;
    public $id_user;
    public $id_portfolio_class = array();
    public $id_portfolio = array();
    public $id_class = array();
    public $results = array();
    public $error = array();
    public $id_comment_sync = array();
    public $id_activity_student = array();
    public $id_activity = array();
    public $id_activity_addComment = array();
    public $id_comment_activity = array();
    public $id_portfolio_student = array();
    public $id_attachment = array();
    public $id_device;
    public $anexos = array();
    public $logControle;
    public $resultadoPortfolio;
    public $resultadoActivity;
    public $id_usuarios_visitante = array();
    public $IdTutorPortfolio = array();
    public $idUsuariosLigados = array();
    public $IdTutorPortfolio_visitante = array();

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

    public function gerarJsonBasicData() {

        $users = $this->selectTb_User();
        $this->results["basicData_response"]["users"]["tb_user"] = $users;
        $this->logControle->log("tb_user: " . sizeof($users) . " registros");

        $portClass = $this->selectTb_portfolio_class();
        $this->results["basicData_response"]["portfolio_class"]["tb_portfolio_class"] = $portClass;
        $this->logControle->log("portfolio_class: " . sizeof($portClass) . " registros");

        $port = $this->selectTb_portfolio();
        $this->results["basicData_response"]["portfolio"]["tb_portfolio"] = $port;
        $this->logControle->log("portfolio: " . sizeof($port) . " registros");

        $class = $this->selectTb_class();
        $this->results["basicData_response"]["class"]["tb_class"] = $class;
        $this->logControle->log("class : " . sizeof($class) . " registros");

        $classStd = $this->selectTb_class_student();
        $this->results["basicData_response"]["classStudent"] ["tb_class_student"] = $classStd;
        $this->logControle->log("classStudent: " . sizeof($classStd) . " registros");

        $tutorPort = $this->selectTb_tutor_portfolio();
        $this->results["basicData_response"]["tutorPortfolio"] ["tb_tutor_portfolio"] = $tutorPort;
        $this->logControle->log("tutorPortfolio: " . sizeof($tutorPort) . " registros");

        $classTut = $this->selectTb_class_tutor();
        $this->results["basicData_response"]["classTutor"]["tb_class_tutor"] = $classTut;
        $this->logControle->log("classTutor: " . sizeof($classTut) . " registros");

        $actStd = $this->selectTb_activity_student();
        $this->results["basicData_response"]["activityStudent"]["tb_activity_student"] = $actStd;
        $this->logControle->log("activityStudent: " . sizeof($actStd) . " registros");

        $act = $this->selectTb_activity();
        $this->results["basicData_response"]["actvity"]["tb_activity"] = $act;
        $this->logControle->log("actvity: " . sizeof($act) . " registros");

        $policy = $this->selectTbPolicy();
        if ($policy['error']) {
            $this->results["basicData_response"]["policy"] = (object) array();
        } else {
            $this->results["basicData_response"]["policy"]["tb_policy"] = $policy;
        }
        $this->logControle->log("policy: " . sizeof($policy) . " registros");
    }

    public function gerarJsonVisitanteBasicData($resultadoVisitante) {


        $resultadoClass = $this->selecionarTbClassVisitante($resultadoVisitante);
        $this->results["basicDataVisitante_response"]["class"]["tb_class"] = $resultadoClass;

        $resultadoPortfolioClass = $this->selecionarTbPortfolioClass();
        $this->results["basicDataVisitante_response"]["portfolio_class"]["tb_portfolio_class"] = $resultadoPortfolioClass;

        $resultadoPortfolioStudent = $this->selecionarTbPortfolioStudent();
        $this->results["basicDataVisitante_response"]["portfolioStudent"]["tb_portfolio_student"] = $resultadoPortfolioStudent;

        $resultadoTutorPortfolio = $this->selecionarTbTutorPortfolio();
        $this->results["basicDataVisitante_response"]["tutorPortfolio"]["tb_tutor_portfolio"] = $resultadoTutorPortfolio;


        $this->results["basicDataVisitante_response"]["portfolio"]["tb_portfolio"] = $this->resultadoPortfolio;

        $resuldatoActivityStudent = $this->selecionarTbActivityStudent();
        $this->results["basicDataVisitante_response"]["activityStudent"]["tb_activity_student"] = $resuldatoActivityStudent;
        $this->results["basicDataVisitante_response"]["actvity"]["tb_activity"] = $this->selecionarActivity();

        $resultadoClassTutor = $this->selecionarClassTutor();
        $this->results["basicDataVisitante_response"]["classTutor"]["tb_class_tutor"] = $resultadoClassTutor;
        $resultadoClassStudent = $this->selecionarClassStudent();
        $this->results["basicDataVisitante_response"]["classStudent"] ["tb_class_student"] = $resultadoClassStudent;

        $this->results["basicDataVisitante_response"]["users"]["tb_user"] = $this->selecionarUsuarios();
    }

    public function selecionarTbActivityStudent() {
        foreach ($this->id_portfolio_student as $idPortfolioStudent) {
            $queryBuilderActivityStudent = $this->em->createQueryBuilder();
            $queryBuilderActivityStudent
                    ->select('ast,a')
                    ->from('AppBundle:TbActivityStudent', 'ast')
                    ->innerJoin('ast.idPortfolioStudent', 'ps', 'WITH', 'ast.idPortfolioStudent = ps.idPortfolioStudent')
                    ->innerJoin('ast.idActivity', 'a', 'WITH', 'ast.idActivity = a.idActivity')
                    ->where($queryBuilderActivityStudent->expr()->eq('ast.idPortfolioStudent', $idPortfolioStudent))
                    ->getQuery()
                    ->execute();

            $arrayActivityStudent = $queryBuilderActivityStudent->getQuery()->getArrayResult();
            $this->logControle->log(print_r($arrayActivityStudent, true));
            foreach ($arrayActivityStudent as $valueArray) {
                if (!in_array($valueArray['idActivity']['idActivity'], $this->id_activity)) {
                    $this->id_activity[] = $valueArray['idActivity']['idActivity'];
                }


                $result[] = array(
                    'id_activity_student' => $valueArray['idActivityStudent'],
                    'id_portfolio_student' => $idPortfolioStudent,
                    'id_activity' => $valueArray['idActivity']['idActivity'],
                    'dt_conclusion' => $valueArray['dtConclusion'],
                    'dt_first_sync' => $valueArray['dtFirstSync'],
                    'id_activity_student_srv' => $valueArray['idActivityStudentSrv'],
                );
            }
        }
        if (!isset($result)) {
            $flag = 7;
            $this->error[] = $this->addError($flag);
            $result['error'] = $this->error;
        }
        return $result;
    }

    public function selecionarActivity() {
        foreach ($this->id_activity as $idActivity) {
            $objActivity = $this->getDoctrine()
                    ->getRepository('AppBundle:TbActivity')
                    ->findOneBy(array('idActivity' => $idActivity));

            $ObjetoPortfolio = $objActivity->getIdPortfolio(); //retorna objeto da relação da classe Portfolio

            $resultadoActivity[] = array(
                'id_activity' => $objActivity->getIdActivity(),
                'id_portfolio' => $ObjetoPortfolio->getIdPortfolio(),
                'nu_order' => $objActivity->getNuOrder(),
                'ds_title' => $objActivity->getDsTitle(),
                'ds_description' => $objActivity->getDsDescription()
            );
        }
        if (!isset($resultadoActivity)) {
            $flag = 7;
            $this->error[] = $this->addError($flag);
            $resultadoActivity['error'] = $this->error;
        }
        return $resultadoActivity;
    }

    public function selecionarTbPortfolioClass() {
        foreach ($this->id_class as $idClass) {
            $queryBuilderPortfolioClass = $this->em->createQueryBuilder();
            $queryBuilderPortfolioClass
                    ->select('pc, c, p')
                    ->from('AppBundle:TbPortfolioClass', 'pc')
                    ->innerJoin('pc.idPortfolio', 'p', 'WITH', 'pc.idPortfolio = p.idPortfolio')
                    ->innerJoin('pc.idClass', 'c', 'WITH', 'c.idClass = pc.idClass')
                    ->where($queryBuilderPortfolioClass->expr()->eq('pc.idClass', $idClass))
                    ->getQuery()
                    ->execute();
            $arrayPortfolioClass = $queryBuilderPortfolioClass->getQuery()->getArrayResult();
            $this->incluirPortfolioJson($arrayPortfolioClass); //utiliza a mesma seleção para os portfolios
            foreach ($arrayPortfolioClass as $valueArray) {
//                if (!in_array($valueArray['idPortfolio']['idPortfolio'], $this->id_portfolio)) {
//                    $this->id_portfolio[] = $valueArray['idPortfolio']['idPortfolio'];
//                }
                if (!in_array($valueArray['idPortfolioClass'], $this->id_portfolio_class)) {
                    $this->id_portfolio_class[] = $valueArray['idPortfolioClass'];
                }
                $result[] = array(
                    'id_portfolio_class' => $valueArray['idPortfolioClass'],
                    'id_class' => $idClass,
                    'id_portfolio' => $valueArray['idPortfolio']['idPortfolio']
                );
            }
        }
        if (!isset($result)) {
            $flag = 7;
            $this->error[] = $this->addError($flag);
            $result['error'] = $this->error;
        }
        return $result;
    }

    public function incluirPortfolioJson($arrayPortfolioClass) {
        foreach ($arrayPortfolioClass as $valueArray) {
            $this->resultadoPortfolio[] = array(
                'id_portfolio' => $valueArray['idPortfolio']['idPortfolio'],
                'ds_title' => $valueArray['idPortfolio']['dsTitle'],
                'ds_description' => $valueArray['idPortfolio']['dsDescription'],
                'nu_portfolio_version' => $valueArray['idPortfolio']['nuPortfolioVersion']
            );
        }
    }

    public function selecionarTbPortfolioStudent() {
        foreach ($this->id_portfolio_class as $idPortfolioClass) {
            $retornoPortfolioStudent_tutorPortfolio_byPC = PortfolioStudentController::selecionarPortfolioStudentByPortfolioClass($idPortfolioClass);
            foreach ($retornoPortfolioStudent_tutorPortfolio_byPC as $valueArray) {
                $this->logControle->log(print_r($valueArray, true));
                if (!in_array($valueArray['idPortfolioStudent']['idPortfolioStudent'], $this->id_portfolio_student)) {
                    $this->id_portfolio_student[] = $valueArray['idPortfolioStudent']['idPortfolioStudent'];
                }
                if (!in_array($valueArray['idTutor']['idUser'], $this->id_usuarios_visitante)) {
                    $this->id_usuarios_visitante[] = $valueArray['idTutor']['idUser'];
                }
                if (!in_array($valueArray['idPortfolioStudent']['idStudent']['idUser'], $this->id_usuarios_visitante)) {
                    $this->id_usuarios_visitante[] = $valueArray['idPortfolioStudent']['idStudent']['idUser'];
                }

                if (!in_array($valueArray['idTutorPortfolio'], $this->IdTutorPortfolio_visitante)) {
                    $this->IdTutorPortfolio_visitante[] = $valueArray['idTutorPortfolio'];
                }
                $result[] = array(
                    'id_portifolio_student' => $valueArray['idPortfolioStudent']['idPortfolioStudent'],
                    'id_portfolio_class' => $valueArray['idPortfolioStudent']['idPortfolioClass']['idPortfolioClass'],
                    'id_student' => $valueArray['idPortfolioStudent']['idStudent']['idUser'],
                    'dt_first_sync' => $valueArray['idPortfolioStudent']['dtFirstSync'],
                    'nu_portfolio_version' => $valueArray['idPortfolioStudent']['nuPortfolioVersion']
                );
            }
        }
        if (!isset($result)) {
            $flag = 7;
            $this->error[] = $this->addError($flag);
            $result['error'] = $this->error;
        }
        return $result;
    }

    public function selecionarTbTutorPortfolio() {
        $this->logControle->log("selecionarTbTutorPortfolio");

        foreach ($this->IdTutorPortfolio_visitante as $idTutorPortfolio) {
            $this->logControle->log("selecionarTbTutorPortfolio");
            $this->logControle->log($idTutorPortfolio);
            $queryBuilderTbTutorPortfolio = $this->em->createQueryBuilder();
            $queryBuilderTbTutorPortfolio
                    ->select('tp, ps, u')
                    ->from('AppBundle:TbTutorPortfolio', 'tp')
                    ->innerJoin('tp.idTutor', 'u', 'WITH', 'u.idUser = tp.idTutor')
                    ->innerJoin('tp.idPortfolioStudent', 'ps', 'WITH', 'ps.idPortfolioStudent = tp.idPortfolioStudent')
                    ->where($queryBuilderTbTutorPortfolio->expr()->eq('tp.idTutorPortfolio', $idTutorPortfolio))
                    ->getQuery()
                    ->execute();
            $arrayTbTutorPortfolio = $queryBuilderTbTutorPortfolio->getQuery()->getArrayResult();


            foreach ($arrayTbTutorPortfolio as $array) {
                $resultado[] = array(
                    'id_tutor_portfolio' => $array['idTutorPortfolio'],
                    'id_tutor' => $array['idTutor']['idUser'],
                    'id_portfolio_student' => $array['idPortfolioStudent']['idPortfolioStudent']
                );
            }
        }
        $this->logControle->log(print_r($resultado, true));
        return $resultado;
    }

    public function selecionarUsuarios() {
        foreach ($this->id_usuarios_visitante as $idUser) {
            $objetoUser = $this->getDoctrine()
                    ->getRepository('AppBundle:TbUser')
                    ->findOneBy(array('idUser' => $idUser));


            $resultUsuarios[] = array(
                'id_user' => $objetoUser->getIdUser(),
                'nm_user' => $objetoUser->getNmUser(),
                'nu_identification' => $objetoUser->getNuIdentification(),
                'ds_email' => $objetoUser->getDsEmail(),
                'nu_cellphone' => $objetoUser->getNuCellphone()
            );
        }
        if (!isset($resultUsuarios)) {
            $flag = 7;
            $this->error[] = $this->addError($flag);
            $resultUsuarios['error'] = $this->error;
        }
        return $resultUsuarios;
    }

    public function selecionarTbClassVisitante($resultadoVisitante) {
        $result = array();
        foreach ($resultadoVisitante as $valueVisitante) {

            if (!in_array($valueVisitante['idClass']['idClass'], $this->id_class)) {
                $this->id_class[] = $valueVisitante['idClass']['idClass'];
            }
            $idProposer = $this->selecionarIdProposer($valueVisitante['idClass']['idClass']);
            $result[] = array(
                'id_class' => $valueVisitante['idClass']['idClass'],
                'id_proposer' => $idProposer,
                'ds_code' => $valueVisitante['idClass']['dsCode'],
                'ds_description' => $valueVisitante['idClass']['dsDescription'],
                'st_status' => $valueVisitante['idClass']['stStatus'],
                'dt_finish' => $valueVisitante['idClass']['dtFinish']
            );
        }
        if (!isset($result)) {
            $flag = 7;
            $this->error[] = $this->addError($flag);
            $result['error'] = $this->error;
        }
        return $result;
    }

    public function selecionarIdProposer($idClass) {
        $this->logControle->log("Selecionando idProposer" . $idClass);
        $queryBuilderProposer = $this->em->createQueryBuilder();
        $queryBuilderProposer
                ->select('c,u')
                ->from('AppBundle:TbClass', 'c')
                ->innerJoin('c.idProposer', 'u', 'WITH', 'c.idProposer = u.idUser')
                ->where($queryBuilderProposer->expr()->eq('c.idClass', $idClass))
                ->getQuery()
                ->execute();
        $arrayClass = $queryBuilderProposer->getQuery()->getArrayResult();

        return $arrayClass[0]["idProposer"]["idUser"];
    }

    public function atualizarDataBasicData() {
        $dtBasic = date('Y-m-d H:i:s');
        $queryBuilder = $this->em->createQueryBuilder();
        $queryBuilder
                ->update('AppBundle:TbDevice', 'd')
                ->set('d.dtBasicData', $queryBuilder->expr()->literal($dtBasic))
                ->where($queryBuilder->expr()->eq('d.idDevice', $this->id_device))
                ->getQuery()
                ->execute();

        error_reporting(0);
    }

    public function selecionarClassTutor() {
        foreach ($this->id_class as $idClass) {
            $queryBuilderClassTutor = $this->em->createQueryBuilder();
            $queryBuilderClassTutor
                    ->select('ct,u')
                    ->from('AppBundle:TbClassTutor', 'ct')
                    ->innerJoin('ct.idTutor', 'u', 'WITH', 'ct.idTutor = u.idUser')
                    ->innerJoin('ct.idClass', 'c', 'WITH', 'ct.idClass = c.idClass')
                    ->where($queryBuilderClassTutor->expr()->eq('ct.idClass', $idClass))
                    ->getQuery()
                    ->execute();
            $arrayClassTutor = $queryBuilderClassTutor->getQuery()->getArrayResult();
            $this->logControle->log("--------------------------");
            $this->logControle->log($idClass);

            foreach ($arrayClassTutor as $valueArray) {
                $result[] = array(
                    'id_class_tutor' => $valueArray['idClassTutor'],
                    'id_class' => $idClass,
                    'id_tutor' => $valueArray['idTutor']['idUser']
                );
            }
        }
        if (!isset($result)) {
            $flag = 7;
            $this->error[] = $this->addError($flag);
            $result['error'] = $this->error;
        }
        return $result;
    }

    public function selecionarClassStudent() {
        foreach ($this->id_class as $idClass) {
            $queryBuilderClassTutor = $this->em->createQueryBuilder();
            $queryBuilderClassTutor
                    ->select('ct,u')
                    ->from('AppBundle:TbClassStudent', 'ct')
                    ->innerJoin('ct.idStudent', 'u', 'WITH', 'ct.idStudent = u.idUser')
                    ->innerJoin('ct.idClass', 'c', 'WITH', 'ct.idClass = c.idClass')
                    ->where($queryBuilderClassTutor->expr()->eq('ct.idClass', $idClass))
                    ->getQuery()
                    ->execute();
            $arrayClassTutor = $queryBuilderClassTutor->getQuery()->getArrayResult();

            foreach ($arrayClassTutor as $valueArray) {
                $result[] = array(
                    'id_class_student' => $valueArray['idClassStudent'],
                    'id_class' => $idClass,
                    'id_student' => $valueArray['idStudent']['idUser']
                );
            }
        }
        if (!isset($result)) {
            $flag = 7;
            $this->error[] = $this->addError($flag);
            $result['error'] = $this->error;
        }
        return $result;
    }

    /**
     * @Route("/basicData")
     */
    public function basicData(Request $req) {
        $this->logControle->log('INICIO basicData');
        $this->em = $this->getDoctrine()->getEntityManager();
        $this->response = NULL;
        $this->error = NULL;
        $this->results = NULL;
        $this->id_user = NULL;
        $this->id_device = NULL;

        if (0 === strpos($req->headers->get('Content-Type'), 'application/json')) {
            $data = json_decode($req->getContent(), true);
            $req->request->replace(is_array($data) ? $data : array());

            if ((!empty($data)) && (!empty($data['basicData_request']))) {
                $this->logControle->log("REQUEST basicData" . print_r($data, true));

                $data = $data['basicData_request'];

                if ((!empty($data['id_user'])) && (!empty($data['ds_hash']))) {

                    $ds_hash = $data['ds_hash'];
                    $iduser = $data['id_user'];
                    $id_device = (IdDeviceSeqController::getIdDeviceSeq($ds_hash, $iduser));

                    if ($id_device == 0) {
                        $flag = 7;
                        $this->error[] = $this->addError($flag);
                        $this->results["basicData_response"]["error"] = $this->error;
                    } else {
                        $this->id_device = $id_device;
                        $this->id_user = $iduser;

                        $portStd = $this->selectTb_portfolio_student();

                        if (empty($portStd['error'])) {
                            $this->results["basicData_response"]["portfolioStudent"]["tb_portfolio_student"] = $portStd;
                            $this->gerarJsonBasicData();
                        }
//                        } else { passar a selecionar os dois tipos de usuarios

                        $resultadoVisitante = VisitanteController::verificarVisitante($this->id_user);
                        if ($resultadoVisitante > 0) {
                            $this->logControle->log("é visitante");
                            $this->gerarJsonVisitanteBasicData($resultadoVisitante);
                        } else {

                            $this->logControle->log("USUARIO SEM PORTFOLIO");
                            $this->results["basicData_response"]['tb_portfolioStudent']["error"] = $portStd['error'];
                        }
//                        }

                        $this->atualizarDataBasicData();
                    }
                } else {
                    $flag = 5;
                    $this->error[] = $this->addError($flag);
                    $this->results["basicData_response"]["error"] = $this->error;
                }
            }
        } else {
            $this->logControle->log('Campos vazios!');
            $flag = 2;
            $this->error[] = $this->addError($flag);
            $this->results["basicData_response"]["error"] = $this->error;
        }

        $this->logControle->log(print_r($this->results, true));

        $this->response = $this->results;

        $this->logControle->log("FIM");
        $this->logControle->log("==============================================================================");
        return new JsonResponse($this->response);
    }

    public function selectTb_User() {
        foreach ($this->idUsuariosLigados as $idUsuario) {
            $obj_student_tutor = $this->getDoctrine()
                    ->getRepository('AppBundle:TbUser')
                    ->findOneBy(array('idUser' => $idUsuario));

            if (!$obj_student_tutor) {

                $this->logControle->log(" ----  OCORREU UM ERRO NO BANCO --- " . $obj_student_tutor . " \nERRO: " . pg_last_error($this->logControle->db));

                $flag = 1;
                $this->error[] = $this->addError($flag);
                $result['error'] = $this->error;
                break;
            } else {


                $result[] = array(
                    'id_user' => $obj_student_tutor->getIdUser(),
                    'nm_user' => $obj_student_tutor->getNmUser(),
                    'nu_identification' => $obj_student_tutor->getNuIdentification(),
                    'ds_email' => $obj_student_tutor->getDsEmail(),
                    'nu_cellphone' => $obj_student_tutor->getNuCellphone()
                );
            }
        }


        return $result;
    }

    public function selectTb_portfolio_student() {


        $retornoPortfolioStudent_tutorPortfolio = PortfolioStudentController::selecionarPortfolioStudent($this->id_user);
        $totalRetorno = count($retornoPortfolioStudent_tutorPortfolio);


        if ($totalRetorno == 0) {
            
            $flag = 1;
            $this->error[] = $this->addError($flag);
            $result['error'] = $this->error;
        } else {
            foreach ($retornoPortfolioStudent_tutorPortfolio as $arrayPortfolioStudent_tutorPortfolio) {
                $this->logControle->log("arrayPortfolioStudent_tutorPortfolio");
                $this->logControle->log(print_r($arrayPortfolioStudent_tutorPortfolio, true));
                $result[] = array(
                    'id_portifolio_student' => $arrayPortfolioStudent_tutorPortfolio['idPortfolioStudent']['idPortfolioStudent'],
                    'id_portfolio_class' => $arrayPortfolioStudent_tutorPortfolio['idPortfolioStudent']['idPortfolioClass']['idPortfolioClass'],
                    'id_student' => $arrayPortfolioStudent_tutorPortfolio['idPortfolioStudent']['idStudent']['idUser'],
                    'dt_first_sync' => $arrayPortfolioStudent_tutorPortfolio['idPortfolioStudent']['dtFirstSync'],
                    'nu_portfolio_version' => $arrayPortfolioStudent_tutorPortfolio['idPortfolioStudent']['nuPortfolioVersion']
                );
                if (!in_array($arrayPortfolioStudent_tutorPortfolio['idTutorPortfolio'], $this->IdTutorPortfolio)) {
                    $this->IdTutorPortfolio[] = $arrayPortfolioStudent_tutorPortfolio['idTutorPortfolio'];
                }
                if (!in_array($arrayPortfolioStudent_tutorPortfolio['idPortfolioStudent']['idPortfolioClass']['idPortfolioClass'], $this->id_portfolio_class)) {
                    $this->id_portfolio_class[] = $arrayPortfolioStudent_tutorPortfolio['idPortfolioStudent']['idPortfolioClass']['idPortfolioClass'];
                }

                if (!in_array($arrayPortfolioStudent_tutorPortfolio['idPortfolioStudent']['idPortfolioStudent'], $this->id_portfolio_student)) {
                    $this->id_portfolio_student[] = $arrayPortfolioStudent_tutorPortfolio['idPortfolioStudent']['idPortfolioStudent'];
                }
                $this->selecionarOutrosUsuarios($arrayPortfolioStudent_tutorPortfolio['idPortfolioStudent']['idPortfolioStudent']);


                if (!in_array($arrayPortfolioStudent_tutorPortfolio['idPortfolioStudent']['idStudent']['idUser'], $this->idUsuariosLigados)) {
                    $this->idUsuariosLigados[] = $arrayPortfolioStudent_tutorPortfolio['idPortfolioStudent']['idStudent']['idUser'];
                }
                if (!in_array($arrayPortfolioStudent_tutorPortfolio['idTutor']['idUser'], $this->idUsuariosLigados)) {
                    $this->idUsuariosLigados[] = $arrayPortfolioStudent_tutorPortfolio['idTutor']['idUser'];
                }
            }
        }

        return $result;
    }

    public function selecionarOutrosUsuarios($idPortfolioStudent) {
        $retornoPortfolioStudent_tutorPortfolio = PortfolioStudentController::selecionarPortfolioStudentByIdPortfolioStudent($idPortfolioStudent);
        $this->logControle->log("selecionarOutrosTutores");
        $this->logControle->log(print_r($retornoPortfolioStudent_tutorPortfolio, true));
        foreach ($retornoPortfolioStudent_tutorPortfolio as $array) {
            if (!in_array($array['idTutor']['idUser'], $this->idUsuariosLigados)) {
                $this->idUsuariosLigados[] = $array['idTutor']['idUser'];
            }
            if (!in_array($array['idPortfolioStudent']['idStudent']['idUser'], $this->idUsuariosLigados)) {
                $this->idUsuariosLigados[] = $array['idPortfolioStudent']['idStudent']['idUser'];
            }
        }
    }

    public function selectTb_portfolio_class() {

        foreach ($this->id_portfolio_class as $id_portfolio_class) {
            $sql = "SELECT
                        id_portfolio_class,
                        id_class, 
                        id_portfolio 
                    FROM
                        tb_portfolio_class
                    WHERE
                        id_portfolio_class = " . $id_portfolio_class . "";

            error_reporting(0);

            $verif = pg_query($this->logControle->db, $sql);

            if (!$verif) {
                $this->logControle->log(" ----  OCORREU UM ERRO NO BANCO --- " . $sql . " \nERRO: " . pg_last_error($this->logControle->db));

                $flag = 1;
                $this->error[] = $this->addError($flag);
                $result['error'] = $this->error;
                break;
            } else {
                if (pg_affected_rows($verif) > 0) {

                    while ($row = pg_fetch_assoc($verif)) {
                        $result[] = array(
                            'id_portfolio_class' => $row['id_portfolio_class'],
                            'id_class' => $row['id_class'],
                            'id_portfolio' => $row['id_portfolio']
                        );


                        if (!in_array($row['id_portfolio'], $this->id_portfolio)) {
                            $this->id_portfolio[] = $row['id_portfolio'];
                        }

                        if (!in_array($row['id_class'], $this->id_class)) {
                            $this->id_class[] = $row['id_class'];
                        }
                    }
                } else {
                    $this->logControle->log(" ----  OCORREU UM ERRO NO BANCO --- " . $sql . " \nERRO: " . pg_last_error($this->logControle->db));
                    $flag = 7;
                    $this->error[] = $this->addError($flag);
                    $result['error'] = $this->error;
                    break;
                }
            }
        }
        return $result;
    }

    public function selectTb_portfolio() {
        foreach ($this->id_portfolio as $id_portfolio) {

            $sql = "SELECT 
                        id_portfolio,
                        ds_title,
                        ds_description,
                        nu_portfolio_version 
                    FROM
                        tb_portfolio
                    WHERE
                        id_portfolio = " . $id_portfolio . "";


            error_reporting(0);

            $verif = pg_query($this->logControle->db, $sql);

            if (!$verif) {
                $this->logControle->log(" ----  OCORREU UM ERRO NO BANCO --- " . $sql . " \nERRO: " . pg_last_error($this->logControle->db));

                $flag = 1;
                $this->error[] = $this->addError($flag);
                $result['error'] = $this->error;
                break;
            } else {

                if (pg_affected_rows($verif) > 0) {

                    while ($row = pg_fetch_assoc($verif)) {
                        $result[] = array(
                            'id_portfolio' => $row['id_portfolio'],
                            'ds_title' => $row['ds_title'],
                            'ds_description' => $row['ds_description'],
                            'nu_portfolio_version' => $row['nu_portfolio_version']
                        );
                    }
                } else {
                    $this->logControle->log(" ----  OCORREU UM ERRO NO BANCO --- " . $sql . " \nERRO: " . pg_last_error($this->logControle->db));
                    $flag = 7;
                    $this->error[] = $this->addError($flag);
                    $result['error'] = $this->error;
                    break;
                }
            }
        }
        return $result;
    }

    public function selectTb_class() {
        foreach ($this->id_class as $id_class) {
            $sql = "SELECT 
                        id_class,
                        id_proposer,
                        ds_code,
                        ds_description,
                        st_status,
                        dt_start,
                        dt_finish
                    FROM 
                        tb_class
                    WHERE
                        id_class = " . $id_class . "";
            error_reporting(0);

            $verif = pg_query($this->logControle->db, $sql);

            if (!$verif) {
                $this->logControle->log(" ----  OCORREU UM ERRO NO BANCO --- " . $sql . " \nERRO: " . pg_last_error($this->logControle->db));

                $flag = 1;
                $this->error[] = $this->addError($flag);
                $result['error'] = $this->error;
                break;
            } else {

                if (pg_affected_rows($verif) > 0) {

                    while ($row = pg_fetch_assoc($verif)) {

                        $result[] = array(
                            'id_class' => $row['id_class'],
                            'id_proposer' => $row['id_proposer'],
                            'ds_code' => $row['ds_code'],
                            'ds_description' => $row['ds_description'],
                            'st_status' => $row['st_status'],
                            'dt_finish' => $row['dt_finish']
                        );
                    }
                } else {
                    $this->logControle->log(" ----  OCORREU UM ERRO NO BANCO --- " . $sql . " \nERRO: " . pg_last_error($this->logControle->db));
                    $flag = 7;
                    $this->error[] = $this->addError($flag);
                    $result['error'] = $this->error;
                    break;
                }
            }
        }
        return $result;
    }

    public function selectTb_class_student() {
        foreach ($this->id_class as $id_class) {
            $sql = "SELECT 
                        id_class_student,
                        id_class,
                        id_student
                    FROM
                        tb_class_student
                    WHERE 
                        id_class=" . $id_class . " 
                        and id_student = " . $this->id_user . " ";

            error_reporting(0);

            $verif = pg_query($this->logControle->db, $sql);

            if (!$verif) {
                $this->logControle->log(" ----  OCORREU UM ERRO NO BANCO --- " . $sql . " \nERRO: " . pg_last_error($this->logControle->db));

                $flag = 1;
                $this->error[] = $this->addError($flag);
                $result['error'] = $this->error;
                break;
            } else {

                if (pg_affected_rows($verif) > 0) {

                    while ($row = pg_fetch_assoc($verif)) {
                        $result[] = array(
                            'id_class_student' => $row['id_class_student'],
                            'id_class' => $row['id_class'],
                            'id_student' => $row['id_student']
                        );
                    }
                } else {
                    $this->logControle->log(" ----  OCORREU UM ERRO NO BANCO --- " . $sql . " \nERRO: " . pg_last_error($this->logControle->db));
                    $flag = 7;
                    $this->error[] = $this->addError($flag);
                    $result['error'] = $this->error;
                    break;
                }
            }
        }
        return $result;
    }

    public function selectTb_class_tutor() {
        foreach ($this->id_class as $id_class) {
            $sql = "SELECT 
                        id_class_tutor,
                        id_class,
                        id_tutor
                    FROM 
                        tb_class_tutor
                    WHERE 
                        id_class = " . $id_class . "";

            error_reporting(0);

            $verif = pg_query($this->logControle->db, $sql);

            if (!$verif) {
                $this->logControle->log(" ----  OCORREU UM ERRO NO BANCO --- " . $sql . " \nERRO: " . pg_last_error($this->logControle->db));

                $flag = 1;
                $this->error[] = $this->addError($flag);
                $result['error'] = $this->error;
                break;
            } else {

                if (pg_affected_rows($verif) > 0) {

                    while ($row = pg_fetch_assoc($verif)) {
                        $result[] = array(
                            'id_class_tutor' => $row['id_class_tutor'],
                            'id_class' => $row['id_class'],
                            'id_tutor' => $row['id_tutor']
                        );
                    }
                } else {
                    $this->logControle->log(" ----  OCORREU UM ERRO NO BANCO --- " . $sql . " \nERRO: " . pg_last_error($this->logControle->db));
                    $flag = 7;
                    $this->error[] = $this->addError($flag);
                    $result['error'] = $this->error;
                    break;
                }
            }
        }
        return $result;
    }

    public function selectTb_activity_student() {
        foreach ($this->id_portfolio_student as $id_portfolio_student) {

            $sql = "SELECT 
                        id_activity_student,
                        id_portfolio_student,
                        id_activity,
                        dt_conclusion,
                        dt_first_sync,
                        id_activity_student_srv 
                    FROM 
                        tb_activity_student
                    WHERE 
                        id_portfolio_student = " . $id_portfolio_student . "";

            error_reporting(0);

            $verif = pg_query($this->logControle->db, $sql);

            if (!$verif) {
                $this->logControle->log(" ----  OCORREU UM ERRO NO BANCO --- " . $sql . " \nERRO: " . pg_last_error($this->logControle->db));

                $flag = 1;
                $this->error[] = $this->addError($flag);
                $result['error'] = $this->error;
                break;
            } else {

                if (pg_affected_rows($verif) > 0) {

                    while ($row = pg_fetch_assoc($verif)) {
                        $result[] = array(
                            'id_activity_student' => $row['id_activity_student'],
                            'id_portfolio_student' => $row['id_portfolio_student'],
                            'id_activity' => $row['id_activity'],
                            'dt_conclusion' => $row['dt_conclusion'],
                            'dt_first_sync' => $row['dt_first_sync'],
                            'id_activity_student_srv' => $row['id_activity_student_srv'],
                        );

                        if (!in_array($row['id_activity'], $this->id_activity)) {
                            $this->id_activity[] = $row['id_activity'];
                        }
                        if (!in_array($row['id_activity_student'], $this->id_activity_student)) {
                            $this->id_activity_student[] = $row['id_activity_student'];
                        }
                    }
                } else {
                    $this->logControle->log(" ----  OCORREU UM ERRO NO BANCO --- " . $sql . " \nERRO: " . pg_last_error($this->logControle->db));
                    $flag = 7;
                    $this->error[] = $this->addError($flag);
                    $result['error'] = $this->error;
                    break;
                }
            }
        }
        return $result;
    }

    public function selectTb_activity() {

        foreach ($this->id_activity as $id_activity) {

            $sql = "SELECT
                         id_activity,
                         id_portfolio,
                         nu_order,
                         ds_title,
                         ds_description
                    FROM
                        tb_activity
                    WHERE
                        id_activity = " . $id_activity . " ";

            error_reporting(0);

            $verif = pg_query($this->logControle->db, $sql);

            if (!$verif) {
                $this->logControle->log(" ----  OCORREU UM ERRO NO BANCO --- " . $sql . " \nERRO: " . pg_last_error($this->logControle->db));

                $flag = 1;
                $this->error[] = $this->addError($flag);
                $result['error'] = $this->error;
                break;
            } else {

                if (pg_affected_rows($verif) > 0) {

                    while ($row = pg_fetch_assoc($verif)) {
                        $result[] = array(
                            'id_activity' => $row['id_activity'],
                            'id_portfolio' => $row['id_portfolio'],
                            'nu_order' => $row['nu_order'],
                            'ds_title' => $row['ds_title'],
                            'ds_description' => $row['ds_description']
                        );
                    }
                } else {
                    $this->logControle->log(" ----  OCORREU UM ERRO NO BANCO --- " . $sql . " \nERRO: " . pg_last_error($this->logControle->db));
                    $flag = 7;
                    $this->error[] = $this->addError($flag);
                    $result['error'] = $this->error;
                    break;
                }
            }
        }
        return $result;
    }

    public function selectTb_tutor_portfolio() {

        foreach ($this->IdTutorPortfolio as $idTutorPortfolio) {

            $this->logControle->log($idTutorPortfolio);
            $queryBuilderTbTutorPortfolio = $this->em->createQueryBuilder();
            $queryBuilderTbTutorPortfolio
                    ->select('tp, ps, u')
                    ->from('AppBundle:TbTutorPortfolio', 'tp')
                    ->innerJoin('tp.idTutor', 'u', 'WITH', 'u.idUser = tp.idTutor')
                    ->innerJoin('tp.idPortfolioStudent', 'ps', 'WITH', 'ps.idPortfolioStudent = tp.idPortfolioStudent')
                    ->where($queryBuilderTbTutorPortfolio->expr()->eq('tp.idTutorPortfolio', $idTutorPortfolio))
                    ->getQuery()
                    ->execute();
            $arrayTbTutorPortfolio = $queryBuilderTbTutorPortfolio->getQuery()->getArrayResult();


            foreach ($arrayTbTutorPortfolio as $array) {
                $resultado[] = array(
                    'id_tutor_portfolio' => $array['idTutorPortfolio'],
                    'id_tutor' => $array['idTutor']['idUser'],
                    'id_portfolio_student' => $array['idPortfolioStudent']['idPortfolioStudent']
                );
            }
        }
        $this->logControle->log(print_r($resultado, true));
        return $resultado;
    }

    public function selectTbPolicy() {
        $totalItens = 0;
        $queryBuilder = $this->em->createQueryBuilder();
        $queryBuilder
                ->select('pu, p, u')
                ->from('AppBundle:TbPolicyUser', 'pu')
                ->innerJoin('pu.idPolicy', 'p', 'WITH', 'pu.idPolicy = p.idPolicy')
                ->innerJoin('pu.idUser', 'u', 'WITH', 'pu.idUser = u.idUser')
                ->Where($queryBuilder->expr()->eq('pu.idUser', $this->id_user))
                ->getQuery()
                ->execute();

        //  $this->logControle->log($queryBuilder);
        $results = $queryBuilder->getQuery()->getArrayResult();
        //  $this->logControle->log("TB POLICY : " . print_r($results, true));
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
            $this->logControle->log(" ----  OCORREU UM ERRO NO BANCO --- " . $sql . " \nERRO: " . pg_last_error($this->logControle->db));
            $flag = 7;
            $this->error[] = $this->addError($flag);
            $result['error'] = $this->error;
        }
        return $result;
    }

}
