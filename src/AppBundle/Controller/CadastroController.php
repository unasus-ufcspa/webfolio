<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace AppBundle\Controller;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;


/**
 * Description of CadastroController
 *
 * @author Zago
 */
class CadastroController extends Controller {

    //put your code here

//    public function __construct() {
//        $this->logControle->dbConnect();
//    }

    public $logControle;
      

    public function __construct() {
       $this->logControle= new LogController();
    }

    public function newActivityStudent(Request $req) {
        $id_user = $_POST['student'];
        $id_tutor = $_POST['tutor'];
        $id_portfolio = $_POST['portfolio'];
        $id_class = $_POST['turma'];
        $jatem = "select 
                    id_portfolio_class
                  from 
                    tb_portfolio_class 
                  where id_class = $id_class and id_portfolio = $id_portfolio"; //verifica se a relação portfolio e turma ja existe
        
        $this->logControle->log("JATEM : " . $jatem);
        $ja = pg_query($this->logControle->db, $jatem);
        

        if (pg_affected_rows($ja)>0) {
            $id = pg_fetch_array($ja);
        } else {
            $insert_tbportfclass = "INSERT INTO tb_portfolio_class(
                                     id_class, id_portfolio)
                                     VALUES ($id_class, $id_portfolio) returning id_portfolio_class;"; //cria nova relação


            $result = pg_query($this->logControle->db, $insert_tbportfclass);
            $id = pg_fetch_array($result);
        }
        $insert_ptstudent = "INSERT INTO tb_portfolio_student(
                            id_portfolio_class, id_student, id_tutor)
                           VALUES ($id[0], $id_user, $id_tutor)returning id_portfolio_student;"; //cria novo portfolio 

        $result2 = pg_query($this->logControle->db, $insert_ptstudent);
        $id_ptest = pg_fetch_array($result2);

        $select_activity = "select 
                                id_activity
                            from 
                                tb_activity 
                            where 
                                id_portfolio= $id_portfolio"; //seleciona a atividade do porfolio
        $resp = pg_query($this->logControle->db, $select_activity);

        while ($rowsel = pg_fetch_assoc($resp)) {

            $insert_actstu = "INSERT INTO tb_activity_student(
                            id_portfolio_student, id_activity)
                            VALUES ($id_ptest[0], '" . $rowsel['id_activity'] . "' );";
            $this->logControle->log(" insert_actstu " . $insert_actstu);
            pg_query($this->logControle->db, $insert_actstu);
            echo "FOI";
        }
        return new Response();
    }

       /**
     * @Route("/addPortfolio")
     */
    
    function addPortfolio(){
         return $this->render("addPortfolio.html.twig");
    }
    
       /**
     * @Route("/carregaPortfolios")
     */
    
    function carregaPortfolios(){
         return $this->render("carregaPortfolios.html.twig");
    }
    
      /**
     * @Route("/teste")
     */
    
    function teste(){
         return $this->render("carregaPortfolios.html.twig");
    }
    
}
