@extends('layouts.app')

@section('title', 'Entrar')

@section('content')
    <div class="auth-container">
        <div class="card">
            <h1>Entrar</h1>
            <p class="muted" style="margin-bottom: 24px;">Acesso para administradores e responsáveis pelas agendas.</p>

            <form method="POST" action="{{ route('login') }}">
                @csrf
                <div class="form-group">
                    <label for="email">E-mail</label>
                    <input id="email" name="email" type="email" class="form-control" value="{{ old('email') }}" required autofocus autocomplete="username">
                    @error('email') <span class="form-error">{{ $message }}</span> @enderror
                </div>
                <div class="form-group">
                    <label for="password">Senha</label>
                    <input id="password" name="password" type="password" class="form-control" required autocomplete="current-password">
                    @error('password') <span class="form-error">{{ $message }}</span> @enderror
                </div>
                <label class="form-check" style="margin-bottom: 24px;">
                    <input type="checkbox" name="lembrar" value="1"> Manter conectado
                </label>
                <button type="submit" class="btn-primary" style="width: 100%; border-radius: 8px;">Entrar</button>
            </form>
        </div>
    </div>
@endsection
