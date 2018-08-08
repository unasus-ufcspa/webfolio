<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use AppBundle\Controller\IdDeviceSeqController;
use AppBundle\Entity\TbUser;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use AppBundle\Entity\TbAttachment;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;

/**
 * Description of policyController
 *
 * @author Zago
 */
class AnexosWebController extends Controller {

    public $logControle;
    public $em;

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
     * @Route("/carregaAnexos")
     */
    public function carregaAnexos() {
        $this->logControle->logWeb("AnexosWebController::carregaAnexos");
        $anexos = null;
        $isVisitante = false;
        $idUser = $this->get('session')->get('idUser');
        if (empty($idUser)) {
            return $this->redirectToRoute('login');
        }
        $retornoVisitante = VisitanteController::verificarVisitante($idUser);

        if ($retornoVisitante) {
            $retornoOutroTipoUsuario = PortfolioStudentController::selecionarPortfolioStudent($idUser);
            if ($retornoOutroTipoUsuario) {
                $this->logControle->logWeb("É VISITANTE E OUTRO TIPO DE USUARIO");
                $isVisitante = false;
            } else {
                $this->logControle->logWeb("É VISITANTE");
                $isVisitante = true;
            }

            $anexosVisitante = $this->selecionarAnexosVisitante($retornoVisitante);

            if (!empty($anexosVisitante)) {
                $anexos = $anexosVisitante;
            }
        }

        $anexosUsuarios = $this->selecionarAnexos($idUser);
        if (!empty($anexosUsuarios)) {
            $anexos[] = $anexosUsuarios;
        }


        $this->logControle->logWeb("Anexos para exibição: " . print_r($anexos, true));
        $this->logControle->logWeb("---------------------- fim carregamento anexos  ----------------------------");
        return $this->render('arquivos.html.twig', array(
                    'anexos' => $anexos, 'flagVisitante' => $isVisitante));
    }

    public function selecionarAnexosVisitante($retornoVisitante) {
        $arrayAnexosTodosUsuarios = array();
        foreach ($retornoVisitante as $visitante) {
            $idUsuarios = VisitanteController::carregarUsuariosTurmas($visitante);
            foreach ($idUsuarios as $idUser) {
                $anexosPorUsuario = $this->selecionarAnexos($idUser);
                if (!empty($anexosPorUsuario)) {
                    $arrayAnexosTodosUsuarios[] = $anexosPorUsuario;
                }
            }
        }
        return $arrayAnexosTodosUsuarios;
    }

    public function selecionarAnexos($idUser) {
        $resp = array();
        $this->em = $this->getDoctrine()->getEntityManager();

        $queryBuilderAnexoAtt = $this->em->createQueryBuilder();
        $queryBuilderAnexoAtt
                ->select('at')
                ->from('AppBundle:TbAttachment', 'at')
                ->innerJoin('at.idAuthor', 'u', 'WITH', 'u.idUser = at.idAuthor')
                ->where($queryBuilderAnexoAtt->expr()->eq('at.idAuthor', $idUser))
                ->getQuery()
                ->execute();
        $anexosGerais = $queryBuilderAnexoAtt->getQuery()->getArrayResult();

        foreach ($anexosGerais as $value) {
            $resp[] = array(
                'tpAttachment' => $value['tpAttachment'],
                'nmSystem' => $value['nmSystem'],
                'nmFile' => $value['nmFile'],
            );
        }
        return $resp;
    }

    public function uploadVideo($file) {
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
        $extAtual = explode('.', $file['video']['name']);

        $_UP['extensoes'] = array('mp4', 'avi');

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
            if ($file['video']['error'] != 0) {
                $this->logControle->log_up('1');
                die("Não foi possível fazer o upload, erro:" . $_UP['erros'][$file['video']['error']]);
                exit; // Para a execução do script
            }

            // Faz a verificação do tamanho do arquivo
            if ($_UP['tamanho'] < $file['video']['size']) {
                $this->logControle->log_up('3');
                echo "O arquivo enviado é muito grande, envie arquivos de até 2Mb.";
                exit;
            }

            // O arquivo passou em todas as verificações, hora de tentar movê-lo para a pasta
            // Primeiro verifica se deve trocar o nome do arquivo
            if ($_UP['renomeia'] == true) {
                $this->logControle->log_up('4');
                // Cria um nome baseado no UNIX TIMESTAMP atual e com extensão .jpg
                $path = pathinfo($_FILES['video']['name']);
                $path['extension'];

                $data = date("Ymd");
                $hora = date("Hms");
                $nome_final = $idUser . '_' . $data . '_' . $hora . '.' . $path['extension'];
            } else {
                $this->logControle->log_up('5');
                // Mantém o nome original do arquivo
                $nome_final = $file['video']['name'];
            }

            // Depois verifica se é possível mover o arquivo para a pasta escolhida
            if (move_uploaded_file($file['video']['tmp_name'], $_UP['pasta'] . $nome_final)) {
                $this->logControle->log_up('6');
                // Upload efetuado com sucesso, exibe uma mensagem e um link para o arquivo
                echo "Upload efetuado com sucesso!";
                $tipo = 'V';

                $this->addAttachment($tipo, $nome_final);
                return true;
            } else {
                $this->logControle->log_up('7');
                // Não foi possível fazer o upload, provavelmente a pasta está incorreta
                echo "Não foi possível enviar o arquivo, tente novamente";
                return false;
            }
        } else {
            $imagens = array('png', 'jpeg', 'jpg', 'gif');
            $documentos = array('pdf', 'txt');
            if (in_array($extAtual[1], $imagens)) {
                $this->uploadImagem($file);
            } else {
                if (in_array($extAtual[1], $documentos)) {
                    $this->uploadArquivo($file);
                } else {
                    $this->logControle->log_up('extensao nao permitida');
                    return false;
                }
            }
        }
    }

    function addAttachment($tipo, $nomeArquivo) {
        $idUser = $this->get('session')->get('idUser');
        if (empty($idUser)) {
            return $this->redirectToRoute('login');
        }
        $objAtt = $this->getDoctrine()
                ->getRepository('AppBundle:TbAttachment')
                ->findOneBy(array('nmSystem' => $nomeArquivo));


        if ($objAtt) {
            $this->logControle->log_up("encontramos");
        } else {


            $this->logControle->logWeb("addAttachment");
            $semEx = explode('.', $nomeArquivo);

            $this->em = $this->getDoctrine()->getEntityManager();

            $objAttach = new TbAttachment();
            $objAttach->setTpAttachment($tipo);

            $objAttach->setNmFile($semEx[0]);
            $objAttach->setNmSystem($nomeArquivo);


            $idUserObj = $this->getDoctrine()
                    ->getRepository('AppBundle:TbUser')
                    ->findOneBy(array('idUser' => $idUser));

            $objAttach->setIdAuthor($idUserObj);

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
        }
    }

    /**
     * @Route("/uploadGeral")
     */
    public function uploadGeral() {
        $idUser = $this->get('session')->get('idUser');
        if (empty($idUser)) {
            return $this->redirectToRoute('login');
        }
        $this->logControle->log_up("upload geral");
        $this->logControle->log_up("FILES: " . print_r($_FILES, true));
        if (!empty($_FILES['video']['name'])) {
            $retorno = $this->uploadVideo($_FILES);
        }

        if (!empty($_FILES['imagem']['name'])) {
            $retorno = $this->uploadImagem($_FILES);
        }

        if (!empty($_FILES['arquivo']['name'])) {
            $retorno = $this->uploadArquivo($_FILES);
        }
        return $this->redirectToRoute('carregaAnexos');
        //  return $this->carregaAnexos($retorno);
    }

    public function uploadArquivo($file) {
        $this->logControle->log_up("Arquivo para Upload: " . print_r($file, true));
        date_default_timezone_set('America/Sao_Paulo');
        $idUser = $this->get('session')->get('idUser');
        // Pasta onde o arquivo vai ser salvo
        $_UP['pasta'] = "../web/uploads/";

        // Tamanho máximo do arquivo (em Bytes)
        $_UP['tamanho'] = 1024 * 1024 * 20 * 20; // 20Mb
        // Array com as extensões permitidas
        $_UP['extensoes'] = array('pdf', 'txt');

        $extAtual = explode('.', $file['arquivo']['name']);


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
            if ($file['arquivo']['error'] != 0) {
                $this->logControle->log_up('1');
                die("Não foi possível fazer o upload, erro:" . $_UP['erros'][$file['arquivo']['error']]);
                exit; // Para a execução do script
            }

            // Faz a verificação do tamanho do arquivo
            if ($_UP['tamanho'] < $file['arquivo']['size']) {
                $this->logControle->log_up('3');
                echo "O arquivo enviado é muito grande, envie arquivos de até 2Mb.";
                exit;
            }

            // O arquivo passou em todas as verificações, hora de tentar movê-lo para a pasta
            // Primeiro verifica se deve trocar o nome do arquivo
            if ($_UP['renomeia'] == true) {
                $this->logControle->log_up('4');

                // Cria um nome baseado no UNIX TIMESTAMP atual e com extensão .jpg

                $path = pathinfo($_FILES['arquivo']['name']);
                $path['extension'];

                $data = date("Ymd");
                $hora = date("Hms");
                $nome_final = $idUser . '_' . $data . '_' . $hora . '.' . $path['extension'];
            } else {
                $this->logControle->log_up('5');
                // Mantém o nome original do arquivo
                $nome_final = $file['arquivo']['name'];
            }

            // Depois verifica se é possível mover o arquivo para a pasta escolhida
            if (move_uploaded_file($file['arquivo']['tmp_name'], $_UP['pasta'] . $nome_final)) {
                $this->logControle->log_up('6');
                // Upload efetuado com sucesso, exibe uma mensagem e um link para o arquivo
                echo "Upload efetuado com sucesso!";
                $tipo = 'T';

                $this->addAttachment($tipo, $nome_final);
                return true;
            } else {
                $this->logControle->log_up('7');
                // Não foi possível fazer o upload, provavelmente a pasta está incorreta
                echo "Não foi possível enviar o arquivo, tente novamente";
                return false;
            }
        } else {
            $imagens = array('png', 'jpeg', 'jpg', 'gif');
            $videos = array('mp4', 'avi');
            if (in_array($extAtual[1], $imagens)) {
                $this->uploadImagem($file);
            } else {
                if (in_array($extAtual[1], $videos)) {
                    $this->uploadVideo($file);
                } else {
                    $this->logControle->log_up('extensao nao permitida');
                    return false;
                }
            }
        }
    }

    public function uploadImagem($file) {
        $idUser = $this->get('session')->get('idUser');
        date_default_timezone_set('America/Sao_Paulo');

        if (empty($idUser)) {
            return $this->redirectToRoute('login');
        }
        $this->logControle->log_up("Imagem para upload: " . print_r($file, true));

        // Pasta onde o arquivo vai ser salvo
        $_UP['pasta'] = "../web/uploads/";

        // Tamanho máximo do arquivo (em Bytes)
        $_UP['tamanho'] = 1024 * 1024 * 20 * 20; // 20Mb
        // Array com as extensões permitidas
        $_UP['extensoes'] = array('png', 'jpeg', 'jpg', 'gif');

        $extAtual = explode('.', $file['imagem']['name']);
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
            if ($file['imagem']['error'] != 0) {
                $this->logControle->log_up('1');
                die("Não foi possível fazer o upload, erro:" . $_UP['erros'][$file['imagem']['error']]);
                exit; // Para a execução do script
            }

            // Faz a verificação do tamanho do arquivo
            if ($_UP['tamanho'] < $file['imagem']['size']) {
                $this->logControle->log_up('3');
                echo "O arquivo enviado é muito grande, envie arquivos de até 2Mb.";
                exit;
            }

            // O arquivo passou em todas as verificações, hora de tentar movê-lo para a pasta
            // Primeiro verifica se deve trocar o nome do arquivo
            if ($_UP['renomeia'] == true) {
                $this->logControle->log_up('4');
                $path = pathinfo($_FILES['imagem']['name']);
                $path['extension'];

                $data = date("Ymd");
                $hora = date("Hms");
                $nome_final = $idUser . '_' . $data . '_' . $hora . '.' . $path['extension'];

                // Cria um nome baseado no UNIX TIMESTAMP atual e com extensão .jpg
            } else {
                $this->logControle->log_up('5');
                // Mantém o nome original do arquivo
                $nome_final = $file['imagem']['name'];
            }

            // Depois verifica se é possível mover o arquivo para a pasta escolhida
            if (move_uploaded_file($file['imagem']['tmp_name'], $_UP['pasta'] . $nome_final)) {
                $this->logControle->log_up('6');
                // Upload efetuado com sucesso, exibe uma mensagem e um link para o arquivo

                $tipo = 'I';

                $this->addAttachment($tipo, $nome_final);
                return true;
            } else {
                $this->logControle->log_up('7');
                // Não foi possível fazer o upload, provavelmente a pasta está incorreta
                echo "Não foi possível enviar o arquivo, tente novamente";
                return false;
            }
        } else {
            $documentos = array('pdf', 'txt');
            $videos = array('mp4', 'avi');
            if (in_array($extAtual[1], $documentos)) {
                $this->uploadArquivo($file);
            } else {
                if (in_array($extAtual[1], $videos)) {
                    $this->uploadVideo($file);
                } else {
                    $this->logControle->log_up('extensao nao permitida');
                    return false;
                }
            }
        }
    }

}
