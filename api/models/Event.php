<?php
require_once __DIR__ . '/Notification.php';

class Event {
    private $db;
    private $notificationModel;

    public function __construct(PDO $db) {
        $this->db = $db;
        $this->notificationModel = new Notification($db);
    }

    // fungsi untuk mengambil semua acara yang dimiliki oleh pengguna
    public function getUpcomingEventsForNotification(int $userId): array {
        // Kueri 1: Mengambil acara yang dimiliki oleh pengguna
        $queryOwner = "
            SELECT id, title, start_time, reminder_minutes
            FROM events
            WHERE
                user_id = :user_id
                AND reminder_sent_at IS NULL
                AND reminder_minutes > 0
                AND NOW() >= DATE_SUB(start_time, INTERVAL reminder_minutes MINUTE)
                AND start_time > NOW()
        ";

        // Kueri 2: Mengambil acara di mana pengguna adalah peserta yang telah menerima
        $queryParticipant = "
            SELECT e.id, e.title, e.start_time, e.reminder_minutes
            FROM events e
            JOIN event_participants ep ON e.id = ep.event_id
            WHERE
                ep.user_id = :user_id
                AND ep.status = 'accepted'
                AND e.reminder_sent_at IS NULL
                AND e.reminder_minutes > 0
                AND NOW() >= DATE_SUB(e.start_time, INTERVAL e.reminder_minutes MINUTE)
                AND e.start_time > NOW()
        ";

        // Eksekusi kedua kueri
        $stmtOwner = $this->db->prepare($queryOwner);
        $stmtOwner->execute([':user_id' => $userId]);
        $ownerEvents = $stmtOwner->fetchAll();

        $stmtParticipant = $this->db->prepare($queryParticipant);
        $stmtParticipant->execute([':user_id' => $userId]);
        $participantEvents = $stmtParticipant->fetchAll();

        // Gabungkan hasil dan menghapus duplikat
        $allEvents = array_merge($ownerEvents, $participantEvents);
        $uniqueEvents = [];
        foreach ($allEvents as $event) {
            $uniqueEvents[$event['id']] = $event;
        }

        return array_values($uniqueEvents);
    }

    // fungsi untuk sinkronisasi peserta acara
    private function syncParticipants(int $eventId, array $participantIds, int $creatorId, string $eventTitle): void {
        $stmt_old = $this->db->prepare("SELECT user_id FROM event_participants WHERE event_id = ?");
        $stmt_old->execute([$eventId]);
        $oldParticipantIds = $stmt_old->fetchAll(PDO::FETCH_COLUMN);

        $this->db->prepare("DELETE FROM event_participants WHERE event_id = ?")->execute([$eventId]);

        if (empty($participantIds)) return;

        $stmt_insert = $this->db->prepare("INSERT INTO event_participants (event_id, user_id, status) VALUES (?, ?, 'pending')");
        $creatorName = $_SESSION['full_name'] ?? 'Seseorang';

        foreach ($participantIds as $pId) {
            if ($pId != $creatorId) {
                $stmt_insert->execute([$eventId, $pId]);
                if (!in_array($pId, $oldParticipantIds)) {
                    $message = "$creatorName mengundang Anda ke acara: \"$eventTitle\"";
                    $this->notificationModel->create($pId, $message, "event:$eventId");
                }
            }
        }
    }

    // fungsi untuk merespons undangan acara
    public function respondToInvitation(int $eventId, int $userId, string $status): bool {
        if (!in_array($status, ['accepted', 'declined'])) {
            return false;
        }
        $query = "UPDATE event_participants SET status = :status WHERE event_id = :event_id AND user_id = :user_id";
        $stmt = $this->db->prepare($query);
        return $stmt->execute([
            ':status' => $status,
            ':event_id' => $eventId,
            ':user_id' => $userId
        ]);
    }

    // fungsi untuk membuat acara baru
    public function create(array $data): int {
        $this->db->beginTransaction();
        try {
            $query = "INSERT INTO events (calendar_id, user_id, title, description, start_time, end_time, is_all_day, location, reminder_minutes) 
                      VALUES (:calendar_id, :user_id, :title, :description, :start_time, :end_time, :is_all_day, :location, :reminder_minutes)";
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                ':calendar_id' => $data['calendar_id'], ':user_id' => $data['user_id'],
                ':title' => $data['title'], ':description' => $data['description'],
                ':start_time' => $data['start_time'], ':end_time' => $data['end_time'],
                ':is_all_day' => $data['is_all_day'], ':location' => $data['location'],
                ':reminder_minutes' => $data['reminder_minutes']
            ]);
            $eventId = (int)$this->db->lastInsertId();

            if (!empty($data['participants'])) {
                $this->syncParticipants($eventId, $data['participants'], $data['user_id'], $data['title']);
            }

            $this->db->commit();
            return $eventId;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    // fungsi untuk memperbarui acara
    public function update(int $eventId, array $data): bool {
        $this->db->beginTransaction();
        try {
            $query = "UPDATE events SET
                        calendar_id = :calendar_id,
                        title = :title,
                        description = :description,
                        start_time = :start_time,
                        end_time = :end_time,
                        is_all_day = :is_all_day,
                        location = :location,
                        reminder_minutes = :reminder_minutes,
                        reminder_sent_at = CASE
                                            WHEN start_time != :start_time_check THEN NULL
                                            ELSE reminder_sent_at
                                           END
                      WHERE id = :id";

            $stmt = $this->db->prepare($query);
            $stmt->execute([
                ':calendar_id' => $data['calendar_id'], ':title' => $data['title'],
                ':description' => $data['description'], ':start_time' => $data['start_time'],
                ':end_time' => $data['end_time'], ':is_all_day' => $data['is_all_day'],
                ':location' => $data['location'], ':id' => $eventId,
                ':reminder_minutes' => $data['reminder_minutes'],
                ':start_time_check' => $data['start_time']
            ]);

            $this->syncParticipants($eventId, $data['participants'] ?? [], $data['user_id'], $data['title']);

            $this->db->commit();
            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    // fungsi untuk mendapatkan acara yang terlihat oleh pengguna
    public function getVisibleEvents(int $userId, array $visibleCalendarIds): array {
        $queryOwnEvents = "
            SELECT
                e.id, e.title, e.start_time as `start`, e.end_time as `end`,
                e.is_all_day as `allDay`, c.color as `backgroundColor`, c.color as `borderColor`
            FROM events e
            JOIN calendars c ON e.calendar_id = c.id
            WHERE e.calendar_id IN (" . implode(',', array_fill(0, count($visibleCalendarIds), '?')) . ")
        ";

        $queryAcceptedEvents = "
            SELECT
                e.id, e.title, e.start_time as `start`, e.end_time as `end`,
                e.is_all_day as `allDay`, '#6b7280' as `backgroundColor`, '#6b7280' as `borderColor`
            FROM events e
            JOIN event_participants ep ON e.id = ep.event_id
            WHERE ep.user_id = ? AND ep.status = 'accepted'
        ";

        $events = [];

        if (!empty($visibleCalendarIds)) {
            $stmtOwn = $this->db->prepare($queryOwnEvents);
            $stmtOwn->execute($visibleCalendarIds);
            $events = array_merge($events, $stmtOwn->fetchAll(PDO::FETCH_ASSOC));
        }

        $stmtAccepted = $this->db->prepare($queryAcceptedEvents);
        $stmtAccepted->execute([$userId]);
        $acceptedEvents = $stmtAccepted->fetchAll(PDO::FETCH_ASSOC);

        $allEvents = array_merge($events, $acceptedEvents);
        $uniqueEvents = [];
        foreach ($allEvents as $event) {
            $uniqueEvents[$event['id']] = $event;
        }

        $finalEvents = array_values($uniqueEvents);

        foreach ($finalEvents as &$event) {
            $event['allDay'] = (bool)$event['allDay'];
            $event['id'] = (int)$event['id'];
        }
        return $finalEvents;
    }

    // fungsi untuk mendapatkan acara berdasarkan ID
    public function findById(int $eventId): ?array {
        $stmt = $this->db->prepare("
            SELECT e.*, c.name as calendar_name, u.full_name as creator_name
            FROM events e
            JOIN calendars c ON e.calendar_id = c.id
            JOIN users u ON e.user_id = u.id
            WHERE e.id = ?");
        $stmt->execute([$eventId]);
        $event = $stmt->fetch();

        if (!$event) return null;

        $stmt_p = $this->db->prepare("
            SELECT u.id, u.full_name, u.email, ep.status
            FROM users u
            JOIN event_participants ep ON u.id = ep.user_id
            WHERE ep.event_id = ?");
        $stmt_p->execute([$eventId]);
        $event['participants'] = $stmt_p->fetchAll();
        return $event;
    }

    // fungsi untuk mendapatkan analitik acara berdasarkan kategori
    public function getAnalyticsByCategory(int $userId, string $startDate, string $endDate): array {
        $query = "
            SELECT
                c.name as category_name,
                c.color as category_color,
                SUM(TIMESTAMPDIFF(MINUTE, e.start_time, e.end_time)) as total_minutes
            FROM events e
            JOIN calendars c ON e.calendar_id = c.id
            WHERE
                e.user_id = :user_id
                AND e.start_time >= :start_date
                AND e.start_time <= :end_date
            GROUP BY
                c.id, c.name, c.color
            HAVING
                total_minutes > 0
            ORDER BY
                total_minutes DESC
        ";

        $stmt = $this->db->prepare($query);
        $stmt->execute([
            ':user_id' => $userId,
            ':start_date' => $startDate,
            ':end_date' => $endDate
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // fungsi untuk menandai acara sebagai telah diberitahukan
    public function markAsNotified(int $eventId): bool {
        $stmt = $this->db->prepare("UPDATE events SET reminder_sent_at = NOW() WHERE id = :id");
        return $stmt->execute([':id' => $eventId]);
    }
    public function delete(int $eventId): bool {
        $stmt = $this->db->prepare("DELETE FROM events WHERE id = ?");
        return $stmt->execute([$eventId]);
    }
    public function searchAllEventsForUser(int $userId, string $searchTerm): array {
        $accessibleCalendars = (new Calendar($this->db))->getAllForUser($userId);
        if (empty($accessibleCalendars)) {
            return [];
        }
        $calendarIds = array_column($accessibleCalendars, 'id');
        $placeholders = implode(',', array_fill(0, count($calendarIds), '?'));

        $query = "
            SELECT e.id, e.title, e.start_time as `start`
            FROM events e
            WHERE e.calendar_id IN ($placeholders)
            AND (e.title LIKE ? OR e.description LIKE ?)
            ORDER BY e.start_time ASC";

        $params = $calendarIds;
        $searchParam = "%" . $searchTerm . "%";
        array_push($params, $searchParam, $searchParam);

        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
?>