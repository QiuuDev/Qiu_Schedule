<?php
require_once __DIR__ . '/../core/ApiController.php';
require_once __DIR__ . '/../models/Calendar.php';

class CalendarController extends ApiController {

    private $calendarModel;

    public function __construct(PDO $db) {
        parent::__construct($db);
        $this->calendarModel = new Calendar($db);
    }

    // fungsi GET request untuk mengambil semua data kalender pengguna ni wak
    protected function getAction(): void {
        $calendars = $this->calendarModel->getAllForUser($this->userId);
        $this->sendResponse(200, ["records" => $calendars]);
    }

    // ini fungsi untuk membuat kalender baru wak.
    protected function postAction(): void {
        $data = $this->getRequestData();
        if (empty($data->name) || empty($data->color)) {
            $this->sendResponse(400, ["message" => "Nama dan warna kalender wajib diisi."]);
        }

        try {
            $calendarId = $this->calendarModel->create(
                $this->userId,
                htmlspecialchars(strip_tags($data->name)),
                htmlspecialchars(strip_tags($data->color))
            );
            $this->sendResponse(201, ["message" => "Kalender berhasil dibuat.", "id" => $calendarId]);
        } catch (Exception $e) {
            $this->sendResponse(503, ["message" => "Gagal membuat kalender."]);
        }
    }

    // ini fungsi put request untuk memperbarui kalender wak
    protected function putAction(): void {
        $data = $this->getRequestData();
        if (empty($data->id) || empty($data->name) || empty($data->color)) {
            $this->sendResponse(400, ["message" => "Data pembaruan kalender tidak lengkap."]);
        }
        
        $calendarId = (int)$data->id;

        // bagian ini untuk otorisasi yang memastikan pengguna adalah pemilik kalender wak
        $permission = $this->calendarModel->checkPermission($calendarId, $this->userId);
        if (!$permission || $permission['permission_level'] !== 'owner') {
            $this->sendResponse(403, ["message" => "Anda tidak memiliki izin untuk mengedit kalender ini."]);
        }

        $this->calendarModel->update(
            $calendarId,
            htmlspecialchars(strip_tags($data->name)),
            htmlspecialchars(strip_tags($data->color))
        );
        $this->sendResponse(200, ["message" => "Kalender berhasil diperbarui."]);
    }

    // ini fungsi untuk menghapus kalender wak
    protected function deleteAction(): void {
        $data = $this->getRequestData();
        if (empty($data->id)) {
            $this->sendResponse(400, ["message" => "ID kalender tidak disertakan."]);
        }

        $calendarId = (int)$data->id;

        // bagian ini untuk otorisasi yang memastikan pengguna adalah pemilik kalender wak
        $permission = $this->calendarModel->checkPermission($calendarId, $this->userId);
        if (!$permission || $permission['permission_level'] !== 'owner') {
            $this->sendResponse(403, ["message" => "Anda tidak memiliki izin untuk menghapus kalender ini."]);
        }
        if ($permission['is_default']) {
            $this->sendResponse(400, ["message" => "Kalender default tidak dapat dihapus."]);
        }

        $this->calendarModel->delete($calendarId);
        $this->sendResponse(200, ["message" => "Kalender berhasil dihapus."]);
    }
}
?>