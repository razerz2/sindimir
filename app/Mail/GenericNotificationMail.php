<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class GenericNotificationMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly string $subjectLine,
        public readonly string $body
    ) {
    }

    public function build(): self
    {
        return $this->subject($this->subjectLine)
            ->text('emails.generic-notification');
    }
}
