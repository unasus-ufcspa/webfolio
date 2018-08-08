<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\EntityManager;
use AppBundle\Entity\TbAttachActivity;
use AppBundle\Entity\TbAttachment;
use AppBundle\Entity\TbUser;
use AppBundle\Entity\TbAttachComment;
use Symfony\Component\Finder\Finder;

class UploadController extends Controller {

    public $em;

    public $logControle ;

    public function __construct() {
          $this->logControle= new LogController(); 
    }

  


    /**
     * @Route("/baixar")
     */
    public function baixar(Request $request) {
        // replace this example code with whatever you need
        $this->logControle->log("dentro da rota baixar");
        return $this->render('fileUpload.html.twig');
    }

    /**
     * @Route("/baixarImagem")
     */
    public function baixarImagem(Request $request) {
        // replace this example code with whatever you need
        $this->logControle->log("dentro da rota baixar");
        return $this->render('fileUploadImagem.html.twig');
    }

    /**
     * @Route("/upload")
     */
    public function upload() {
        $this->logControle->log_up("FILES: " . print_r($_FILES, true));

        // Pasta onde o arquivo vai ser salvo
        $_UP['pasta'] = "../web/uploads/";

        // Tamanho máximo do arquivo (em Bytes)
        $_UP['tamanho'] = 1024 * 1024 * 20; // 20Mb
        // Array com as extensões permitidas
        $_UP['extensoes'] = array('csv');

        // Renomeia o arquivo? (Se true, o arquivo será salvo como .jpg e um nome único)
        $_UP['renomeia'] = false;

        // Array com os tipos de erros de upload do PHP
        $_UP['erros'][0] = 'Não houve erro';
        $_UP['erros'][1] = 'O arquivo no upload é maior do que o limite do PHP';
        $_UP['erros'][2] = 'O arquivo ultrapassa o limite de tamanho especifiado no HTML';
        $_UP['erros'][3] = 'O upload do arquivo foi feito parcialmente';
        $_UP['erros'][4] = 'Não foi feito o upload do arquivo';

        // Verifica se houve algum erro com o upload. Se sim, exibe a mensagem do erro
        if ($_FILES['arquivo']['error'] != 0) {
            $this->logControle->log_up('1');
            die("Não foi possível fazer o upload, erro:" . $_UP['erros'][$_FILES['arquivo']['error']]);
            exit; // Para a execução do script
        }

        // Faz a verificação do tamanho do arquivo
        if ($_UP['tamanho'] < $_FILES['arquivo']['size']) {
            $this->logControle->log_up('3');
            echo "O arquivo enviado é muito grande, envie arquivos de até 2Mb.";
            exit;
        }

        // O arquivo passou em todas as verificações, hora de tentar movê-lo para a pasta
        // Primeiro verifica se deve trocar o nome do arquivo
        if ($_UP['renomeia'] == true) {
            $this->logControle->log_up('4');
            // Cria um nome baseado no UNIX TIMESTAMP atual e com extensão .jpg
            $nome_final = md5(time()) . '.jpg';
        } else {
            $this->logControle->log_up('5');
            // Mantém o nome original do arquivo
            $nome_final = $_FILES['arquivo']['name'];
        }

        // Depois verifica se é possível mover o arquivo para a pasta escolhida
        if (move_uploaded_file($_FILES['arquivo']['tmp_name'], $_UP['pasta'] . $nome_final)) {
            $this->logControle->log_up('6');
            // Upload efetuado com sucesso, exibe uma mensagem e um link para o arquivo
            echo "Upload efetuado com sucesso!";
            echo '<a href="' . $this->getParameter('web_dir') . 'download/' . $nome_final . '">Clique aqui para acessar o arquivo</a>';
        } else {
            $this->logControle->log_up('7');
            // Não foi possível fazer o upload, provavelmente a pasta está incorreta
            echo "Não foi possível enviar o arquivo, tente novamente";
        }

        return new Response();
    }

    /**
     * @Route("/uploadWeb")
     */
    public function uploadWeb() {
        $this->logControle->log_up("FILES: " . print_r($_FILES, true));
        date_default_timezone_set('America/Sao_Paulo');
          $idUser = $this->get('session')->get('idUser');
        // Pasta onde o arquivo vai ser salvo
        $_UP['pasta'] = "../web/uploads/";

        // Tamanho máximo do arquivo (em Bytes)
        $_UP['tamanho'] = 1024 * 1024 * 20 * 20; // 20Mb
        // Array com as extensões permitidas
        $_UP['extensoes'] = array('jpg', 'png', 'gif', 'mp4', 'avi');

        // Renomeia o arquivo? (Se true, o arquivo será salvo como .jpg e um nome único)
        $_UP['renomeia'] = true;

        // Array com os tipos de erros de upload do PHP
        $_UP['erros'][0] = 'Não houve erro';
        $_UP['erros'][1] = 'O arquivo no upload é maior do que o limite do PHP';
        $_UP['erros'][2] = 'O arquivo ultrapassa o limite de tamanho especifiado no HTML';
        $_UP['erros'][3] = 'O upload do arquivo foi feito parcialmente';
        $_UP['erros'][4] = 'Não foi feito o upload do arquivo';

        if (!empty($_FILES)) {
            // Verifica se houve algum erro com o upload. Se sim, exibe a mensagem do erro
            if ($_FILES['arquivo']['error'] != 0) {
                $this->logControle->log_up('1');
                die("Não foi possível fazer o upload, erro:" . $_UP['erros'][$_FILES['arquivo']['error']]);
                exit; // Para a execução do script
            }

            // Faz a verificação do tamanho do arquivo
            if ($_UP['tamanho'] < $_FILES['arquivo']['size']) {
                $this->logControle->log_up('3');
                echo "O arquivo enviado é muito grande, envie arquivos de até 2Mb.";
                exit;
            }

            // O arquivo passou em todas as verificações, hora de tentar movê-lo para a pasta
            // Primeiro verifica se deve trocar o nome do arquivo
            $path = pathinfo($_FILES['arquivo']['name']);

            if ($_UP['renomeia'] == true) {
                $this->logControle->log_up('Renoemando arquivo');
                $idUser = $this->get('session')->get('idUser');
                // Cria um nome baseado no UNIX TIMESTAMP atual e com extensão .jpg


                $data = date("Ymd");
                $hora = date("Hms");
                $nome_final = $idUser . '_' . $data . '_' . $hora . '.' . $path['extension'];
                $this->logControle->log_up("nome final ". $nome_final);
            } else {
                $this->logControle->log_up('5');
                // Mantém o nome original do arquivo
                $nome_final = $_FILES['arquivo']['name'];
            }
            if (move_uploaded_file($_FILES['arquivo']['tmp_name'], $_UP['pasta'] . $nome_final)) {
                if ($path['extension'] == 'mp4' || $path['extension'] == 'avi') {

                    // Depois verifica se é possível mover o arquivo para a pasta escolhida
                    // where ffmpeg is located
                    $ffmpeg = 'C:\ffmpeg';

//video dir
                    $video = $this->getParameter('web_dir') . '/web/uploads/' . $nome_final;
                    $semExt = explode(".", $nome_final);
//where to save the image
                    $image = $this->getParameter('web_dir') . '/web/uploads/' . $semExt[0] . '.png';

//time to take screenshot at
                    $interval = 5;

//screenshot size
                    $size = '640x480';

//ffmpeg command
                    $cmd = "ffmpeg -i $video -deinterlace -an -ss $interval -f mjpeg -t 1 -r 1 -y -s $size $image 2>&1";
                    exec($cmd);

                    $this->logControle->log_up($cmd);
                    $this->logControle->log_up('6');
                    return $this->render("insertContent.html.twig", array(
                                'nome' => $semExt[0] . '.png'));
                } else {
                    $semExt = explode(".", $nome_final);
                    return $this->render("insertContentImagem.html.twig", array(
                                'nome' => $semExt[0] . '.' . $path['extension']));
                }
                // Upload efetuado com sucesso, exibe uma mensagem e um link para o arquivo
                echo "Upload efetuado com sucesso!";
            } else {
                $this->logControle->log_up('7');
                // Não foi possível fazer o upload, provavelmente a pasta está incorreta
                echo "Não foi possível enviar o arquivo, tente novamente";
            }
        } else {
            $this->logControle->log_up('7');
            // Não foi possível fazer o upload, provavelmente a pasta está incorreta
            echo "Não foi possível enviar o arquivo, tente novamente";
            exit();
        }
    }

//    
//      $idActivityStudent = $this->get('session')->get('atividadeAtual');
//
//        $this->addAttachment($semExt[0], $idActivityStudent);

    /**
     * @Route("/addAttachmentVideo")
     */
    function addAttachmentVideo() {
        $this->logControle->log_up("addAttachmentVideo");

        $nomeFile = $_POST['nomeFile'];
        $this->logControle->log_up("nome do arquivo" . $nomeFile);

        $nomeFile = explode(".", $nomeFile);
        $nomeFile = $nomeFile[0];
        $this->logControle->log_up("nome do arquivo depois " . $nomeFile);
        $finderMP4 = new Finder();
        $finderMP4->name('' . $nomeFile . '.mp4');
        foreach ($finderMP4->in($this->getParameter('web_dir') . '/web/uploads') as $file) {
            // do something
            if ($file != NULL) {
                $this->logControle->log_up($file->getFilename() . "\n");
                $nomeFile = $file->getFilename();
                $this->logControle->log_up("é mp4");
            }
        }
        $finderAVI = new Finder();
        $finderAVI->name('' . $nomeFile . '.avi');
        foreach ($finderAVI->in($this->getParameter('web_dir') . '/web/uploads') as $file) {
            // do something

            if ($file != NULL) {
                $this->logControle->log_up($file->getFilename() . "\n");
                $nomeFile = $file->getFilename();
                $this->logControle->log_up("é avi");
            }
        }

        $idActivityStudent = $this->get('session')->get('atividadeAtual');
        $idUser = $this->get('session')->get('idUser');
        $objAtt = $this->getDoctrine()
                ->getRepository('AppBundle:TbAttachment')
                ->findOneBy(array('nmSystem' => $nomeFile));


        if ($objAtt) {
            $this->logControle->log_up("encontramos");
            return new Response();
        } else {
            $this->em = $this->getDoctrine()->getEntityManager();
            $this->logControle->log("id user " . $idUser);
            $objAttach = new TbAttachment();
            $objAttach->setTpAttachment('V');
            $objAttach->setNmFile($nomeFile);
            $objAttach->setNmSystem($file->getFilename());

            $objuser = $this->getDoctrine()
                    ->getRepository('AppBundle:TbUser')
                    ->findOneBy(array('idUser' => $idUser));

            $this->logControle->log_up("id user " . $idUser);
            $this->logControle->log_up("print " . print_r($objuser, true));
            $objAttach->setIdAuthor($objuser);

            $this->em->persist($objAttach);


            $idAttach = $objAttach->getIdAttachment();
            $this->em->flush();

            $queryBuilder = $this->em->createQueryBuilder();
            $queryBuilder
                    ->update('AppBundle:TbAttachment', 'a')
                    ->set('a.idAttachmentSrv', $queryBuilder->expr()->literal($idAttach))
                    ->where($queryBuilder->expr()->eq('a.idAttachment', $idAttach))
                    ->getQuery()
                    ->execute();


            $objActivity = $this->getDoctrine()
                    ->getRepository('AppBundle:TbActivityStudent')
                    ->findOneBy(array('idActivityStudent' => $idActivityStudent));

            $objAttachment = $this->getDoctrine()
                    ->getRepository('AppBundle:TbAttachment')
                    ->findOneBy(array('idAttachment' => $idAttach));

            $objAttachAct = new TbAttachActivity();
            $objAttachAct->setIdAttachment($objAttachment);
            $objAttachAct->setIdActivityStudent($objActivity);

            $this->em->persist($objAttachAct);
            $idAttachActivity = $objAttachAct->getIdAttachActivity();
            $this->em->flush();

            $nm_table = "tb_attach_activity";
            $idUser = $this->get('session')->get('idUser');

            AddSyncWebController::addSync($idAttachActivity, $idUser, $nm_table, $idActivityStudent);
            AddNoticeControllerWeb::addNoticeWeb($idAttachActivity, $idAttachActivity, $idActivityStudent, $nm_table, $idUser);


            return new Response();
        }
    }

    /**
     * @Route("/download/{filename}")
     */
    public function download($filename) {
        $response = new BinaryFileResponse('../web/uploads/' . $filename);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT);

        return $response;
    }

    /**
     * @Route("/addAttachmentImagem")
     */
    public function addAttachmentImagem() {
        $this->logControle->log_up("addAttachmentImagem");

        $nomeFile = $_POST['nomeFile'];
        $this->logControle->log_up("nome do arquivo" . $nomeFile);

        $nomeFile = explode(".", $nomeFile);
        $nomeFile = $nomeFile[0];
        $this->logControle->log_up("nome do arquivo depois " . $nomeFile);
        $idUser = $this->get('session')->get('idUser');
        $finderMP4 = new Finder();
        $finderMP4->name('' . $nomeFile . '*');
        $this->logControle->log_up("caminho " . $this->getParameter('web_dir'));
        foreach ($finderMP4->in($this->getParameter('web_dir') . '/web/uploads') as $file) {
            // do something
            if ($file != NULL) {
                $this->logControle->log_up($file->getFilename() . "\n");
                $this->logControle->log_up("encontramos esse arquivo " . $file->getFilename() . "\n");
                $nomeSyst = $file->getFilename();
            } else {
                $this->logControle->log_up("nao encontramos o arquivo");
            }
        }


        $objAtt = $this->getDoctrine()
                ->getRepository('AppBundle:TbAttachment')
                ->findOneBy(array('nmSystem' => $nomeSyst));


        if ($objAtt) {
            $this->logControle->log_up("encontramos");
            return new Response();
        } else {
            $this->logControle->log_up("vamos adicionar");
            $idActivityStudent = $this->get('session')->get('atividadeAtual');
            $this->em = $this->getDoctrine()->getEntityManager();

            $objAttach = new TbAttachment();
            $objAttach->setTpAttachment('I');



            $objuser = $this->getDoctrine()
                    ->getRepository('AppBundle:TbUser')
                    ->findOneBy(array('idUser' => $idUser));
            $objAttach->setIdAuthor($objuser);
            $this->logControle->log_up("adicionou author");
            $this->logControle->log(print_r($objuser, true));
//        try {
//            $objAttach->setIdAuthor($objuser);
//        } catch (Exception $e) {
//            $objAttach->setIdAuthor($objuser);
//        } finally {
//            $objAttach->setIdAuthor($objuser);
//        }

            $objAttach->setNmFile($nomeFile);
            $objAttach->setNmSystem($nomeSyst);

            $this->em->persist($objAttach);
            $idAttach = $objAttach->getIdAttachment();
            $this->em->flush();

            $queryBuilder = $this->em->createQueryBuilder();
            $queryBuilder
                    ->update('AppBundle:TbAttachment', 'a')
                    ->set('a.idAttachmentSrv', $queryBuilder->expr()->literal($idAttach))
                    ->where($queryBuilder->expr()->eq('a.idAttachment', $idAttach))
                    ->getQuery()
                    ->execute();


            $objActivity = $this->getDoctrine()
                    ->getRepository('AppBundle:TbActivityStudent')
                    ->findOneBy(array('idActivityStudent' => $idActivityStudent));

            $objAttachment = $this->getDoctrine()
                    ->getRepository('AppBundle:TbAttachment')
                    ->findOneBy(array('idAttachment' => $idAttach));

            $objAttachAct = new TbAttachActivity();
            $objAttachAct->setIdAttachment($objAttachment);
            $objAttachAct->setIdActivityStudent($objActivity);

            $this->em->persist($objAttachAct);
            $idAttachActivity = $objAttachAct->getIdAttachActivity();
            $this->em->flush();

            $nm_table = "tb_attach_activity";


            AddSyncWebController::addSync($idAttachActivity, $idUser, $nm_table, $idActivityStudent);
            AddNoticeControllerWeb::addNoticeWeb($idAttachActivity, $idAttachActivity, $idActivityStudent, $nm_table, $idUser);

            return new Response();
        }
    }

    /**
     * @Route("/verificaExt")
     */
    function verificaExt() {
        $this->logControle->log("verifica extet");
        $nomeFile = $_POST['nomeFile'];
        $this->logControle->log("nome da imagem" . $nomeFile);
        $nomeFile = explode("/", $nomeFile);
        $this->logControle->log(print_r($nomeFile, true));
        $nomeFile = end($nomeFile);
        $finderMP4 = new Finder();
        $finderMP4->name('' . $nomeFile . '.mp4');
        $nome = "";
        foreach ($finderMP4->in($this->getParameter('web_dir') . '/web/uploads') as $file) {
            // do something
            if ($file != NULL) {
                $this->logControle->log($file->getFilename() . "\n");
                $nome = $file->getFilename();
                $this->logControle->log("é mp4");
            }
        }

        $finderAVI = new Finder();
        $finderAVI->name('' . $nomeFile . '.avi');
        foreach ($finderAVI->in($this->getParameter('web_dir') . '/web/uploads') as $file) {
            // do something

            if ($file != NULL) {
                $this->logControle->log($file->getFilename() . "\n");
                $nome = $file->getFilename();
                $this->logControle->log("é avi");
            }
        }

        return new Response($nome);
    }

}
