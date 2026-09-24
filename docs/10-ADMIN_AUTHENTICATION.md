# 10 — Admin e autenticação

## Escopo implementado

O ambiente Admin possui login local, logout, sessão protegida, autorização pelo papel `administrador` e painel inicial com indicadores. A interface usa a linguagem visual do Threeebs Identity; o código de segurança pertence ao Hub.

## Controles

- consultas PDO preparadas e emulação desativada;
- verificação de senha com `password_verify` e rehash automático;
- resposta genérica para usuário inexistente, senha inválida, conta inativa e ausência de papel;
- comparação de senha dummy para reduzir diferença de tempo;
- limite por identificador e IP usando hashes HMAC;
- sessão somente por cookie, HttpOnly, SameSite Strict e ID regenerado no login;
- expiração por inatividade e tempo absoluto;
- CSRF de uso único em login e logout;
- redirecionamentos limitados a caminhos internos;
- CSP, bloqueio de frame, `nosniff`, política de referência e permissões;
- HSTS quando a requisição é HTTPS;
- auditoria de sucesso, falha e logout sem armazenar e-mail ou IP em texto aberto;
- autorização do papel consultada no login e preservada na sessão.

## Variáveis obrigatórias

`APP_KEY` deve ser aleatória e ter pelo menos 32 caracteres. Em produção:

~~~env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://hub.example.com
SESSION_SECURE=true
TRUST_PROXY_HEADERS=true
~~~

Ative `TRUST_PROXY_HEADERS` somente quando o Admin estiver protegido por proxy ou túnel controlado.

## Usuário de demonstração

Com `LOAD_DEMO_SEEDS=true`, o usuário é `admin@local.test` e a senha inicial é `123456`. Esta conta pertence exclusivamente a ambiente descartável.

## Testes

~~~bash
bash tests/run-auth.sh
~~~

A integração completa requer MySQL e os containers:

~~~bash
docker compose build
docker compose up -d
bash scripts/migrate.sh
docker compose exec -T admin php /var/www/app/../shared/../app/tests/security-unit.php
~~~

O último caminho pode variar conforme a montagem do ambiente; o teste principal pode ser executado no clone com PHP 8.3.

## Limites atuais

Ainda não existem criação de usuários pela interface, recuperação de senha, MFA ou identidade federada pelo Edge. Esses recursos devem ser adicionados em etapas próprias. Nenhuma implementação pode ser declarada livre de vulnerabilidades; cada publicação exige testes integrados, revisão e atualização contínua.
