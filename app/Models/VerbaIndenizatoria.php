<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Utilities\Curl;

class VerbaIndenizatoria extends Model
{
    use HasFactory;

    const MESES_ANO = [
        1 => "Janeiro",
        2 => "Fevereiro",
        3 => "Março",
        4 => "Abril",
        5 => "Maio",
        6 => "Junho",
        7 => "Julho",
        8 => "Agosto",
        9 => "Setembro",
        10 => "Outubro",
        11 => "Novembro",
        12 => "Dezembro",
    ];

    /**Função para verificar quais Legislaturas estão incluídas naquele ano.
     * As legislaturas se encerram no dia 31/01 (no ano devido) e se iniciam no dia 01/02
     * @author Lucas Magalhães
     * @param int $ano - Ano a ser utilizado para consulta
     * @return array - Retorna o id ou id's da(s) legislatura(s)
     */
    public function getLegislaturas(int $ano) : array
    {
        $dataFinalMandato = $ano."-01-31";
        $dataInicialMandato = $ano."-02-01";
        $idLegislaturas = [];
        try{
            $url = "https://dadosabertos.almg.gov.br/ws/legislaturas/lista";
            $dados = Curl::getConsulta($url);
            if ($dados) {
                $dadosJson = json_decode(json_encode(simplexml_load_string($dados)));
                foreach($dadosJson->legislatura as $legislatura){
                    if ($legislatura->dataInicio == $dataInicialMandato || $legislatura->dataTermino == $dataFinalMandato){
                        if (!in_array($legislatura->id, $idLegislaturas)) array_push($idLegislaturas, $legislatura->id);
                    }
                }
            }else{
                return [];
            }
        }catch(\Exception $e){
            return [];
        }

        return $idLegislaturas;
    }

    /**Função para verificar quais deputados estão incluídos na legislatura informada.
     * @author Lucas Magalhães
     * @param int $idLeg - Id da legislatura
     * @return array - Retorna a lista de deputados da legislatura passada ou um array vazio no caso de erro
     */
    public function getDeputadosLegislatura(int $idLeg) : array
    {
        $deputados = [];
        try{
            $url = "https://dadosabertos.almg.gov.br/ws/legislaturas/{$idLeg}/deputados/em_exercicio";
            $dados = Curl::getConsulta($url);
            if ($dados) {
                $dadosJson = json_decode(json_encode(simplexml_load_string($dados)));
                foreach($dadosJson->deputado as $deputado){
                    array_push($deputados, [
                                            "id"    => $deputado->id,
                                            "nome"  => $deputado->nome
                                        ]);
                }
            }else{
                return [];
            }
        }catch(\Exception $e){
            return [];
        }

        return $deputados;
    }

    /**Função para verificar em quais meses do ano o deputado pediu reembolso.
     * @author Lucas Magalhães
     * @param int $idDep - Id do deputado
     * @param int $ano - Ano a ser utilizado para consulta
     * @return array - Retorna os meses encontrado ou [] em caso de erro ou se o deputado não pediu reembolso
     */
    private function checkMesesPedidoReembolsoDeputado($idDep, $ano) : array
    {
        $mesesPedidoReembolso = [];
        try{
            $url = "https://dadosabertos.almg.gov.br/ws/prestacao_contas/verbas_indenizatorias/deputados/{$idDep}/datas";
            $dados = Curl::getConsulta($url);
            if ($dados) {
                $pedidosReembolso = json_decode(json_encode(simplexml_load_string($dados)));
                $fechamentoVerba = $pedidosReembolso->fechamentoVerba;
                if (!is_null($fechamentoVerba) && count($fechamentoVerba) > 0){
                    $datasReembolso = array_map(function ($item){
                        return $item->dataReferencia;
                    }, $fechamentoVerba);

                    $datasAno = array_filter($datasReembolso, function ($item) use ($ano){
                        return getDate(strtotime($item))["year"] == $ano;
                    });

                    $mesesPedidoReembolso = array_map(function ($item){
                        return getDate(strtotime($item))["mon"];
                    }, $datasAno);
                }
            }else{
                return [];
            }
        }catch(\Exception $e){
            return [];
        }

        return $mesesPedidoReembolso;
    }

    /**Função para verificar o total de reembolso das verbas que os deputados solicitaram, separados por mês
     * @author Lucas Magalhães
     * @param array $idsDeputados - Id's dos deputados
     * @param int $ano - Ano a ser utilizado para consulta
     * @return array - Retorna a totalização dos valores solicitados pelos deputados, separados por mês
     */
    public function getPedidoReembolso(array $idsDeputados, int $ano) : array
    {
        ini_set("max_execution_time", 120);
        $pedidosReembolso = [];
        foreach($idsDeputados as $idDep){
            $mesesPedidoReembolso = $this->checkMesesPedidoReembolsoDeputado($idDep, $ano);
            if (!is_null($mesesPedidoReembolso) && count($mesesPedidoReembolso) > 0){
                $pedidosReembolso[$idDep] = [];
                foreach($mesesPedidoReembolso as $mes){
                    try{
                        $url = "https://dadosabertos.almg.gov.br/ws/prestacao_contas/verbas_indenizatorias/deputados/{$idDep}/{$ano}/{$mes}?formato=json";
                        $dados = Curl::getConsulta($url);
                        if ($dados) {
                            $dadosVerbasMes = json_decode($dados)->list ?? null;
                            $total = 0;
                            if (!is_null($dadosVerbasMes) && count($dadosVerbasMes) > 0){
                                $total = array_reduce($dadosVerbasMes, function ($soma, $item){
                                    return $soma + (number_format(intval($item->valor), 2, ".", ""));
                                });
                            }
    
                            array_push($pedidosReembolso[$idDep], [
                                "mes" => $mes,
                                "total" => $total
                            ]);
                            
                        }else{
                            return [];
                        }
                    }catch(\Exception $e){
                        return [];
                    }
                }
            }
        }

        return $pedidosReembolso;
    }

    /**Função para retornar os deputados que mais pediram reembolso por mês
     * @author Lucas Magalhães
     * @param array $pedidosReembolso - Lista de pedidos de reembolso dos deputados
     * @param array $listaDeputados - Lista de deputados
     * @return array - Retorna os 5 deputados que mais pediram o reembolso por mês
     */
    public function getDeputadosMaisGastadores(array $pedidosReembolso, array $listaDeputados) : array
    {
        $dadosGerais = [];
        foreach(self::MESES_ANO as $numMes => $descMes){
            $dados = [];
            $dados[$numMes] = $descMes;
            $dados["listagem"] = [];
            foreach($pedidosReembolso as $idDep => $dadosDep){
                $nomeDeputado = $this->getNomeDeputado($listaDeputados, $idDep);
                foreach($dadosDep as $dadosMes){
                    if ($numMes == $dadosMes["mes"]) array_push($dados["listagem"], ["idDeputado" => $idDep, "nomeDeputado" => $nomeDeputado, "valor" => number_format($dadosMes["total"], 2, ".", "")]);
                }
            }
            $dados["listagem"] = collect($dados["listagem"])->sortBy([["valor", "desc"]])->slice(0,5)/* ->all() */;           
            $dados["listagem"] = array_slice($dados["listagem"]->toArray(), 0);
            array_push($dadosGerais, $dados);
        }
        
        return $dadosGerais;
    }

    /**Função para retornar retornar o nome do deputado
     * @author Lucas Magalhães
     * @param array $listaDeputados - Lista de deputados
     * @param array $idDeputado - Id do deputador
     * @return string - Retorna o nome do deputado
     */
    private function getNomeDeputado($listaDeputados, $idDeputado) : string
    {
        $dadosDeputado = array_filter($listaDeputados, function ($dep) use ($idDeputado){
            return $dep["id"] == $idDeputado;
        });

        return array_slice($dadosDeputado, 0)[0]["nome"];
    }
}
