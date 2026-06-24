<?php
class Calendar {
    private $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    // ini fungsi untuk mengambil semua kalender yang dimiliki oleh pengguna
    public function getAllForUser(int $userId): array {
        $query = "
            (SELECT id, name, color, is_default, 'owner' as permission_level
             FROM calendars
             WHERE user_id = :user_id)
            UNION
            (SELECT c.id, c.name, c.color, c.is_default, sc.permission_level 
             FROM calendars c 
             JOIN shared_calendars sc ON c.id = sc.calendar_id 
             WHERE sc.user_id = :user_id_shared)";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([':user_id' => $userId, ':user_id_shared' => $userId]);
        
        $calendars = $stmt->fetchAll();
        foreach ($calendars as &$cal) {
            $cal['is_default'] = (bool)$cal['is_default'];
        }
        return $calendars;
    }

    // ini fungsi untuk membuat kalender baru wak
    public function create(int $userId, string $name, string $color): int {
        $query = "INSERT INTO calendars (user_id, name, color, is_default) VALUES (:user_id, :name, :color, 0)";
        $stmt = $this->db->prepare($query);
        $stmt->execute([
            ':user_id' => $userId,
            ':name' => $name,
            ':color' => $color
        ]);
        return (int)$this->db->lastInsertId();
    }

    // ini fungsi untuk memperbarui kalender wak
    public function update(int $calendarId, string $name, string $color): bool {
        $query = "UPDATE calendars SET name = :name, color = :color WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->execute([
            ':name' => $name,
            ':color' => $color,
            ':id' => $calendarId
        ]);
        return $stmt->rowCount() > 0;
    }

    // ini fungsi untuk menghapus kalender wak
    public function delete(int $calendarId): bool {
        $stmt = $this->db->prepare("DELETE FROM calendars WHERE id = :id");
        return $stmt->execute([':id' => $calendarId]);
    }

    // ini fungsi untuk memeriksa izin pengguna terhadap kalender tertentu wak
    public function checkPermission(int $calendarId, int $userId) {
        // Cek apakah pengguna adalah pemilik
        $stmt = $this->db->prepare("SELECT id, is_default, 'owner' as permission_level FROM calendars WHERE id = :id AND user_id = :user_id");
        $stmt->execute([':id' => $calendarId, ':user_id' => $userId]);
        $permission = $stmt->fetch();

        if ($permission) {
            return $permission;
        }

        $stmt = $this->db->prepare("SELECT calendar_id as id, permission_level FROM shared_calendars WHERE calendar_id = :id AND user_id = :user_id");
        $stmt->execute([':id' => $calendarId, ':user_id' => $userId]);

        return $stmt->fetch();
    }
}
?>