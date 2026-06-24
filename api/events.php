<?php
// ngatur zona waktu ke UTC disini wak
date_default_timezone_set('UTC');

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

require_once '../config/database.php';
require_once '../helpers/auth_helper.php';
require_once './controllers/EventController.php';

secure_session_start();

// otentikasi pengguna sebelum mengakses endpoint ini wak
authenticate();

// menginisialisasi koneksi ke database ni wak
$database = new Database();
$db = $database->getConnection();

// menginisialisasi controller dan proses request wak
$controller = new EventController($db);
$controller->processRequest();
?>