# Agenda Agora

Plataforma de agendamento online multi-agenda para a UniFacema. Projeto acadêmico em dupla, da disciplina de Prática Profissional (ciclo de vida de desenvolvimento, Fase 4).

- **Público:** o aluno escolhe polo e serviço (e curso, quando houver), depois o dia e o horário, e confirma com nome, matrícula e e-mail. Pode cancelar até 24h antes pelo link recebido.
- **Administrador:** acompanha ocupação, reservas do mês e taxa de no-show; cria, edita, pausa e ativa agendas; copia o link público de cada agenda.
- **Responsável:** vê os agendamentos do dia e marca presença ou falta.

## Tecnologias

- Laravel 12 (PHP 8.2+), Blade e Alpine.js, com o CSS do protótipo original
- PostgreSQL no Supabase (schema `agenda`)
- Publicação na Vercel via Container Runtime (FrankenPHP), com `Dockerfile` e `vercel.json`

## Rodando localmente

```bash
cp .env.example .env        # preencha DB_HOST, DB_USERNAME e DB_PASSWORD (Supabase > Connect > Session pooler)
composer install
npm install && npm run build
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

## Testes

```bash
php artisan test
```

Os testes usam SQLite em memória e nunca acessam o Supabase.

## Deploy (Vercel)

1. Importe este repositório em https://vercel.com/new (Framework Preset: **Other**).
2. Cadastre as variáveis de ambiente de produção: `APP_KEY`, `APP_URL`, `DB_*`, `SESSION_DRIVER=database`, entre outras. Veja `.env.example`.
3. A Vercel monta o container a partir do `Dockerfile` a cada push na branch `main`.
