<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AgendarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'nome' => trim((string) $this->input('nome')),
            'matricula' => preg_replace('/\s+/', '', (string) $this->input('matricula')),
            'email' => mb_strtolower(trim((string) $this->input('email'))),
        ]);
    }

    /** Em caso de erro, volta sempre para a página da agenda (nunca para o JSON de horários). */
    protected function getRedirectUrl(): string
    {
        return route('publico.agenda', $this->route('slug'));
    }

    public function rules(): array
    {
        return [
            'data' => ['required', 'date_format:Y-m-d'],
            'hora' => ['required', 'date_format:H:i'],
            'nome' => ['required', 'string', 'min:3', 'max:150'],
            'matricula' => ['required', 'string', 'max:30', 'regex:/^[0-9A-Za-z.\-]+$/'],
            'email' => ['required', 'email', 'max:150'],
        ];
    }
}
