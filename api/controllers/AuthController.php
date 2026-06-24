<?php

require_once __DIR__ . '/../core/ApiController.php';
require_once __DIR__ . '/../models/User.php';

class AuthController extends ApiController {

    private $userModel;

    // inisialisasi model User ni wak
    public function __construct(PDO $db) {
        parent::__construct($db);
        $this->userModel = new User($db);
    }

    // fungsi untuk mendapatkan data dari request GET ni wak
    protected function getAction(): void {
        $action = $_GET['action'] ?? '';
        if ($action === 'check_session') {
            $this->checkSession();
        } else {
            $this->sendResponse(404, ["message" => "Aksi tidak valid untuk metode GET."]);
        }
    }

    // nanganin request dengan POST ni wak
    protected function postAction(): void {
        $data = $this->getRequestData();
        $action = $data->action ?? '';

        switch ($action) {
            case 'register':
                $this->register($data);
                break;
            case 'login':
                $this->login($data);
                break;
            case 'logout':
                $this->logout();
                break;
            default:
                $this->sendResponse(404, ["message" => "Aksi tidak valid untuk metode POST."]);
        }
    }

    // fungsi untuk bagian register ni wak
    private function register(stdClass $data): void {
        if (empty($data->full_name) || empty($data->email) || empty($data->password) || empty($data->confirm_password)) {
            $this->sendResponse(400, ["message" => "Semua kolom wajib diisi."]);
        }
        if (!filter_var($data->email, FILTER_VALIDATE_EMAIL)) {
            $this->sendResponse(400, ["message" => "Format email tidak valid."]);
        }
        if (strlen($data->password) < 6) {
            $this->sendResponse(400, ["message" => "Kata sandi minimal harus 6 karakter."]);
        }
        if ($data->password !== $data->confirm_password) {
            $this->sendResponse(400, ["message" => "Konfirmasi kata sandi tidak cocok."]);
        }

        try {
            $this->userModel->create($data->full_name, $data->email, $data->password);
            $this->sendResponse(201, ["message" => "Registrasi berhasil."]);
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $this->sendResponse(409, ["message" => "Email sudah terdaftar."]);
            } else {
                $this->sendResponse(503, ["message" => "Gagal melakukan registrasi."]);
            }
        }
    }

    // ni fungsi buat bagian login wak
    private function login(stdClass $data): void {
        if (empty($data->email) || empty($data->password)) {
            $this->sendResponse(400, ["message" => "Email dan kata sandi harus diisi."]);
        }

        $user = $this->userModel->findByEmail($data->email);

        if ($user && password_verify($data->password, $user['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int)$user['id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['email'] = $user['email'];
            
            $this->sendResponse(200, [ "message" => "Login berhasil." ]);
        } else {
            $this->sendResponse(401, ["message" => "Login gagal. Email atau kata sandi salah."]);
        }
    }

    // yang ini bagian fungsi baut logout wak
    private function logout(): void {
        $_SESSION = [];
        session_destroy();
        $this->sendResponse(200, ["message" => "Logout berhasil."]);
    }

    // ini fungsi buat cek sesinya wak
    private function checkSession(): void {
        if (isset($_SESSION['user_id'])) {
            $this->sendResponse(200, [ "loggedIn" => true ]);
        } else {
            $this->sendResponse(200, ["loggedIn" => false]);
        }
    }
}
?>