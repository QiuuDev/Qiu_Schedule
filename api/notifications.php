<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') { exit(0); }

session_start();
include_once '../config/database.php';
include_once '../helpers/auth_helper.php';

authenticate();

// Inisialisasi koneksi ke database disini wak
$database = new Database();
$db = $database->getConnection();
$user_id = $_SESSION['user_id'];
$request_method = $_SERVER["REQUEST_METHOD"];

switch ($request_method) {
    case 'GET':
        handle_get_notifications($db, $user_id);
        break;
    case 'POST':
        handle_post_notifications($db, $user_id);
        break;
    default:
        http_response_code(405);
        echo json_encode(["message" => "Metode tidak diizinkan."]);
        break;
}

// fungsi unruk mengambil 20 notifikasi terakhir dari database disini wak
function handle_get_notifications($db, $user_id) {
    $query = "SELECT id, message, link, is_read, created_at
              FROM notifications
              WHERE user_id = :user_id
              ORDER BY created_at DESC
              LIMIT 20";

    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();

    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    http_response_code(200);
    echo json_encode(["records" => $notifications]);
}

// fungsi untuk menangani aksi POST untuk notifikasi disini wak
function handle_post_notifications($db, $user_id) {
    $data = json_decode(file_get_contents("php://input"));

    if (isset($data->mark_all_as_read) && $data->mark_all_as_read === true) {
        $query = "UPDATE notifications SET is_read = 1 WHERE user_id = :user_id AND is_read = 0";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        if ($stmt->execute()) {
            http_response_code(200);
            echo json_encode(["message" => "Semua notifikasi ditandai sebagai dibaca."]);
        } else {
            http_response_code(503);
            echo json_encode(["message" => "Gagal memperbarui notifikasi."]);
        }
    } elseif (isset($data->notification_id)) {
        $query = "UPDATE notifications SET is_read = 1 WHERE id = :notification_id AND user_id = :user_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':notification_id', $data->notification_id, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        if ($stmt->execute()) {
            http_response_code(200);
            echo json_encode(["message" => "Notifikasi ditandai sebagai dibaca."]);
        } else {
            http_response_code(503);
            echo json_encode(["message" => "Gagal memperbarui notifikasi."]);
        }
    } else {
        http_response_code(400);
        echo json_encode(["message" => "Aksi tidak valid atau data tidak lengkap."]);
    }
}
?>