# 08 — Instalação local

## Requisitos

- Docker com Compose v2;
- portas 6041 a 6044 disponíveis, ou valores alternativos no `.env`;
- volume descartável durante o rework das migrations.

## Configuração

~~~bash
cp .env.example .env
~~~

Troque todas as senhas. Para criar os usuários e regras de teste, defina `LOAD_DEMO_SEEDS=true`. Mantenha `false` em qualquer ambiente real.

## Primeira inicialização

~~~bash
docker compose config
docker compose build
docker compose up -d
docker compose ps
~~~

Na criação do volume MySQL, o script de inicialização aplica migrations em ordem e carrega seeds somente quando autorizado.

## Atualizar um banco existente

~~~bash
bash scripts/migrate.sh
~~~

O runner consulta `schema_migrations` e ignora arquivos já aplicados. Uma migration aplicada nunca deve ser editada após uso compartilhado; crie um novo arquivo numerado.

Para carregar manualmente as fixtures em ambiente descartável:

~~~bash
bash scripts/seed-demo.sh
~~~

## Serviços

| Serviço | Porta padrão | Exposição padrão |
| --- | ---: | --- |
| Admin | 6041 | todas as interfaces |
| Users | 6042 | todas as interfaces |
| MySQL | 6043 | somente localhost |
| Redis | 6044 | somente localhost |

## Contas da fixture

Quando os seeds estiverem habilitados, são criadas contas locais de Administrador, Captador, Desenvolvedor e Comercial. A senha inicial é `123456`. Elas não podem ser usadas em produção.

## Observações

As aplicações PHP ainda não foram implementadas. Os healthchecks dos containers PHP verificam o runtime e a extensão PDO MySQL; eles não representam um teste funcional das futuras telas.
