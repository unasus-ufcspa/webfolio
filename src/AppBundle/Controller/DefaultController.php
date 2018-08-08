<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends Controller {

    /**
     * @Route("/", name="homepage")
     */
    public function indexAction(Request $request) {

        if ($this->get('session')->get('idUser')) {

            return $this->redirectToRoute('portfolios');
        } else {
            return $this->redirectToRoute('login');
        }
    }

}
