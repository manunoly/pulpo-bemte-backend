<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class Bienvenida extends Mailable
{
    use Queueable, SerializesModels;

    public $persona;
    public $empresa;
    public $url;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(string $person, $url, string $emp)
    {
        $this->persona = $person;
        $this->url = $url;
        $this->empresa = $emp;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('emails.bienvenida');
    }
}