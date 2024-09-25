<?php
// Database connection credentials
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "proj";

class LogisticRegression {
    private $weights;
    private $learning_rate;

    public function __construct($learning_rate = 0.01) {
        $this->weights = [0, 0, 0]; // initial weights
        $this->learning_rate = $learning_rate;
    }

    private function sigmoid($z) {
        return 1 / (1 + exp(-$z));
    }

    public function train($data, $iterations = 1000) {
        for ($i = 0; $i < $iterations; $i++) {
            $gradient = [0, 0, 0]; // initial gradient

            foreach ($data as $row) {
                $par1 = $row['par1'];
                $par2 = $this->encodePar2($row['par2']);
                $y = $row['event_type'];

                $z = $this->weights[0] * $par1 + $this->weights[1] * $par2 + $this->weights[2]; // LK
                $predicted = $this->sigmoid($z);

                $error = $predicted - $y;

                // gradients update
                $gradient[0] += $error * $par1;
                $gradient[1] += $error * $par2;
                $gradient[2] += $error; // bias
            }

            // weights update
            $this->weights[0] -= $this->learning_rate * $gradient[0];
            $this->weights[1] -= $this->learning_rate * $gradient[1];
            $this->weights[2] -= $this->learning_rate * $gradient[2];
        }
    }

    // par2 to numbers encoding
    private function encodePar2($par2) {
        $mapping = [
            'Pacifico' => 1,
            'Merriweather' => 2,
            'Arial' => 3,
            'Courier New' => 4
        ];
        return $mapping[$par2] ?? 0; // no par2 -> 0
    }

    // 'pressed' probability
    public function predict($par1, $par2) {
        $par2 = $this->encodePar2($par2);
        $z = $this->weights[0] * $par1 + $this->weights[1] * $par2 + $this->weights[2];
        return $this->sigmoid($z);
    }

    public function getWeights() {
        return $this->weights;
    }
}

// Function to handle database operations
function handleDatabaseOperations() {
    global $servername, $username, $password, $dbname;

    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        die("Ошибка подключения к базе данных: " . $conn->connect_error);
    }
    $sql = "SELECT par1, par2, event_type FROM events";
    $result = $conn->query($sql);

    $data = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $data[] = [
                'par1' => $row['par1'],
                'par2' => $row['par2'],
                'event_type' => $row['event_type'] === 'pressed' ? 1 : 0 // converting to binary
            ];
        }
    }

    $conn->close();
    return $data;
}

// Function to find the optimal values
function findOptimalValues($data) {
    $model = new LogisticRegression();
    $model->train($data);

    $best_par1 = null;
    $best_par2 = null;
    $max_probability = 0;

    $font_options = ['Pacifico', 'Merriweather', 'Arial', 'Courier New'];

    for ($par1 = -40; $par1 <= 0; $par1++) {
        foreach ($font_options as $par2) {
            $probability = $model->predict($par1, $par2);

            if ($probability > $max_probability) {
                $max_probability = $probability;
                $best_par1 = $par1;
                $best_par2 = $par2;
            }
        }
    }

    $par1=rand(-40, 0);
    $par2=$font_options[array_rand($font_options)];
    $predicted_probability_percent = round(($max_probability - $model->predict($par1, $par2)) * 100, 2);

    if ($predicted_probability_percent <= 0) { // actually can't happen, but is usual when there is not enough data, so we hardcode
        $predicted_probability_percent = 0.01;
    }

    return [
        'par1' => $par1,
        'par2' => $par2,
        'best_par1' => $best_par1,
        'best_par2' => $best_par2,
        'predicted_probability_percent' => $predicted_probability_percent
    ];
}