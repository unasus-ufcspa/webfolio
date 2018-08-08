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

/**
 * Description of FirstLoginController
 *
 * @author Marilia
 */
class FirstLoginController extends Controller {

    public $em;
    public $results = NULL;
    public $response = NULL;
    public $error = NULL;
    public $id_class = NULL;
    public $id_activity = NULL;
    public $id_activity_student = NULL;
    public $id_portfolio = NULL;
    public $id_portfolio_class = NULL;
    public $id_portfolio_student = NULL;
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

    public function gerarJsons($fl_device, $fl_firstSync, $user) {
        $idUser = $user->getIdUser();

        $photo = UserController::selecionarFotoUsuario($idUser);
        $this->results["firstLogin_response"] = array(
            'fl_device' => $fl_device,
            'fl_firstSync' => $fl_firstSync
        );

        $this->results["firstLogin_response"]["tb_user"] = array(
            'id_user' => $user->getIdUser(),
            'nm_user' => $user->getNmUser(),
            'nu_identification' => $user->getNuIdentification(),
            'ds_email' => $user->getDsEmail(),
            'nu_cellphone' => $user->getNuCellphone(),
            'im_photo' => $photo
        );
        $resultadoVisitante = VisitanteController::verificarVisitante($idUser);
        if ($resultadoVisitante > 0) {
            foreach ($resultadoVisitante as $valueVisitante) {
                $this->results["firstLogin_response"]["tb_guest"][] = array(
                    "id_class" => $valueVisitante['idClass']['idClass'],
                    "id_guest" => $valueVisitante['idGuest'],
                    "fl_comments" => $valueVisitante['flComments']
                );
            }
        }
    }

    public function atualizarDispositivoBranco($ds_hash, $idDevice, $dtfirstlogin) {
        $queryBuilder = $this->em->createQueryBuilder();
        $queryBuilder
                ->update('AppBundle:TbDevice', 'u')
                ->set('u.dsHash', $queryBuilder->expr()->literal($ds_hash))
                ->set('u.dtFirstLogin', $queryBuilder->expr()->literal($dtfirstlogin))
                ->where($queryBuilder->expr()->eq('u.idDevice', $idDevice))
                ->getQuery()
                ->execute();
    }

    /**
     * @Route("/verificarEmail")
     */
    public function verificarEmailAction(Request $request) {
        $this->logControle->log('VERIFICAR EMAIL');

        if (0 === strpos($request->headers->get('Content-Type'), 'application/json')) {
            $data = json_decode($request->getContent(), true);
            $request->request->replace(is_array($data) ? $data : array());
            $this->logControle->log("REQUEST VERIFICAR EMAIL " . print_r($data, true));

            if ((!empty($data)) && (!empty($data['verificarEmail_request']))) {
                $data = $data['verificarEmail_request'];
                $this->buscarUsuarioPorEmail($data);
            } else {
                $this->logControle->log(" ----  OCORREU UM ERRO NO BANCO --- " . pg_last_error($this->db));
                $flag = 3;
                $this->error[] = $this->addError($flag);
            }
        } else {
            $this->logControle->log(" ----  FORMATO NÃO É JSON --- ");
            $flag = 4;
            $this->error[] = $this->addError($flag);
        }
        if (empty($this->error)){
            $this->response['verificarEmail_response']=  $this->results;
        }
        $this->logControle->log("RESPONSE VERIFICAR EMAIL " . print_r($this->response, true));
        $this->logControle->log("FIM");
        $this->logControle->log("==============================================================================");
        return new JsonResponse($this->response);
    }

    public function buscarUsuarioPorEmail($data) {
        if (!empty($data['email'])) {
            $email = $data['email'];
            $userEmail = $this->getDoctrine()
                    ->getRepository('AppBundle:TbUser')
                    ->findOneBy(array('dsEmail' => $email));
            if ($userEmail) {
                $this->verificarSenha($userEmail);
            } else {
                $this->logControle->log('Usuario nao localizado');
                $flag = 5;
                $this->error[] = $this->addError($flag);
            }
        } else {
            $this->logControle->log(" ----  OCORREU UM ERRO NO BANCO --- " . pg_last_error($this->db));
            $this->logControle->log('campos vazios');
            $flag = 2;
            $this->error[] = $this->addError($flag);
        }
    }

    public function verificarSenha($userEmail) {
        if (empty($userEmail->getDsPassword())) {
            $possuiSenha = false;
           // sendEmail(); //TO-DO: implementar envio de email
        } else {
            $possuiSenha = true;
        }
        $this->results = array(
            'possuiSenha' => $possuiSenha
        );
    }

    /**
     * @Route("/firstLogin")
     */
    public function firstLogin(Request $req) {
        $this->em = $this->getDoctrine()->getEntityManager();
        $this->logControle->log('INICIO FIRST_LOGIN');

        if (0 === strpos($req->headers->get('Content-Type'), 'application/json')) {
            $data = json_decode($req->getContent(), true);
            $req->request->replace(is_array($data) ? $data : array());
            $this->logControle->log("REQUEST FIRST_LOGIN " . print_r($data, true));

            if ((!empty($data)) && (!empty($data['firstLogin_request']))) {
                $data = $data['firstLogin_request'];

                if ((!empty($data['email'])) && (!empty($data['passwd'])) && (!empty($data['ds_hash'])) && (!empty($data['tp_device']))) {
                    $email = $data['email'];
                    $passwd = $data['passwd'];
                    $ds_hash = $data['ds_hash'];
                    $tp_device = $data['tp_device'];

                    $user = $this->getDoctrine()
                            ->getRepository('AppBundle:TbUser')
                            ->findOneBy(array('dsEmail' => $email, 'dsPassword' => $passwd));

                    if ($user) {

                        //   $this-> $this->logControle->log(" iduser : " . $id);
                        //logando de novo --  isso nao deve ocorrer ao passo que o dev tem o dado que o firstLogin ja foi feito
                        // -- nao devendo exibir a tela de login nem fazer o basicData
                        //user again
//                        $queryBuilder = $this->em->createQueryBuilder();
//                        $queryBuilder
//                                ->select('u,d')
//                                ->from('AppBundle:TbDevice', 'd')
//                                ->innerJoin('d.idUser', 'u', 'WITH', 'd.idUser =u.idUser')
//                                ->Where($queryBuilder->expr()->eq('d.idUser', $id))
//                                ->andWhere($queryBuilder->expr()->isNotNull('d.dsHash'))
//                                ->andWhere($queryBuilder->expr()->isNotNull('d.tpDevice'))
//                                ->getQuery()
//                                ->execute();
//
//                        $resultadoUserAgain = $queryBuilder->getQuery()->getArrayResult();
                        $id = $user->getIdUser();
                        $fl_firstSync = "S";
                        $fl_device = "N";
                        $this->logControle->log("teste de entrada na função login");

                        $deviceAgain = $this->getDoctrine()
                                ->getRepository('AppBundle:TbDevice')
                                ->findOneBy(array('idUser' => $id, 'dsHash' => $ds_hash, 'tpDevice' => $tp_device, 'dtLogout' => NULL));
                        $totalItens = count($deviceAgain);

                        if ($deviceAgain) {
                            $this->gerarJsons($fl_device, $fl_firstSync, $user);
                        } else {

                            $queryBuilder = $this->em->createQueryBuilder();
                            $queryBuilder
                                    ->select('u,d')
                                    ->from('AppBundle:TbDevice', 'd')
                                    ->innerJoin('d.idUser', 'u', 'WITH', 'd.idUser =u.idUser')
                                    ->Where($queryBuilder->expr()->eq('d.idUser', $id))
                                    ->andWhere($queryBuilder->expr()->eq('d.dsHash', "'" . $ds_hash . "'"))
                                    ->andWhere($queryBuilder->expr()->eq('d.tpDevice', "'" . $tp_device . "'"))
                                    ->andWhere($queryBuilder->expr()->isNotNull('d.dtLogout'))
                                    ->getQuery()
                                    ->execute();

                            $resultado = $queryBuilder->getQuery()->getArrayResult();
                            $totalItens = count($resultado);

                            if ($totalItens > 0) {
                                $this->gerarJsons($fl_device, $fl_firstSync, $user);

                                $time = new \DateTime();
                                $time->format('H:i:s \O\n Y-m-d');
                                $newDev = new TbDevice();
                                $newDev->setDsHash($ds_hash);
                                $newDev->setIdUser($user);
                                $newDev->setTpDevice($tp_device);
                                $newDev->setDtFirstLogin($time);

                                $this->em->persist($newDev);
                                $this->em->flush();
                            } else {
                                $device = $this->getDoctrine()
                                        ->getRepository('AppBundle:TbDevice')
                                        ->findOneBy(array('idUser' => $id, 'dsHash' => NULL, 'tpDevice' => NULL));

                                $this->logControle->log(" device : " . print_r($device, true));

                                if ($device) {
                                    //algo em branco
                                    $idDevice = $device->getIdDevice();
                                    $dtfirstlogin = date('Y-m-d H:i:s');
                                    $this->atualizarDispositivoBranco($ds_hash, $idDevice, $dtfirstlogin);
                                    $fl_device = "S";
                                    $this->gerarJsons($fl_device, $fl_firstSync, $user);
                                } else {
                                    // NÃO TEM NADA EM BRANCO PRA ELE

                                    $device = $this->getDoctrine()
                                            ->getRepository('AppBundle:TbDevice')
                                            ->findOneBy(array('idUser' => $user->getIdUser(), 'dsHash' => $ds_hash, 'dtLogout' => null));

                                    $iduser = $user->getIdUser();

                                    $queryBuilder = $this->em->createQueryBuilder();
                                    $queryBuilder
                                            ->select('d,u')
                                            ->from('AppBundle:TbDevice', 'd')
                                            ->innerJoin('d.idUser', 'u')
                                            ->where($queryBuilder->expr()->eq('d.idUser', $iduser))
                                            ->andWhere($queryBuilder->expr()->isNull('d.dtLogout'))
                                            ->getQuery()
                                            ->execute();

                                    $result = $queryBuilder->getQuery()->getResult(\Doctrine\ORM\Query::HYDRATE_ARRAY);

                                    if ($result) {
                                        //segundo device
                                        $fl_device = "N";
                                        $this->gerarJsons($fl_device, $fl_firstSync, $user);
                                        $time = new \DateTime();
                                        $time->format('H:i:s \O\n Y-m-d');
                                        $newDev = new TbDevice();
                                        $newDev->setDsHash($ds_hash);
                                        $newDev->setIdUser($user);
                                        $newDev->setTpDevice($tp_device);
                                        $newDev->setDtFirstLogin($time);
                                        $this->em->persist($newDev);
                                        $this->em->flush();
                                    } else {
                                        $fl_device = "S";
                                        $this->gerarJsons($fl_device, $fl_firstSync, $user);
                                        $time = new \DateTime();
                                        $time->format('H:i:s \O\n Y-m-d');
                                        $newDev = new TbDevice();
                                        $newDev->setDsHash($ds_hash);
                                        $newDev->setIdUser($user);
                                        $newDev->setTpDevice($tp_device);
                                        $newDev->setDtFirstLogin($time);

                                        $this->em->persist($newDev);
                                        $this->em->flush();
                                    }
                                }
                            }
                        }
                    } else {
                        $this->logControle->log('Usuario nao localizado');
                        $flag = 5;
                        $this->error[] = $this->addError($flag);
                    }
                } else {
                    $this->logControle->log(" ----  OCORREU UM ERRO NO BANCO --- " . pg_last_error($this->db));
                    $this->logControle->log('campos vazios');
                    $flag = 2;
                    $this->error[] = $this->addError($flag);
                }
            } else {
                $this->logControle->log(" ----  OCORREU UM ERRO NO BANCO --- " . pg_last_error($this->db));
                $flag = 3;
                $this->error[] = $this->addError($flag);
            }
        } else {
            $this->logControle->log(" ----  FORMATO NÃO É JSON --- ");
            $flag = 4;
            $this->error[] = $this->addError($flag);
        }

        if (!empty($this->error)) {
            $this->response['firstLogin']['error'] = $this->error;
        } else {
            $this->response = $this->results;
        }
        $this->logControle->log("RESPONSE FIRSTLOGIN " . print_r($this->response, true));
        $this->logControle->log("FIM");
        $this->logControle->log("==============================================================================");
        return new JsonResponse($this->response);
    }

}
