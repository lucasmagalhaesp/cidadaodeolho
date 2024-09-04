<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\RedeSocial;

class RedesSociaisController extends Controller
{
     /**Função para receber os dados das redes sociais mais utilizadas pelos deputados e organizar em um ranking
     * @author Lucas Magalhães
     * @param object $mdRedeSocial - Model que chamará as funções recebendo a lista de redes sociais e depois o ranking gerado
     * @return array JSON - Retorna um array com o ranking das redes sociais
     */
    public function getDados(RedeSocial $mdRedeSocial) : JsonResponse
    {
        $redesSociaisDeputados = $mdRedeSocial->getRedesSociaisDeputados();
        $rankingRedesSociais = $mdRedeSocial->getRanking($redesSociaisDeputados);
        return response()->json($rankingRedesSociais, 200);
    }
}
