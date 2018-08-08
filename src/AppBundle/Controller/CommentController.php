<?php

namespace AppBundle\Controller;

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

class CommentController extends Controller {

    private $em;

 public $logControle;
      

    public function __construct() {
       $this->logControle= new LogController();
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
     * @Route("/insComment")
     */
    public function insComment($idActStd, $idAuth, $txComment, $dtComment) {
        $this->em = $this->getDoctrine()->getEntityManager();
       
        $this->logControle->log("ID ATIVIDADE! <--------" . print_r($idActStd->getIdActivityStudent(), true));
        $this->logControle->log("id estudent! <--------" . print_r($idAuth->getIdUser(), true));
        $this->logControle->log("tx comment! <--------" . print_r($txComment, true));
        $this->logControle->log("data! <--------" . print_r($dtComment->getOffset(), true));

        $act = $this->getDoctrine()
                ->getRepository('AppBundle:TbActivityStudent')
                ->find($idActStd);

        $auth = $this->getDoctrine()
                ->getRepository('AppBundle:TbUser')
                ->find($idAuth);

        $this->logControle->log("SELECT : " . print_r($auth, true));
        $comment = new TbComment();


        $comment->setIdActivityStudent($act);
        $comment->setIdAuthor($auth);
        $comment->setTxComment($txComment);
        $comment->setDtComment($dtComment);


        $this->em->persist($comment);
        $this->em->flush();




        return true;
    }

    /**
     * @Route("/comment")
     */
    public function newComment(Request $req) {
        $comment = new TbComment();
        $this->em = $this->getDoctrine()->getEntityManager();
        $form = $this->createFormBuilder($comment)
                ->add('idActivityStudent', TbActivityStudentType::class)
                ->add('idAuthor', TbUserType::class)
                ->add('txComment', TextType::class, array('label' => 'Comentário'))
                ->add('dtComment', DateTimeType::class, array('label' => 'Data'))
                ->add('save', SubmitType::class, array('label' => 'Inserir feditocomentário'))
                ->getForm();

        $form->handleRequest($req);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->insComment($comment->getIdActivityStudent(), $comment->getIdAuthor(), $comment->getTxComment(), $comment->getDtComment());
        }

        return $this->render('comment.html.twig', array(
                    'form' => $form->createView(),
        ));
    }

   
    /**
     * @Route("/logoutWeb")
     */
    public function logoutWeb() {

        $this->get('session')->invalidate();
         return $this->redirectToRoute('login');
        
    }

   
    /**
     * @Route("/newActivityStudent")
     */
    public function newActivityStudent(Request $req) {
        $turmas = $this->turmas();
        $usuarios = $this->usuarios();
        $portfolio = $this->portfolio();

        $default = array('default' => '');
        $form = $this->createFormBuilder($default)
                ->add('Aluno', ChoiceType::class, [
                    'choices' => $usuarios,
                ])
                ->add('Tutor', ChoiceType::class, [
                    'choices' => $usuarios,
                ])
                ->add('TbClass', ChoiceType::class, [
                    'choices' => $turmas,
                ])
                ->add('TbPortfolio', ChoiceType::class, [
                    'choices' => $portfolio,
                ])
                ->add('enviar', SubmitType::class, array('label' => 'enviar'))
                ->getForm();

        $form->handleRequest($req);

        if ($form->isSubmitted() && $form->isValid()) {
            $aluno = $form->get('Aluno')->getData();

            $turma = $form->get('TbClass')->getData();

            $portfolio = $form->get('TbPortfolio')->getData();

            $tutor = $form->get('Tutor')->getData();

            $this->logControle->log("-----  ALUNO  -----  " . $aluno);
            $this->logControle->log("-----  TURMA  -----  " . $turma);
            $this->logControle->log("-----  PORTFOLIO  -----  " . $portfolio);
            $this->logControle->log("-----  TUTOR  -----  " . $tutor);
            $this->cadastra($aluno, $tutor, $turma, $portfolio);
        }
        return $this->render('cadastro.html.twig', array(
                    'form' => $form->createView(),
        ));
    }

    public function turmas() {
        $this->em = $this->getDoctrine()->getEntityManager();
        $queryBuilder = $this->em->createQueryBuilder();
        $queryBuilder
                ->select('cl')
                ->from('AppBundle:TbClass', 'cl')
                ->getQuery()
                ->execute();

        $this->logControle->log($queryBuilder);
        $results = $queryBuilder->getQuery()->getArrayResult();

        $this->logControle->log("QUERY STUDENT " . print_r($results, true));
        $descriptions = array();
        $idsClasses = array();
        foreach ($results as $classes) {

            $descriptions[] = $classes['dsDescription'];
            $idsClasses[] = $classes['idClass'];
        }

        $turmas = array_combine($descriptions, $idsClasses);
        $this->logControle->log(" ARRAY - - -- - - -- -  " . print_r($turmas, true));

        return $turmas;
    }

    public function usuarios() {
        $this->em = $this->getDoctrine()->getEntityManager();

        $queryBuilder = $this->em->createQueryBuilder();
        $queryBuilder
                ->select('u')
                ->from('AppBundle:TbUser', 'u')
                ->getQuery()
                ->execute();

        $this->logControle->log($queryBuilder);
        $results = $queryBuilder->getQuery()->getArrayResult();

        $this->logControle->log("QUERY STUDENT " . print_r($results, true));
        $descriptions = array();
        $ids = array();
        foreach ($results as $users) {
            $descriptions[] = $users['nmUser'];
            $ids[] = $users['idUser'];
        }
        $usuarios = array_combine($descriptions, $ids);
        $this->logControle->log(" ARRAY - - -- - - -- -  " . print_r($usuarios, true));

        return $usuarios;
    }

    public function portfolio() {
        $this->em = $this->getDoctrine()->getEntityManager();
        $queryBuilder = $this->em->createQueryBuilder();
        $queryBuilder
                ->select('p')
                ->from('AppBundle:TbPortfolio', 'p')
                ->getQuery()
                ->execute();

        $this->logControle->log($queryBuilder);
        $results = $queryBuilder->getQuery()->getArrayResult();

        $this->logControle->log("QUERY STUDENT " . print_r($results, true));
        $descriptions = array();
        $ids = array();
        foreach ($results as $users) {
            $descriptions[] = $users['dsTitle'];
            $ids[] = $users['idPortfolio'];
        }
        $portfolios = array_combine($descriptions, $ids);
        $this->logControle->log(" ARRAY - - -- - - -- -  " . print_r($portfolios, true));

        return $portfolios;
    }

    public function cadastra($aluno, $tutor, $turma, $portfolio) {

        $jatem = "select 
                    id_portfolio_class
                  from 
                    tb_portfolio_class 
                  where id_class = $turma and id_portfolio = $portfolio"; //verifica se a relação portfolio e turma ja existe

        $this->logControle->log("JATEM : " . $jatem);
        $ja = pg_query($this->logControle->db, $jatem);


        if (pg_affected_rows($ja) > 0) {
            $id = pg_fetch_array($ja);
        } else {
            $insert_tbportfclass = "INSERT INTO tb_portfolio_class(
                                     id_class, id_portfolio)
                                     VALUES ($turma, $portfolio) returning id_portfolio_class;"; //cria nova relação


            $result = pg_query($this->logControle->db, $insert_tbportfclass);
            $id = pg_fetch_array($result);
        }


        $insert_ptstudent = "INSERT INTO tb_portfolio_student(
                            id_portfolio_class, id_student, id_tutor)
                           VALUES ($id[0], $aluno, $tutor)returning id_portfolio_student;"; //cria novo portfolio 

        $result2 = pg_query($this->logControle->db, $insert_ptstudent);
        $id_ptest = pg_fetch_array($result2);

        $select_activity = "select 
                                id_activity
                            from 
                                tb_activity 
                            where 
                                id_portfolio= $portfolio"; //seleciona a atividade do porfolio
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
  
}
