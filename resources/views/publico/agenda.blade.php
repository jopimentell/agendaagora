@extends('layouts.app')

@section('title', $agenda->nome)

@section('content')
    @php
        $hoje = now();
        $dataInicial = old('data');
        $horaInicial = old('hora');
    @endphp

    <div class="booking-layout"
         x-data="agendamento({
            url: @js(route('publico.disponibilidade', $agenda->slug)),
            ano: {{ $hoje->year }},
            mes: {{ $hoje->month }},
            dataInicial: @js($dataInicial),
            horaInicial: @js($horaInicial),
         })">

        {{-- Lado esquerdo: informações do atendimento --}}
        <div class="booking-host">
            <div class="host-info">
                <div class="host-avatar">👨‍🏫</div>
                <div>
                    <div style="font-weight: 600; color: var(--color-body); font-size: 14px;">{{ $agenda->tenant->nome }}</div>
                    <h2 style="font-size: 20px;">{{ $agenda->nome }}</h2>
                </div>
            </div>
            <h2>{{ $agenda->titulo_evento }}</h2>
            @if ($agenda->descricao)
                <p>{{ $agenda->descricao }}</p>
            @endif

            <div style="display: flex; gap: 16px; margin-bottom: 24px; color: var(--color-body); font-size: 14px; flex-wrap: wrap;">
                <span>⏱ {{ $agenda->duracao_minutos }} min</span>
                @if ($agenda->local)
                    <span>📍 {{ $agenda->local }}</span>
                @endif
            </div>

            <div class="card" style="margin-top: 32px;">
                <h3 style="font-size: 16px; margin-bottom: 8px;">{{ $agenda->responsavel->name }}</h3>
                @if ($agenda->responsavel->cargo)
                    <p style="font-size: 14px; color: var(--color-body); margin-bottom: 16px;">{{ $agenda->responsavel->cargo }}</p>
                @endif
                @if ($agenda->mensagem_responsavel)
                    <p style="font-size: 14px; color: var(--color-body);">"{{ $agenda->mensagem_responsavel }}"</p>
                @endif
            </div>
        </div>

        {{-- Lado direito: calendário, horários e dados --}}
        <div class="card" style="padding: 32px;">

            {{-- Passo 1: calendário + horários --}}
            <div x-show="etapa === 'calendario'">
                <div class="calendar-header">
                    <span style="font-weight: 600; font-size: 16px;" x-text="tituloMes"></span>
                    <div style="display: flex; gap: 12px;">
                        <button type="button" @click="mudarMes(-1)" aria-label="Mês anterior" style="background:none; border:none; cursor:pointer; color: var(--color-ink);">◀</button>
                        <button type="button" @click="mudarMes(1)" aria-label="Próximo mês" style="background:none; border:none; cursor:pointer; color: var(--color-ink);">▶</button>
                    </div>
                </div>

                <div style="font-size: 14px; color: var(--color-body); margin-bottom: 24px; display: flex; align-items: center; gap: 8px;">
                    🌐 GMT-03:00 Horário de Brasília
                </div>

                <div class="calendar-grid" style="margin-bottom: 24px;">
                    @foreach (['D', 'S', 'T', 'Q', 'Q', 'S', 'S'] as $rotulo)
                        <div class="cal-day-label">{{ $rotulo }}</div>
                    @endforeach

                    <template x-for="(celula, i) in celulas" :key="i">
                        <button type="button"
                                class="cal-day"
                                :class="{
                                    'empty': !celula,
                                    'disabled': celula && !disponivel(celula.data),
                                    'available': celula && disponivel(celula.data) && dataSelecionada !== celula.data,
                                    'selected': celula && dataSelecionada === celula.data,
                                }"
                                :disabled="!celula || !disponivel(celula.data)"
                                @click="escolherDia(celula)"
                                x-text="celula ? celula.dia : ''"></button>
                    </template>
                </div>

                <p class="muted" x-show="carregando">Carregando horários…</p>
                <p class="form-error" x-show="erro" x-text="erro" x-cloak></p>
                <p class="muted" x-show="!carregando && !erro && Object.keys(dias).length === 0" x-cloak>
                    Não há horários disponíveis neste mês. Tente o próximo.
                </p>

                <div class="time-slots-container" x-show="dataSelecionada" x-cloak>
                    <div class="time-slots-title">Horários disponíveis (<span x-text="rotuloDia"></span>)</div>
                    <div class="time-grid">
                        <template x-for="hora in horarios" :key="hora">
                            <button type="button" class="time-slot"
                                    :class="{ 'selected': horaSelecionada === hora }"
                                    @click="escolherHora(hora)"
                                    x-text="hora"></button>
                        </template>
                    </div>
                </div>

                <button type="button" class="btn-primary" style="width: 100%; margin-top: 32px; border-radius: 8px;"
                        :disabled="!horaSelecionada" @click="continuar()">
                    Continuar
                </button>
            </div>

            {{-- Passo 2: dados do solicitante --}}
            <form x-show="etapa === 'dados'" x-cloak method="POST" action="{{ route('publico.agendar', $agenda->slug) }}">
                @csrf
                <input type="hidden" name="data" :value="dataSelecionada">
                <input type="hidden" name="hora" :value="horaSelecionada">

                <button type="button" class="btn-link" @click="voltar()" style="margin-bottom: 16px;">← Alterar horário</button>

                <div class="alert alert-info">
                    <strong x-text="resumo"></strong><br>
                    {{ $agenda->duracao_minutos }} min · {{ $agenda->local }}
                </div>

                @error('data') <p class="form-error">{{ $message }}</p> @enderror
                @error('hora') <p class="form-error">{{ $message }}</p> @enderror

                <div class="form-group">
                    <label for="nome">Nome completo</label>
                    <input id="nome" name="nome" class="form-control" value="{{ old('nome') }}" required maxlength="150" autocomplete="name">
                    @error('nome') <span class="form-error">{{ $message }}</span> @enderror
                </div>
                <div class="form-group">
                    <label for="matricula">Matrícula</label>
                    <input id="matricula" name="matricula" class="form-control mono" value="{{ old('matricula') }}" required maxlength="30">
                    @error('matricula') <span class="form-error">{{ $message }}</span> @enderror
                </div>
                <div class="form-group">
                    <label for="email">E-mail</label>
                    <input id="email" name="email" type="email" class="form-control" value="{{ old('email') }}" required maxlength="150" autocomplete="email">
                    @error('email') <span class="form-error">{{ $message }}</span> @enderror
                </div>

                <button type="submit" class="btn-primary" style="width: 100%; margin-top: 16px; border-radius: 8px;">Confirmar Agendamento</button>
            </form>
        </div>
    </div>
@endsection
