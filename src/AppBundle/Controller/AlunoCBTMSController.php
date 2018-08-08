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
use AppBundle\Entity\TbUser;
use AppBundle\Entity\TbTutorPortfolio;
use AppBundle\Entity\TbPortfolioStudent;
use AppBundle\Entity\TbPortfolioClass;
use AppBundle\Entity\TbActivity;
use AppBundle\Entity\TbActivityStudent;


use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\FormType;


class AlunoCBTMSController extends Controller {
  public $logControle;

  public function __construct() {
      $this->logControle = new LogController();
  }
       /**
     * @Route("/alunoCBTMS")
     */

    function alunoCBTMS(Request $request){

          $this->em = $this->getDoctrine()->getManager();

          $this->formAdicionarAluno = $this->gerarFormulario("adicionar");
          $this->formAdicionarAluno->handleRequest($request);

          if ($request->request->has($this->formAdicionarAluno->getName())) {
              if ($this->formAdicionarAluno->isSubmitted() && $this->formAdicionarAluno->isValid()) {
                  $dadosFormAdicionarAluno = $this->formAdicionarAluno->getData();
                  $this->adicionarAluno($dadosFormAdicionarAluno);

                  // return $this->redirectToRoute('login');
              }
          }

          return $this->render('inserirAluno.html.twig', array('formAddAluno' => $this->formAdicionarAluno->createView()));
    }

    function gerarFormulario($nomeFormulario) {

        $formularioTbUser = $this->get('form.factory')
                ->createNamedBuilder($nomeFormulario, FormType::class)
                ->add('NmUser', TextType::class, array('label' => false))
                ->add('IdUser', HiddenType::class, array('label' => false))
                ->add('DsEmail', EmailType::class, array('label' => false))
                ->add('NuCellphone', NumberType::class, array('label' => false))
                ->getForm();
        return $formularioTbUser;
    }

    function adicionarAluno($dadosFormAdicionarAluno) {
      $host = "localhost";
      $user = "postgres";
      $pswd = "PgUn45usufc5p4";
      $dbname = "webfolio";

      $this->db = pg_connect("host=$host port=5432 dbname=$dbname user=$user password=$pswd");

      $result = pg_query($this->db, "SELECT * FROM insere_usuario_tutores ('".$dadosFormAdicionarAluno['NmUser']."', '".$dadosFormAdicionarAluno['DsEmail']."',null,11,15,array[2,108])");

      $arr = pg_fetch_array($result);

     echo "<div class='mensagemPHP'>";
     echo " ".$arr[0]." ";
     echo "</div>";

    }

    function persistirObjetoAluno($objetoUsuario, $dadosUsuario, $flag, $valor) {

        $this->em = $this->getDoctrine()->getManager();

        $senhaFormatada = hash('sha256', 'folio');

        $objetoUsuario->setDsEmail($dadosUsuario['DsEmail']);
        $objetoUsuario->setNmUser($dadosUsuario['NmUser']);
        $objetoUsuario->setDsPassword($senhaFormatada);
        $objetoUsuario->setNuCellphone($dadosUsuario['NuCellphone']);
        $objetoUsuario->setNuIdentification(null);
        $objetoUsuario->setFlAdmin('F');
        $objetoUsuario->setFlProposer('F');

        $this->em->persist($objetoUsuario);
        $idUser = $objetoUsuario->getIdUser();

        $this->registrarAlunoTurma($objetoUsuario);

        $this->em->flush();
    }

    // function registrarAlunoTurma($objetoUsuario){
    //   $novoPortfolioStudent = new TbPortfolioStudent();
    //
    //   $queryBuilderPortClass = $this->em->createQueryBuilder();
    //   $queryBuilderPortClass
    //           ->select('pc')
    //           ->from('AppBundle:TbPortfolioClass', 'pc')
    //           ->where($queryBuilderPortClass->expr()->eq('pc.idPortfolioClass', "4"))
    //           ->getQuery()
    //           ->execute();
    //   $portClass = $queryBuilderPortClass->getQuery()->getResult();
    //
    //   $this->em = $this->getDoctrine()->getManager();
    //
    //   $novoPortfolioStudent->setIdPortfolioClass($portClass[0]);
    //   $novoPortfolioStudent->setIdStudent($objetoUsuario);
    //   $novoPortfolioStudent->setDtFirstSync(null);
    //   $novoPortfolioStudent->setNuPortfolioVersion(null);
    //
    //   $this->em->persist($novoPortfolioStudent);
    //   $idPortfolioStudent = $novoPortfolioStudent->getIdPortfolioStudent();
    //
    //   $this->registrarTutorPortfolio($novoPortfolioStudent);
    //
    //
    //
    //
    //   $queryBuilderActivityStudent = $this->em->createQueryBuilder();
    //   $queryBuilderActivityStudent
    //           ->select('ac')
    //           ->from('AppBundle:TbActivity', 'ac')
    //           ->where($queryBuilderActivityStudent->expr()->eq('ac.idPortfolio', "4"))
    //           ->getQuery()
    //           ->execute();
    //   $atividades = $queryBuilderActivityStudent->getQuery()->getArrayResult();
    //
    //   foreach ($atividades as $atividade) {
    //     $this->em = $this->getDoctrine()->getManager();
    //
    //     $novaActivityStudent = new TbActivityStudent();
    //
    //     $novaActivityStudent->setIdPortfolioClass($portClass[0]);
    //     $novaActivityStudent->setIdStudent($objetoUsuario);
    //     $novaActivityStudent->setDtFirstSync(null);
    //     $novaActivityStudent->setNuPortfolioVersion(null);
    //     $novaActivityStudent->setNuPortfolioVersion(null);
    //
    //     $this->em->persist($novaActivityStudent);
    //   }
    //
    //   $this->em->flush();
    // }

    function registrarTutorPortfolio($idPortfolioStudent){
      $novoTutorPortfolio = new TbTutorPortfolio();

      $queryBuilderUser = $this->em->createQueryBuilder();
      $queryBuilderUser
              ->select('it')
              ->from('AppBundle:TbUser', 'it')
              ->where($queryBuilderUser->expr()->eq('it.idUser', "2"))
              ->getQuery()
              ->execute();
      $tutor = $queryBuilderUser->getQuery()->getResult();

      $this->em = $this->getDoctrine()->getManager();

      $novoTutorPortfolio->setIdTutor($tutor[0]);
      $novoTutorPortfolio->setIdPortfolioStudent($idPortfolioStudent);

      $this->em->persist($novoTutorPortfolio);

      $this->em->flush();
    }
}
