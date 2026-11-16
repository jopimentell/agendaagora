<?php

namespace App\Http\Requests;

use App\Models\Agenda;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class AgendaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    protected function prepareForValidation(): void
    {
        $slug = Str::slug((string) ($this->input('slug') ?: $this->input('nome')));

        $this->merge([
            'slug' => $slug,
            'curso_id' => $this->input('curso_id') ?: null,
            'disponibilidades' => array_values(array_filter(
                (array) $this->input('disponibilidades', []),
                fn ($linha) => is_array($linha) && (($linha['hora_inicio'] ?? '') !== '' || ($linha['hora_fim'] ?? '') !== '')
            )),
        ]);
    }

    public function rules(): array
    {
        $tenantId = $this->user()->tenant_id;
        $agenda = $this->route('agenda');

        return [
            'servico_id' => ['required', 'integer', Rule::exists('servicos', 'id')->where(
                fn ($q) => $q->whereIn('polo_id', fn ($s) => $s->select('id')->from('polos')->where('tenant_id', $tenantId))
            )],
            'curso_id' => ['nullable', 'integer', Rule::exists('cursos', 'id')->where('tenant_id', $tenantId)],
            'responsavel_id' => ['required', 'integer', Rule::exists('users', 'id')->where('tenant_id', $tenantId)],
            'nome' => ['required', 'string', 'max:150'],
            'titulo_evento' => ['required', 'string', 'max:150'],
            'descricao' => ['nullable', 'string', 'max:2000'],
            'mensagem_responsavel' => ['nullable', 'string', 'max:1000'],
            'local' => ['nullable', 'string', 'max:150'],
            'duracao_minutos' => ['required', 'integer', 'min:5', 'max:480'],
            'antecedencia_minima_horas' => ['required', 'integer', 'min:0', 'max:720'],
            'janela_dias' => ['required', 'integer', 'min:1', 'max:365'],
            'slug' => [
                'required', 'string', 'max:100',
                Rule::unique('agendas', 'slug')->where('tenant_id', $tenantId)->ignore($agenda?->id),
            ],
            'status' => ['required', Rule::in([Agenda::STATUS_ATIVA, Agenda::STATUS_PAUSADA])],
            'disponibilidades' => ['required', 'array', 'min:1', 'max:50'],
            'disponibilidades.*.dia_semana' => ['required', 'integer', 'min:0', 'max:6'],
            'disponibilidades.*.hora_inicio' => ['required', 'date_format:H:i'],
            'disponibilidades.*.hora_fim' => ['required', 'date_format:H:i', 'after:disponibilidades.*.hora_inicio'],
        ];
    }

    public function messages(): array
    {
        return [
            'disponibilidades.required' => 'Informe pelo menos um horário de atendimento.',
            'disponibilidades.min' => 'Informe pelo menos um horário de atendimento.',
            'disponibilidades.*.hora_fim.after' => 'A hora de término deve ser depois da hora de início.',
        ];
    }

    /** Faixas do mesmo dia não podem se sobrepor. */
    public function after(): array
    {
        return [function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }
            $porDia = collect($this->input('disponibilidades'))->groupBy('dia_semana');
            foreach ($porDia as $faixas) {
                $ordenadas = $faixas->sortBy('hora_inicio')->values();
                for ($i = 1; $i < $ordenadas->count(); $i++) {
                    if ($ordenadas[$i]['hora_inicio'] < $ordenadas[$i - 1]['hora_fim']) {
                        $validator->errors()->add('disponibilidades', 'Há horários sobrepostos no mesmo dia da semana.');

                        return;
                    }
                }
            }
        }];
    }

    public function dadosAgenda(): array
    {
        return $this->safe()->except('disponibilidades');
    }

    public function disponibilidades(): array
    {
        return collect($this->validated('disponibilidades'))
            ->map(fn ($l) => [
                'dia_semana' => (int) $l['dia_semana'],
                'hora_inicio' => $l['hora_inicio'],
                'hora_fim' => $l['hora_fim'],
            ])
            ->all();
    }
}
