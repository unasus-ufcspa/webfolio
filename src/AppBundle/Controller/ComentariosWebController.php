<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use AppBundle\Entity\TbComment;
use AppBundle\Entity\TbCommentVersion;
use Symfony\Component\HttpFoundation\JsonResponse;
use AppBundle\Controller\AddNoticeControllerWeb;
use Symfony\Component\Validator\Constraints\DateTime;
use AppBundle\Entity\TbVersionActivity;
use AppBundle\Entity\TbAttachment;
use AppBundle\Entity\TbAttachComment;
use AppBundle\Entity\TbReference;
use AppBundle\Entity\TbAttachActivity;
use AppBundle\Controller\AddSyncController;
use AppBundle\Controller\AddNoticeController;
use AppBundle\Entity\TbSync;
use AppBundle\Controller\EditorWebController;

header('Content-Type: text/html; charset=UTF-8');
mb_internal_encoding("iso-8859-1");
mb_http_output("iso-8859-1");
ob_start("mb_output_handler");

/**
 * Description of PHPClass
 *
 * @author Marilia
 */
class ComentariosWebController extends Controller {

    public $em;
    public $listaComentariosEspec = array(array());
    public $logControle;

    public function __construct() {
        $this->logControle = new LogController();
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
     * @Route("/addComGeral")
     */
    function addComGeral() {
        $idUser = $this->get('session')->get('idUser');
        if (empty($idUser)) {
            return $this->redirectToRoute('login');
        }
        $this->em = $this->getDoctrine()->getEntityManager();
        $this->logControle->log("\n \n comentando geral");
        $commentGeral = $_POST['comentario'];
        $idActivityStudent = $_POST['idActivityStudent'];
        $this->logControle->log("POSTS: " . $commentGeral . " " . $idActivityStudent);
        $this->logControle->log(print_r($_FILES, true));
        //$this->logControle->log(print_r($_POST, true));
        $objcom = new TbComment();

        $idAuthor = $this->get('session')->get('idUser');

        $this->logControle->log("id do autor " . $idAuthor);
        $objuser = $this->getDoctrine()
                ->getRepository('AppBundle:TbUser')
                ->findOneBy(array('idUser' => $idAuthor));

        $objact = $this->getDoctrine()
                ->getRepository('AppBundle:TbActivityStudent')
                ->findOneBy(array('idActivityStudent' => $idActivityStudent));

        $objcom->setIdActivityStudent($objact);
        $objcom->setIdAuthor($objuser);
        $dt_comment = new \DateTime();
        $dt_comment->format('H:i:s \O\n Y-m-d');

        $objcom->setDtComment($dt_comment);


        $dt_send = new \DateTime();
        $dt_send->format('Y-m-d H:i:s');
        $objcom->setDtSend($dt_send);
        $hora = $dt_send->format('H:i:s');

        $objcom->setTxComment($commentGeral);
        $objcom->setTpComment('G');

        $this->em->persist($objcom);
        $id_comment_srv = $objcom->getIdComment();

        $this->em->flush();
        $idUser = $this->get('session')->get('idUser');
        $nm_table = "tb_comment";
        $resultSync = (AddSyncWebController::addSync($id_comment_srv, $idUser, $nm_table, $idActivityStudent));
        AddNoticeControllerWeb::addNoticeWeb($id_comment_srv, $id_comment_srv, $idActivityStudent, $nm_table, $idUser);
        $nmSystem = null;
        if (!empty($_FILES)) {
            $nmFile = $_FILES['anexoCom']['name'];

            $tipo = $_FILES['anexoCom']['type'];

            if (strpos($tipo, 'image') !== false) {
                $this->logControle->log("é uma imagem");
                $tpAttachment = 'I';
            }
            $path = pathinfo($_FILES['anexoCom']['name']);


            $data = date("Ymd");
            $horaNome = date("Hms");
            $nmSystem = $idUser . '_' . $data . '_' . $horaNome . '.' . $path['extension'];


            $this->uploadAnexoComentario($_FILES, $nmSystem, $tpAttachment, $nmFile, $id_comment_srv);
        }




        $resp = array(
            'id_comment_srv' => $id_comment_srv,
            'nm_user' => $objuser->getNmUser(),
            'dt_send' => $dt_send,
            'hora' => $hora,
            'anexo' => $nmSystem,
            'nomeFile' => $nmFile
        );
        return new JsonResponse($resp);
    }

    public function uploadAnexoComentario($file, $nome_final, $tpAttachment, $nmFile, $id_comment_srv) {
        $idUser = $this->get('session')->get('idUser');
        date_default_timezone_set('America/Sao_Paulo');

        if (empty($idUser)) {
            return $this->redirectToRoute('login');
        }
        $this->logControle->log_up("FILES: " . print_r($file, true));

        // Pasta onde o arquivo vai ser salvo
        $_UP['pasta'] = "../web/uploads/";

        // Tamanho máximo do arquivo (em Bytes)
        $_UP['tamanho'] = 1024 * 1024 * 20 * 20; // 20Mb
        // Array com as extensões permitidas
        $_UP['extensoes'] = array('png', 'jpeg', 'jpg', 'gif', 'avi', 'mp4', 'pdf', 'txt');

        $extAtual = explode('.', $file['anexoCom']['name']);
        $this->logControle->log_up(print_r($extAtual, true));
        if (in_array($extAtual[1], $_UP['extensoes'])) {
            // Renomeia o arquivo? (Se true, o arquivo será salvo como .jpg e um nome único)
            $_UP['renomeia'] = true;

            // Array com os tipos de erros de upload do PHP
            $_UP['erros'][0] = 'Não houve erro';
            $_UP['erros'][1] = 'O arquivo no upload é maior do que o limite do PHP';
            $_UP['erros'][2] = 'O arquivo ultrapassa o limite de tamanho especifiado no HTML';
            $_UP['erros'][3] = 'O upload do arquivo foi feito parcialmente';
            $_UP['erros'][4] = 'Não foi feito o upload do arquivo';

            // Verifica se houve algum erro com o upload. Se sim, exibe a mensagem do erro
            if ($file['anexoCom']['error'] != 0) {
                $this->logControle->log_up('1');
                die("Não foi possível fazer o upload, erro:" . $_UP['erros'][$file['anexoCom']['error']]);
                exit; // Para a execução do script
            }

            // Faz a verificação do tamanho do arquivo
            if ($_UP['tamanho'] < $file['anexoCom']['size']) {
                $this->logControle->log_up('3');
                echo "O arquivo enviado é muito grande, envie arquivos de até 2Mb.";
                exit;
            }

            // O arquivo passou em todas as verificações, hora de tentar movê-lo para a pasta
            // Primeiro verifica se deve trocar o nome do arquivo
            // Depois verifica se é possível mover o arquivo para a pasta escolhida
            if (move_uploaded_file($file['anexoCom']['tmp_name'], $_UP['pasta'] . $nome_final)) {
                $this->logControle->log_up('6');
                // Upload efetuado com sucesso, exibe uma mensagem e um link para o arquivo

                $this->insertCommentAttachWeb($tpAttachment, $nmFile, $nome_final, $id_comment_srv);
                return true;
            } else {
                $this->logControle->log_up('7');
                // Não foi possível fazer o upload, provavelmente a pasta está incorreta
                echo "Não foi possível enviar o arquivo, tente novamente";
                return false;
            }
        } else {
            $this->logControle->log_up('extensao nao permitida');
            return false;
        }
    }

    public function insertCommentAttachWeb($tpAttachment, $nmFile, $nmSystem, $id_comment_srv) {

        $this->logControle->log("adiciona anexo no comentario");
        $objAttach = new TbAttachment();
        $objAttach->setTpAttachment($tpAttachment);
        $objAttach->setNmFile($nmFile);
        $objAttach->setNmSystem($nmSystem);

        $this->em->persist($objAttach);
        $idAttachSrv = $objAttach->getIdAttachment();
        $this->em->flush();

        $queryBuilder = $this->em->createQueryBuilder();
        $queryBuilder
                ->update('AppBundle:TbAttachment', 'a')
                ->set('a.idAttachmentSrv', $queryBuilder->expr()->literal($idAttachSrv))
                ->where($queryBuilder->expr()->eq('a.idAttachment', $idAttachSrv))
                ->getQuery()
                ->execute();
        //$this->logControle->log($queryBuilder);

        $objAttach = $this->getDoctrine()
                ->getRepository('AppBundle:TbAttachment')
                ->findOneBy(array('idAttachment' => $idAttachSrv));

        $objComm = $this->getDoctrine()
                ->getRepository('AppBundle:TbComment')
                ->findOneBy(array('idComment' => $id_comment_srv));
        $objAttachComm = new TbAttachComment();
        $objAttachComm->setIdAttachment($objAttach);
        $objAttachComm->setIdComment($objComm);

        $this->em->persist($objAttachComm);

        $this->em->flush();

        return $idAttachSrv;
    }

    /**
     * @Route("/addComEspecif")
     */
    function addComEspecif(Request $requisi) {
        $idUser = $this->get('session')->get('idUser');
        if (empty($idUser)) {
            return $this->redirectToRoute('login');
        }
        $this->em = $this->getDoctrine()->getEntityManager();
        if (0 === strpos($requisi->headers->get('Content-Type'), 'application/json')) {
            $data = json_decode($requisi->getContent(), true);
            $requisi->request->replace(is_array($data) ? $data : array());
            $this->logControle->log("REQUEST addComEspecific : " . print_r($data, true));

            $commentEspecifico = $data['comentario'];
            $idActivityStudent = $data['idActivityStudent'];
            $idVersionActivity = $data['idVersionActivity'];
            $txReference = $data['txReference'];
            $txActivity = $data['txActivity'];

            $txActivity = str_replace("\"background-color: #d9fce6;\"", '"' . 'background-color:#d9fce6' . '"', $txActivity);

            $txActivity = str_replace("class=\"mce-object mce-object-video\"", '', $txActivity);
            $txActivity = str_replace("class=\"bolinhaFolio\"", "class='bolinhaFolio'", $txActivity);
            $bolinhaAberta = $data['bolinhaAberta'];
            $this->logControle->log(" COMENTARIO " . $txActivity);

            //ta dando problema quando tem acento
            $this->logControle->log("TXACTIVITY UPDATE " . $txActivity);

            $product = $this->em->getRepository('AppBundle:TbVersionActivity')->find($idVersionActivity);

            if (!$product) {
                throw $this->createNotFoundException(
                        'No product found for id ' . $idVersionActivity
                );
            }


            if ($bolinhaAberta == 0 || $bolinhaAberta == null) {
                $this->logControle->log("id activity " . $idActivityStudent);
                $maiorNuComVersion = (EditorWebController::getMaxNumComActvity($idActivityStudent));

                $this->logControle->log("nuCommentActivity" . print_r($maiorNuComVersion, true));
                if ($maiorNuComVersion > 0) {
                    $numComActivity = $maiorNuComVersion + 1;
                } else {
                    $numComActivity = 1;
                }
            } else {
                $numComActivity = $bolinhaAberta;
            }

            //codigo para pegar a posicao atual



            $findme = "<span id=\"" . $numComActivity . "\"";
            $this->logControle->log($findme);
            //   $this->logControle->log("strip" . strip_tags($txActivity, "<span>"));
            $pos = strpos($txActivity, $findme);
            $this->logControle->log("posicao " . $pos);
            $tamanho = strlen($txActivity);
            //   $total = ($tamanho-$pos)+53;
            //   $this->logControle->log($total);
            $sub = substr($txActivity, 0, $pos); // pablo.blog.br

            $this->logControle->log($sub);
            $oqueSobrou = strip_tags($sub);
            $this->logControle->log("strip" . $oqueSobrou);

            $oResto = substr($txActivity, $pos, $tamanho);
            $oqueSobrou.=$oResto;
            $this->logControle->log($oqueSobrou);

            $posFinal = strpos($oqueSobrou, $findme);
            $posSize = strpos($oqueSobrou, "</span>");
            //  $size=$posFinal-$posSize;
            $size = strlen($txReference);
            $this->logControle->log("posicao FINAL " . $posFinal - 1);
            $this->logControle->log("\\n<br>");

            // FIM codigo para pegar a posicao atual
            $texto = $txActivity;
            $texto = preg_replace('<img src="/webfolio/uploads/$1">', '#<img.+?src=[\'"]([^\'"]+)[\'"].*>#i', $texto);
            // $texto = preg_replace('<img src="/webfolio/uploads/$1">','#<video.+?src=[\'"]([^\'"]+)[\'"].*>#i', $texto);
            $product->setTxActivity($texto);
            $this->em->flush();
            $queryBuilder = $this->em->createQueryBuilder();
            $queryBuilder
                    ->select('c.idCommentVersion as idCommentVersion')
                    ->from('AppBundle:TbCommentVersion', 'c')
                    ->innerJoin('c.idVersionActivity', 'v', 'WITH', 'c.idVersionActivity = v.idVersionActivity')
                    ->where($queryBuilder->expr()->eq('c.nuCommentActivity', $bolinhaAberta))
                    ->andWhere($queryBuilder->expr()->eq('c.idVersionActivity', $idVersionActivity))
                    ->getQuery()
                    ->execute();

            $results = $queryBuilder->getQuery()->getArrayResult();
            $this->logControle->log("resultado da tb comment version" . print_r($results, true));
            $total = count($results);
            if ($total == 0) {

                $objComVer = new TbCommentVersion();
                $objetoVersao = $this->getDoctrine()
                        ->getRepository('AppBundle:TbVersionActivity')
                        ->findOneBy(array('idVersionActivity' => $idVersionActivity));

                $objComVer->setIdVersionActivity($objetoVersao);
                $objComVer->setTxReference($txReference);
                $objComVer->setNuCommentActivity($numComActivity);
                $objComVer->setNuInitialPos($posFinal - 1);
                $objComVer->setNuSize($size);
                $this->em->persist($objComVer);

                $idCM = $objComVer->getIdCommentVersion();
                $idUser = $this->get('session')->get('idUser');
                $nm_table = "tb_comment_version";

                $this->em->flush();
                $resultSyncComentVersion = (AddSyncWebController::addSync($idCM, $idUser, "tb_comment_version", $idActivityStudent));

                $nm_table_update = "tb_version_activity_update";
                $resultSyncVersion = (AddSyncWebController::addSync($idVersionActivity, $idUser,  $nm_table_update, $idActivityStudent));
                //  AddNoticeControllerWeb::addNoticeWeb($idCM, $idCM, $idActivityStudent, "tb_comment_version", $idUser);
            } else {
                $idCM = $results[0]["idCommentVersion"];
            }
            $objcom = new TbComment();

            $idAuthor = $this->get('session')->get('idUser');
            $objact = $this->getDoctrine()
                    ->getRepository('AppBundle:TbActivityStudent')
                    ->findOneBy(array('idActivityStudent' => $idActivityStudent));

            $objuser = $this->getDoctrine()
                    ->getRepository('AppBundle:TbUser')
                    ->findOneBy(array('idUser' => $idAuthor));

            $objcom->setIdActivityStudent($objact);
            $objcom->setIdAuthor($objuser);
            $dt_comment = new \DateTime();
            $dt_comment->format('H:i:s \O\n Y-m-d');

            $objcom->setDtComment($dt_comment);

            $dt_send = new \DateTime();
            $dt_send->format('Y-m-d H:i:s');
            $objcom->setDtSend($dt_send);

            $hora = $dt_send->format('H:i:s');

            $objcom->setTxComment($commentEspecifico);
            $objcom->setTpComment('O');

            $objid = $this->getDoctrine()
                    ->getRepository('AppBundle:TbCommentVersion')
                    ->findOneBy(array('idCommentVersion' => $idCM));
            $objcom->setIdCommentVersion($objid);

            $this->em->persist($objcom);
            $last_id_comment = $objcom->getIdComment();
            $this->logControle->log("aqui");


            $last_num_com_srv = $numComActivity;
            $this->em->flush();


            $resultSyncComment = (AddSyncWebController::addSync($last_id_comment, $idAuthor, "tb_comment", $idActivityStudent));
            AddNoticeControllerWeb::addNoticeWeb($last_id_comment, $last_id_comment, $idActivityStudent, "tb_comment", $idAuthor);

            $resultado = array(
                "last_num_com_srv" => $last_num_com_srv,
                "last_id_comment" => $last_id_comment,
                "dt_send" => $dt_send,
                "hora" => $hora
            );
        } else {
            $this->logControle->log(" ----  OCORREU UM ERRO NO BANCO --- " . pg_last_error($this->logControle->db));
            $flag = 4;
            $erro = $this->addError($flag);
        }
        if (!empty($erro)) {
            $response = $erro;
        } else {
            if (!empty($resultado)) {
                $response = $resultado;
            }
        }
        $this->logControle->log("RESPONSE ADDCOMESPECIF : " . print_r($response, true));
        return new JsonResponse($response);
    }

//       /**
//     * @Route("/addRespostaEspecif")
//     */
//    function addRespostaEspecif(Request $requisi) {
//        $this->em = $this->getDoctrine()->getEntityManager();
//        if (0 === strpos($requisi->headers->get('Content-Type'), 'application/json')) {
//            $data = json_decode($requisi->getContent(), true);
//            $requisi->request->replace(is_array($data) ? $data : array());
//            $this->logControle->log("REQUEST addComEspecific : " . print_r($data, true));
//
//            $commentEspecifico = $data['comentario'];
//            $idActivityStudent = $data['idActivityStudent'];
//            $idVersionActivity = $data['idVersionActivity'];
//            $txReference = $data['txReference'];
//            $txActivity = $data['txActivity'];
//            $bolinhaAberta  = $data['bolinhaAberta'];
//            $this->logControle->log(" COMENTARIO " . $txActivity);
//
//            //ta dando problema quando tem acento
//            $this->logControle->log("TXACTIVITY UPDATE " . $txActivity);
//
//            $product = $this->em->getRepository('AppBundle:TbVersionActivity')->find($idVersionActivity);
//
//            if (!$product) {
//                throw $this->createNotFoundException(
//                        'No product found for id ' . $idVersionActivity
//                );
//            }
//
//            $product->setTxActivity($txActivity);
//            $this->em->flush();
//            if ($bolinhaAberta==0 || $bolinhaAberta==null){
//            $maiorNuComVersion = (EditorWebController::getMaxNumComActvity($idActivityStudent));
//
//            $this->logControle->log("nuCommentActivity" . print_r($maiorNuComVersion, true));
//            if ($maiorNuComVersion > 0 ) {
//                $numComActivity = $maiorNuComVersion + 1;
//            } else {
//                $numComActivity = 1;
//            }
//            }else{
//                $numComActivity=$bolinhaAberta;
//            }
//
//            $objComVer = new TbCommentVersion();
//
//            $objComVer->setIdVersionActivity($idVersionActivity);
//            $objComVer->setTxReference($txReference);
//            $objComVer->setNuCommentActivity($numComActivity);
//            $objComVer->setNuInitialPos(0);
//            $objComVer->setNuSize(10);
//            $this->em->persist($objComVer);
//
//            $idCM = $objComVer->getIdCommentVersion();
//            $idUser = $this->get('session')->get('idUser');
//            $nm_table = "tb_comment_version";
//
//            $this->em->flush();
//
//            $objcom = new TbComment();
//
//            $idAuthor = $this->get('session')->get('idUser');
//            $objact = $this->getDoctrine()
//                    ->getRepository('AppBundle:TbActivityStudent')
//                    ->findOneBy(array('idActivityStudent' => $idActivityStudent));
//
//            $objuser = $this->getDoctrine()
//                    ->getRepository('AppBundle:TbUser')
//                    ->findOneBy(array('idUser' => $idAuthor));
//
//            $objcom->setIdActivityStudent($objact);
//            $objcom->setIdAuthor($objuser);
//            $dt_comment = new \DateTime();
//            $dt_comment->format('H:i:s \O\n Y-m-d');
//
//            $objcom->setDtComment($dt_comment);
//
//            $dt_send = new \DateTime();
//            $dt_send->format('Y-m-d H:i:s');
//            $objcom->setDtSend($dt_send);
//
//            $objcom->setTxComment($commentEspecifico);
//            $objcom->setTpComment('O');
//
//            $objid = $this->getDoctrine()
//                    ->getRepository('AppBundle:TbCommentVersion')
//                    ->findOneBy(array('idCommentVersion' => $idCM));
//            $objcom->setIdCommentVersion($objid);
//
//            $this->em->persist($objcom);
//            $last_id_comment = $objcom->getIdComment();
//            $this->logControle->log("aqui");
//
//
//            $last_num_com_srv = $objComVer->getNuCommentActivity();
//            $this->em->flush();
//            $resultSyncComentVersion = (AddSyncWebController::addSync($idCM, $idUser, "tb_comment_version", $idActivityStudent));
//            AddNoticeControllerWeb::addNoticeWeb($idCM, $idCM, $idActivityStudent, "tb_comment_version", $idUser);
//
//            $resultSyncComment = (AddSyncWebController::addSync($last_id_comment, $idUser, "tb_comment", $idActivityStudent));
//            AddNoticeControllerWeb::addNoticeWeb($last_id_comment, $last_id_comment, $idActivityStudent, "tb_comment", $idUser);
//
//            $resultado = array(
//                "last_num_com_srv" => $last_num_com_srv,
//                "last_id_comment" => $last_id_comment
//            );
//        } else {
//            $this->logControle->log(" ----  OCORREU UM ERRO NO BANCO --- " . pg_last_error($this->logControle->db));
//            $flag = 4;
//            $erro = $this->addError($flag);
//        }
//        if (!empty($erro)) {
//            $response = $erro;
//        } else {
//            if (!empty($resultado)) {
//                $response = $resultado;
//            }
//        }
//        $this->logControle->log("RESPONSE ADDCOMESPECIF : " . print_r($response, true));
//        return new JsonResponse($response);
//    }

    /**
     * @Route("/carregaComGeral")
     */
    public function carregaComGeral(Request $rq) {
        $idUser = $this->get('session')->get('idUser');
        if (empty($idUser)) {
            return $this->redirectToRoute('login');
        }
        $this->em = $this->getDoctrine()->getEntityManager();
        if (0 === strpos($rq->headers->get('Content-Type'), 'application/json')) {
            $data = json_decode($rq->getContent(), true);
            $rq->request->replace(is_array($data) ? $data : array());
            $this->logControle->log("json ADD COM ESPECIFICO : " . print_r($data, true));
//        $idActivityStudent = $_POST['idActivityStudent'];
            $idActivityStudent = $data['idActivityStudent'];
            $ultimoComentario = $data['ultimoComentario'];
            $tpComment = 'G';


            if ($ultimoComentario == 0) {
                $queryBuilder = $this->em->createQueryBuilder();
                $queryBuilder
                        ->select('c, u')
                        ->from('AppBundle:TbComment', 'c')
                        ->innerJoin('c.idActivityStudent', 'a', 'WITH', 'c.idActivityStudent = a.idActivityStudent')
                        ->innerJoin('c.idAuthor', 'u')
                        ->where($queryBuilder->expr()->eq('c.idActivityStudent', $idActivityStudent))
                        ->andWhere($queryBuilder->expr()->eq('c.tpComment', "'" . $tpComment . "'"))
                        ->orderBy('c.idComment', 'ASC')
                        ->getQuery()
                        ->execute();

                $results = $queryBuilder->getQuery()->getArrayResult();
            } else {
                $queryBuilder = $this->em->createQueryBuilder();
                $queryBuilder
                        ->select('c, u')
                        ->from('AppBundle:TbComment', 'c')
                        ->innerJoin('c.idActivityStudent', 'a', 'WITH', 'c.idActivityStudent = a.idActivityStudent')
                        ->innerJoin('c.idAuthor', 'u')
                        ->where($queryBuilder->expr()->eq('c.idActivityStudent', $idActivityStudent))
                        ->andWhere($queryBuilder->expr()->eq('c.tpComment', "'" . $tpComment . "'"))
                        ->andWhere($queryBuilder->expr()->gt('c.idComment', "'" . $ultimoComentario . "'"))
                        ->orderBy('c.idComment', 'ASC')
                        ->getQuery()
                        ->execute();

                $results = $queryBuilder->getQuery()->getArrayResult();
            }


            $this->logControle->log("SQL carregaComGeral " . print_r($results, true));

            $totalItens = count($results);

            if ($totalItens > 0) {
                foreach ($results as $row) {
                    $user = $this->getDoctrine()
                            ->getRepository('AppBundle:TbUser')
                            ->findOneBy(array('idUser' => $row['idAuthor']['idUser']));
                    //    $this->logControle->log("USER COMENTARIO GERAL " . print_r($user, true));

                    if ($user) {
                        $nome = $user->getNmUser();
                    }

                    $anexo = $this->verificaAnexoCom($row['idComment']);

                    $this->logControle->log("data " . $row['dtComment']->format('Y-m-d H:i:s'));
                    $data = $row['dtComment']->format('Y-m-d');
                    $hora = $row['dtSend']->format('H:i:s');
                    $result[][$data] = array(
                        'id_comment' => (string) $row['idComment'],
                        'id_activity_student' => $idActivityStudent,
                        'id_author' => (string) $row['idAuthor']['idUser'],
                        'nm_user' => $nome,
                        'tx_comment' => $row['txComment'],
                        'tp_comment' => $row['tpComment'],
                        'dt_comment' => $row['dtComment']->format('Y-m-d H:i:s'),
                        'dt_send' => $row['dtSend']->format('Y-m-d H:i:s'),
                        'hora' => $hora,
                        'anexo' => $anexo
                    );
                }
            } else {
                $result = array();
            }
        }
        // $this->logControle->log(print_r($result, true));
        return new JsonResponse($result);
    }

    function verificaAnexoCom($id_comment) {
        $result = null;
        $queryBuilder = $this->em->createQueryBuilder();
        $queryBuilder
                ->select('ac, a')
                ->from('AppBundle:TbAttachComment', 'ac')
                ->innerJoin('ac.idComment', 'c', 'WITH', 'c.idComment = ac.idComment')
                ->innerJoin('ac.idAttachment', 'a', 'WITH', 'ac.idAttachment = a.idAttachment')
                ->where($queryBuilder->expr()->eq('c.idComment', $id_comment))
                ->getQuery()
                ->execute();

        $results = $queryBuilder->getQuery()->getArrayResult();
        $totalItens = count($results);

        if ($totalItens > 0) {

            foreach ($results as $row_attach) {
                $result = array(
                    'id_attachment' => (string) $row_attach['idAttachment']['idAttachment'],
                    'tp_attachment' => $row_attach['idAttachment']['tpAttachment'],
                    'nm_file' => $row_attach['idAttachment']['nmFile'],
                    'nm_system' => $row_attach['idAttachment']['nmSystem']
                );
            }
        }
        return $result;
    }

    /**
     * @Route("/getObservacoesView")
     */
    public function getObservacoesView(Request $rq) {
        $idUser = $this->get('session')->get('idUser');
        if (empty($idUser)) {
            return $this->redirectToRoute('login');
        }

        if (0 === strpos($rq->headers->get('Content-Type'), 'application/json')) {
            $data = json_decode($rq->getContent(), true);
            $rq->request->replace(is_array($data) ? $data : array());
            $this->logControle->log(" --------------------- getObservacoesView ---------------------------------");
            $versao = $data['idVersionActivity'];
            $numComVersion = $data['numComVersion'];
            $ultimaObserv = $data['ultimaObserv'];
            $this->logControle->log("observation " . $numComVersion . " versao " . $versao);
            $em = $this->getDoctrine()->getEntityManager();
            // $retorno = array();

            if ($ultimaObserv == 0) {
                $queryBuilder = $em->createQueryBuilder();
                $queryBuilder
                        ->select('c, a, u, v, va')
                        ->from('AppBundle:TbComment', 'c')
                        ->innerJoin('c.idActivityStudent', 'a')
                        ->innerJoin('c.idAuthor', 'u')
                        ->innerJoin('c.idCommentVersion', 'v')
                        ->innerJoin('v.idVersionActivity', 'va')
                        ->where($queryBuilder->expr()->eq('v.nuCommentActivity', $numComVersion))
                        ->orderBy('c.dtSend', 'ASC')
                        ->getQuery()
                        ->execute();

                //$this->logControle->log($queryBuilder);
                $results = $queryBuilder->getQuery()->getArrayResult();
            } else {
                $queryBuilder = $em->createQueryBuilder();
                $queryBuilder
                        ->select('c, a, u, v, va')
                        ->from('AppBundle:TbComment', 'c')
                        ->innerJoin('c.idActivityStudent', 'a')
                        ->innerJoin('c.idAuthor', 'u')
                        ->innerJoin('c.idCommentVersion', 'v')
                        ->innerJoin('v.idVersionActivity', 'va')
                        ->where($queryBuilder->expr()->eq('v.nuCommentActivity', $numComVersion))
                        ->andWhere($queryBuilder->expr()->gt('c.idComment', "'" . $ultimaObserv . "'"))
                        ->orderBy('c.dtSend', 'ASC')
                        ->getQuery()
                        ->execute();

                //$this->logControle->log($queryBuilder);
                $results = $queryBuilder->getQuery()->getArrayResult();
            }
            $this->logControle->log("SQL SELECT_TB_COMMENT: " . print_r($results, true));
            $totalItens = count($results);
            //   $this->logControle->log("total itens: " . $totalItens);
            if ($totalItens > 0) {
                foreach ($results as $row) {
                    if ($row['idCommentVersion']['idVersionActivity']['idVersionActivity'] == $versao) {
                        $data = $row['dtComment']->format('Y-m-d');
                        $hora = $row['dtComment']->format('H:i:s');

                        $array = array(
                            "id_comment" => $row['idComment'],
                            "txComment" => $row["txComment"],
                            "idAuthor" => $row["idAuthor"]["idUser"],
                            "nmUser" => $row["idAuthor"]["nmUser"],
                            "data" => $data,
                            "hora" => $hora
                        );
                        $retorno[] = $array;
                        //  $this->listaComentariosEspec[$numComVersion][] = $array;
                    }
                }
            } else {
                $retorno = array();
            }
        } else {
            $retorno = array();
        }
        $this->logControle->log("RETORNO COMENTARIOS ESPECIFICOS: " . print_r($retorno, true));
        $this->logControle->log("FIM");
        $this->logControle->log("==============================================================================");
        return new JsonResponse($retorno);
    }

}
