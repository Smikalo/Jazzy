<?php
// debug, remove upon prod
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
file_put_contents('php://stderr', "Метод запроса: " . $_SERVER['REQUEST_METHOD'] . PHP_EOL);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    file_put_contents('php://stderr', "Данные POST: " . print_r($_POST, true) . PHP_EOL);

    // parsing POST request
    $event_type = isset($_POST['event_type']) ? $_POST['event_type'] : '';
    $total_time = isset($_POST['total_time']) ? floatval($_POST['total_time']) : 0;
    $awareness_time = isset($_POST['awareness_time']) ? floatval($_POST['awareness_time']) : 0;
    $par1 = isset($_POST['par1']) ? intval($_POST['par1']) : null;
    $par2 = isset($_POST['par2']) ? $_POST['par2'] : '';

    // validating
    if (!in_array($event_type, ['pressed', 'closed'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Wrong event type.']);
        exit;
    }

    if ($total_time <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Wrong total time on website.']);
        exit;
    }

    // DB credentials
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "proj";

    $conn = new mysqli($servername, $username, $password, $dbname);

    if ($conn->connect_error) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'COULD NOT CONNECT TO DB: ' . $conn->connect_error]);
        exit;
    }

    // generating SQL request
    $stmt = $conn->prepare("INSERT INTO events (par1, par2, total_time, awareness_time, event_type) VALUES (?, ?, ?, ?, ?)");
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'COULD NOT SEND DB REQUEST: ' . $conn->error]);
        exit;
    }

    // inserting parameters
    $stmt->bind_param("issds", $par1, $par2, $total_time, $awareness_time, $event_type);

    // executing
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'SUCCESS']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'COULD NOT EXECUTE DB REQUEST: ' . $stmt->error]);
    }

    $stmt->close();
    $conn->close();
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
}
?>
