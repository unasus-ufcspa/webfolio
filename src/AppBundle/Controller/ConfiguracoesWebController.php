<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use AppBundle\Entity\TbUser;

header('Content-Type: text/html; charset=utf-8');

/**
 * Description of PortfoliosWebController
 *
 * @author Marilia
 */
class ConfiguracoesWebController extends Controller {

    public $em;
    public $response;
    public $error = array();

  public $logControle;
      

    public function __construct() {
       $this->logControle= new LogController();
    }
    public function addError($flag) {
        $status = array(
            1 => 'Erro no banco!',
            2 => 'Campos obrigatórios vazios!',
            3 => 'Senhas não conferem!',
            4 => 'Senha atual incorreta!',
            100 => "Senha alterada com sucesso!"
        );

        if ($flag == 100) {
            $array = array(
                "success" => $status[$flag]
            );
        } else {

            $array = array(
                "erro" => $status[$flag]
            );
        }
        return $array;
    }

    /**
     * @Route("/config")
     */
    public function config() {
        if (isset($_GET['error']))
            $this->logControle->log("------------------------------------------ERROR" . print_r($_GET['error'], true));

        $this->em = $this->getDoctrine()->getEntityManager();
        $idUser = $this->get('session')->get('idUser');
        if (empty($idUser)) {
            return $this->redirectToRoute('login');
        }
        $queryBuilderUser = $this->em->createQueryBuilder();
        $queryBuilderUser
                ->select('u')
                ->from('AppBundle:TbUser', 'u')
                ->where($queryBuilderUser->expr()->eq('u.idUser ', $idUser))
                ->getQuery()
                ->execute();

        $this->logControle->log($queryBuilderUser);
        $results = $queryBuilderUser->getQuery()->getArrayResult();
        $totalItens = count($results);
        $this->logControle->log(" RESULT TB USER ====== " . print_r($results, true));

        if ($totalItens > 0) {
            $select = "SELECT 
                            encode(im_photo::bytea, 'escape') as photo 
                        FROM 
                            tb_user
                        WHERE
                            id_user = " . $idUser;

            $this->logControle->log("selecct user: " . $select);
            $resultado = pg_query($this->logControle->db, $select);
            $this->logControle->log(" RESULTADO IMAGEM ------------------------------- : " . print_r($resultado['photo'], true));

            if (pg_affected_rows($resultado) > 0) {
                while ($row = pg_fetch_assoc($resultado)) {
                    $photo = $row['photo'];
                }
            }

            foreach ($results as $row) {
                $this->response = array(
                    'nm_user' => $row['nmUser'],
                    'nu_cellphone' => $row['nuCellphone'],
                    'foto' => $photo
                );
            }
        }
        $this->logControle->log("erros ---->" . print_r($this->error, true));
        return $this->render('configuracoes.html.twig', array('dados' => $this->response, 'erros' => $this->error));
    }

    /**
     * @Route("/atualizaInfoOne")
     */
    public function atualizaInfoOne() {
        $this->logControle->log("atualiza info one");

        $this->logControle->log("FILES: " . print_r($_FILES, true));

        if ((!isset($_FILES['fotoPerfil']) || ($_FILES['fotoPerfil']['size']) == 0)) {

            $nome = pg_escape_string($_POST['nome']);
            $this->logControle->log($nome);
            $telefone = $_POST['telefone'];
            $this->logControle->log($_POST['telefone']);


            $idUser = $this->get('session')->get('idUser');
            $sql_update = " UPDATE 
                                tb_user
                            SET 
                                nm_user =  '$nome',
                                nu_cellphone = '$telefone'
                           WHERE 
                               id_user = " . $idUser . "";

            $this->logControle->log("SQL BASE62 - BLOB " . $sql_update);
            $res_update = pg_query($this->logControle->db, $sql_update);

            $resultSync = (AddSyncWebController::updateUserDevSrv($idUser));


            if (!$res_update) {
                $flag = 1;
                $this->error[] = $this->addError($flag);
            }
        } else {
            // Pasta onde o arquivo vai ser salvo
            $_UP['pasta'] = "../web/uploads/";

            // Tamanho máximo do arquivo (em Bytes)
            $_UP['tamanho'] = 1024 * 1024 * 20; // 20Mb
            // Array com as extensões permitidas
            $_UP['extensoes'] = array('jpg', 'png', 'gif', 'jpeg');

            // Renomeia o arquivo? (Se true, o arquivo será salvo como .jpg e um nome único)
            $_UP['renomeia'] = false;

            // Array com os tipos de erros de upload do PHP
            $_UP['erros'][0] = 'Não houve erro';
            $_UP['erros'][1] = 'O arquivo no upload é maior do que o limite do PHP';
            $_UP['erros'][2] = 'O arquivo ultrapassa o limite de tamanho especifiado no HTML';
            $_UP['erros'][3] = 'O upload do arquivo foi feito parcialmente';
            $_UP['erros'][4] = 'Não foi feito o upload do arquivo';

            // Verifica se houve algum erro com o upload. Se sim, exibe a mensagem do erro
            if ($_FILES['fotoPerfil']['error'] != 0) {
                $this->logControle->log_up('1');
                die("Não foi possível fazer o upload, erro:" . $_UP['erros'][$_FILES['fotoPerfil']['error']]);
                exit; // Para a execução do script
            }

            // Faz a verificação do tamanho do arquivo
            if ($_UP['tamanho'] < $_FILES['fotoPerfil']['size']) {
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
                $nome_final = $_FILES['fotoPerfil']['name'];
            }

            // Depois verifica se é possível mover o arquivo para a pasta escolhida
            if (move_uploaded_file($_FILES['fotoPerfil']['tmp_name'], $_UP['pasta'] . $nome_final)) {
                $this->logControle->log_up('6');
                // Upload efetuado com sucesso, exibe uma mensagem e um link para o arquivo
                echo "Upload efetuado com sucesso!";

                $this->logControle->log($_POST['nome']);
                 $nome = pg_escape_string($_POST['nome']);
                $telefone = $_POST['telefone'];
                $this->logControle->log($_POST['telefone']);


                $bin_string = file_get_contents("../web/uploads/" . $_FILES["fotoPerfil"]["name"]);
                $hex_string = base64_encode($bin_string);

                $idUser = $this->get('session')->get('idUser');
                $sql_update = " UPDATE 
                                tb_user
                            SET 
                                im_photo='" . pg_escape_bytea($hex_string) . "',
                                nm_user =  '$nome',
                                nu_cellphone = '$telefone'
                           WHERE 
                               id_user = " . $idUser . "";

                $this->logControle->log("SQL BASE62 - BLOB " . $sql_update);
                $res_update = pg_query($this->logControle->db, $sql_update);

                $resultSync = (AddSyncWebController::updateUserDevSrv($idUser));

                echo '<a href="' . $this->getParameter('web_dir') . 'download/' . $nome_final . '">Clique aqui para acessar o arquivo</a>';
            } else {
                $this->logControle->log_up('7');
                // Não foi possível fazer o upload, provavelmente a pasta está incorreta
                echo "Não foi possível enviar o arquivo, tente novamente";
            }
        }

        return $this->redirectToRoute('config');
    }

    /**
     * @Route("/atualizaInfoTwo")
     */
    public function atualizaInfoTwo() {
        $this->error = array();
        $senhaAtual = $_POST['senhaAtual'];
        $novaSenha = $_POST['novaSenha'];
        $confirma = $_POST['confirmarSenha'];

        $idUser = $this->get('session')->get('idUser');
        $idUserObj = $this->getDoctrine()
                ->getRepository('AppBundle:TbUser')
                ->findOneBy(array('idUser' => $idUser));

        $senhaAntigaBD = $idUserObj->getDsPassword();
        $this->em = $this->getDoctrine()->getEntityManager();
        $senhaAtual = hash('sha256', $senhaAtual);
        if ($senhaAtual == $senhaAntigaBD) {
            if ($novaSenha == $confirma) {
                $novaSenhaSha = hash('sha256', $novaSenha);
                $this->logControle->log($novaSenhaSha);
                $queryBuilder = $this->em->createQueryBuilder();
                $queryBuilder
                        ->update('AppBundle:TbUser', 'u')
                        ->set('u.dsPassword', $queryBuilder->expr()->literal($novaSenhaSha))
                        ->where($queryBuilder->expr()->eq('u.idUser', $idUser))
                        ->getQuery()
                        ->execute();
                $this->error = $this->addError(100);
                $resultSync = (AddSyncWebController::updateUserDevSrv($idUser));
            } else {
                $this->logControle->log("as senhas nao conferem");

                $flag = 3;
                $this->error = $this->addError($flag);
            }
        } else {
            $this->logControle->log("A senha antiga esta incorreta");

            $flag = 4;
            $this->error = $this->addError($flag);
        }

        //    $this->logControle->log("erros ---->" . print_r($this->error, true));
//        return $this->redirectToRoute('config', array(
//                    'error' => $this->error,
//        307));
        return new JsonResponse($this->error);
    }

}
