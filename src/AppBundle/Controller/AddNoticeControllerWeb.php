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
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Description of addNoticeController
 *
 * @author Marilia
 */
class AddNoticeControllerWeb extends Controller {

    public $em;
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

    public function addNoticeWeb($co_id_table, $co_id_table_srv, $id_activity_student, $nm_table, $iduser) {
        $erro = array();
        $this->logControle->logWeb("AddNoticeControllerWeb::addNoticeWeb \n Adicionando notificação para a tabela " . $nm_table . "");

        $sql_view = "SELECT DISTINCT
                        id_user
                    FROM
                        vw_device
                    WHERE
                        id_user NOT IN ($iduser)
                        and id_activity_student =" . $id_activity_student . " ";
        error_reporting(0);
        $result = pg_query($this->logControle->db, $sql_view);
        if (!$result) {
            $this->logControle->logWeb(" ----  OCORREU UM ERRO NO BANCO --- " . $sql_view . " \nERRO: " . pg_last_error($this->logControle->db));
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

            $this->logControle->logWeb("---------------------- fim adição notificacao web ----------------------------");
        }
    }

    /**
     * @Route("/readNoticeComments")
     */
    function readNoticeComments(Request $req) {
        $this->logControle->logWeb("AddNoticeControllerWeb::readNoticeComments");
        date_default_timezone_set('UTC');
        $this->em = $this->getDoctrine()->getEntityManager();
        $idVersionActivity = $_POST['idVersionActivity'];
        $idActivity = $_POST['atividade'];
        $this->logControle->logWeb("Lendo notificações para atividade : " . $idActivity . " e versao " . $idVersionActivity);
        $queryBuilderCV = $this->em->createQueryBuilder();
        $queryBuilderCV
                ->select('c')
                ->from('AppBundle:TbComment', 'c')
                ->innerJoin('c.idCommentVersion', 'cv', 'WITH', 'c.idCommentVersion = cv.idCommentVersion')
                ->where($queryBuilderCV->expr()->eq('c.tpComment', $queryBuilderCV->expr()->literal("O")))
                ->andWhere($queryBuilderCV->expr()->eq('cv.idVersionActivity', "" . $idVersionActivity . ""))
                ->andWhere($queryBuilderCV->expr()->eq('c.idActivityStudent', "" . $idActivity . ""))
                ->getQuery()
                ->execute();
        $retorno = $queryBuilderCV->getQuery()->getArrayResult();

        $total = count($retorno);
        if ($total > 0) {
            foreach ($retorno as $value) {
                $hora = date("Y-m-d  H:i:s");
                $queryBuilderCom = $this->em->createQueryBuilder();
                $queryBuilderCom
                        ->update('AppBundle:TbNotice', 'n')
                        ->set('n.dtRead', $queryBuilderCom->expr()->literal($hora))
                        ->where($queryBuilderCom->expr()->eq('n.coIdTableSrv', $value['idComment']))
                        ->getQuery()
                        ->execute();
            }
        }
        $this->logControle->logWeb("---------------------- fim leitura de notificacao comments ----------------------------");
        return new JsonResponse($req);
    }

    /**
     * @Route("/readNoticeActivity")
     */
    function readNoticeActivity(Request $req) {
        $this->logControle->logWeb("AddNoticeControllerWeb::readNoticeActivity");
        $this->em = $this->getDoctrine()->getEntityManager();
        date_default_timezone_set('UTC');

        $data = json_decode($req->getContent(), true);
        $req->request->replace(is_array($data) ? $data : array());
        $this->logControle->logWeb("REQUEST readNoticeActivity : " . print_r($data, true));
        $idActivity = $data['atividade'];
        $hora = date("Y-m-d  H:i:s");

        $queryBuilderCom = $this->em->createQueryBuilder();
        $queryBuilderCom
                ->update('AppBundle:TbNotice', 'n')
                ->set('n.dtRead', $queryBuilderCom->expr()->literal($hora))
                ->where($queryBuilderCom->expr()->eq('n.idActivityStudent', $idActivity))
                ->getQuery()
                ->execute();
        $this->logControle->logWeb("---------------------- fim leitura de notificacao activity ----------------------------");
        return new JsonResponse($req);
    }

}
