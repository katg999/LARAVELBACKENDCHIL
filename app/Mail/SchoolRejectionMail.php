<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SchoolRejectionMail extends Mailable
{
    use Queueable, SerializesModels;

    public $rejectionMessage;

    public function __construct($rejectionMessage)
    {
        $this->rejectionMessage = $rejectionMessage;
    }

    public function build()
    {
        return $this->subject('Your School Registration Status')
                    ->view('emails.school_rejection')
                    ->with(['rejectionMessage' => $this->rejectionMessage]);
    }
}
