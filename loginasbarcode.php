<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barcode Scanner</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }

        h1,
        h2,
        h3 {
            color: #333;
        }

        .scanner-container {
            background-color: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .viewport {
            width: 100%;
            height: 300px;
            background-color: #eee;
            margin-bottom: 15px;
            position: relative;
            overflow: hidden;
            border: 2px solid #ddd;
        }

        .controls {
            display: flex;
            gap: 10px;
        }

        .button {
            padding: 10px 15px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }

        .button:disabled {
            background-color: #cccccc;
            cursor: not-allowed;
        }

        #stopButton {
            background-color: #f44336;
        }

        .result-container {
            background-color: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        #result {
            padding: 15px;
            background-color: #f9f9f9;
            border-radius: 4px;
            margin-bottom: 15px;
            min-height: 50px;
        }

        .user-info {
            padding: 15px;
            background-color: #e9f7ef;
            border-radius: 4px;
            border-left: 4px solid #4CAF50;
        }

        #userDetails {
            margin-top: 10px;
        }

        /* Scanner overlay */
        .viewport canvas,
        .viewport video {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .viewport .drawingBuffer {
            position: absolute;
            top: 0;
            left: 0;
        }

        /* Scanner guidance */
        .scanner-guide {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 70%;
            height: 2px;
            background-color: rgba(255, 0, 0, 0.5);
            z-index: 10;
        }

        .scanner-box {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 70%;
            height: 100px;
            border: 2px solid rgba(255, 0, 0, 0.5);
            z-index: 9;
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/quagga@0.12.1/dist/quagga.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="container">
        <center>
            <img src="./assets/logoblack.png" class="img-fluid" style="width:25%; text-align:center; margin-bottom:25px;">
            <h4 style="font-size:30px;">Login with Barcode</h4>
        </center>

        <div class="scanner-container">
            <div id="interactive" class="viewport">
                <div class="scanner-box"></div>
                <div class="scanner-guide"></div>
            </div>
            <div class="controls">
                <button id="startButton" class="button">Start Scanning</button>
                <button id="stopButton" class="button" disabled>Stop Scanning</button>
            </div>
        </div>

        <form action="./logqr.php" method="post">
            <div class="result-container">
                <h2>Scan Results</h2>
                <input type="text" name="result" class="form-control" id="result" readonly>
            </div>

            <div class="user-info" id="userInfo" style="display:none;">
                <input type="hidden" class="form-control" id="userDetails" readonly>
            </div>

            <input type="submit" value="Login" name="logbarcode" class="btn btn-primary mt-3">
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const startButton = document.getElementById('startButton');
            const stopButton = document.getElementById('stopButton');
            const resultDiv = document.getElementById('result');
            const userInfoDiv = document.getElementById('userInfo');
            const userDetailsDiv = document.getElementById('userDetails');

            let scanning = false;
            let lastScannedCode = '';
            let lastScanTime = 0;
            const scanCooldown = 2000; // 2 seconds cooldown between scans

            startButton.addEventListener('click', startScanning);
            stopButton.addEventListener('click', stopScanning);

            function startScanning() {
                scanning = true;
                startButton.disabled = true;
                stopButton.disabled = false;
                resultDiv.value = 'Scanning...';
                userInfoDiv.style.display = 'none';

                Quagga.init({
                    inputStream: {
                        name: "Live",
                        type: "LiveStream",
                        target: document.querySelector('#interactive'),
                        constraints: {
                            width: 640,
                            height: 480,
                            facingMode: "environment",
                            aspectRatio: {
                                min: 1,
                                max: 2
                            }
                        },
                    },
                    decoder: {
                        readers: [
                            "code_128_reader",
                            "ean_reader",
                            "ean_8_reader",
                            "code_39_reader",
                            "code_39_vin_reader",
                            "codabar_reader",
                            "upc_reader",
                            "upc_e_reader"
                        ],
                        multiple: false,
                        debug: {
                            drawBoundingBox: true,
                            showFrequency: true,
                            drawScanline: true,
                            showPattern: true
                        }
                    },
                    locator: {
                        halfSample: true,
                        patchSize: "medium", // x-small, small, medium, large, x-large
                        debug: {
                            showCanvas: true,
                            showPatches: false,
                            showFoundPatches: false,
                            showSkeleton: false
                        }
                    },
                    locate: true,
                    frequency: 10
                }, function(err) {
                    if (err) {
                        console.error(err);
                        resultDiv.value = 'Error initializing scanner: ' + err.message;
                        stopScanning();
                        return;
                    }
                    console.log("Initialization finished. Ready to start");
                    Quagga.start();
                });

                Quagga.onProcessed(function(result) {
                    const drawingCtx = Quagga.canvas.ctx.overlay;
                    const drawingCanvas = Quagga.canvas.dom.overlay;

                    if (result) {
                        if (result.boxes) {
                            drawingCtx.clearRect(0, 0, parseInt(drawingCanvas.getAttribute("width")), parseInt(drawingCanvas.getAttribute("height")));
                            result.boxes.filter(function(box) {
                                return box !== result.box;
                            }).forEach(function(box) {
                                Quagga.ImageDebug.drawPath(box, {
                                    x: 0,
                                    y: 1
                                }, drawingCtx, {
                                    color: "green",
                                    lineWidth: 2
                                });
                            });
                        }

                        if (result.box) {
                            Quagga.ImageDebug.drawPath(result.box, {
                                x: 0,
                                y: 1
                            }, drawingCtx, {
                                color: "blue",
                                lineWidth: 2
                            });
                        }

                        if (result.codeResult && result.codeResult.code) {
                            Quagga.ImageDebug.drawPath(result.line, {
                                x: 'x',
                                y: 'y'
                            }, drawingCtx, {
                                color: 'red',
                                lineWidth: 3
                            });
                        }
                    }
                });

                Quagga.onDetected(function(result) {
                    if (!scanning) return;

                    const now = Date.now();
                    const code = result.codeResult.code;

                    // Prevent duplicate scans within the cooldown period
                    if (code === lastScannedCode && (now - lastScanTime) < scanCooldown) {
                        return;
                    }

                    lastScannedCode = code;
                    lastScanTime = now;

                    // Validate the code
                    if (isValidBarcode(code)) {
                        resultDiv.value = code;
                        stopScanning();

                        // Send the barcode to the server
                        fetchUserInfo(code);
                    } else {
                        console.log("Invalid barcode format detected:", code);
                    }
                });
            }

            function stopScanning() {
                if (scanning) {
                    Quagga.stop();
                    scanning = false;
                    startButton.disabled = false;
                    stopButton.disabled = true;
                }
            }

            function fetchUserInfo(barcode) {
                resultDiv.value = barcode;

                fetch('lookup.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'barcode=' + encodeURIComponent(barcode)
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.error) {
                            userDetailsDiv.value = 'Error: ' + data.error;
                        } else {
                            userDetailsDiv.value = barcode;
                        }
                        userInfoDiv.style.display = 'block';
                    })
                    .catch(error => {
                        userDetailsDiv.value = 'Error fetching user details: ' + error.message;
                        userInfoDiv.style.display = 'block';
                    });
            }

            function isValidBarcode(code) {
                // Basic validation - adjust according to your barcode types
                if (!code || typeof code !== 'string') return false;

                // Check for common barcode patterns
                // EAN-13: 13 digits
                if (/^\d{13}$/.test(code)) return true;

                // UPC-A: 12 digits
                if (/^\d{12}$/.test(code)) return true;

                // Code 128: variable length alphanumeric
                if (/^[\x00-\x7F]{4,}$/.test(code)) return true;

                // Code 39: variable length alphanumeric with * as start/stop
                if (/^[A-Z0-9\-\.\ \$\/\+\%]{4,}$/i.test(code)) return true;

                // If none of the above, you might want to reject it
                return false;
            }
        });
    </script>

</body>

</html>