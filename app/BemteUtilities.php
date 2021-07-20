<?php

namespace App;
use DateTime;

class BemteUtilities
{
    public static function getUrl() {
        // DEVELOPMENT ===================================
        return 'https://ccapi-stg.paymentez.com/v2/';

        // PRODUCTION ===================================
        // return 'https://ccapi.paymentez.com/v2/';
    }

    public static function getAuthToken() {
        try {
            // DEVELOPMENT ===================================
            $API_LOGIN_DEV = "TPP3-EC-SERVER";
            $API_KEY_DEV = "JdXTDl2d0o0B8ANZ1heJOq7tf62PC6";

            // PRODUCTION ===================================
            // $API_LOGIN_DEV = "TPP3-EC-SERVER";
            // $API_KEY_DEV = "JdXTDl2d0o0B8ANZ1heJOq7tf62PC6  ";

            $server_application_code = $API_LOGIN_DEV;
            $server_app_key = $API_KEY_DEV ;

            $date = new DateTime();
            $unix_timestamp = $date->getTimestamp();
            $uniq_token_string = $server_app_key.$unix_timestamp;
            $uniq_token_hash = hash('sha256', $uniq_token_string);
            $auth_token = base64_encode($server_application_code.";".$unix_timestamp.";".$uniq_token_hash);
            return $auth_token;
        } catch (Exception $e) {
            return null;
        }
    }
}
