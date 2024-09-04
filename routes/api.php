<?php

use App\Http\Controllers\DadosPublicosALMGController;
use App\Http\Controllers\VerbasIndenizatoriasController;
use App\Http\Controllers\RedesSociaisController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get("verbas/reembolso/{ano}", [VerbasIndenizatoriasController::class, "getDados"]);
Route::get("redessociais/ranking", [RedesSociaisController::class, "getDados"]);
Route::post("dadospublicos/almg", [DadosPublicosALMGController::class, "getDados"]);