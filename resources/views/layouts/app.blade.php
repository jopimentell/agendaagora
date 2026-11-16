<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@hasSection('title')@yield('title') — @endif{{ config('app.name') }}</title>
    <script>
        try {
            var t = localStorage.getItem('agenda-tema');
            if (t) document.documentElement.setAttribute('data-theme', t);
        } catch (e) {}
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=JetBrains+Mono:wght@500&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <header class="app-header">
        <div class="container">
            <a href="{{ route('publico.inicio') }}" class="app-brand">Agenda <span>Agora</span></a>
            <nav class="app-nav">
                @auth
                    @if (auth()->user()->isAdmin())
                        <a href="{{ route('admin.dashboard') }}" @class(['active' => request()->routeIs('admin.*')])>Painel</a>
                    @endif
                    @if (auth()->user()->agendasResponsavel()->exists())
                        <a href="{{ route('responsavel.agenda') }}" @class(['active' => request()->routeIs('responsavel.*')])>Minha Agenda</a>
                    @endif
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit">Sair</button>
                    </form>
                @else
                    <a href="{{ route('login') }}">Entrar</a>
                @endauth
            </nav>
        </div>
    </header>

    <main class="screen">
        <div class="container">
            @if (session('sucesso'))
                <div class="alert alert-success" role="status">{{ session('sucesso') }}</div>
            @endif
            @if (session('erro'))
                <div class="alert alert-error" role="alert">{{ session('erro') }}</div>
            @endif

            @yield('content')
        </div>
    </main>

    <button class="theme-toggle" onclick="toggleTheme()" title="Alternar tema" aria-label="Alternar tema claro/escuro">🌓</button>
</body>
</html>
