<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['consent'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo 'Wrong email format.';
        exit;
    }

    // DB
    $conn = new mysqli('localhost', 'root', '', 'proj'); // DB credentials
    if ($conn->connect_error) {
        die("Connection error: " . $conn->connect_error);
    }

    // email has to be unique
    $stmt = $conn->prepare("SELECT * FROM contacts WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo 'Email already exists.';
        exit;
    } else {
        // inserting data
        $stmt = $conn->prepare("INSERT INTO contacts (name, email) VALUES (?, ?)");
        $stmt->bind_param("ss", $name, $email);
        if ($stmt->execute()) {
            echo 'SUCCESS';
            // Редирект на страницу благодарности
            header("Location: thank_you.php");
        } else {
            echo 'FORM DATA CONNECTION ERROR';
        }
    }

    $stmt->close();
    $conn->close();
}
?>
