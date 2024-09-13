<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Real-Time Object Detection</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }
        h1 {
            margin-top: 20px;
            color: #333;
        }
        #video-container {
            position: relative;
            display: inline-block;
            max-width: 100%;
            margin-top: 20px;
        }
        video, canvas, img {
            width: 100%;
            height: auto;
            display: block;
            border: 1px solid #ddd;
            border-radius: 8px;
        }
        canvas {
            position: absolute;
            top: 0;
            left: 0;
            z-index: 1;
        }
        .status-container {
            margin-top: 10px;
        }
        #status {
            font-size: 18px;
            font-weight: bold;
            color: #007BFF;
        }
        .btn-container {
            margin-top: 20px;
        }
        button {
            padding: 10px 20px;
            margin: 5px;
            font-size: 16px;
            cursor: pointer;
        }
        #capture-btn {
            background-color: #007BFF;
            color: white;
            border: none;
            border-radius: 5px;
        }
        #cancel-btn {
            background-color: #dc3545;
            color: white;
            border: none;
            border-radius: 5px;
        }
        #save-btn {
            background-color: #28a745;
            color: white;
            border: none;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <h1>Real-Time Object Detection</h1>
    <div id="video-container">
        <video id="video" autoplay></video>
        <canvas id="canvas"></canvas>
        <img id="captured-image" style="display: none;" />
    </div>
    <div class="status-container">
        <p id="status">Loading...</p>
    </div>
    <div class="btn-container">
        <button id="capture-btn">Capture</button>
        <button id="cancel-btn" style="display: none;">Cancel</button>
        <button id="save-btn" style="display: none;">Save</button>
        
        <div class="d-flex justify-content-end mb-3">
                <a href="<?php echo base_url(); ?>index.php/dashboard" class="btn btn-primary">View Data</a>
            </div>
    </div>

    <script>
        const video = document.getElementById('video');
        const canvas = document.getElementById('canvas');
        const ctx = canvas.getContext('2d');
        const statusText = document.getElementById('status');
        const capturedImage = document.getElementById('captured-image');
        const captureButton = document.getElementById('capture-btn');
        const cancelButton = document.getElementById('cancel-btn');
        const saveButton = document.getElementById('save-btn');

        let detectionInterval = null;
        let detectionActive = false;
        const flaskAPIUrl = 'https://74b8-114-10-153-181.ngrok-free.app';

        // Get user media for video
        if (navigator.mediaDevices.getUserMedia) {
            navigator.mediaDevices.getUserMedia({ video: true })
            .then(function(stream) {
                video.srcObject = stream;
            })
            .catch(function(err) {
                console.error("Error accessing media devices.", err);
            });
        }

        // Draw bounding boxes on canvas
        function drawBoundingBoxes(objects) {
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

            objects.forEach(obj => {
                const [x_min, y_min, x_max, y_max] = obj.box;
                const label = obj.label;
                const confidence = (obj.confidence * 100).toFixed(2);

                ctx.strokeStyle = "#FF0000";
                ctx.lineWidth = 2;
                ctx.strokeRect(x_min, y_min, x_max - x_min, y_max - y_min);

                ctx.fillStyle = "#FF0000";
                ctx.font = "16px Arial";
                ctx.fillText(`${label} (${confidence}%)`, x_min, y_min - 10);
            });
        }

        // Start real-time detection
        function startDetection() {
            detectionActive = true;
            detectionInterval = setInterval(function() {
                canvas.width = video.videoWidth;
                canvas.height = video.videoHeight;
                ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

                const frameData = canvas.toDataURL('image/jpeg');

                fetch(`${flaskAPIUrl}/detect`, {
                    method: 'POST',
                    body: JSON.stringify({ image: frameData }),
                    headers: {
                        'Content-Type': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.logo_detected) {
                        statusText.textContent = 'Logo detected!';
                    } else {
                        drawBoundingBoxes(data.objects);
                        statusText.textContent = 'Detecting...';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
            }, 1000);
        }

        // Get user location
        function getLocation() {
            return new Promise((resolve, reject) => {
                if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(position => {
                        resolve({
                            latitude: position.coords.latitude,
                            longitude: position.coords.longitude
                        });
                    }, () => {
                        resolve({
                            latitude: 0.0,
                            longitude: 0.0
                        });
                    });
                } else {
                    resolve({
                        latitude: 0.0,
                        longitude: 0.0
                    });
                }
            });
        }

        
        // Cancel detection and restart detection
        cancelButton.addEventListener('click', () => {
            fetch(`${flaskAPIUrl}/cancel`, { method: 'POST' })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'Detection successfully canceled and reset.') {
                    capturedImage.style.display = 'none';
                    video.style.display = 'block';
                    canvas.style.display = 'block';
                    statusText.textContent = 'Restarting detection...';
                    cancelButton.style.display = 'none';
                    saveButton.style.display = 'none';
                    startDetection();
                } else {
                    console.error('Error restarting detection:', data.message);
                    statusText.textContent = 'Failed to restart detection.';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                statusText.textContent = 'Error restarting detection.';
            });
        });

        captureButton.addEventListener('click', () => {
    // Setup canvas to match the video dimensions
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    
    // Draw the current frame from the video onto the canvas
    ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
    
    // Convert the canvas image to a base64 string
    const frameData = canvas.toDataURL('image/jpeg');

    // Send the captured frame to the Flask API for saving and processing
    fetch(`${flaskAPIUrl}/manual_capture`, {
        method: 'POST',
        body: JSON.stringify({ image: frameData }),
        headers: {
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.capture_completed) {
            // Stop the detection interval if active
            if (detectionActive) {
                clearInterval(detectionInterval);
                detectionActive = false;
            }

            // Hide the video and display the canvas or captured image
            video.style.display = 'none';
            canvas.style.display = 'none';

            // Display the captured image from the response
            capturedImage.src = data.captured_image_url;
            capturedImage.fileName = data.captured_image_url.split('/').pop();  // Save file name for saving later
            capturedImage.style.display = 'block';
            
            // Show status and buttons
            statusText.textContent = 'Capture completed.';
            cancelButton.style.display = 'inline-block';
            saveButton.style.display = 'inline-block';

        } else {
            // Handle error if capture failed
            statusText.textContent = 'Capture failed: ' + data.error;
        }
    })
    .catch(error => {
        console.error('Error from Flask API:', error);
        statusText.textContent = 'Error occurred during capture.';
    });
});


// Save captured image
saveButton.addEventListener('click', async () => {
    const location = await getLocation();
    const data = {
        file_name: capturedImage.fileName,  // Send correct file name
        capture_time: new Date().toISOString(),
        latitude: location.latitude,
        longitude: location.longitude,
        delay_status: 'Unknown',
        user_id: '<?php echo $this->session->userdata('id_user'); ?>',  // Get user_id from session
        username: '<?php echo $this->session->userdata('username'); ?>'  // Get username from session
    };

    fetch(`${flaskAPIUrl}/save`, {
        method: 'POST',
        body: JSON.stringify(data),
        headers: {
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        statusText.textContent = 'Capture saved successfully.';
    })
    .catch(error => {
        console.error('Error:', error);
        statusText.textContent = 'Error saving capture.';
    });
});



        // Start detection on window load
        window.onload = startDetection;
    </script>
</body>
</html>