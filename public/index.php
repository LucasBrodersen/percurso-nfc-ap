<?php
declare(strict_types=1);

// Percurso NFC - Activity Provider
// Router principal: recebe o pedido e chama o serviço certo

require __DIR__ . '/../src/Http.php';
require __DIR__ . '/../src/Repositorio.php';
require __DIR__ . '/../src/Servicos.php';

use PercursoNfc\Http;
use PercursoNfc\Servicos;

// CORS para a Inven!RA poder chamar de outro domínio
Http::cors();
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$caminho = rtrim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/', '/') ?: '/';
$metodo  = $_SERVER['REQUEST_METHOD'] ?? 'GET';

$servicos = new Servicos(Http::urlBase());

// tabela de rotas — cada entrada tem [método esperado, função a chamar]
$rotas = [
    '/'               => ['GET',  fn() => $servicos->inicio()],
    '/config'         => ['GET',  fn() => $servicos->paginaConfiguracao()],
    '/params'         => ['GET',  fn() => $servicos->listaParametros()],
    '/deploy'         => ['GET',  fn() => $servicos->deploy($_GET['activityID'] ?? null)],
    '/analytics-list' => ['GET',  fn() => $servicos->listaAnalytics()],
    '/analytics'      => ['POST', fn() => $servicos->analytics(Http::corpoJson())],
    '/analise'        => ['GET',  fn() => $servicos->analiseQualitativa($_GET['APAnID'] ?? null)],
    '/percurso'       => ['GET',  fn() => $servicos->percursoEmConstrucao($_GET['activityID'] ?? null)],
];

if (!isset($rotas[$caminho])) {
    Http::erro(404, 'Serviço inexistente: ' . $caminho);
}

[$metodoEsperado, $acao] = $rotas[$caminho];

if ($metodo !== $metodoEsperado) {
    header('Allow: ' . $metodoEsperado);
    Http::erro(405, "Este serviço só aceita {$metodoEsperado}.");
}

$acao();
