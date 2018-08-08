<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

/**
 * Description of idDeviceSeqController
 *
 * @author Marilia
 */
class IdDeviceSeqController extends Controller {

    public $em;

    public $logControle;
      

    public function __construct() {
       $this->logControle= new LogController();
    }
    public function getIdDeviceSeq($ds_hash, $id_user) {
        $totalItens = 0;
        $this->em = $this->getDoctrine()->getEntityManager();
        // $this->logControle->log("AQUI DENTRO DO GET ID");

        $queryBuilder = $this->em->createQueryBuilder();
        $queryBuilder
                ->select('u,d')
                ->from('AppBundle:TbDevice', 'd')
                ->innerJoin('d.idUser', 'u', 'WITH', 'd.idUser =u.idUser')
                ->Where($queryBuilder->expr()->eq('d.idUser', $id_user))
                ->andWhere($queryBuilder->expr()->eq('d.dsHash', "'" . $ds_hash . "'"))
                ->andWhere($queryBuilder->expr()->isNull('d.dtLogout'))
                ->getQuery()
                ->execute();



        //$this->logControle->log($queryBuilder);
        $results = $queryBuilder->getQuery()->getArrayResult();
        $this->logControle->log("AQUI NO GET ID : " . print_r($results, true));
        $totalItens = count($results);


        if ($totalItens > 0) {
            $id_dev = $results[0]['idDevice'];
            //$this->logControle->log("AQUI DENTRODO IF");
            return $id_dev;
        } ELSE {
            return 0;
        }
    }

    //put your code here
}
