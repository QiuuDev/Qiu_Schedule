<?php
require_once __DIR__ . '/../core/ApiController.php';
require_once __DIR__ . '/../models/Event.php';
require_once __DIR__ . '/../models/Calendar.php';

class EventController extends ApiController {

    private $eventModel;
    private $calendarModel;

    public function __construct(PDO $db) {
        parent::__construct($db);
        $this->eventModel = new Event($db);
        $this->calendarModel = new Calendar($db);
    }

    // ini fungsi untuk mencari semua acara pengguna wak
    protected function getAction(): void {
        if (isset($_GET['action'])) {
            switch ($_GET['action']) {
                case 'search':
                    $this->searchAllUserEvents();
                    return;
                case 'upcoming':
                    $this->getUpcomingEvents();
                    return;
                case 'analytics':
                    $this->getAnalyticsData();
                    return;
            }
        }

        if (isset($_GET['event_id'])) {
            $this->getSingleEvent((int)$_GET['event_id']);
        } else {
            $this->getAllVisibleEvents();
        }
    }

    // ini fungsi untuk membuat acara baru wak
    protected function postAction(): void {
        $data = $this->getRequestData();

        // Aksi baru untuk merespons undangan
        if (isset($data->action) && $data->action === 'respond_invitation') {
            $this->respondToInvitation($data);
            return;
        }

        if (isset($data->action) && $data->action === 'mark_notified') {
            $this->markEventAsNotified($data);
            return;
        }

        if (empty($data->title) || empty($data->start) || empty($data->end) || !isset($data->calendar_id)) {
            $this->sendResponse(400, ["message" => "Data acara tidak lengkap."]);
        }

        $calendarId = (int)$data->calendar_id;
        $this->authorizeEdit($calendarId);

        $eventData = [
            'calendar_id' => $calendarId, 'user_id' => $this->userId,
            'title' => htmlspecialchars(strip_tags($data->title)),
            'description' => htmlspecialchars(strip_tags($data->description ?? '')),
            'start_time'  => $data->start, 'end_time' => $data->end,
            'is_all_day'  => (int)($data->allDay ?? false),
            'location'    => htmlspecialchars(strip_tags($data->location ?? '')),
            'participants'=> array_map('intval', $data->participants ?? []),
            'reminder_minutes' => (int)($data->reminder_minutes ?? 30)
        ];

        $eventId = $this->eventModel->create($eventData);
        $this->sendResponse(201, ["message" => "Acara berhasil dibuat.", "id" => $eventId]);
    }

    // ini fungsi untuk memperbarui acara wak
    protected function putAction(): void {
        $data = $this->getRequestData();
        if (empty($data->id) || empty($data->title) || empty($data->start) || empty($data->end) || !isset($data->calendar_id)) {
            $this->sendResponse(400, ["message" => "Data pembaruan acara tidak lengkap."]);
        }

        $eventId = (int)$data->id;
        $event = $this->authorizeAccess($eventId);
        $this->authorizeEdit($event['calendar_id']);

        $eventData = [
            'calendar_id' => (int)$data->calendar_id, 'user_id' => $this->userId,
            'title' => htmlspecialchars(strip_tags($data->title)),
            'description' => htmlspecialchars(strip_tags($data->description ?? '')),
            'start_time'  => $data->start, 'end_time' => $data->end,
            'is_all_day'  => (int)($data->allDay ?? false),
            'location'    => htmlspecialchars(strip_tags($data->location ?? '')),
            'participants'=> array_map('intval', $data->participants ?? []),
            'reminder_minutes' => (int)($data->reminder_minutes ?? 30)
        ];

        $this->eventModel->update($eventId, $eventData);
        $this->sendResponse(200, ["message" => "Acara berhasil diperbarui."]);
    }

    // ini fungsi untuk mengambil acara yang akan datang wak
    private function getUpcomingEvents(): void {
        $events = $this->eventModel->getUpcomingEventsForNotification($this->userId);
        $this->sendResponse(200, $events);
    }

    // ini fungsi untuk merespons undangan acara wak
    private function respondToInvitation(stdClass $data): void {
        if (empty($data->event_id) || empty($data->status)) {
            $this->sendResponse(400, ["message" => "Data respons tidak lengkap."]);
        }

        $eventId = (int)$data->event_id;
        $status = (string)$data->status;

        $success = $this->eventModel->respondToInvitation($eventId, $this->userId, $status);

        if ($success) {
            $this->sendResponse(200, ["message" => "Respons undangan berhasil disimpan."]);
        } else {
            $this->sendResponse(500, ["message" => "Gagal menyimpan respons undangan."]);
        }
    }

    // ini fungsi untuk mengambil data analitik acara wak
    private function getAnalyticsData(): void {
        $period = $_GET['period'] ?? 'this_month';
        $now = new DateTime('now', new DateTimeZone('UTC'));

        switch ($period) {
            case 'last_month':
                $startDate = (clone $now)->modify('first day of last month')->format('Y-m-d 00:00:00');
                $endDate = (clone $now)->modify('last day of last month')->format('Y-m-d 23:59:59');
                break;
            case 'this_year':
                $startDate = (clone $now)->modify('first day of january this year')->format('Y-m-d 00:00:00');
                $endDate = (clone $now)->modify('last day of december this year')->format('Y-m-d 23:59:59');
                break;
            case 'this_month':
            default:
                $startDate = (clone $now)->modify('first day of this month')->format('Y-m-d 00:00:00');
                $endDate = (clone $now)->modify('last day of this month')->format('Y-m-d 23:59:59');
                break;
        }

        $analyticsData = $this->eventModel->getAnalyticsByCategory($this->userId, $startDate, $endDate);
        $this->sendResponse(200, $analyticsData);
    }

    private function markEventAsNotified(stdClass $data): void {
        if (empty($data->event_id)) {
            $this->sendResponse(400, ["message" => "Event ID diperlukan."]);
        }
        $eventId = (int)$data->event_id;
        // bagian ini untuk otorisasi yang memastikan pengguna adalah pemilik acara
        $event = $this->eventModel->findById($eventId);
        if (!$event || ($event['user_id'] != $this->userId && !in_array($this->userId, array_column($event['participants'], 'id')))) {
            $this->sendResponse(403, ["message" => "Akses ditolak."]);
        }

        $this->eventModel->markAsNotified($eventId);
        $this->sendResponse(200, ["message" => "Notifikasi ditandai terkirim."]);
    }

    // ini fungsi untuk menghapus acara wak
    protected function deleteAction(): void {
        $data = $this->getRequestData();
        if (empty($data->id)) {
            $this->sendResponse(400, ["message" => "ID acara tidak disertakan."]);
        }

        $eventId = (int)$data->id;
        $event = $this->authorizeAccess($eventId);
        $this->authorizeEdit($event['calendar_id']);

        $this->eventModel->delete($eventId);
        $this->sendResponse(200, ["message" => "Acara berhasil dihapus."]);
    }

    // ini fungsi untuk mencari semua acara pengguna wak
    private function searchAllUserEvents(): void {
        if (empty($_GET['q'])) {
            $this->sendResponse(200, []);
            return;
        }
        $searchTerm = trim($_GET['q']);
        $events = $this->eventModel->searchAllEventsForUser($this->userId, $searchTerm);
        $this->sendResponse(200, $events);
    }

    // ini fungsi untuk mengambil semua acara yang terlihat wak
    private function getAllVisibleEvents(): void {
        $accessibleCalendars = $this->calendarModel->getAllForUser($this->userId);
        $allCalendarIds = array_column($accessibleCalendars, 'id');
        $visibleIdsStr = $_GET['visible_calendars'] ?? '';
        $requestedIds = !empty($visibleIdsStr) ? explode(',', $visibleIdsStr) : $allCalendarIds;
        $finalVisibleIds = array_intersect($allCalendarIds, array_map('intval', $requestedIds));

        $events = $this->eventModel->getVisibleEvents($this->userId, $finalVisibleIds);

        $this->sendResponse(200, $events);
    }

    // ini fungsi untuk mengambil acara secara tunggal wak
    private function getSingleEvent(int $eventId): void {
        $event = $this->authorizeAccess($eventId);
        $this->sendResponse(200, $event);
    }

    // ini fungsi untuk otorisasi akses ke acara wak
    private function authorizeAccess(int $eventId): array {
        $event = $this->eventModel->findById($eventId);
        if (!$event) {
            $this->sendResponse(404, ["message" => "Acara tidak ditemukan."]);
        }

        // Cek apakah user adalah pembuat ATAU peserta
        $participantIds = array_column($event['participants'], 'id');
        if ($event['user_id'] != $this->userId && !in_array($this->userId, $participantIds)) {
            $this->sendResponse(403, ["message" => "Anda tidak memiliki izin untuk mengakses acara ini."]);
        }

        return $event;
    }

    // ini fungsi untuk otorisasi edit acara wak
    private function authorizeEdit(int $calendarId): void {
        $permission = $this->calendarModel->checkPermission($calendarId, $this->userId);
        if (!$permission || !in_array($permission['permission_level'], ['owner', 'can_edit', 'can_edit_and_share'])) {
            $this->sendResponse(403, ["message" => "Anda tidak memiliki izin untuk mengubah acara di kalender ini."]);
        }
    }
}
?>