@extends('layouts.app')

@section('content')
    <div class="flugo-container">
        <h1>Selecione o Polo e o Atendimento</h1>

        <div class="option-grid">
            @forelse ($servicos as $servico)
                <a class="option-btn" href="{{ route('publico.servico', $servico) }}">
                    <div>
                        <div class="option-polo">{{ $servico->polo->nome }}</div>
                        @if ($servico->agendasAtivas->count() === 1)
                            {{ $servico->agendasAtivas->first()->nome }}
                        @else
                            {{ $servico->nome }} (Escolher Curso)
                        @endif
                    </div>
                </a>
            @empty
                <p class="empty-state">Nenhum atendimento disponível no momento.</p>
            @endforelse
        </div>
    </div>
@endsection
