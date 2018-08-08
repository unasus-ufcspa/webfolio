<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class LogsController extends Controller {
    /**
     * @Route("/showLog/{cl}")
     */
    public function showLog($cl) {
        
        $hoje = date("Y_m_d");
        $arq = "../var/logs/log_WEB.$hoje.log";

        if(!file_exists($arq)){
            die("$arq não encontrado.");
        }
        
        if($cl == 1){
            unlink($arq);
            echo "$arq removido com sucesso.";
        }
        else {
            $arquivo = fopen($arq, "r");

            $log = "";
            while(!feof ($arquivo)) {
                $linha = fgets($arquivo, 4096);
                $log.= $linha;
            }

            echo "<pre>";
            print_r($log);
            echo "</pre>";

            fclose($arquivo);
        }
        
        return new Response();
    }
     /**
     * @Route("/showLogEscolha/{cl}")
     */
    public function showLogEscolha($cl) {
        
        $hoje = date("Y_m_d");
        $arq = "../var/logs/$cl.$hoje.log";

        if(!file_exists($arq)){
            die("$arq não encontrado.");
        }
        
        if($cl == 1){
            unlink($arq);
            echo "$arq removido com sucesso.";
        }
        else {
            $arquivo = fopen($arq, "r");

            $log = "";
            while(!feof ($arquivo)) {
                $linha = fgets($arquivo, 4096);
                $log.= $linha;
            }

            echo "<pre>";
            print_r($log);
            echo "</pre>";

            fclose($arquivo);
        }
        
        return new Response();
    }
}
