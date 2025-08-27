<?php
use App\Http\Controllers\EstruturaController;
use App\Http\Controllers\ExternalUserController;
use App\Http\Controllers\GrupoAcessoController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ComentarioController;
use App\Http\Controllers\ArquivoClassificacaoController;
use App\Http\Controllers\ArquivoEstruturaController;
use App\Http\Controllers\ClassificacaoEstruturaController;
use App\Http\Controllers\CompanyController;
use App\Http\Middleware\ForceJsonResponse;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', ForceJsonResponse::class])->group(function () {
    Route::apiResource('estruturas', EstruturaController::class);
    Route::apiResource('grupos-acesso', GrupoAcessoController::class);

    Route::post('/grupos-acesso/{grupoId}/usuarios', [GrupoAcessoController::class, 'addUsersToGroup']);
    Route::get('/grupos-acesso/{grupoId}/usuarios', [GrupoAcessoController::class, 'getUsuariosGroup']);
    Route::delete('/grupos-acesso/{grupoId}/usuarios', [GrupoAcessoController::class, 'removeUsersFromGroup']);

    Route::get('/users', [UserController::class, 'index']); // ->middleware('auth:sanctum');
    Route::post('/users', [UserController::class, 'store']);
    Route::get('/users/{id}', [UserController::class, 'show']);
    Route::put('/users/{id}', [UserController::class, 'update']);
    Route::delete('/users/{id}', [UserController::class, 'destroy']);

    Route::get('/estruturas/{id}/comentarios', [ComentarioController::class, 'index']);
    Route::post('/estruturas/{id}/comentarios', [ComentarioController::class, 'store']);
    
    Route::prefix('estruturas/{estruturaId}/arquivos-classificacao')->group(function () {
        Route::get('/', [ArquivoClassificacaoController::class, 'index']);
        Route::post('/', [ArquivoClassificacaoController::class, 'store']);
        Route::get('{arquivoId}', [ArquivoClassificacaoController::class, 'show']);
        Route::delete('{arquivoId}', [ArquivoClassificacaoController::class, 'destroy']);
        Route::get('{arquivoId}/download', [ArquivoClassificacaoController::class, 'download']);
    });

    Route::get('/companies', [CompanyController::class, 'index']);
    Route::post('/companies', [CompanyController::class, 'store']);
    Route::get('/companies/{id}', [CompanyController::class, 'show']);
    Route::put('/companies/{id}', [CompanyController::class, 'update']);
    
    // Rotas para Classificacao Estrutura
    Route::apiResource('classificacao-estrutura', ClassificacaoEstruturaController::class);
    
    Route::delete('/companies/{id}', [CompanyController::class, 'destroy']);
    Route::get('/companies/{id}/users', [CompanyController::class, 'getUsers']);
    Route::post('/companies/{id}/users', [CompanyController::class, 'addUser']);
    Route::delete('/companies/{id}/users/{userId}', [CompanyController::class, 'removeUser']);

    Route::get('/estruturas/{id}/auditoria', [EstruturaController::class, 'auditoria']);

    Route::get('/estruturas/{estruturaId}/arquivos-estrutura', [ArquivoEstruturaController::class, 'index']);
    Route::post('/estruturas/{estruturaId}/arquivos-estrutura', [ArquivoEstruturaController::class, 'store']);
    Route::get('/estruturas/{estruturaId}/arquivos-estrutura/{arquivoId}/download', [ArquivoEstruturaController::class, 'download']);
    Route::delete('/estruturas/{estruturaId}/arquivos-estrutura/{arquivoId}', [ArquivoEstruturaController::class, 'destroy']);
    Route::get('/estruturas/{estruturaId}/arquivos-estrutura/historico', [ArquivoEstruturaController::class, 'historico']);
});

Route::prefix('auth')->middleware(ForceJsonResponse::class)->group(function () {
    Route::get('/external-users', [ExternalUserController::class, 'index']);
    Route::post('/federated-login', [ExternalUserController::class, 'federatedLogin']);
    Route::get('/logout', [ExternalUserController::class, 'logout'])->name('logout');
    Route::post('/register', [ExternalUserController::class, 'register'])->name('register');
    Route::get('/login', [ExternalUserController::class, 'login'])->name('login.get');
    Route::get('/current-account', [ExternalUserController::class, 'getCurrentAccount'])->name('getCurrentAccount')->middleware('auth:sanctum');
    Route::match(['post', 'get', 'delete', 'put'], 'login', [ExternalUserController::class, 'login'])->name('login.multi');
});
/*
Route::prefix('estruturas/{estruturaId}/arquivos-classificacao')->group(function () {
    Route::get('/', [ArquivoClassificacaoController::class, 'index']);
    Route::post('/', [ArquivoClassificacaoController::class, 'store']);
    Route::get('{arquivoId}', [ArquivoClassificacaoController::class, 'show']);
    Route::delete('{arquivoId}', [ArquivoClassificacaoController::class, 'destroy']);
});*/
