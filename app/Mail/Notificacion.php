<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class Notificacion extends Mailable
{
    use Queueable, SerializesModels;

    public $persona;
    public $solicitud;
    public $estado;
    public $texto;
    public $empresa;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(string $student, string $solicitud, string $estado, string $texto, string $emp)
    {
        $this->persona = $student;
        $this->solicitud = $solicitud;
        $this->estado = $estado;
        $this->texto = $texto;
        $this->empresa = $emp;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('emails.message');
    }
}