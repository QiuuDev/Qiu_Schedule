<?php
require_once __DIR__ . '/../core/ApiController.php';
require_once __DIR__ . '/../models/User.php';

class UserController extends ApiController {

    private $userModel;

    public function __construct(PDO $db) {
        parent::__construct($db);
        $this->userModel = new User($db);
    }

    // ini fungsi untuk mencari pengguna dan mendapatkan profil pengguna yang sedang login wak
    protected function getAction(): void {
        if (isset($_GET['search'])) {
            $users = $this->userModel->searchUsers($_GET['search'], $this->userId);
            $this->sendResponse(200, ["records" => $users]);
            return;
        }

        $user = $this->userModel->findById($this->userId);
        if ($user) {
            $this->sendResponse(200, $user);
        } else {
            $this->sendResponse(404, ["message" => "Pengguna tidak ditemukan."]);
        }
    }

    // ini fungsi untuk memperbarui profil pengguna wak
    protected function postAction(): void {

        if (!isset($_POST['full_name']) || empty(trim($_POST['full_name']))) {
            $this->sendResponse(400, ["message" => "Nama lengkap harus diisi."]);
        }
        $fullName = htmlspecialchars(strip_tags($_POST['full_name']));

        if (!empty($_POST['new_password'])) {
            $this->handleChangePassword($_POST);
        }

        $newPictureFilename = null;
        if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
            $newPictureFilename = $this->handleProfilePictureUpload();
        }

        $this->userModel->updateProfile($this->userId, $fullName, $newPictureFilename);

        $_SESSION['full_name'] = $fullName;
        if ($newPictureFilename) {
            $_SESSION['profile_picture'] = $newPictureFilename;
        }

        $this->sendResponse(200, [
            "message" => "Profil berhasil diperbarui.",
            "user" => [
                "full_name" => $_SESSION['full_name'],
                "profile_picture" => $_SESSION['profile_picture'] ?? $this->userModel->findById($this->userId)['profile_picture']
            ]
        ]);
    }

    // ini fungsi untuk mengubah kata sandi pengguna wak
    private function handleChangePassword(array $postData): void {
        if (empty($postData['current_password']) || empty($postData['confirm_new_password'])) {
            $this->sendResponse(400, ["message" => "Untuk mengubah kata sandi, semua kolom kata sandi harus diisi."]);
        }
        if ($postData['new_password'] !== $postData['confirm_new_password']) {
            $this->sendResponse(400, ["message" => "Konfirmasi kata sandi baru tidak cocok."]);
        }

        $user = $this->userModel->findByEmail($_SESSION['email']);
        if (!$user || !password_verify($postData['current_password'], $user['password_hash'])) {
            $this->sendResponse(401, ["message" => "Kata sandi saat ini salah."]);
        }

        $this->userModel->updatePassword($this->userId, $postData['new_password']);
    }

    // ini fungsi untuk mengunggah foto profil pengguna wak
    private function handleProfilePictureUpload(): ?string {
        $target_dir = __DIR__ . "/../../uploads/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0755, true);
        }

        $file = $_FILES['profile_picture'];
        if ($file['size'] > 2000000) { // ukuran maksimal 2MB
            $this->sendResponse(400, ["message" => "Ukuran file terlalu besar (Maks 2MB)."]);
        }
        $file_extension = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
        if (!in_array($file_extension, $allowed_types)) {
            $this->sendResponse(400, ["message" => "Hanya format JPG, JPEG, PNG & GIF yang diizinkan."]);
        }

        $new_filename = 'user_' . $this->userId . '_' . time() . '.' . $file_extension;
        $target_file = $target_dir . $new_filename;

        if (move_uploaded_file($file["tmp_name"], $target_file)) {
            $old_pic = $this->userModel->findById($this->userId)['profile_picture'];
            if ($old_pic && $old_pic != 'default_avatar.png' && file_exists($target_dir . $old_pic)) {
                unlink($target_dir . $old_pic);
            }
            return $new_filename;
        } else {
            $this->sendResponse(500, ["message" => "Gagal mengunggah file."]);
        }
        return null;
    }
}
?>