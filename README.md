# Percurso NFC — Activity Provider (Inven!RA)

Activity Provider para a cadeira de **Arquitetura e Padrões de Software**, mestrado MEIW 2026/27.

A ideia é: o aluno percorre pontos físicos num campus (ou jardim, museu, etc.) e em cada ponto lê uma etiqueta NFC ou QR code. O professor consegue ver o progresso na Inven!RA.

Por agora (semana 1, tópico 4) estão implementados os 5 serviços com URL fixo que a Inven!RA precisa, com dados de exemplo hardcoded. O *Provide activity* (a parte do aluno) fica para depois.

## Serviços implementados

| Serviço | Método | Caminho | O que faz |
|---------|--------|---------|-----------|
| config_url | GET | /config | Devolve o HTML de configuração |
| json_params_url | GET | /params | Lista de parâmetros em JSON |
| user_url | GET | /deploy?activityID=... | Faz deploy e devolve URL do percurso |
| analytics_list_url | GET | /analytics-list | Lista de analytics disponíveis |
| analytics_url | POST | /analytics | Recebe `{"activityID":"..."}` e devolve analytics |

Também há `GET /` que devolve o JSON de registo (com os 5 URLs) e `GET /analise?APAnID=...` para ver as analíticas qualitativas.

### Parâmetros

- `codigo_percurso` — identificador do percurso
- `pontos` — JSON com os pontos (id, nome, pergunta, resposta, obrigatorio)
- `ordem_sequencial` — se os pontos têm de ser visitados por ordem
- `tempo_limite_min` — tempo máximo em minutos
- `permitir_qr` — se aceita QR além de NFC
- `instrucoes` — texto livre para os alunos

### Analytics

Quantitativas: `pontos_visitados`, `percurso_completo`, `respostas_certas`, `ordem_respeitada`, `tempo_total_min`

Qualitativas (devolvem URL): `sequencia_pontos`, `pontos_obrigatorios_em_falta`

## Como correr

```bash
php -S localhost:8000 public/index.php
```

Depois:
```
curl localhost:8000/config
curl localhost:8000/params
curl "localhost:8000/deploy?activityID=teste123"
curl localhost:8000/analytics-list
curl -X POST localhost:8000/analytics -H "Content-Type: application/json" -d '{"activityID":"teste123"}'
```

Erros: 405 se o método estiver errado, 400 se faltar activityID ou o JSON for inválido, 404 se o caminho não existir.

## Deploy (Render)

O Dockerfile cria uma imagem com PHP 8.3 + Apache. No Render: New > Web Service > Docker, e ele trata do resto. A porta vem da variável `PORT`.

## Estrutura do projeto

```
public/index.php        router (método + caminho -> serviço)
src/Servicos.php        lógica dos 5 serviços
src/Http.php            helpers para respostas HTTP e CORS
src/Repositorio.php     guarda instâncias em ficheiro JSON
data/exemplo.json       dados de teste (3 alunos fictícios)
Dockerfile              para o Render
```

## TODO

- [ ] Implementar o Provide activity (página do aluno)
- [ ] Ligar os analytics a dados reais em vez do exemplo.json
- [ ] Validar melhor o JSON dos pontos na configuração
