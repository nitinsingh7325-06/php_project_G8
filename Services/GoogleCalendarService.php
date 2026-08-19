<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

class GoogleCalendarService
{
    public function createEvent(array $event, int $appointmentId): ?string
    {
        $calendarId = env('GOOGLE_CALENDAR_ID');
        $creds = env('GCP_CREDENTIALS_PATH');
        if (!$calendarId || !$creds || !file_exists((string) $creds) || !class_exists(\Google_Client::class)) {
            return null;
        }

        try {
            $client = new \Google_Client();
            $client->setAuthConfig($creds);
            $client->addScope(\Google_Service_Calendar::CALENDAR);
            $service = new \Google_Service_Calendar($client);

            $start = $event['date'] . 'T' . substr($event['start'], 0, 8);
            $end = $event['date'] . 'T' . substr($event['end'], 0, 8);
            $gEvent = new \Google_Service_Calendar_Event([
                'summary' => $event['summary'],
                'description' => 'The Wave Men\'s Salon booking ' . $event['booking_id'],
                'start' => ['dateTime' => $start, 'timeZone' => env('TIMEZONE', 'Asia/Kolkata')],
                'end' => ['dateTime' => $end, 'timeZone' => env('TIMEZONE', 'Asia/Kolkata')],
            ]);
            $created = $service->events->insert($calendarId, $gEvent);
            $eventId = $created->getId();
            Database::query('UPDATE appointments SET calendar_event_id = ? WHERE id = ?', [$eventId, $appointmentId]);
            return $eventId;
        } catch (\Throwable $e) {
            log_message('warning', 'Google Calendar sync failed', ['error' => $e->getMessage()]);
            return null;
        }
    }
}
