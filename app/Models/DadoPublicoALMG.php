<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MongoDB\Laravel\Eloquent\Model;
use App\Utilities\Curl;

class DadoPublicoALMG extends Model
{
    use HasFactory;

    protected $connection = "mongodb";
    protected $collection = "dadospublicos";

    /**Função para realizar consultas no webservice da ALMG
     * @author Lucas Magalhães
     * @param string $url - URL a ser utilizada para consulta
     * @return object - Objeto com os dados retornados ou um objeto vazio no caso de erro
     */
    public function consultar(string $url) : object
    {
        try{
            $dados = Curl::getConsulta($url);
            if ($dados) {
                if (str_contains($url, "formato=json")) $dadosJson = json_decode($dados);
                else $dadosJson = json_decode(json_encode(simplexml_load_string($dados)));
            }else{
                return new \stdClass();
            }
        }catch(\Exception $e){
            return new \stdClass();
        }

        return $dadosJson;
    }

    /**Função para gravar em banco de dados os dados consumidos do webservice da ALMG
     * @author Lucas Magalhães
     * @param string $urlConsulta - URL que foi utilizada na consulta
     * @param string $dadosConsulta - Dados retornados na consulta
     * @return bool - Confirmação se a gravação em banco de dados teve sucesso ou não
     */
    public function gravar(string $urlConsulta, object $dadosConsulta) : bool
    {
        try {
            $consultaExistente = $this->where("url", $urlConsulta)->first();
            if (is_null($consultaExistente)){
                $this->url = $urlConsulta;
                $this->dados = $dadosConsulta;
                $this->save();
            }else{
                $this->where("url", $urlConsulta)->update(["dados" => $dadosConsulta]);
            }
            
        } catch (\Exception $e) {
            return false;
        }
        
        return true;
    }
}
