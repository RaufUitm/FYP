<?php
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$username = $_SESSION['username'];
$email = $_SESSION['email'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Fault Prediction System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }

        .navbar {
            background: rgba(255, 255, 255, 0.95);
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            backdrop-filter: blur(10px);
        }

        .navbar h1 {
            color: #2c3e50;
            font-size: 24px;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            background: #4b7bec;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 16px;
        }

        .logout-btn, .history-btn { /* Added .history-btn */
            background: #ff6b6b;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            transition: background 0.3s ease;
        }

        .logout-btn:hover {
            background: #ff5252;
        }

        .history-btn { /* Style for history button, make it distinct if desired */
            background: #4CAF50; /* Example: Green color for history */
        }

        .history-btn:hover {
            background: #45a049;
        }

        .container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .welcome-card {
            background: rgba(255, 255, 255, 0.95);
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            backdrop-filter: blur(10px);
        }

        .welcome-card h2 {
            color: #2c3e50;
            margin-bottom: 10px;
            font-size: 28px;
        }

        .welcome-card p {
            color: #666;
            font-size: 16px;
            line-height: 1.6;
        }

        /* Prediction Upload Section */
        .prediction-section {
            background: rgba(255, 255, 255, 0.95);
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            backdrop-filter: blur(10px);
        }

        .prediction-section h2 {
            color: #2c3e50;
            margin-bottom: 20px;
            font-size: 24px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .upload-area {
            border: 2px dashed #4b7bec;
            border-radius: 10px;
            padding: 40px;
            text-align: center;
            background: rgba(75, 123, 236, 0.05);
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }

        .upload-area:hover {
            border-color: #3867d6;
            background: rgba(75, 123, 236, 0.1);
        }

        .upload-area.drag-over {
            border-color: #26de81;
            background: rgba(38, 222, 129, 0.1);
        }

        .upload-icon {
            font-size: 48px;
            color: #4b7bec;
            margin-bottom: 15px;
        }

        .upload-text {
            color: #666;
            font-size: 16px;
            margin-bottom: 15px;
        }

        .file-input {
            display: none;
        }

        .file-input-label {
            background: #4b7bec;
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            display: inline-block;
            transition: background 0.3s ease;
            font-weight: 500;
        }

        .file-input-label:hover {
            background: #3867d6;
        }

        .selected-file {
            margin-top: 15px;
            padding: 10px 15px;
            background: rgba(38, 222, 129, 0.1);
            border: 1px solid #26de81;
            border-radius: 8px;
            color: #20bf6b;
            display: none;
        }

        .predict-btn {
            background: #26de81;
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
            transition: all 0.3s ease;
            display: none;
            margin-top: 15px;
        }

        .predict-btn:hover {
            background: #20bf6b;
            transform: translateY(-2px);
        }

        .predict-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
        }

        .loading {
            display: none;
            text-align: center;
            margin-top: 20px;
        }

        .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #4b7bec;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 10px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .error-message {
            background: rgba(255, 107, 107, 0.1);
            border: 1px solid #ff6b6b;
            color: #ff5252;
            padding: 15px;
            border-radius: 8px;
            margin-top: 15px;
            display: none;
        }

        .success-message {
            background: rgba(38, 222, 129, 0.1);
            border: 1px solid #26de81;
            color: #20bf6b;
            padding: 15px;
            border-radius: 8px;
            margin-top: 15px;
            display: none;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <h1>Fault Prediction System</h1>
        <div class="user-info">
            <div class="user-avatar">
                <?php echo strtoupper(substr($username, 0, 1)); ?>
            </div>
            <span>Welcome, <?php echo htmlspecialchars($username); ?>!</span>
            <a href="history.php" class="history-btn">History</a> <a href="logout.php" class="logout-btn">Logout</a>
        </div>
    </nav>

    <div class="container">
        <div class="welcome-card">
            <h2>Dashboard Overview</h2>
            <p>Welcome to your Fault Prediction System dashboard, <?php echo htmlspecialchars($username); ?>!.</p>
        </div>

        <div class="prediction-section">
            <h2>AI Fault Prediction</h2>
            <p style="color: #666; margin-bottom: 20px;">Upload your code metrics CSV file to get AI-powered fault predictions for your systems.</p>

            <form id="uploadForm" enctype="multipart/form-data">
                <div class="upload-area" id="uploadArea">
                    <div class="upload-icon">&#128193;</div> <div class="upload-text">
                        <strong>Drag and drop your CSV file here</strong><br>
                        or click below to browse
                    </div>
                    <label for="codeMetricsFile" class="file-input-label">
                        Choose CSV File
                    </label>
                    <input type="file" name="code_metrics_file" id="codeMetricsFile" class="file-input" accept=".csv">

                    <div class="selected-file" id="selectedFile">
                        <strong>Selected:</strong> <span id="fileName"></span>
                    </div>
                </div>

                <button type="submit" class="predict-btn" id="predictBtn">
                    &#128269; Predict Faults </button>
            </form>

            <div class="loading" id="loading">
                <div class="spinner"></div>
                <p>Analyzing your data and predicting faults...</p>
            </div>

            <div class="error-message" id="errorMessage"></div>
            <div class="success-message" id="successMessage"></div>
        </div>
    </div>

    <script>
        // File upload handling
        const uploadArea = document.getElementById('uploadArea');
        const fileInput = document.getElementById('codeMetricsFile');
        const selectedFile = document.getElementById('selectedFile');
        const fileName = document.getElementById('fileName');
        const predictBtn = document.getElementById('predictBtn');
        const uploadForm = document.getElementById('uploadForm');
        const loading = document.getElementById('loading');
        const errorMessage = document.getElementById('errorMessage');
        const successMessage = document.getElementById('successMessage');

        // Drag and drop functionality
        uploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadArea.classList.add('drag-over');
        });

        uploadArea.addEventListener('dragleave', () => {
            uploadArea.classList.remove('drag-over');
        });

        uploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadArea.classList.remove('drag-over');

            const files = e.dataTransfer.files;
            if (files.length > 0 && files[0].type === 'text/csv') {
                fileInput.files = files;
                handleFileSelect();
            }
        });

        // File input change handler
        fileInput.addEventListener('change', handleFileSelect);

        function handleFileSelect() {
            const file = fileInput.files[0];
            if (file) {
                fileName.textContent = file.name;
                selectedFile.style.display = 'block';
                predictBtn.style.display = 'inline-block';
                hideMessages();
            }
        }

        // Form submission handler
        uploadForm.addEventListener('submit', async function(event) {
            event.preventDefault();

            const file = fileInput.files[0];
            if (!file) {
                showError('Please select a CSV file to upload.');
                return;
            }

            showLoading();
            hideMessages();

            const formData = new FormData();
            formData.append('file', file);

            try {
                // Replace with your actual Python API endpoint
                const response = await fetch('http://localhost:5000/predict_fault', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (response.ok) {
                    // Store results in session via PHP
                    // Make sure 'file' is accessible here (it should be from fileInput.files[0])
                    await storeResults(result, file.name); // PASS file.name here
                    showSuccess('Prediction completed successfully! Redirecting to results...');

                    // Redirect to results page after 2 seconds
                    setTimeout(() => {
                        window.location.href = 'results.php';
                    }, 2000);
                } else {
                    showError(result.error || 'Unknown error occurred');
                }
            } catch (error) {
                console.error('Error:', error);
                showError('Failed to connect to prediction service. Please ensure the API server is running.');
            } finally {
                hideLoading();
            }
        });

       // Store results in session via PHP
        async function storeResults(results, fileName) { // ADD fileName parameter
            try {
                await fetch('store_results.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ // Create a new object that includes fileName
                        predictions: results.predictions,
                        summary: results.summary,
                        fileName: fileName // ADD this line to send the filename
                    })
                });
            } catch (error) {
                console.error('Error storing results:', error);
            }
        }

        function showLoading() {
            loading.style.display = 'block';
            predictBtn.disabled = true;
        }

        function hideLoading() {
            loading.style.display = 'none';
            predictBtn.disabled = false;
        }

        function showError(message) {
            errorMessage.textContent = message;
            errorMessage.style.display = 'block';
        }

        function showSuccess(message) {
            successMessage.textContent = message;
            successMessage.style.display = 'block';
        }

        function hideMessages() {
            errorMessage.style.display = 'none';
            successMessage.style.display = 'none';
        }
    </script>
</body>
</html>