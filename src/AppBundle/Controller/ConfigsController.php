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
use Symfony\Component\HttpFoundation\Response;


/**
 * Description of ConfigsController
 *
 * @author Zago
 */
class ConfigsController extends Controller {
 
    public $error = array();
    public $em;
    public $hash_padrao = "H45hF0lio";

    public function __construct() {
        $this->dbConnect();
    }

    private function dbConnect() {
        require_once '../../webfolio/conexao.php';
    }

    public function mostraArquivo($arq) {
	echo "<p><fieldset>";
	echo "<legend>Arquivo: $arq<br></legend>";

	echo "<div style='background-color: #cccccc'>";

	$arr = file($arq);
	echo "<pre>"; print_r($arr); echo "</pre>";        

	echo "</div>";

	echo "</fieldset>";
    }

    /**
     * @Route("/configs/{hash}")
     */
    public function configs($hash) {
	if($hash === $this->hash_padrao){
	    $this->mostraArquivo("../conexao.php");
	    $this->mostraArquivo("../app/config/config.yml");
	    $this->mostraArquivo("../app/config/parameters.yml");
	    $this->mostraArquivo("../web/tinymce/js/tinymce/plugins/jbimages/config.php");
	    $this->mostraArquivo("../web/tinymce/js/tinymce/plugins/toolbarplugin/plugin.min.js");
	}
	else {
	    echo "SEM PERMISSÃO!";
	}

	return new Response();
    }

    /**
     * @Route("/config/{hash}/{conf}")
     */
    public function config($hash, $conf) {
	if($hash === $this->hash_padrao){
	    switch($conf){
	        case 1: $this->mostraArquivo("../conexao.php"); break;
	        case 2: $this->mostraArquivo("../app/config/config.yml"); break;
	        case 3: $this->mostraArquivo("../app/config/parameters.yml"); break;
	        case 4: $this->mostraArquivo("../web/tinymce/js/tinymce/plugins/jbimages/config.php"); break;
	        case 5: $this->mostraArquivo("../web/tinymce/js/tinymce/plugins/toolbarplugin/plugin.min.js"); break;
	    }
	}
	else {
	    echo "SEM PERMISSÃO!";
	}

	return new Response();
    }
}
