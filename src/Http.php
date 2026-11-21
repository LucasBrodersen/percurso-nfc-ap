<?php
declare(strict_types=1);

namespace PercursoNfc;

// Funções auxiliares para respostas HTTP
class Http
{
    /**
     * Headers CORS — a Inven!RA chama o AP a partir de outro domínio,
     * por isso precisamos disto em todos os pedidos
     */
    public static function cors(): void
    {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type');
    }

    // Responde com JSON
    public static function json(mixed $dados, int $estado = 200): never
    {
        http_response_code($estado);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($dados, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    // Responde com HTML
    public static function html(string $html, int $estado = 200): never
    {
        http_response_code($estado);
        header('Content-Type: text/html; charset=utf-8');
        echo $html;
        exit;
    }

    // Resposta de erro (JSON com campo "erro")
    public static function erro(int $estado, string $mensagem): never
    {
        self::json(['erro' => $mensagem], $estado);
    }

    /**
     * Lê e faz parse do corpo JSON dum POST.
     * Se não for JSON válido devolve 400.
     */
    public static function corpoJson(): array
    {
        $bruto = file_get_contents('php://input') ?: '';
        $dados = json_decode($bruto, true);
        if (!is_array($dados)) {
            self::erro(400, 'O corpo do pedido tem de ser JSON, por exemplo {"activityID": "..."}.');
        }
        return $dados;
    }

    // Descobre o URL base do serviço (funciona atrás de proxy HTTPS tipo Render)
    public static function urlBase(): string
    {
        $esquema = $_SERVER['HTTP_X_FORWARDED_PROTO']
            ?? ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http');
        $host = $_SERVER['HTTP_X_FORWARDED_HOST'] ?? $_SERVER['HTTP_HOST'] ?? 'localhost';
        return $esquema . '://' . $host;
    }
}
