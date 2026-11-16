@extends('layouts.app')

@section('title', $servico->nome)

@section('content')
    <div class="flugo-container">
        <h1>Qual curso você deseja falar com a {{ mb_strtolower($servico->nome) }}?</h1>

        <div class="option-grid">
            @foreach ($agendas as $agenda)
                <a class="option-btn" href="{{ route('publico.agenda', $agenda->slug) }}">
                    {{ $agenda->curso?->nome ?? $agenda->nome }}
                </a>
            @endforeach
        </div>
        <br>
        <a href="{{ route('publico.inicio') }}" class="btn-link">← Voltar</a>
    </div>
@endsection
