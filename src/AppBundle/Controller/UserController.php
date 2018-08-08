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

/**
 * Description of UserController
 *
 * @author Marilia
 */
class UserController extends Controller {

  public $logControle;
  

    public function __construct() {
        $this->logControle = new LogController();
    }


    public function selecionarFotoUsuario($idUser) {
        $select = "SELECT 
                                encode(im_photo::bytea, 'escape') as photo 
                            FROM 
                                tb_user
                            WHERE
                                id_user = " . $idUser;

       $this->logControle->log("selecct user: " . $select);
        $resultado = pg_query($this->logControle->db, $select);
        if (pg_affected_rows($resultado) > 0) {
            while ($row = pg_fetch_assoc($resultado)) {
                $photo = $row['photo'];
            }
        } else {
            $photo = null;
        }
        return $photo;
    }

}
