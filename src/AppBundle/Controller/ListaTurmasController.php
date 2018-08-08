<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Response;

class ListaTurmasController extends Controller {
    /**
     * @Route("/listaTurmas")
     */
    public function listaTurmas() {
        $turmas = $this->getDoctrine()->getRepository('AppBundle:TbClass');
        
        $todasTurmas = $turmas->findAll();
        
        return $this->render('listaTurmas.html.twig', array('response' => $todasTurmas));
    }
}
