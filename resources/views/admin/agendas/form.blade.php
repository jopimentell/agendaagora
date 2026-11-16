@extends('layouts.app')

@section('title', $agenda->exists ? 'Editar agenda' : 'Nova agenda')

@section('content')
    @php
        $linhas = old('disponibilidades', $agenda->exists
            ? $agenda->disponibilidades->map(fn ($d) => ['dia_semana' => $d->dia_semana, 'hora_inicio' => $d->inicioHm(), 'hora_fim' => $d->fimHm()])->values()->all()
            : [['dia_semana' => 1, 'hora_inicio' => '09:00', 'hora_fim' => '12:00']]);
    @endphp

    <div style="max-width: 760px; margin: 0 auto;">
        <a href="{{ route('admin.dashboard') }}" class="btn-link">← Voltar ao painel</a>
        <h1 style="font-size: 28px; margin: 16px 0 24px;">{{ $agenda->exists ? 'Editar agenda' : 'Criar nova agenda' }}</h1>

        @if ($errors->any())
            <div class="alert alert-error" role="alert">Revise os campos destacados.</div>
        @endif

        <form method="POST" action="{{ $agenda->exists ? route('admin.agendas.update', $agenda) : route('admin.agendas.store') }}" class="card">
            @csrf
            @if ($agenda->exists) @method('PUT') @endif

            <h2 style="font-size: 18px; margin-bottom: 16px;">Atendimento</h2>

            <div class="form-row">
                <div class="form-group">
                    <label for="servico_id">Polo / Serviço</label>
                    <select id="servico_id" name="servico_id" class="form-control" required>
                        <option value="">Selecione…</option>
                        @foreach ($servicos as $s)
                            <option value="{{ $s->id }}" @selected(old('servico_id', $agenda->servico_id) == $s->id)>{{ $s->polo->nome }} — {{ $s->nome }}</option>
                        @endforeach
                    </select>
                    @error('servico_id') <span class="form-error">{{ $message }}</span> @enderror
                </div>
                <div class="form-group">
                    <label for="curso_id">Curso (opcional)</label>
                    <select id="curso_id" name="curso_id" class="form-control">
                        <option value="">Nenhum</option>
                        @foreach ($cursos as $c)
                            <option value="{{ $c->id }}" @selected(old('curso_id', $agenda->curso_id) == $c->id)>{{ $c->nome }}</option>
                        @endforeach
                    </select>
                    @error('curso_id') <span class="form-error">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="nome">Nome da agenda</label>
                    <input id="nome" name="nome" class="form-control" value="{{ old('nome', $agenda->nome) }}" required maxlength="150" placeholder="Ex.: Coordenação de ADS">
                    @error('nome') <span class="form-error">{{ $message }}</span> @enderror
                </div>
                <div class="form-group">
                    <label for="responsavel_id">Responsável</label>
                    <select id="responsavel_id" name="responsavel_id" class="form-control" required>
                        <option value="">Selecione…</option>
                        @foreach ($responsaveis as $r)
                            <option value="{{ $r->id }}" @selected(old('responsavel_id', $agenda->responsavel_id) == $r->id)>{{ $r->name }}</option>
                        @endforeach
                    </select>
                    @error('responsavel_id') <span class="form-error">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="form-group">
                <label for="titulo_evento">Título do evento</label>
                <input id="titulo_evento" name="titulo_evento" class="form-control" value="{{ old('titulo_evento', $agenda->titulo_evento) }}" required maxlength="150" placeholder="Ex.: Reunião de Orientação">
                @error('titulo_evento') <span class="form-error">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label for="descricao">Descrição</label>
                <textarea id="descricao" name="descricao" class="form-control" maxlength="2000">{{ old('descricao', $agenda->descricao) }}</textarea>
                @error('descricao') <span class="form-error">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label for="mensagem_responsavel">Mensagem do responsável</label>
                <textarea id="mensagem_responsavel" name="mensagem_responsavel" class="form-control" maxlength="1000">{{ old('mensagem_responsavel', $agenda->mensagem_responsavel) }}</textarea>
                @error('mensagem_responsavel') <span class="form-error">{{ $message }}</span> @enderror
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="local">Local</label>
                    <input id="local" name="local" class="form-control" value="{{ old('local', $agenda->local) }}" maxlength="150" placeholder="Ex.: Polo Caxias - Sala 04">
                    @error('local') <span class="form-error">{{ $message }}</span> @enderror
                </div>
                <div class="form-group">
                    <label for="slug">Endereço do link (/a/…)</label>
                    <input id="slug" name="slug" class="form-control mono" value="{{ old('slug', $agenda->slug) }}" maxlength="100" placeholder="gerado a partir do nome">
                    @error('slug') <span class="form-error">{{ $message }}</span> @enderror
                </div>
            </div>

            <h2 style="font-size: 18px; margin: 16px 0;">Regras</h2>
            <div class="form-row">
                <div class="form-group">
                    <label for="duracao_minutos">Duração (min)</label>
                    <input id="duracao_minutos" name="duracao_minutos" type="number" min="5" max="480" class="form-control" value="{{ old('duracao_minutos', $agenda->duracao_minutos) }}" required>
                    @error('duracao_minutos') <span class="form-error">{{ $message }}</span> @enderror
                </div>
                <div class="form-group">
                    <label for="antecedencia_minima_horas">Antecedência mínima (h)</label>
                    <input id="antecedencia_minima_horas" name="antecedencia_minima_horas" type="number" min="0" max="720" class="form-control" value="{{ old('antecedencia_minima_horas', $agenda->antecedencia_minima_horas) }}" required>
                    @error('antecedencia_minima_horas') <span class="form-error">{{ $message }}</span> @enderror
                </div>
                <div class="form-group">
                    <label for="janela_dias">Agendar até (dias)</label>
                    <input id="janela_dias" name="janela_dias" type="number" min="1" max="365" class="form-control" value="{{ old('janela_dias', $agenda->janela_dias) }}" required>
                    @error('janela_dias') <span class="form-error">{{ $message }}</span> @enderror
                </div>
                <div class="form-group">
                    <label for="status">Status</label>
                    <select id="status" name="status" class="form-control">
                        <option value="ativa" @selected(old('status', $agenda->status) === 'ativa')>Ativa</option>
                        <option value="pausada" @selected(old('status', $agenda->status) === 'pausada')>Pausada</option>
                    </select>
                </div>
            </div>

            <h2 style="font-size: 18px; margin: 16px 0 8px;">Horários de atendimento</h2>
            <p class="muted" style="margin-bottom: 16px;">Dentro de cada faixa, os horários são oferecidos em intervalos iguais à duração do atendimento.</p>
            @error('disponibilidades') <p class="form-error" style="margin-bottom: 8px;">{{ $message }}</p> @enderror

            <div x-data="{ linhas: @js(array_values($linhas)) }">
                <template x-for="(linha, i) in linhas" :key="i">
                    <div class="availability-row">
                        <select class="form-control" :name="`disponibilidades[${i}][dia_semana]`" x-model.number="linha.dia_semana">
                            @foreach (\App\Models\Disponibilidade::DIAS as $num => $dia)
                                <option value="{{ $num }}">{{ $dia }}</option>
                            @endforeach
                        </select>
                        <input type="time" class="form-control" :name="`disponibilidades[${i}][hora_inicio]`" x-model="linha.hora_inicio" required>
                        <input type="time" class="form-control" :name="`disponibilidades[${i}][hora_fim]`" x-model="linha.hora_fim" required>
                        <button type="button" class="btn-link" @click="linhas.splice(i, 1)" :disabled="linhas.length === 1" aria-label="Remover faixa">✕</button>
                    </div>
                </template>
                @foreach ($errors->get('disponibilidades.*') as $mensagens)
                    <p class="form-error">{{ $mensagens[0] }}</p>
                @endforeach
                <button type="button" class="btn-secondary btn-sm" style="margin-top: 8px;"
                        @click="linhas.push({ dia_semana: 1, hora_inicio: '09:00', hora_fim: '12:00' })">+ Adicionar faixa</button>
            </div>

            <div style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 32px;">
                <a href="{{ route('admin.dashboard') }}" class="btn-secondary">Cancelar</a>
                <button type="submit" class="btn-primary">{{ $agenda->exists ? 'Salvar alterações' : 'Criar agenda' }}</button>
            </div>
        </form>
    </div>
@endsection
