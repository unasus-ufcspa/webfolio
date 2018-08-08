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
use AppBundle\Entity\TbUser;
use AppBundle\Entity\TbSync;
use AppBundle\Entity\TbSyncDevice;
use AppBundle\Form\Type\TbActivityStudentType;
use AppBundle\Form\Type\TbUserType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use AppBundle\Entity\TbNotice;

/**
 * Description of addNoticeController
 *
 * @author Marilia
 */
class AddNoticeController extends Controller {

    public $logControle;

    public function __construct() {
        $this->logControle->logControle = new LogController();
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

    public function addNotice($co_id_table, $co_id_table_srv, $id_activity_student, $nm_table, $iduser, $ds_hash) {
        $erro = array();
        $this->logControle->log("AddNoticeController::AddNotice \n  Adicionando notificação para a tabela " . $nm_table . "");

        $id_device = (IdDeviceSeqController::getIdDeviceSeq($ds_hash, $iduser));
        $sql_view = "SELECT DISTINCT
                        id_user
                    FROM
                        vw_device
                    WHERE
                        id_device NOT IN('" . $id_device . "')
                        and id_user NOT IN ($iduser)
                        and id_activity_student =" . $id_activity_student . " ";
        error_reporting(0);
        $result = pg_query($this->logControle->db, $sql_view);
        if (!$result) {
            $this->logControle->log(" ----  OCORREU UM ERRO NO BANCO --- " . $sql_view . " \nERRO: " . pg_last_error($this->logControle->db));
            $flag = 1;
            $erro['error'] = $this->addError($flag);
        } else {
            if (pg_affected_rows($result) > 0) {
                while ($row = pg_fetch_assoc($result)) {

                    $dt_notice = date('Y-m-d H:i:s');
                    $objNotice = new TbNotice();

                    $author = $this->getDoctrine()
                            ->getRepository('AppBundle:TbUser')
                            ->findOneBy(array('idUser' => $iduser));

                    $destination = $this->getDoctrine()
                            ->getRepository('AppBundle:TbUser')
                            ->findOneBy(array('idUser' => $row['id_user']));

                    $idActivityStudent = $this->getDoctrine()
                            ->getRepository('AppBundle:TbActivityStudent')
                            ->findOneBy(array('idActivityStudent' => $id_activity_student));

                    $dtNotice = new \DateTime();
                    $dtNotice->format('H:i:s  Y-m-d');

                    $objNotice->setIdAuthor($author);
                    $objNotice->setIdDestination($destination);
                    $objNotice->setIdActivityStudent($idActivityStudent);
                    $objNotice->setNmTable($nm_table);
                    $objNotice->setCoIdTable($co_id_table);
                    $objNotice->setCoIdTableSrv($co_id_table_srv);
                    $objNotice->setDtNotice($dtNotice);

                    $this->em->persist($objNotice);
                    $this->em->flush();
                }
            }
            $this->logControle->log("---------------------- fim adição notificacao ----------------------------");
        }
    }

}
