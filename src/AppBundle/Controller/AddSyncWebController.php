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
use AppBundle\Entity\TbDevice;
use AppBundle\Form\Type\TbActivityStudentType;
use AppBundle\Form\Type\TbUserType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use AppBundle\Controller\IdDeviceSeqController;

/**
 * Description of AddSyncController
 *
 * @author Marilia
 */
class AddSyncWebController extends Controller {

    public $logControle;
    public $em;

    public function __construct() {
        $this->logControleControle = new LogController();
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

    public function addSync($co_id_table, $iduser, $nm_table, $id_activity_student) {
        $this->logControle->logWeb("AddSyncWebController::addSync");
        $this->em = $this->getDoctrine()->getEntityManager();
        $author = $this->getDoctrine()
                ->getRepository('AppBundle:TbUser')
                ->findOneBy(array('idUser' => $iduser));
        $dtSync = new \DateTime();
        $dtSync->format('H:i:s \O\n Y-m-d');


        $activity = $this->getDoctrine()
                ->getRepository('AppBundle:TbActivityStudent')
                ->findOneBy(array('idActivityStudent' => $id_activity_student));
        if ($nm_table != 'tb_annotation') {
            $queryview = "select 
                        id_tutor, 
                        id_student
                     from
                        vw_activity
                     where
                        id_activity_student = $id_activity_student";

            $ret_view = pg_query($this->logControle->db, $queryview);

            while ($origem = pg_fetch_assoc($ret_view)) {
                $this->logControle->logWeb("Usuarios da activity para sincronismo:  " . print_r($origem, true));
                if ($origem['id_tutor'] == $iduser) {
                    $destino = $origem['id_student'];
                } else {
                    $destino = $origem['id_tutor'];
                }

                $objdestino = $this->getDoctrine()
                        ->getRepository('AppBundle:TbUser')
                        ->findOneBy(array('idUser' => $destino));

                $objSync = new TbSync();

                $objSync->setIdAuthor($author);
                $objSync->setIdDestination($objdestino);
                $objSync->setIdActivityStudent($activity);
                $objSync->setCoIdTable($co_id_table);
                $objSync->setNmTable($nm_table);
                $objSync->setDtSync($dtSync);

                $this->em->persist($objSync);
                
                $idSyncSrv = $objSync->getIdSync();
                $this->em->flush();

                AddSyncWebController::addSyncDeviceDestino($destino, $idSyncSrv);
            }
        } else {
            $objSync = new TbSync();

            $objSync->setIdAuthor($author);
            $objSync->setIdDestination($author);
            $objSync->setIdActivityStudent($activity);
            $objSync->setCoIdTable($co_id_table);
            $objSync->setNmTable($nm_table);
            $objSync->setDtSync($dtSync);

            $this->em->persist($objSync);
            $idSyncSrv = $objSync->getIdSync();
            $this->em->flush();
            AddSyncWebController::addSyncDeviceAuthor($iduser, $idSyncSrv);
        }
        $this->logControle->logWeb("---------------------- fim adição sincronismo web ----------------------------");
    }

    public function addSyncDeviceDestino($destino, $idSyncSrv) {
        $this->em = $this->getDoctrine()->getEntityManager();
        $this->logControle->logWeb("AddSyncWebController::addSyncDeviceDestino");
        $totalItens = 0;
        $queryBuilder = $this->em->createQueryBuilder();
        $queryBuilder
                ->select('d, u')
                ->from('AppBundle:TbDevice', 'd')
                ->innerJoin('d.idUser', 'u', 'WITH', 'u.idUser = d.idUser')
                ->Where($queryBuilder->expr()->eq('d.idUser', $destino))
                ->andWhere($queryBuilder->expr()->isNull('d.dtLogout'))
                ->getQuery()
                ->execute();


        $results = $queryBuilder->getQuery()->getArrayResult();

        $totalItens = count($results);

        if ($totalItens == 0) {

            $newdev = new TbDevice();
            $destinoObj = $this->getDoctrine()
                    ->getRepository('AppBundle:TbUser')
                    ->findOneBy(array('idUser' => $destino));

            $newdev->setIdUser($destinoObj);

            $this->em->persist($newdev);
            $id_device = $newdev->getIdDevice();
            $this->em->flush();

            $objTbSync = new TbSyncDevice();

            $objIdDevice = $this->getDoctrine()
                    ->getRepository('AppBundle:TbDevice')
                    ->findOneBy(array('idDevice' => $id_device));

            $objTbSync->setIdDevice($objIdDevice);
            $objSync = $this->getDoctrine()
                    ->getRepository('AppBundle:TbSync')
                    ->findOneBy(array('idSync' => $idSyncSrv));
            $objTbSync->setIdSync($objSync);
            $objTbSync->setTpSync('R');

            $this->em->persist($objTbSync);
            $this->em->flush();
        } else {

            foreach ($results as $devices) {
                $id_device = $devices['idDevice'];

                $objTbSync = new TbSyncDevice();

                $objIdDevice = $this->getDoctrine()
                        ->getRepository('AppBundle:TbDevice')
                        ->findOneBy(array('idDevice' => $id_device));


                $objTbSync->setIdDevice($objIdDevice);

                $objSync = $this->getDoctrine()
                        ->getRepository('AppBundle:TbSync')
                        ->findOneBy(array('idSync' => $idSyncSrv));


                $objTbSync->setIdSync($objSync);
                $objTbSync->setTpSync('R');

                $this->em->persist($objTbSync);
                $this->em->flush();
            }
        }
        $this->logControle->logWeb("---------------------- fim adição sincronismo dispositivos destino ----------------------------");
    }

    public function addSyncDeviceAuthor($iduser, $idSyncSrv) {
        $this->em = $this->getDoctrine()->getEntityManager();
        $this->logControle->logWeb("AddSyncWebController::addSyncDeviceAuthor");
        $totalItens = 0;
        $queryBuilder = $this->em->createQueryBuilder();
        $queryBuilder
                ->select('d, u')
                ->from('AppBundle:TbDevice', 'd')
                ->innerJoin('d.idUser', 'u', 'WITH', 'u.idUser = d.idUser')
                ->Where($queryBuilder->expr()->eq('d.idUser', $iduser))
                ->andWhere($queryBuilder->expr()->isNull('d.dtLogout'))
                ->getQuery()
                ->execute();


        $results = $queryBuilder->getQuery()->getArrayResult();

        $totalItens = count($results);


        if ($totalItens > 0) {

            foreach ($results as $devices) {
                $id_device = $devices['idDevice'];

                $objTbSync = new TbSyncDevice();

                $objIdDevice = $this->getDoctrine()
                        ->getRepository('AppBundle:TbDevice')
                        ->findOneBy(array('idDevice' => $id_device));


                $objTbSync->setIdDevice($objIdDevice);

                $objSync = $this->getDoctrine()
                        ->getRepository('AppBundle:TbSync')
                        ->findOneBy(array('idSync' => $idSyncSrv));

                $objTbSync->setIdSync($objSync);

                $objTbSync->setTpSync('R');

                $this->em->persist($objTbSync);
                $this->em->flush();
            }
        }
        $this->logControle->logWeb("---------------------- fim adição sincronismo dispositivos autor web ----------------------------");
    }

    public function updateUserDevSrv($iduser) {
        $this->em = $this->getDoctrine()->getEntityManager();
        $this->logControle->log("AddSyncWebController::updateUserDevSrv");

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

        error_reporting(0);

        $ret_view = pg_query($this->logControle->db, $sql_view);

        error_reporting(0);
        if (!$ret_view) {
            $this->logControle->logWeb(" ----  OCORREU UM ERRO NO BANCO --- \n ERRO: " . pg_last_error($this->logControle->db));

            $flag = 1;
            $this->error[] = $this->addError($flag);
            $resp['error'] = $this->error;
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


                    $results = $queryBuilder->getQuery()->getArrayResult();

                    foreach ($results as $devices) {

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

                        AddSyncWebController::addSyncDeviceDestino($idDestino, $idsync);

                        AddSyncWebController::addSyncDeviceAuthor($iduser, $idsync);
                    }
                }
            }
        }
        $this->logControle->logWeb("---------------------- fim adição sincronismo updateUserDevSrv ----------------------------");
        return $resp;
    }

}
