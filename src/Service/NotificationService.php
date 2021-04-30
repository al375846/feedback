<?php

namespace App\Service;

use App\Entity\Notification;
use Doctrine\ORM\EntityManagerInterface;

class NotificationService {

    /**
     * @var EntityManagerInterface
     */
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function getQueue()
    {
        return $this->queue;
    }

    function sendMessage()
    {
        $notification = $this->em->getRepository(Notification::class)->findByFisrtNotSent();
        if ($notification !== null) {
            $content = array(
                "en" => $notification->getMessage(),
                "es" => $notification->getMessage()
            );

            $fields = array(
                'app_id' => "577d1044-4e8f-423a-b22d-1eedfff41a75",
                'include_player_ids' => $notification->getPlayerids(),
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

            $notification->setSent(true);

            $this->em->persist($notification);
            $this->em->flush();
        }

    }

    function enqueueMessage($playerIds, $message) {
        $notification = new Notification();
        $notification->setMessage($message);
        $notification->setPlayerids($playerIds);
        $notification->setSent(false);

        $this->em->persist($notification);
        $this->em->flush();
    }
}


