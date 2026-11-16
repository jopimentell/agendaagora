<?php

namespace App\Mail;

use App\Models\Agendamento;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AgendamentoConfirmado extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Agendamento $agendamento)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Agendamento confirmado — '.$this->agendamento->agenda->nome,
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.agendamento-confirmado');
    }
}
