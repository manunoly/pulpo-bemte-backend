<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

use App\Clase;

class NotificacionClases extends Mailable
{
    use Queueable, SerializesModels;

    public $clase;
    public $transactionID;
    public $autorization;
    public $valor;
    public $alumno;
    public $profesor;
    public $empresa;
    public $envioAlumno;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(Clase $task, string $transactionID, string $autorization, string $valor, string $student, string $teacher, string $emp, bool $paraAlumno)
    {
        $this->clase = $task;
        $this->transactionID = $transactionID;
        $this->autorization = $autorization;
        $this->valor = $valor;
        $this->alumno = $student;
        $this->profesor = $teacher;
        $this->envioAlumno = $paraAlumno;
        $this->empresa = $emp;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('emails.clases');
    }
}