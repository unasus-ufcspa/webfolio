<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace AppBundle\Controller;
use Swift_SmtpTransport;
use Swift_Mailer;
use Swift_Message;
use Swift;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use AppBundle\Entity\TbComment;
use AppBundle\Entity\TbCommentVersion;
use Symfony\Component\HttpFoundation\Response;
use AppBundle\Entity\TbUser;
use AppBundle\Form\Type\TbActivityStudentType;
use AppBundle\Form\Type\TbUserType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

header('Content-Type: text/html; charset=utf-8');

/**
 * Description of AtividadesController
 *
 * @author Marilia
 */
class LoginController extends Controller {

    private $em;
    public $logControle;

    public function __construct() {
        $this->logControle = new LogController();
    }

    /**
     * @Route("/")
     */
    public function raiz() {
        if ($this->get('session')->get('idUser')) {

            return $this->redirectToRoute('portfolios');
        } else {
            return $this->redirectToRoute('login');
        }
    }

    /**
     * @Route("/login")
     */
    public function login(Request $req) {
        $this->em = $this->getDoctrine()->getManager();

        if ($this->get('session')->get('idUser')) {

            return $this->redirectToRoute('portfolios');
        } else {
            $user = new TbUser();

            $form = $this->createFormBuilder($user)
                    ->add('DsEmail', TextType::class, array('label' => false))
                    ->add('DsPassword', PasswordType::class, array('label' => false))
                    ->add('save', SubmitType::class, array('label' => false))
                    ->getForm();

            $form->handleRequest($req);

            if ($form->isSubmitted() && $form->isValid()) {

                if (!$this->verif($user->getDsEmail(), $user->getDsPassword())) {
                    return $this->render('login.html.twig', array(
                                'form' => $form->createView(),
                    ));
                } else {
                    return $this->redirectToRoute('portfolios');
                }
            }
            return $this->render('login.html.twig', array(
                        'form' => $form->createView(),
            ));
        }
    }

  
    public function verif($email, $senha) {
        $this->em = $this->getDoctrine()->getEntityManager();
        $senha = hash('sha256', $senha);
        $usuario = $this->getDoctrine()
                ->getRepository('AppBundle:TbUser')
                ->findBy(array('dsEmail' => $email, 'dsPassword' => $senha));


        if ($usuario) {
            $this->logControle->log("SELECT : " . print_r($usuario, true));
            $this->logControle->log("id user: " . $usuario[0]->getIdUser());
            $idUser = $usuario[0]->getIdUser();
            $this->logControle->log("id user" . $idUser);

            $session = new Session();
            $session->set('idUser', $idUser);
            $session->get('idUser');
            $this->logControle->log($session->get('idUser'));

            // $this->get('session')->set('idUser', $idUser);

            return true;
        } else {
            echo"<script type='text/javascript'>";

            echo "alert(' ------ Usuário ou senha incorretos -------');";

            echo "</script>";
            return false;
        }


    }
    

}
