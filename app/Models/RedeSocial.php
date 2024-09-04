<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Utilities\Curl;

class RedeSocial extends Model
{
    use HasFactory;

    /**Função para verificar as redes sociais utilizadas pelos deputados
     * @author Lucas Magalhães
     * @return array - Lista de redes sociais ou array vazio no caso de erro
     */
    public function getRedesSociaisDeputados() : array
    {
        $redesSociaisUtilizadas = [];
        try{
            $url = "https://dadosabertos.almg.gov.br/ws/deputados/lista_telefonica";
            $dados = Curl::getConsulta($url);
            if ($dados) {
                $dadosJson = json_decode(json_encode(simplexml_load_string($dados)));
                foreach($dadosJson->contato as $deputado){
                    if (!get_object_vars($deputado->redesSociais)) continue;
                    $redesSociaisDeputado = $deputado->redesSociais->redeSocialDeputado;
                    if (is_array($redesSociaisDeputado)){
                        foreach($redesSociaisDeputado as $item){
                            array_push($redesSociaisUtilizadas, $item->redeSocial);
                        }
                    }else{
                        array_push($redesSociaisUtilizadas, $redesSociaisDeputado->redeSocial);
                    }
                }
            }else{
                return [];
            }
        }catch(\Exception $e){
            return [];
        }
        
        return $redesSociaisUtilizadas;
    }

    /**Função para contabilizar e ordenar as redes sociais utilizadas pelos deputados
     * @author Lucas Magalhães
     * @param string redesSociais - Lista das redes sociais
     * @return array - Array com a listagem das redes sociais ordenada pelas mais utilizadas
     */
    public function getRanking(array $redesSociais) : array
    {
        $grupos = collect($redesSociais)->groupBy("id")->all();
        $grupos = array_slice($grupos, 0);
        $ranking = [];
        foreach($grupos as $redeSocial){
            array_push($ranking, [
                "redeSocial" => $redeSocial[0]->nome,
                "qtde" => count($redeSocial)
            ]);
        }
        return array_slice(collect($ranking)->sortBy([["qtde", "desc"]])->all(), 0);
    }
}
