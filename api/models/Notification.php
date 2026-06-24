<?php
class Notification {
    private $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    // Fungsi untuk membuat notifikasi baru
    public function create(int $userId, string $message, ?string $link = null): bool {
        $query = "INSERT INTO notifications (user_id, message, link) VALUES (:user_id, :message, :link)";
        $stmt = $this->db->prepare($query);
        return $stmt->execute([
            ':user_id' => $userId,
            ':message' => $message,
            ':link' => $link
        ]);
    }

    // Fungsi untuk mendapatkan notifikasi untuk pengguna
    public function getForUser(int $userId): array {
        $query = "SELECT id, message, link, is_read, created_at
                  FROM notifications
                  WHERE user_id = :user_id
                  ORDER BY created_at DESC
                  LIMIT 20";
        $stmt = $this->db->prepare($query);
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Fungsi untuk menandai notifikasi sebagai telah dibaca
    public function markAllAsRead(int $userId): bool {
        $query = "UPDATE notifications SET is_read = 1 WHERE user_id = :user_id AND is_read = 0";
        $stmt = $this->db->prepare($query);
        return $stmt->execute([':user_id' => $userId]);
    }
}
?>