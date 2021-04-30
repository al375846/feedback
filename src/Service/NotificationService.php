<?php

namespace App\Service;

class NotificationService {

    function sendMessage($playerIds, $message)
    {
        $content = array(
            "en" => $message,
            "es" => $message
        );

        $fields = array(
            'app_id' => "577d1044-4e8f-423a-b22d-1eedfff41a75",
            'include_player_ids' => $playerIds,
            'contents' => $content
        );

        $fields = json_encode($fields);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://onesignal.com/api/v1/notifications");
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json; charset=utf-8'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

        curl_exec($ch);
        curl_close($ch);
    }
}


