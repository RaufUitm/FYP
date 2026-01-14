<?php
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$username = $_SESSION['username'];
$user_id = $_SESSION['user_id'];

// Get prediction ID from URL
$prediction_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$prediction_id) {
    header('Location: history.php');
    exit();
}

// Fetch specific prediction from database
$prediction_data = null;
if (isset($pdo)) {
    try {
        $stmt = $pdo->prepare("
            SELECT id, results_data, file_name, created_at
            FROM prediction_history
            WHERE id = ? AND user_id = ?
        ");
        $stmt->execute([$prediction_id, $user_id]);
        $record = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($record) {
            $prediction_data = json_decode($record['results_data'], true);
            $file_name = $record['file_name'];
            $created_at = $record['created_at'];
        }
    } catch (PDOException $e) {
        // Handle error
        $prediction_data = null;
    }
}

if (!$prediction_data) {
    header('Location: history.php');
    exit();
}

$results = $prediction_data;
$summary = $results['summary'] ?? [];
$predictions = $results['predictions'] ?? [];

// PHP-side variables for pagination are no longer strictly needed for display logic,
// as client-side JS will handle it, but kept for context if any server-side validation was added.
$total_predictions = count($predictions);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historical Prediction - Fault Prediction System</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
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

        .nav-links {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .back-btn {
            background: #4b7bec;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            transition: background 0.3s ease;
        }

        .back-btn:hover {
            background: #3867d6;
        }

        .logout-btn {
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

        .container {
            max-width: 1400px;
            margin: 20px auto;
            padding: 0 20px;
        }

        .results-header {
            background: rgba(255, 255, 255, 0.95);
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            backdrop-filter: blur(10px);
            text-align: center;
        }

        .results-header h1 {
            color: #2c3e50;
            font-size: 32px;
            margin-bottom: 10px;
        }

        .results-header p {
            color: #666;
            font-size: 18px;
        }

        .summary-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .summary-card {
            background: rgba(255, 255, 255, 0.95);
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            backdrop-filter: blur(10px);
            text-align: center;
            border-left: 5px solid;
        }

        .summary-card.total {
            border-left-color: #4b7bec;
        }

        .summary-card.healthy {
            border-left-color: #26de81;
        }

        .summary-card.faulty {
            border-left-color: #ff6b6b;
        }

        .summary-card.percentage {
            border-left-color: #ffa726;
        }

        .summary-card h3 {
            color: #2c3e50;
            font-size: 24px;
            margin-bottom: 5px;
        }

        .summary-card .number {
            font-size: 36px;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .summary-card .total .number {
            color: #4b7bec;
        }

        .summary-card .healthy .number {
            color: #26de81;
        }

        .summary-card .faulty .number {
            color: #ff6b6b;
        }

        .summary-card .percentage .number {
            color: #ffa726;
        }

        .charts-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }

        .chart-card {
            background: rgba(255, 255, 255, 0.95);
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            backdrop-filter: blur(10px);
            height: 400px; /* Fixed height for consistent sizing */
            display: flex;
            flex-direction: column;
        }

        .chart-card h3 {
            color: #2c3e50;
            margin-bottom: 20px;
            text-align: center;
            font-size: 18px;
            flex-shrink: 0; /* Prevent title from shrinking */
        }

        .chart-container {
            flex: 1; /* Take remaining space */
            position: relative;
            min-height: 0; /* Important for flexbox */
        }

        .chart-container canvas {
            position: absolute !important;
            top: 0;
            left: 0;
            width: 100% !important;
            height: 100% !important;
        }

        .detailed-results {
            background: rgba(255, 255, 255, 0.95);
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            backdrop-filter: blur(10px);
        }

        .detailed-results h2 {
            color: #2c3e50;
            margin-bottom: 20px;
            font-size: 24px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .filters {
            margin-bottom: 20px;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: center;
        }

        .filter-btn {
            padding: 8px 16px;
            border: 2px solid #4b7bec;
            background: transparent;
            color: #4b7bec;
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .filter-btn.active {
            background: #4b7bec;
            color: white;
        }

        .filter-btn:hover {
            background: #4b7bec;
            color: white;
        }

        .search-box {
            flex: 1;
            min-width: 250px;
            padding: 10px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
        }

        .search-box:focus {
            outline: none;
            border-color: #4b7bec;
        }

        .items-per-page-select {
            padding: 8px 10px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            cursor: pointer;
            background-color: white;
            color: #2c3e50;
        }

        .items-per-page-select:focus {
            outline: none;
            border-color: #4b7bec;
        }

        .results-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .results-table th,
        .results-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }

        .results-table th {
            background: rgba(75, 123, 236, 0.1);
            color: #2c3e50;
            font-weight: 600;
            position: sticky;
            top: 0;
        }

        .results-table tr:hover {
            background: rgba(75, 123, 236, 0.05);
        }

        .prediction-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .badge-healthy {
            background: rgba(38, 222, 129, 0.2);
            color: #20bf6b;
        }

        .badge-faulty {
            background: rgba(255, 107, 107, 0.2);
            color: #ff5252;
        }

        .confidence-bar {
            width: 100px;
            height: 8px;
            background: #e0e0e0;
            border-radius: 4px;
            overflow: hidden;
            position: relative;
        }

        .confidence-fill {
            height: 100%;
            transition: width 0.3s ease;
        }

        .confidence-high {
            background: #26de81;
        }

        .confidence-medium {
            background: #ffa726;
        }

        .confidence-low {
            background: #ff6b6b;
        }

        .export-btn {
            background: #26de81;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
            margin-top: 20px;
        }

        .export-btn:hover {
            background: #20bf6b;
            transform: translateY(-2px);
        }

        .no-results {
            text-align: center;
            padding: 40px;
            color: #666;
            font-size: 16px;
        }

        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-top: 20px;
            gap: 10px;
        }

        .pagination button {
            background: #4b7bec;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        .pagination button:hover:not(:disabled) {
            background: #3867d6;
        }

        .pagination button:disabled {
            background: #aab8c2;
            cursor: not-allowed;
        }

        .pagination span {
            font-size: 16px;
            color: #2c3e50;
            font-weight: 500;
        }

        @media (max-width: 768px) {
            .charts-section {
                grid-template-columns: 1fr;
            }

            .chart-card {
                height: 350px; /* Slightly smaller on mobile */
            }

            .summary-cards {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            }

            .filters {
                flex-direction: column;
                align-items: stretch;
            }

            .results-table {
                font-size: 14px;
            }

            .results-table th,
            .results-table td {
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <h1>🔧 Historical Prediction Results</h1>
        <div class="nav-links">
            <a href="history.php" class="back-btn">← Back to History</a>
            <a href="dashboard.php" class="back-btn">Dashboard</a>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
    </nav>

    <div class="container">
        <div class="results-header">
            <h1>📊 Historical Analysis</h1>
            <p>Viewing prediction results from your archived analysis</p>

            <div class="meta-info">
                <div class="meta-item">
                    <span>📁</span>
                    <span><strong>File:</strong> <?php echo htmlspecialchars($file_name ?? 'Unknown'); ?></span>
                </div>
                <div class="meta-item">
                    <span>📅</span>
                    <span><strong>Date:</strong> <?php echo date('M j, Y H:i', strtotime($created_at)); ?></span>
                </div>
                <div class="meta-item">
                    <span>🔍</span>
                    <span><strong>ID:</strong> #<?php echo $prediction_id; ?></span>
                </div>
            </div>
        </div>

        <div class="summary-cards">
            <div class="summary-card total">
                <h3>Total Items</h3>
                <div class="total">
                    <div class="number"><?php echo $summary['total_items'] ?? 0; ?></div>
                </div>
                <p>Analyzed</p>
            </div>

            <div class="summary-card healthy">
                <h3>Healthy Items</h3>
                <div class="healthy">
                    <div class="number"><?php echo $summary['healthy_items'] ?? 0; ?></div>
                </div>
                <p>No faults detected</p>
            </div>

            <div class="summary-card faulty">
                <h3>Faulty Items</h3>
                <div class="faulty">
                    <div class="number"><?php echo $summary['faulty_items'] ?? 0; ?></div>
                </div>
                <p>Require attention</p>
            </div>

            <div class="summary-card percentage">
                <h3>Fault Rate</h3>
                <div class="percentage">
                    <div class="number"><?php echo $summary['fault_percentage'] ?? '0%'; ?></div>
                </div>
                <p>Of total items</p>
            </div>
        </div>

        <div class="charts-section">
            <div class="chart-card">
                <h3>Distribution Overview</h3>
                <div class="chart-container">
                    <canvas id="pieChart"></canvas>
                </div>
            </div>

            <div class="chart-card">
                <h3>Confidence Distribution</h3>
                <div class="chart-container">
                    <canvas id="confidenceChart"></canvas>
                </div>
            </div>
        </div>

        <div class="detailed-results">
            <h2>📋 Detailed Results</h2>

            <div class="filters">
                <button class="filter-btn active" onclick="filterResults('all')">All</button>
                <button class="filter-btn" onclick="filterResults('healthy')">Healthy</button>
                <button class="filter-btn" onclick="filterResults('faulty')">Faulty</button>
                <input type="text" class="search-box" placeholder="Search by name..." oninput="searchResults(this.value)">
                <label for="itemsPerPageSelect">Items per page:</label>
                <select id="itemsPerPageSelect" class="items-per-page-select" onchange="changeItemsPerPage(this.value)">
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="all">All</option>
                </select>
            </div>

            <div style="overflow-x: auto;">
                <table class="results-table" id="resultsTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Name/Identifier</th>
                            <th>Prediction</th>
                            <th>Confidence</th>
                            <th>Risk Level</th>
                            <?php if (isset($predictions[0]['fault_probability'])): ?>
                            <th>Fault Probability</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody id="resultsTableBody">
                        </tbody>
                </table>
            </div>

            <div class="pagination">
                <button id="prevPageBtn">Previous</button>
                <span id="pageInfo">Page 1 of 1</span>
                <button id="nextPageBtn">Next</button>
            </div>

            <button class="export-btn" onclick="exportResults()">
                📥 Export Results to CSV
            </button>
        </div>
    </div>

    <script>
        // Data from PHP
        const allPredictions = <?php echo json_encode($predictions); ?>;
        const summary = <?php echo json_encode($summary); ?>;
        let filteredPredictions = [...allPredictions]; // Working array for filtering and searching
        let currentPage = 1;
        let itemsPerPage = 10; // Default items per page

        // Chart.js configuration
        const ctx1 = document.getElementById('pieChart').getContext('2d');
        const ctx2 = document.getElementById('confidenceChart').getContext('2d');

        // Pie Chart for Distribution
        new Chart(ctx1, {
            type: 'doughnut',
            data: {
                labels: ['Healthy', 'Faulty'],
                datasets: [{
                    data: [summary['healthy_items'] ?? 0, summary['faulty_items'] ?? 0],
                    backgroundColor: ['#26de81', '#ff6b6b'],
                    borderWidth: 3,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true
                        }
                    }
                }
            }
        });

        // Confidence Distribution Chart
        const confidenceRanges = {'0-20%': 0, '21-40%': 0, '41-60%': 0, '61-80%': 0, '81-100%': 0};
        allPredictions.forEach(prediction => {
            if (prediction.confidence) {
                const conf = parseFloat(prediction.confidence.replace('%', ''));
                if (conf <= 20) confidenceRanges['0-20%']++;
                else if (conf <= 40) confidenceRanges['21-40%']++;
                else if (conf <= 60) confidenceRanges['41-60%']++;
                else if (conf <= 80) confidenceRanges['61-80%']++;
                else confidenceRanges['81-100%']++;
            }
        });

        new Chart(ctx2, {
            type: 'bar',
            data: {
                labels: Object.keys(confidenceRanges),
                datasets: [{
                    label: 'Number of Items',
                    data: Object.values(confidenceRanges),
                    backgroundColor: ['#ff6b6b', '#ffa726', '#ffeb3b', '#4caf50', '#26de81'],
                    borderRadius: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });

        // Function to render table rows for a given page
        function renderTable(predictionsToDisplay) {
            const tableBody = document.getElementById('resultsTableBody');
            tableBody.innerHTML = ''; // Clear existing rows

            if (predictionsToDisplay.length === 0) {
                tableBody.innerHTML = '<tr><td colspan="6" class="no-results">No results found for the current filters/search.</td></tr>';
                return;
            }

            predictionsToDisplay.forEach((prediction, index) => {
                // Calculate correct global index for display (not just index within the current page)
                const globalIndex = allPredictions.indexOf(prediction); // Use allPredictions to get the true original index

                const row = document.createElement('tr');
                row.classList.add('result-row');
                row.dataset.prediction = prediction.prediction_label.toLowerCase();

                let nameIdentifier = prediction['name.1'] ??
                                     prediction['name'] ??
                                     prediction['class_name'] ??
                                     prediction['module_name'] ??
                                     prediction['file_name'] ??
                                     `Item_${globalIndex + 1}`;

                let confidenceHtml = '';
                if (prediction.confidence) {
                    const confidenceNum = parseFloat(prediction.confidence.replace('%', ''));
                    const confidenceClass = confidenceNum >= 80 ? 'confidence-high' : (confidenceNum >= 60 ? 'confidence-medium' : 'confidence-low');
                    confidenceHtml = `
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <div class="confidence-bar">
                                <div class="confidence-fill ${confidenceClass}" style="width: ${confidenceNum}%"></div>
                            </div>
                            <span>${htmlspecialchars(prediction.confidence)}</span>
                        </div>
                    `;
                } else {
                    confidenceHtml = '<span>N/A</span>';
                }

                let riskLevel = 'Low';
                let riskClass = 'badge-healthy';

                if (prediction.prediction_label.toLowerCase() === 'faulty') {
                    if (prediction.fault_probability) {
                        const prob = parseFloat(prediction.fault_probability);
                        if (prob >= 0.8) {
                            riskLevel = 'Critical';
                            riskClass = 'badge-faulty';
                        } else if (prob >= 0.6) {
                            riskLevel = 'High';
                            riskClass = 'badge-faulty';
                        } else {
                            riskLevel = 'Medium';
                            riskClass = 'badge-faulty';
                        }
                    } else {
                        riskLevel = 'High';
                        riskClass = 'badge-faulty';
                    }
                }

                const faultProbabilityHtml = `
                    <td>
                        ${prediction.fault_probability ? (prediction.fault_probability * 100).toFixed(1) + '%' : 'N/A'}
                    </td>
                `;

                row.innerHTML = `
                    <td>${globalIndex + 1}</td>
                    <td><strong>${htmlspecialchars(nameIdentifier)}</strong></td>
                    <td>
                        <span class="prediction-badge ${prediction.prediction_label.toLowerCase() === 'healthy' ? 'badge-healthy' : 'badge-faulty'}">
                            ${htmlspecialchars(prediction.prediction_label)}
                        </span>
                    </td>
                    <td>${confidenceHtml}</td>
                    <td>
                        <span class="prediction-badge ${riskClass}">
                            ${riskLevel}
                        </span>
                    </td>
                    ${allPredictions[0] && allPredictions[0].fault_probability !== undefined ? faultProbabilityHtml : ''}
                `;
                tableBody.appendChild(row);
            });
        }

        function updatePaginationControls() {
            const totalPages = Math.ceil(filteredPredictions.length / itemsPerPage);
            document.getElementById('pageInfo').textContent = `Page ${currentPage} of ${totalPages || 1}`; // Ensure it shows "Page 1 of 1" for no items
            document.getElementById('prevPageBtn').disabled = currentPage === 1;
            document.getElementById('nextPageBtn').disabled = currentPage === totalPages || totalPages === 0;
        }

        function displayPage(page) {
            currentPage = page;
            const startIndex = (currentPage - 1) * itemsPerPage;
            const endIndex = itemsPerPage === -1 ? filteredPredictions.length : startIndex + itemsPerPage; // -1 for "All"
            const predictionsToDisplay = filteredPredictions.slice(startIndex, endIndex);
            renderTable(predictionsToDisplay);
            updatePaginationControls();
        }

        // Event listeners for pagination buttons
        document.getElementById('prevPageBtn').addEventListener('click', () => {
            if (currentPage > 1) {
                displayPage(currentPage - 1);
            }
        });

        document.getElementById('nextPageBtn').addEventListener('click', () => {
            const totalPages = Math.ceil(filteredPredictions.length / itemsPerPage);
            if (currentPage < totalPages) {
                displayPage(currentPage + 1);
            }
        });

        // Filter functionality
        let currentFilter = 'all';
        function filterResults(type) {
            currentFilter = type;
            const buttons = document.querySelectorAll('.filter-btn');

            buttons.forEach(btn => btn.classList.remove('active'));
            // Use event.currentTarget if called directly from onclick, or find by type
            const clickedButton = document.querySelector(`.filter-btn[onclick="filterResults('${type}')"]`);
            if (clickedButton) {
                clickedButton.classList.add('active');
            }


            applyFiltersAndSearch();
        }

        // Search functionality
        let currentSearchQuery = '';
        function searchResults(query) {
            currentSearchQuery = query.toLowerCase();
            applyFiltersAndSearch();
        }

        // New function to handle items per page selection
        function changeItemsPerPage(value) {
            if (value === 'all') {
                itemsPerPage = -1; // Special value to indicate showing all
            } else {
                itemsPerPage = parseInt(value, 10);
            }
            applyFiltersAndSearch(); // Re-apply filters and search to refresh pagination
        }

        function applyFiltersAndSearch() {
            filteredPredictions = allPredictions.filter(prediction => {
                const matchesFilter = currentFilter === 'all' || prediction.prediction_label.toLowerCase() === currentFilter;

                let nameIdentifier = prediction['name.1'] ??
                                     prediction['name'] ??
                                     prediction['class_name'] ??
                                     prediction['module_name'] ??
                                     prediction['file_name'] ??
                                     `Item_${allPredictions.indexOf(prediction) + 1}`; // Use original index for unique ID

                const matchesSearch = nameIdentifier.toLowerCase().includes(currentSearchQuery);

                return matchesFilter && matchesSearch;
            });
            displayPage(1); // Reset to first page after filtering/searching
        }


        // Export functionality (remains largely the same, but uses allPredictions)
        function exportResults() {
            const csvContent = generateCSV(allPredictions); // Export all predictions
            downloadCSV(csvContent, 'historical_prediction_results_<?php echo $prediction_id; ?>.csv');
        }

        function generateCSV(data) {
            const headers = ['Index', 'Name', 'Prediction', 'Predicted_Faulty'];
            if (data.length > 0 && data[0].confidence !== undefined) headers.push('Confidence');
            if (data.length > 0 && data[0].fault_probability !== undefined) headers.push('Fault_Probability');

            let csv = headers.join(',') + '\n';

            data.forEach((item, index) => {
                const row = [
                    index + 1,
                    `"${htmlspecialchars(item['name.1'] || item.name || item.class_name || item.module_name || item.file_name || 'Item_' + (index + 1))}"`,
                    item.prediction_label,
                    item.predicted_faulty
                ];

                if (item.confidence !== undefined) row.push(item.confidence);
                if (item.fault_probability !== undefined) row.push(item.fault_probability);

                csv += row.join(',') + '\n';
            });

            return csv;
        }

        function downloadCSV(csvContent, fileName) {
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');

            if (link.download !== undefined) {
                const url = URL.createObjectURL(blob);
                link.setAttribute('href', url);
                link.setAttribute('download', fileName);
                link.style.visibility = 'hidden';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            }
        }

        // Helper function for HTML escaping in JS (for dynamically generated content)
        function htmlspecialchars(str) {
            if (typeof str !== 'string') return str;
            return str.replace(/&/g, '&amp;')
                      .replace(/</g, '&lt;')
                      .replace(/>/g, '&gt;')
                      .replace(/"/g, '&quot;')
                      .replace(/'/g, '&#039;');
        }

        // Initial display of the first page
        document.addEventListener('DOMContentLoaded', () => {
            applyFiltersAndSearch(); // This will render the first page and set up pagination
        });
    </script>
</body>
</html>