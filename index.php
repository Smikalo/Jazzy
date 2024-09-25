<?php
// DB connection credentials
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "proj";

$conn = new mysqli($servername, $username, $password);

if ($conn->connect_error) {
    die("Could not connect to DB: " . $conn->connect_error);
}

$sql = "CREATE DATABASE IF NOT EXISTS $dbname";

$conn->select_db($dbname);

// clicks
$sql = "CREATE TABLE IF NOT EXISTS events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    par1 INT NOT NULL,
    par2 VARCHAR(255) NOT NULL,
    total_time FLOAT NOT NULL,
    awareness_time FLOAT NOT NULL,
    event_type ENUM('pressed', 'closed') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

// user emails
$sql = "CREATE TABLE IF NOT EXISTS contacts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (email)
)";

$conn->close();

require 'model.php';

$optimizedValues = findOptimalValues(handleDatabaseOperations());
$par1=$optimizedValues['par1']; // original parameters
$par2=$optimizedValues['par2'];
$best_par1 = $optimizedValues['best_par1']; // optimized parameters
$best_par2 = $optimizedValues['best_par2'];
$predicted_probability_percent = $optimizedValues['predicted_probability_percent']; // increase in probability
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Jazzy</title>
    <link rel="icon" type="image/png" href="./logo.png">
    <link rel="stylesheet" type="text/css" href="styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Pacifico&family=Merriweather:wght@400&display=swap" rel="stylesheet">
    <style>
        /* randomized elements */
        body {
            font-family: <?php echo $par2; ?>, sans-serif;
        }
        #button {
            margin-top: <?php echo $par1;?>px;
            font-family: <?php echo $par2;?>, sans-serif;
        }

    </style>
</head>
<body>

<!-- lable and button -->
<div class="container">
    <div class="center-container" id="content">
        <div id="message">Nice<a id="dot">.</a></div>
        <button id="button">Button</button>
    </div>
</div>


<!-- paragraphs and a feedback form  -->
<div class="container" id="article">
    <p></p>
    <p>No, that button wasn't quite nice. We know that for a fact.</p>
    <p>Based on the user statistics of this site, we know that if we move this button <?php echo abs($best_par1)+$par1; ?> pixels and change its font to <?php echo $best_par2; ?>, it will bring <?php echo $predicted_probability_percent; ?>% more clicks. </p>
    <p>With our framework, you can measure such statistics for any component on your site too. You tell the neural network which component you want to test, and it will test it for you, dynamically adjusting the website to your customers' needs. </p>
    <p>And you don't need to send user data anywhere: all analytics are securely hosted on your server.</p>
    <p>If you want to help us in our development and get free one-year access to our custom profiling tools, please fill out the form:</p>

    <!-- form -->
    <div id="contact-form">
        <form action="form_handler.php" method="post">
            <label for="name">Name:</label><br>
            <input type="text" id="name" name="name" required><br>

            <label for="email">E-mail:</label><br>
            <input type="email" id="email" name="email" required><br>

            <label>
                <input type="checkbox" id="consent" name="consent" required>
                I agree to the processing of my personal data
            </label><br>

            <button type="submit">Send</button>
        </form>
    </div>
</div>


<!--<div id="timer">Awareness Time: 0.00 sec</div>-->

<script>
    (function() {
        var startTime = new Date().getTime();
        var buttonClicked = false;
        var isMouseMoving = false;
        var mouseMoveTime = 0;
        var mouseMoveStart = null;
        var par1 = <?php echo $par1; ?>;
        var par2 = '<?php echo $par2; ?>';
        var mouseStopTimeout;

        // AJAX send
        function sendDataToServer(eventType, totalTime, mouseMoveTime, par1, par2) {
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'save_event.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

            var params = 'event_type=' + encodeURIComponent(eventType) +
                '&total_time=' + encodeURIComponent(totalTime) +
                '&awareness_time=' + encodeURIComponent(mouseMoveTime) +
                '&par1=' + encodeURIComponent(par1) +
                '&par2=' + encodeURIComponent(par2);

            xhr.send(params);

            // debug
            xhr.onload = function() {
                if (xhr.status === 200) {
                    var response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        console.log('SUCCESS');
                    } else {
                        console.log('COULD NOT SAVE:', response.message);
                    }
                } else {
                    console.log('COULD NOT SEND:', xhr.status);
                }
            };

            xhr.onerror = function() {
                console.log('COULD NOT CONNECT.');
            };
        }

        // button pressed
        document.getElementById('button').addEventListener('click', function() {
            buttonClicked = true;
            var endTime = new Date().getTime();
            var totalTime = (endTime - startTime) / 1000;

            startButtonDisappearAnimation();

            sendDataToServer('pressed', totalTime, mouseMoveTime, par1, par2);
        });

        // button disappears
        function startButtonDisappearAnimation() {
            const content = document.getElementById('content');
            const article = document.getElementById('article');
            const paragraphsAndImages = article.querySelectorAll('p, img');
            const contactForm = document.getElementById('contact-form');

            content.style.opacity = 0;
            content.style.transform = 'scale(0.9)';

            // paragraphs show up
            setTimeout(function() {
                content.style.display = 'none';
                article.style.display = 'block';

                paragraphsAndImages.forEach((el, index) => {
                    setTimeout(() => {
                        el.style.opacity = 1;
                    }, index * 1000); // 1 sec/element
                });

                // form
                setTimeout(() => {
                    contactForm.style.opacity = 1;
                }, paragraphsAndImages.length * 1000);
            }, 1000);
        }

        // mouse movement time = awareness time
        function handleMouseMoveStart() {
            if (!isMouseMoving) {
                isMouseMoving = true;
                mouseMoveStart = new Date().getTime();
            }
            if (mouseStopTimeout) {
                clearTimeout(mouseStopTimeout);
            }
        }
        function handleMouseMoveStop() {
            if (isMouseMoving) {
                var mouseMoveEnd = new Date().getTime();
                mouseMoveTime += (mouseMoveEnd - mouseMoveStart) / 1000;
                isMouseMoving = false;
            }
        }
        // mouse movement
        document.addEventListener('mousemove', function() {
            handleMouseMoveStart();
            if (mouseStopTimeout) {
                clearTimeout(mouseStopTimeout);
            }
            mouseStopTimeout = setTimeout(handleMouseMoveStop, 200);
        });

        // tab closed
        window.addEventListener('beforeunload', function(event) {
            if (!buttonClicked) {
                var endTime = new Date().getTime();
                var totalTime = (endTime - startTime) / 1000;
                sendDataToServer('closed', totalTime, mouseMoveTime, par1, par2);
            }
        });

        function changeTextWithAnimation(newText) { // disappearing dot
            const messageElement = document.getElementById('dot');
            messageElement.style.opacity = 0;
            setTimeout(function () {
                messageElement.textContent = newText;
                messageElement.style.opacity = 1;
            }, 1000);
        }

        setTimeout(() => { // appearing question mark
            changeTextWithAnimation('?');
        }, 5000);
    })();
</script>
</body>
</html>
