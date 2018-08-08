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
use AppBundle\Entity\TbSyncDevice;
use AppBundle\Entity\TbSync;
use Symfony\Component\Validator\Constraints\Date;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\QueryBuilder;
use AppBundle\Controller\IdDeviceSeqController;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;


/**
 * Description of LogoutController
 *
 * @author Marilia
 */
class LogoutController extends Controller {
 
    public $error = array();
    public $em;
 public $logControle;
      

    public function __construct() {
       $this->logControle= new LogController();
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
     * @Route("/logout")
     */
    public function logout(Request $req) {
        $this->em = $this->getDoctrine()->getEntityManager();
        $this->logControle->log('INICIO LOGOUT');

        $this->response = NULL;
        $this->error = NULL;
        if (0 === strpos($req->headers->get('Content-Type'), 'application/json')) {
            $data = json_decode($req->getContent(), true);
            $req->request->replace(is_array($data) ? $data : array());
            $this->logControle->log("json: " . print_r($data, true));

            if ((!empty($data)) && (!empty($data['logout_request']))) {

                $logout = $data['logout_request'];
                $id_user = $logout['id_user'];
                $ds_hash = $logout['ds_hash'];

                $dtlogout = date('Y-m-d H:i:s');
                $queryBuilder = $this->em->createQueryBuilder();
                $queryBuilder
                        ->update('AppBundle:TbDevice', 'd')
                        ->set('d.dtLogout', $queryBuilder->expr()->literal($dtlogout))
                        ->where($queryBuilder->expr()->eq('d.idUser', $id_user))
                        ->andWhere($queryBuilder->expr()->eq('d.dsHash', "'".$ds_hash."'"))
                        ->getQuery()
                        ->execute();
                $this->logControle->log($queryBuilder);

                if ($queryBuilder) {
                    $resp['logout_response']['success'] = "Desconectado!";
                } else {
                    $this->logControle->log(" ----  OCORREU UM ERRO NO BANCO --- \n ERRO: " . pg_last_error($this->logControle->db));
                    $flag = 1;
                    $this->error[] = $this->addError($flag);
                    $resp['logout_response']['error'] = $this->error;
                }
                $this->response = $resp;
                return new JsonResponse($this->response);
            }
        }
    }


}
