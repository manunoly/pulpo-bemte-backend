<?php

namespace App;

use Illuminate\Support\Facades\Mail;
use App\Mail\Notificacion;
use App\Notificacione;

class NotificacionesPushFcm
{
    public function enviar($notification)
    {
        $url = 'https://fcm.googleapis.com/fcm/send';
        $headers = [
            'Authorization:key=AAAA2g1vE-8:APA91bF4Dq3VCN_j-nswDCoHx_r6i_9vCN3lxyX6NsbKKdtMD6PKhX80qkyyyVM7VcqRyLKgZ1hr-Zl2P41vc9zW7YF0kGC3rmjQpfT4uYbJe8cFBt7z3af7sy4Ft7tzgIyTLnvs5qJo',
            'Content-Type: application/json'
        ];

        $notificacionEnviar = [];
        $notificacionEnviar["notification"]['title'] = isset($notification['title']) ? $notification['title'] : 'Bemte';
        $notificacionEnviar["notification"]['body'] = isset($notification['body']) ? $notification['body'] : 'Nueva notificación de Bemte';
        $notificacionEnviar["notification"]['sound'] = isset($notification['sound']) ? $notification['sound'] : 'default';
        $notificacionEnviar["notification"]['click_action'] = isset($notification['click_action']) ? $notification['click_action'] : 'FCM_PLUGIN_ACTIVITY';
        $notificacionEnviar["notification"]['icon'] = isset($notification['icon']) ? $notification['icon'] : 'fcm_push_icon';

        if (isset($notification['data']))
            $notificacionEnviar['data'] = $notification['data'];

        if (isset($notification['to']))
            $notificacionEnviar['to'] = $notification['to'];
        else 
        {
            echo 'Falta el to ';
            return false;
        }

        $notificacionEnviar['priority'] = $notification['priority'] ? $notification['priority'] : 'high';

        if (isset($notification['restricted_package_name']))
            $notificacionEnviar['restricted_package_name'] = $notification['restricted_package_name'];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($notificacionEnviar));
        $result = curl_exec($ch);
        if ($result === FALSE) 
        {
            die('Oops! FCM Send Error: ' . curl_error($ch));
        }
        curl_close($ch);

        //var_dump($result);
    }

     public function enviarNotificacion($notificacion, $user)
    {
        $errorNotif = 'OK';
        try 
        {
            if ($user != null && $user->token != null)
            {
                $notificacionEnviar['to'] = $user->token;
                $notificacionEnviar['title'] = $notificacion['titulo'];
                $notificacionEnviar['body'] = $notificacion['texto'];
                $notificacionEnviar['priority'] = 'normal';
                $this->enviar($notificacionEnviar);
            }
            else
                $errorNotif = 'No se pudo encontrar el Token del Usuario a notificar';
        }
        catch (Exception $e) 
        {
            $errorNotif = $e->getMessage();
        }
        $notifBD = Notificacione::create([
                                'user_id' => $user->id,
                                'titulo' => $notificacion['titulo'],
                                'notificacion' => $notificacion['texto'],
                                'estado' => $errorNotif,
                                'leida' => false,
                                'tarea_id' => $notificacion['tarea_id'],
                                'clase_id' => $notificacion['clase_id'],
                                'chat_id' => $notificacion['chat_id'],
                                'compra_id' => $notificacion['compra_id']
                                ]);
        if ($notificacion['estado'] != 'NO')
        {
            try 
            {
                Mail::to($user->email)->send(new Notificacion(
                        $user->name, $notificacion['texto'], '', $notificacion['estado'], env('EMPRESA')));
            }
            catch (Exception $e) { }
        }
    }
}