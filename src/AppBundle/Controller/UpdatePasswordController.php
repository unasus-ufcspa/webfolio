<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use AppBundle\Entity\TbNotice;
use AppBundle\Entity\TbNoticeDevice;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

/**
 * Description of UpdatePasswordController
 *
 * @author Marilia
 */
class UpdatePasswordController extends Controller {

  public $logControle ;

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
     * @Route("/password")
     */
    public function password(Request $req) {
        $this->logControle->log('INICIO UpdatePassword');
        if (0 === strpos($req->headers->get('Content-Type'), 'application/json')) {
            $data = json_decode($req->getContent(), true);
            $req->request->replace(is_array($data) ? $data : array());

            if ((!empty($data)) && (!empty($data['password_request']))) {
                $this->logControle->log(print_r($data, true));

                $data = $data['password_request'];

                if ((!empty($data['id_user'])) && (!empty($data['old_password'])) && (!empty($data['new_password']))) {
                    $this->id_user = $data['id_user'];


                    $sql_user = "select 
                                    ds_password
                                 from 
                                      tb_user 
                                 where 
                                      id_user = $this->id_user ";

                    $this->logControle->log("VERIF USUARIO: " . $sql_user);
                    error_reporting(0);
                    $verif_user = pg_query($this->logControle->db, $sql_user);

                    if (!$verif_user) {
                        $flag = 1;
                        $this->error[] = $this->addError($flag);
                        $resp[] = array(
                            "error" => $this->error);
                    } else {
                        if (pg_affected_rows($verif_user) > 0) {
                            $old_pswd = pg_fetch_array($verif_user);
                            $this->logControle->log("OLD PASS : " . $old_pswd[0]);

                            if ($old_pswd[0] == $data['old_password']) {
                                $update_pswd = "update tb_user 
                                                set ds_password ='" . $data['new_password'] . "' 
                                                where  id_user =" . $this->id_user;

                                error_reporting(0);
                                $result = pg_query($this->logControle->db, $update_pswd);

                                $this->logControle->log("RESULT : " . print_r($update_pswd, true));
                                if ($result) {

                                    $resp[] = array(
                                        "success" => "Senha atualizada!"
                                    );
                                } else {
                                    $flag = 1;
                                    $this->error[] = $this->addError($flag);
                                    $resp[] = array(
                                        "error" => $this->error);
                                }
                            } else {
                                $resp[] = array(
                                    "error" => "Senhas nÃ£o conferem!"
                                );
                            }
                        } else {
                            $flag = 10;
                            $this->error[] = $this->addError($flag);
                            $resp[] = array(
                                "error" => $this->error);
                        }
                    }
                }
            }
        }
        $this->logControle->log("RESP : " . print_r($resp, true));
        $this->response["password_response"][] = $resp;
        return new JsonResponse($this->response);
    }

}
