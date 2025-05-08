<?php

namespace Modules\Asset\Domain\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class UploadMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $messageContent;

    public function __construct(
        protected string $subjectLine,
        string $messageContent
    ) {
        $this->messageContent = $messageContent;
    }

    public function build(): self
    {
        return $this->subject($this->subjectLine)
            ->view('emails.template1');
    }
}