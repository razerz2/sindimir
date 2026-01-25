<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ContatoMensagemMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(public readonly array $data)
    {
    }

    public function build(): self
    {
        return $this->subject($this->data['assunto'])
            ->text('emails.contato-mensagem');
    }
}
