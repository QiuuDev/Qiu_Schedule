<?php
class User {
    private $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    // ini fungsi untuk membuat pengguna baru wak
    public function create(string $fullName, string $email, string $password): int {
        $this->db->beginTransaction();
        try {
            $password_hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt_user = $this->db->prepare("INSERT INTO users (full_name, email, password_hash) VALUES (:full_name, :email, :password_hash)");
            $stmt_user->execute([':full_name' => $fullName, ':email' => $email, ':password_hash' => $password_hash]);
            $userId = (int)$this->db->lastInsertId();

            $stmt_cal = $this->db->prepare("INSERT INTO calendars (user_id, name, color, is_default) VALUES (:user_id, 'Pribadi', '#3b82f6', 1)");
            $stmt_cal->execute([':user_id' => $userId]);

            $this->db->commit();
            return $userId;
        } catch (PDOException $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    // ini fungsi untuk mencari pengguna berdasarkan ID wak
    public function findById(int $userId) {
        $stmt = $this->db->prepare("SELECT id, full_name, email, profile_picture FROM users WHERE id = :id");
        $stmt->execute([':id' => $userId]);
        return $stmt->fetch();
    }

    // ini fungsi untuk mencari pengguna berdasarkan email wak
    public function findByEmail(string $email) {
        $stmt = $this->db->prepare("SELECT id, full_name, email, password_hash, profile_picture FROM users WHERE email = :email LIMIT 1");
        $stmt->execute([':email' => $email]);
        return $stmt->fetch();
    }

    // ini fungsi untuk memperbarui profil pengguna wak
    public function updateProfile(int $userId, string $fullName, ?string $newPictureFilename): bool {
        $params = [':id' => $userId, ':full_name' => $fullName];
        $sql = "UPDATE users SET full_name = :full_name";

        if ($newPictureFilename) {
            $sql .= ", profile_picture = :profile_picture";
            $params[':profile_picture'] = $newPictureFilename;
        }

        $sql .= " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    // ini fungsi untuk memperbarui kata sandi pengguna wak
    public function updatePassword(int $userId, string $newPassword): bool {
        $new_password_hash = password_hash($newPassword, PASSWORD_BCRYPT);
        $stmt = $this->db->prepare("UPDATE users SET password_hash = :password_hash WHERE id = :id");
        return $stmt->execute([':password_hash' => $new_password_hash, ':id' => $userId]);
    }

    // ini fungsi untuk mencari pengguna berdasarkan nama atau email wak
    public function searchUsers(string $searchTerm, int $excludeUserId): array {
        $term = "%" . htmlspecialchars(strip_tags($searchTerm)) . "%";
        $sql = "SELECT id, full_name, email, profile_picture FROM users WHERE (full_name LIKE ? OR email LIKE ?) AND id != ? LIMIT 10";
        $stmt = $this->db->prepare($sql);
        
        // Mengikat parameter sesuai urutan placeholder (?):
        // 1. ? pertama -> $term (untuk full_name)
        // 2. ? kedua -> $term (untuk email)
        // 3. ? ketiga -> $excludeUserId
        $stmt->execute([$term, $term, $excludeUserId]);
        
        return $stmt->fetchAll();
    }
}
?>