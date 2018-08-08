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
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;

/**
 * Description of policyController
 *
 * @author Zago
 */
class policyController extends Controller {

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
     * @Route("/policy")
     */
    public function policyController(Request $req) {
        $this->logControle->log('INICIO policyController');

        $this->response = NULL;
        $this->error = NULL;
        $this->results = NULL;

        if (0 === strpos($req->headers->get('Content-Type'), 'application/json')) {
            $data = json_decode($req->getContent(), true);
            $req->request->replace(is_array($data) ? $data : array());

            if ((!empty($data)) && (!empty($data['policyRequest']))) {
                $this->logControle->log(print_r($data, true));

                $data = $data['policyRequest'];

                if ((!empty($data['id_policy_user'])) && (!empty($data['id_user'])) && (!empty($data['fl_accept']))) {
                    $idPolicyUser = $data['id_policy_user'];
                    $iduser = $data['id_user'];
                    $flAccept = $data['fl_accept'];
                    $em = $this->getDoctrine()->getEntityManager();
                    $queryBuilder = $em->createQueryBuilder();
                    $queryBuilder
                            ->update('AppBundle:TbPolicyUser', 'pu')
                            ->set('pu.flAccept', $queryBuilder->expr()->literal($flAccept))
                            ->where($queryBuilder->expr()->eq('pu.idPolicyUser', $idPolicyUser))
                            ->andWhere($queryBuilder->expr()->eq('pu.idUser', $iduser))
                            ->getQuery()
                            ->execute();

                    $this->logControle->log($queryBuilder);
                    if ($queryBuilder) {
                        $results["policyRequest"] = array(
                            'success' => true
                        );
                    } else {
                        $this->logControle->log('Campos vazios!');
                        $flag = 1;
                        $this->error[] = $this->addError($flag);
                        $results["policyRequest"] = array(
                            'error' => $this->error
                        );
                    }
                }
            }
        }
        $this->response = $results;
        $this->logControle->log(print_r($this->response, true));
        $this->logControle->log("FIM");
        $this->logControle->log("==============================================================================");
        return new JsonResponse($this->response);
    }

}
