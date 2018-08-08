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
use AppBundle\Entity\TbClass;
use AppBundle\Entity\TbPortfolioClass;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;

/**
 * Description of SyncConvidado
 *
 * @author Marilia
 */
class SyncVisitanteController extends Controller {

    public $em;
    private $response;
    private $db;
    public $logControle;
    public $results;
    public $flComments;
    public $idUsuariosSincronismo = array();
    public $arr_data = array();
    public $id_user;

    public function __construct() {
        $this->logControle = new LogController();
    }

    public function addError($flag) {
        $status = array(
            1 => 'Erro no banco!',
            2 => 'Campos obrigatÃ³rios vazios!',
            3 => 'Json vazio!',
            4 => 'Nenhum json recebido pelo servidor!',
            5 => 'UsuÃ¡rio nÃ£o localizado!',
            6 => 'UsuÃ¡rio sem portFolio cadastrado!',
            7 => 'Nenhum dado foi econtrado no Banco de Dados!',
            8 => 'NÃ£o hÃ¡ dados para sincronizar!',
            9 => 'IdDevice/tpDevice nÃ£o pode estar vazio!',
            10 => 'Falha ao atualizar tabela!',
            11 => 'Falha na inserÃ§Ã£o dos dados no Banco de Dados',
            12 => 'Basic Data ja foi sincronizado!',
            13 => 'First Login ja foi sincronizado!',
            14 => 'Falha na inserÃ§Ã£o dos dados para sincronismo!'
        );

        $array = array(
            "erro" => $status[$flag]
        );
        return $array;
    }

    /**
     * @Route("/syncVisitante")
     */
    public function syncVisitante(Request $req) {
        $this->logControle->log("==================INICIO SYNC VISITANTE==================");

        $this->em = $this->getDoctrine()->getEntityManager();
        if (0 === strpos($req->headers->get('Content-Type'), 'application/json')) {
            $data = json_decode($req->getContent(), true);
            $req->request->replace(is_array($data) ? $data : array());

            if ((!empty($data)) && (!empty($data['syncVisitante_request']))) {
                $this->logControle->log("REQUEST syncVisitante" . print_r($data, true));
                $this->id_user = $data['syncVisitante_request']['id_user'];
                $resultadoVisitante = VisitanteController::verificarVisitante($this->id_user);
                $this->logControle->log("sync visitante verificar visitante");
                $this->logControle->log(print_r($resultadoVisitante, true));
                foreach ($resultadoVisitante as $visitante) {
                    $this->carregarDadosTurma($visitante);
                }
                $this->selecionarSincronismo();
                if (!empty($this->arr_data)) {
                    $this->results['SyncVisitante_response']['data'] = $this->arr_data;
                } else {

                    $this->logControle->log(" ----  OCORREU UM ERRO NO BANCO --- " . pg_last_error($this->logControle->db));
                    $flag = 8;
                    $this->error[] = $this->addError($flag);
                    $this->results['SyncVisitante_response']['data']['error'] = $this->error;
                }
            } else {
                $this->logControle->log('Campos vazios!');
                $flag = 2;
                $this->error[] = $this->addError($flag);
                $this->results["SyncVisitante_response"]["error"] = $this->error;
            }
        } else {
            $this->logControle->log('Campos vazios!');
            $flag = 4;
            $this->error[] = $this->addError($flag);
            $this->results["SyncVisitante_response"]["error"] = $this->error;
        }
        $this->response = $this->results;

        $this->logControle->log("==============================================================================");
        return new JsonResponse($this->response);
    }

    public function carregarDadosTurma($visitante) {
        $this->flComments = $visitante['flComments'];


        $arrayIdPortfolioClass = $this->getIdPortfolioClassByClass($visitante['idClass']['idClass']);
        $this->logControle->log("teste");
        $this->logControle->log(print_r($arrayIdPortfolioClass, true));
        foreach ($arrayIdPortfolioClass as $idPortfolioClass) {
            $retornoPortfolioStudent_tutorPortfolio_byPC = PortfolioStudentController::selecionarPortfolioStudentByPortfolioClass($idPortfolioClass['idPortfolioClass']);


            foreach ($retornoPortfolioStudent_tutorPortfolio_byPC as $valueArray) {
                if (!in_array($valueArray['idTutor']['idUser'], $this->idUsuariosSincronismo)) {
                    $this->idUsuariosSincronismo[] = $valueArray['idTutor']['idUser'];
                }
                if (!in_array($valueArray['idPortfolioStudent']['idStudent']['idUser'], $this->idUsuariosSincronismo)) {
                    $this->idUsuariosSincronismo[] = $valueArray['idPortfolioStudent']['idStudent']['idUser'];
                }
            }
        }
    }

    function getIdPortfolioClassByClass($idClass) {

        $this->em = $this->getDoctrine()->getEntityManager();

        $queryBuilder = $this->em->createQueryBuilder();
        $queryBuilder
                ->select('pc')
                ->from('AppBundle:TbPortfolioClass', 'pc')
                ->innerJoin('pc.idClass', 'c', 'WITH', 'pc.idClass = c.idClass')
                ->where($queryBuilder->expr()->eq('pc.idClass', $idClass))
                ->getQuery()
                ->execute();
        $results = $queryBuilder->getQuery()->getArrayResult();
        $this->logControle->log("TbPortfolioClass : " . print_r($results, true));

        return $results;
    }

    function selecionarSincronismo() {
        foreach ($this->idUsuariosSincronismo as $idUserSincronismo) {
            $queryBuilderSync = $this->em->createQueryBuilder();
            $queryBuilderSync
                    ->select('s, a')
                    ->from('AppBundle:TbSync', 's')
                    ->innerJoin('s.idActivityStudent', 'a')
                    ->where($queryBuilderSync->expr()->eq('s.idAuthor', $idUserSincronismo))
                    ->orderBy('s.coIdTable', 'ASC')
                    ->getQuery()
                    ->execute();

            $resultadoSync = $queryBuilderSync->getQuery()->getArrayResult();

            foreach ($resultadoSync as $valueArray) {
                $nomeTabela = $valueArray['nmTable'];
                $nomeFuncao = "select_" . $nomeTabela;
                $this->logControle->log("FL COMMENTS -------" . $this->flComments);
                if ($nomeTabela == 'tb_user') {
                    $this->selecionarSyncUsuariosSemRepeticao($nomeFuncao, $nomeTabela, $valueArray);
                }

                if ($this->flComments == 'S') {
                    $this->gerarJsonSyncVisitante($nomeFuncao, $nomeTabela, $valueArray);
                } else {
                    //sem permissão para as tabelas comment e comment version
                    $this->semPermissaoComentarios($nomeFuncao, $nomeTabela, $valueArray);
                }
            }
        }
    }

    function semPermissaoComentarios($nomeFuncao, $nomeTabela, $valueArray) {
        if ($nomeTabela != 'tb_comment' && $nomeTabela != 'tb_comment_version') {
            $this->gerarJsonSyncVisitante($nomeFuncao, $nomeTabela, $valueArray);
        }
        $this->arr_data["annotation"]["tb_annotation"] = AnnotationsController::selecionarAnotacoes($this->id_user);
    }

    function gerarJsonSyncVisitante($nomeFuncao, $nomeTabela, $valueArray) {
        $this->logControle->log("Nome tabela " . $nomeTabela);

        $retorno = FirstSyncController::$nomeFuncao($valueArray);
        if (!empty($retorno)) {
            $this->arr_data[substr($nomeTabela, 3)][$nomeTabela][] = $retorno;
        }
    }

    function selecionarSyncUsuariosSemRepeticao($nomeFuncao, $nomeTabela, $valueArray) {

        if ((!in_array($valueArray['coIdTable'], $ids_users))) {
            $ids_users[] = $valueArray['coIdTable'];
            $retorno = FirstSyncController::$nomeFuncao($valueArray);
            if (!empty($retorno)) {
                $this->arr_data[substr($nomeTabela, 3)][$nomeTabela][] = $retorno;
            }
        }
    }

}
