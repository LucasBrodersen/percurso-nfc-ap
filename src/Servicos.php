<?php
declare(strict_types=1);

namespace PercursoNfc;

/**
 * Os 5 serviços que a Inven!RA precisa nesta fase.
 * O Provide activity fica para a próxima entrega.
 */
class Servicos
{
    // Parâmetros de configuração que a Inven!RA recolhe
    public const PARAMETROS = [
        ['name' => 'codigo_percurso', 'type' => 'text/plain'],
        ['name' => 'pontos', 'type' => 'text/plain'],
        ['name' => 'ordem_sequencial', 'type' => 'boolean'],
        ['name' => 'tempo_limite_min', 'type' => 'integer'],
        ['name' => 'permitir_qr', 'type' => 'boolean'],
        ['name' => 'instrucoes', 'type' => 'text/plain'],
    ];

    // Analytics disponíveis
    public const ANALYTICS = [
        'qualAnalytics' => [
            ['name' => 'sequencia_pontos', 'type' => 'URL'],
            ['name' => 'pontos_obrigatorios_em_falta', 'type' => 'URL'],
        ],
        'quantAnalytics' => [
            ['name' => 'pontos_visitados', 'type' => 'integer'],
            ['name' => 'percurso_completo', 'type' => 'boolean'],
            ['name' => 'respostas_certas', 'type' => 'integer'],
            ['name' => 'ordem_respeitada', 'type' => 'boolean'],
            ['name' => 'tempo_total_min', 'type' => 'integer'],
        ],
    ];

    private Repositorio $repo;

    public function __construct(private string $urlBase)
    {
        $this->repo = new Repositorio(__DIR__ . '/../data');
    }

    // GET / — devolve o JSON de registo com os URLs dos serviços
    public function inicio(): never
    {
        Http::json([
            'name'               => 'Percurso NFC',
            'config_url'         => $this->urlBase . '/config',
            'json_params_url'    => $this->urlBase . '/params',
            'user_url'           => $this->urlBase . '/deploy',
            'analytics_url'      => $this->urlBase . '/analytics',
            'analytics_list_url' => $this->urlBase . '/analytics-list',
        ]);
    }

    // GET /config — página HTML de configuração (sem botão de guardar, a Inven!RA trata disso)
    public function paginaConfiguracao(): never
    {
        // exemplo de pontos para preencher o textarea
        $pontosExemplo = htmlspecialchars(json_encode([
            ['id' => 'P1', 'nome' => 'Portão principal', 'pergunta' => 'Em que ano foi construído?', 'resposta' => '1912', 'obrigatorio' => true],
            ['id' => 'P2', 'nome' => 'Jardim das aromáticas', 'pergunta' => 'Que planta tem flores roxas?', 'resposta' => 'lavanda', 'obrigatorio' => true],
            ['id' => 'P3', 'nome' => 'Miradouro', 'pergunta' => 'Quantas torres se veem?', 'resposta' => '3', 'obrigatorio' => false],
        ], JSON_UNESCAPED_UNICODE), ENT_QUOTES);

        Http::html(<<<HTML
<div class="percurso-nfc-config" style="font-family:Arial,sans-serif;max-width:640px">
  <h2 style="margin:0 0 4px">Percurso NFC · configuração</h2>
  <p style="margin:0 0 12px;color:#555">Defina o percurso que os alunos vão fazer, lendo etiquetas NFC (ou códigos QR) em cada ponto.</p>

  <label for="codigo_percurso"><strong>Código do percurso</strong></label><br>
  <input id="codigo_percurso" name="codigo_percurso" type="text" value="JARDIM-2026" maxlength="30" style="width:100%"><br><br>

  <label for="pontos"><strong>Pontos do percurso</strong> (JSON: id, nome, pergunta, resposta, obrigatorio)</label><br>
  <textarea id="pontos" name="pontos" rows="7" style="width:100%;font-family:monospace">{$pontosExemplo}</textarea><br><br>

  <label><input name="ordem_sequencial" type="checkbox" value="true" checked> Os pontos têm de ser visitados por ordem</label><br>
  <label><input name="permitir_qr" type="checkbox" value="true" checked> Permitir leitura por código QR (alternativa ao NFC)</label><br><br>

  <label for="tempo_limite_min"><strong>Tempo limite (minutos)</strong></label><br>
  <input id="tempo_limite_min" name="tempo_limite_min" type="number" min="0" value="60"><br><br>

  <label for="instrucoes"><strong>Instruções para os alunos</strong></label><br>
  <textarea id="instrucoes" name="instrucoes" rows="3" style="width:100%">Siga os pontos pela ordem indicada e responda à pergunta de cada etiqueta.</textarea>
</div>
HTML);
    }

    // GET /params — nomes e tipos dos parâmetros
    public function listaParametros(): never
    {
        Http::json(self::PARAMETROS);
    }

    // GET /deploy?activityID=... — cria instância e devolve URL
    public function deploy(?string $activityId): never
    {
        if ($activityId === null || trim($activityId) === '') {
            Http::erro(400, 'Falta o parâmetro activityID.');
        }

        $this->repo->registarInstancia($activityId);

        // a Inven!RA espera text/plain com o URL
        http_response_code(200);
        header('Content-Type: text/plain; charset=utf-8');
        echo $this->urlBase . '/percurso?activityID=' . rawurlencode($activityId);
        exit;
    }

    // GET /analytics-list — lista de analytics que recolhemos
    public function listaAnalytics(): never
    {
        Http::json(self::ANALYTICS);
    }

    // POST /analytics — recebe {"activityID": "..."} e devolve analytics de todos os alunos
    public function analytics(array $corpo): never
    {
        $activityId = $corpo['activityID'] ?? null;
        if (!is_string($activityId) || trim($activityId) === '') {
            Http::erro(400, 'Falta o campo activityID no corpo JSON.');
        }

        $exemplo = $this->repo->exemplo();
        $resposta = [];

        foreach ($exemplo['alunos'] ?? [] as $aluno) {
            $id = $aluno['inveniraStdID'];

            // quantitativas — valor direto
            $quant = [];
            foreach (self::ANALYTICS['quantAnalytics'] as $def) {
                $quant[] = ['name' => $def['name'], 'value' => $aluno[$def['name']] ?? null];
            }

            // qualitativas — URL para a página de análise
            $qual = [];
            foreach (self::ANALYTICS['qualAnalytics'] as $def) {
                $apAnId = "{$activityId}:{$id}:{$def['name']}";
                $qual[] = [$def['name'] => $this->urlBase . '/analise?APAnID=' . rawurlencode($apAnId)];
            }

            $resposta[] = [
                'inveniraStdID' => $id,
                'quantAnalytics' => $quant,
                'qualAnalytics' => $qual,
            ];
        }

        Http::json($resposta);
    }

    // GET /analise?APAnID=... — mostra uma analítica qualitativa (HTML)
    public function analiseQualitativa(?string $apAnId): never
    {
        // o APAnID tem formato "activityID:alunoID:nomeAnalytica"
        [$activityId, $alunoId, $nome] = array_pad(explode(':', (string) $apAnId, 3), 3, null);

        // procurar o aluno nos dados de exemplo
        $aluno = null;
        foreach ($this->repo->exemplo()['alunos'] ?? [] as $a) {
            if ((string) $a['inveniraStdID'] === (string) $alunoId) {
                $aluno = $a;
            }
        }

        if ($aluno === null || !in_array($nome, ['sequencia_pontos', 'pontos_obrigatorios_em_falta'], true)) {
            Http::erro(404, 'Analítica não encontrada.');
        }

        $titulo = $nome === 'sequencia_pontos'
            ? 'Sequência de pontos visitados'
            : 'Pontos obrigatórios em falta';
        $itens = $nome === 'sequencia_pontos' ? $aluno['sequencia'] : $aluno['em_falta'];

        $lista = $itens
            ? implode('', array_map(fn($p) => '<li>' . htmlspecialchars($p) . '</li>', $itens))
            : '<li>Nenhum.</li>';

        $act = htmlspecialchars((string) $activityId);

        Http::html(
            "<div style=\"font-family:Arial,sans-serif\">"
            . "<h2>{$titulo}</h2>"
            . "<p>Aluno {$alunoId} · atividade {$act}</p>"
            . "<ol>{$lista}</ol>"
            . "</div>"
        );
    }

    // GET /percurso — placeholder, o Provide activity vem depois
    public function percursoEmConstrucao(?string $activityId): never
    {
        $act = htmlspecialchars((string) $activityId);
        Http::html(
            "<div style=\"font-family:Arial,sans-serif\">"
            . "<h2>Percurso NFC</h2>"
            . "<p>Atividade <strong>{$act}</strong> preparada.</p>"
            . "<p>A página do aluno (serviço <em>Provide activity</em>) será disponibilizada numa fase posterior do projeto.</p>"
            . "</div>"
        );
    }
}
