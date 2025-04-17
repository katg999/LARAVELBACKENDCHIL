<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SchoolQueryMail extends Mailable
{
    use Queueable, SerializesModels;

    public $queryMessage;

    public function __construct($queryMessage)
    {
        $this->queryMessage = $queryMessage;
    }

    public function build()
    {
        return $this->subject('Regarding Your School Registration')
                    ->view('emails.school_query')
                    ->with(['queryMessage' => $this->queryMessage]);
    }
}
