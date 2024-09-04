<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\DadoPublicoALMG;

class DadosPublicosALMGController extends Controller
{
    const PREFIXO_URL = ["http://dadosabertos.almg.gov.br/ws/", "https://dadosabertos.almg.gov.br/ws/"];

    /**Função para listar os dados consumidos no webservice da ALMG
     * @author Lucas Magalhães
     * @param object $request - Objeto que fornecerá a URL a ser utilizada para consulta
     * @param object $orcamentos - Model que chamará a função as funções consulta dos dados e gravação no banco de dados
     * @return string JSON - Mensagem de confirmação ou erro
     */
    public function getDados(Request $request, DadoPublicoALMG $mdDadoPublico) : JsonResponse
    {
        $urlConsulta = $request->url ?? null;
        if (is_null($urlConsulta)) return response()->json("Url não informada para consulta", 500);
        
        $urlValida = array_filter(self::PREFIXO_URL, function ($url)use($urlConsulta){
            return str_contains($urlConsulta, $url);
        });

        if (count($urlValida) == 0) return response()->json("Essa url não pertence ao WebService da Assembléia Legislativa do Estado de
 Minas Gerais", 500);

        $dadosConsulta = $mdDadoPublico->consultar($urlConsulta);
        if ($dadosConsulta == new \stdClass()) return response()->json("URL não disponível para consumo dos dados", 500);
        if (!$mdDadoPublico->gravar($urlConsulta, $dadosConsulta)) return response()->json("Não foi possível gravar os dados no banco de dados", 500);
        
        return response()->json("Dados gravados no banco de dados com sucesso", 200);
    }
}
