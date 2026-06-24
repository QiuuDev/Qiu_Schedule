<?php
abstract class ApiController {

    protected $db;
    protected $userId;
    protected $requestMethod;

    public function __construct(PDO $db) {
        $this->db = $db;
        $this->requestMethod = $_SERVER['REQUEST_METHOD'];
        $this->userId = $_SESSION['user_id'] ?? 0;
    }

    // fungsi untuk menangani request masuk disini wak
    public function processRequest(): void {
        $methodName = strtolower($this->requestMethod) . 'Action';
        if (method_exists($this, $methodName)) {
            $this->{$methodName}();
        } else {
            $this->sendResponse(405, ["message" => "Metode tidak diizinkan."]);
        }
    }

    // ini fungsi untuk mengirimkan response ke client wak
    protected function sendResponse(int $statusCode, $data = null, array $headers = []): void {
        http_response_code($statusCode);
        $allHeaders = array_merge(['Content-Type' => 'application/json; charset=UTF-8'], $headers);
        foreach ($allHeaders as $name => $value) {
            header("$name: $value");
        }
        if ($data !== null) {
            echo json_encode($data);
        }
        exit();
    }

    // ini fungsi untuk mendapatkan data dari body request (untuk POST, PUT, DELETE) wak
    protected function getRequestData(): ?stdClass {
        return json_decode(file_get_contents('php://input'));
    }
}
?>