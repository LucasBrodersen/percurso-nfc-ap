<?php
declare(strict_types=1);

namespace PercursoNfc;

/**
 * Persistência simples em ficheiro JSON.
 * Por agora só guarda as instâncias criadas pelo deploy;
 * os analytics vêm do ficheiro de exemplo (data/exemplo.json).
 */
class Repositorio
{
    private string $ficheiroInstancias;

    public function __construct(private string $pastaDados)
    {
        $this->ficheiroInstancias = $pastaDados . '/instancias.json';
    }

    // Regista uma nova instância (ou devolve a existente se já existir)
    public function registarInstancia(string $activityId): array
    {
        $instancias = $this->lerJson($this->ficheiroInstancias, []);

        if (!isset($instancias[$activityId])) {
            $instancias[$activityId] = [
                'activityID' => $activityId,
                'criadaEm' => date(DATE_ATOM),
            ];
            // @ para não rebentar se a pasta não tiver permissões de escrita
            @file_put_contents(
                $this->ficheiroInstancias,
                json_encode($instancias, JSON_PRETTY_PRINT),
                LOCK_EX
            );
        }

        return $instancias[$activityId];
    }

    // Carrega os dados de exemplo (3 alunos fictícios)
    public function exemplo(): array
    {
        return $this->lerJson($this->pastaDados . '/exemplo.json', []);
    }

    private function lerJson(string $ficheiro, array $omissao): array
    {
        if (!is_file($ficheiro)) {
            return $omissao;
        }
        $dados = json_decode((string) file_get_contents($ficheiro), true);
        return is_array($dados) ? $dados : $omissao;
    }
}
