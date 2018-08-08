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
use AppBundle\Entity\TbNotice;
use AppBundle\Entity\TbNoticeDevice;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use AppBundle\Controller\IdDeviceSeqController;

/**
 * Description of FindNoticeController
 *
 * @author Marilia
 */
class FindNoticeController extends Controller {

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

    /**
     * @Route("/findNotice")
     */
    public function findNotice($user, $ds_hash) {
        $totalItens = 0;
        $this->em = $this->getDoctrine()->getEntityManager();

        $this->logControle->log("FIND NOTICE");

        $id_device = (idDeviceSeqController::getIdDeviceSeq($ds_hash, $user));
        // $this->logControle->log("id dev" . $id_device);

        if ($id_device > 0) {
            $queryBuilder = $this->em->createQueryBuilder();
            $qb2 = $queryBuilder;
            $qb2
                    ->select('nu')
                    ->from('AppBundle:TbNoticeDevice', 'nu')
                    ->innerJoin('nu.idNotice', 'ut', 'WITH', 'ut.idNotice = nu.idNotice')
                    ->where($queryBuilder->expr()->eq('nu.idDevice', $id_device));


            $queryBuilder = $this->em->createQueryBuilder();
            $queryBuilder
                    ->select('n,u, us, ac')
                    ->from('AppBundle:TbNotice', 'n')
                    ->innerJoin('n.idDestination', 'u', 'WITH', 'u.idUser = n.idDestination')
                    ->innerJoin('n.idAuthor', 'us', 'WITH', 'us.idUser = n.idAuthor')
                    ->innerJoin('n.idActivityStudent', 'ac', 'WITH', 'ac.idActivityStudent = n.idActivityStudent')
                    ->Where($queryBuilder->expr()->eq('n.idDestination', $user))
                    ->andWhere($queryBuilder->expr()->isNull('n.dtRead'))
                    ->andWhere($queryBuilder->expr()->notIn('n.idNotice', $qb2->getDQL()))
                    ->getQuery()
                    ->execute();



            // $this->logControle->log($queryBuilder);
            $results = $queryBuilder->getQuery()->getArrayResult();
            // $this->logControle->log("TB GET NOTICE : " . print_r($results, true));


            $totalItens = count($results);
            // $this->logControle->log(" itens total : " . $totalItens);

            if ($totalItens > 0) {
                foreach ($results as $row) {
                    if (empty($row['dtNotice'])) {
                        $dtNotice = null;
                    } else {
                        $dtNotice = $row['dtNotice']->format('Y-m-d H:i:s');
                    }
                    $result[] = array(
                        'id_notice' => (string) $row['idNotice'],
                        'id_author' => (string) $row['idAuthor']['idUser'],
                        'id_destination' => (string) $row['idDestination']['idUser'],
                        'id_activity_student' => (string) $row['idActivityStudent']['idActivityStudent'],
                        'nm_table' => $row['nmTable'],
                        'co_id_table_srv' => (string) $row['coIdTableSrv'],
                        'dt_notice' => $dtNotice
                    );

                    $time = new \DateTime();
                    $time->format('H:i:s \O\n Y-m-d');

                    $newDev = new TbNoticeDevice();
                    $idnotice = $row['idNotice'];

                    $id = $this->getDoctrine()
                            ->getRepository('AppBundle:TbNotice')
                            ->findOneBy(array('idNotice' => $idnotice));
                    $newDev->setIdNotice($id);

                    $iddev = $this->getDoctrine()
                            ->getRepository('AppBundle:TbDevice')
                            ->findOneBy(array('idDevice' => $id_device));

                    $newDev->setIdDevice($iddev);


                    $this->em->persist($newDev);
                    $this->em->flush();

                    $dtread = date('Y-m-d H:i:s');
                    $queryBuilder = $this->em->createQueryBuilder();
                    $queryBuilder
                            ->update('AppBundle:TbNotice', 'n')
                            ->set('n.dtRead', $queryBuilder->expr()->literal($dtread))
                            ->where($queryBuilder->expr()->eq('n.idNotice', $idnotice))
                            ->getQuery()
                            ->execute();
                    //   $this->logControle->log($queryBuilder);
                }
            } else {
                $this->logControle->log("DENTRO DO ERROOOO");
                $flag = 7;
                $this->error[] = $this->addError($flag);
                $result['error'] = $this->error;
            }
        } else {
            $this->logControle->log("DENTRO DO ERROOOO");
            $flag = 1;
            $this->error[] = $this->addError($flag);
            $result['error'] = $this->error;
        }
        return $result;
    }

}
