<?php
// Splash page with rotating SVG animation
?>
<!DOCTYPE html>
<html>

<head>
    <meta http-equiv="refresh" content="2;url=./login.php">
    <script>
        // Change SVG circle color every 400ms
        document.addEventListener("DOMContentLoaded", function () {
            const colors = ["#5D6773", "#54A9D1", "#EEDD5F", "#80BF93", "#E06758"];
            const circle = document.querySelector("svg circle");
            let idx = 0;
            setInterval(() => {
                circle.setAttribute("stroke", colors[idx]);
                idx = (idx + 1) % colors.length;
            }, 400);
        });
    </script>
    <title>Inventory System</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background: #f5f5f5;
        }

        .splash {
            text-align: center;
        }

        .splash h1 {
            font-size: 2.5em;
            color: #333;
        }

        .splash p {
            font-size: 1.2em;
            color: #666;
        }

        .splash img {
            width: 120px;
            margin-bottom: 20px;
        }
    </style>
</head>

<body>
    <div class="splash">
        <img src="assets/logoblack.png" alt="Inventory Logo">
        <h1>Welcome to Inventory System</h1>
        <div>
            <svg width="60" height="60" viewBox="0 0 50 50" xmlns="http://www.w3.org/2000/svg">
                <circle cx="25" cy="25" r="20" stroke="#333" stroke-width="5" fill="none" stroke-linecap="round">
                    <animate
                        attributeName="stroke-dasharray"
                        values="10,40;40,10;10,40"
                        dur="1s"
                        repeatCount="indefinite" />
                    <animateTransform
                        attributeName="transform"
                        type="rotate"
                        from="0 25 25"
                        to="360 25 25"
                        dur="1s"
                        repeatCount="indefinite" />
                </circle>
            </svg>
            <p>Loading, please wait...</p>
        </div>
    </div>
</body>

</html>
<?php
exit;
//header("Location: ./login.php");
?>