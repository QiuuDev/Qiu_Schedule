<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

require_once '../config/database.php';
require_once '../helpers/auth_helper.php';
require_once './controllers/UserController.php';

secure_session_start();
authenticate();

// Inisialisasi koneksi ke database disini wak
$database = new Database();
$db = $database->getConnection();

// Inisialisasi controller dan proses request wak
$controller = new UserController($db);
$controller->processRequest();
?>