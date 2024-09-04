<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\VerbaIndenizatoria;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VerbasIndenizatoriasController extends Controller
{
    /**Função para coletar os dados dos deputados e informar quais são os que mais pediram reembolsos das verbas indenizatórias
     * @author Lucas Magalhães
     * @param object $request - Objeto que fornecerá o ano a ser utilizado para consulta
     * @param object $mdVerba - Model que irá relacionar as diversas consultas para retornar os dados necessários
     * @return array JSON - Retorna um array com os 5 deputados que mais pediram reembolso dentro do ano, separados por mês
     */
    public function getDados(Request $request, VerbaIndenizatoria $mdVerba) : JsonResponse
    {
        $ano = $request->ano;
        if (is_null($ano)) return response()->json("Ano para pesquisa não informado", 500);

        $idLegislaturas = $mdVerba->getLegislaturas($ano);
        if (count($idLegislaturas) == 0) return response()->json("Erro ao verificar o mandato relacionado ao ano informado", 500);

        $idsDeputados = [];
        $deputados = [];
        foreach($idLegislaturas as $idLeg){
            $deputadosLeg = $mdVerba->getDeputadosLegislatura($idLeg);
            $deputados = array_merge($deputados, $deputadosLeg);
            $idsDeputados = array_merge($idsDeputados, array_column($deputadosLeg, "id"));
        }

        //Retira a duplicação de ids (devido a deputados reeleitos)
        $idsDeputados = array_unique($idsDeputados);

        $pedidosReembolso = $mdVerba->getPedidoReembolso($idsDeputados, $ano);

        $listaDeputados = $mdVerba->getDeputadosMaisGastadores($pedidosReembolso, $deputados);

        return response()->json($listaDeputados, 200);
    }
}
