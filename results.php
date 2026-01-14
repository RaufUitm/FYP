<?php
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$username = $_SESSION['username'];

// Get results from session or redirect if no results
if (!isset($_SESSION['prediction_results'])) {
    header('Location: dashboard.php');
    exit();
}

$results = $_SESSION['prediction_results'];
$summary = $results['summary'] ?? [];
$predictions = $results['predictions'] ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prediction Results - Fault Prediction System</title>
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
        
        /* Pagination Styles */
        .pagination-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 20px;
            padding: 20px 0;
            border-top: 1px solid #e0e0e0;
        }
        
        .pagination-info {
            color: #666;
            font-size: 14px;
        }
        
        .pagination {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .pagination button {
            padding: 8px 12px;
            border: 1px solid #e0e0e0;
            background: white;
            color: #666;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 14px;
        }
        
        .pagination button:hover:not(:disabled) {
            background: #4b7bec;
            color: white;
            border-color: #4b7bec;
        }
        
        .pagination button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .pagination button.active {
            background: #4b7bec;
            color: white;
            border-color: #4b7bec;
        }
        
        .pagination .ellipsis {
            padding: 8px 4px;
            color: #666;
        }
        
        .page-size-selector {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .page-size-selector select {
            padding: 6px 10px;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .page-size-selector select:focus {
            outline: none;
            border-color: #4b7bec;
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
        <h1>🔧 Fault Prediction Results</h1>
        <div class="nav-links">
            <a href="dashboard.php" class="back-btn">← Back to Dashboard</a>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
    </nav>
    
    <div class="container">
        <!-- Results Header -->
        <div class="results-header">
            <h1>📊 Analysis Complete</h1>
            <p>Your fault prediction analysis has been processed successfully</p>
        </div>
        
        <!-- Summary Cards -->
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
        
        <!-- Charts Section -->
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
        
        <!-- Detailed Results -->
        <div class="detailed-results">
            <h2>📋 Detailed Results</h2>
            
            <!-- Filters -->
            <div class="filters">
                <button class="filter-btn active" onclick="filterResults('all')">All</button>
                <button class="filter-btn" onclick="filterResults('healthy')">Healthy</button>
                <button class="filter-btn" onclick="filterResults('faulty')">Faulty</button>
                <input type="text" class="search-box" placeholder="Search by name..." oninput="searchResults(this.value)">
            </div>
            
            <!-- Page Size Selector -->
            <div class="page-size-selector">
                <label>Show:</label>
                <select id="pageSizeSelect" onchange="changePageSize(this.value)">
                    <option value="10" selected>10 per page</option>
                    <option value="25">25 per page</option>
                    <option value="50">50 per page</option>
                    <option value="100">100 per page</option>
                </select>
            </div>
            
            <!-- Results Table -->
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
                        <!-- Results will be populated by JavaScript -->
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <div class="pagination-container">
                <div class="pagination-info" id="paginationInfo">
                    Showing 1 to 25 of <?php echo count($predictions); ?> results
                </div>
                <div class="pagination" id="pagination">
                    <!-- Pagination buttons will be generated by JavaScript -->
                </div>
            </div>
            
            <!-- Export Button -->
            <button class="export-btn" onclick="exportResults()">
                📥 Export Results to CSV
            </button>
        </div>
    </div>

    <script>
        // Store all predictions data
        const allPredictions = <?php echo json_encode($predictions); ?>;
        let filteredPredictions = [...allPredictions];
        let currentPage = 1;
        let pageSize = 25;
        let currentFilter = 'all';
        let currentSearch = '';
        
        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            renderTable();
        });
        
        // Pagination functions
        function renderTable() {
            const startIndex = (currentPage - 1) * pageSize;
            const endIndex = Math.min(startIndex + pageSize, filteredPredictions.length);
            const pageData = filteredPredictions.slice(startIndex, endIndex);
            
            const tbody = document.getElementById('resultsTableBody');
            tbody.innerHTML = '';
            
            if (pageData.length === 0) {
                tbody.innerHTML = '<tr><td colspan="100%" class="no-results">No results found</td></tr>';
                updatePaginationInfo(0, 0, 0);
                renderPagination();
                return;
            }
            
            pageData.forEach((prediction, index) => {
                const globalIndex = startIndex + index;
                const row = createTableRow(prediction, globalIndex + 1);
                tbody.appendChild(row);
            });
            
            updatePaginationInfo(startIndex + 1, endIndex, filteredPredictions.length);
            renderPagination();
        }
        
        function createTableRow(prediction, displayIndex) {
            const row = document.createElement('tr');
            row.className = 'result-row';
            row.setAttribute('data-prediction', prediction.prediction_label.toLowerCase());
            
            const name = prediction['name.1'] || prediction.name || prediction.class_name || 
                        prediction.module_name || prediction.file_name || `Item_${displayIndex}`;
            
            const isHealthy = prediction.prediction_label.toLowerCase() === 'healthy';
            const badgeClass = isHealthy ? 'badge-healthy' : 'badge-faulty';
            
            // Calculate risk level
            let riskLevel = 'Low';
            let riskClass = 'badge-healthy';
            if (!isHealthy) {
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
            
            // Build confidence display
            let confidenceHtml = '<span>N/A</span>';
            if (prediction.confidence) {
                const confidenceNum = parseFloat(prediction.confidence.replace('%', ''));
                const confidenceClass = confidenceNum >= 80 ? 'confidence-high' : 
                                      (confidenceNum >= 60 ? 'confidence-medium' : 'confidence-low');
                
                confidenceHtml = `
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <div class="confidence-bar">
                            <div class="confidence-fill ${confidenceClass}" style="width: ${confidenceNum}%"></div>
                        </div>
                        <span>${prediction.confidence}</span>
                    </div>
                `;
            }
            
            // Check if fault probability column should be shown
            const showFaultProb = allPredictions.some(p => p.fault_probability !== undefined);
            const faultProbHtml = showFaultProb ? 
                `<td>${prediction.fault_probability ? (prediction.fault_probability * 100).toFixed(1) + '%' : 'N/A'}</td>` : '';
            
            row.innerHTML = `
                <td>${displayIndex}</td>
                <td><strong>${escapeHtml(name)}</strong></td>
                <td><span class="prediction-badge ${badgeClass}">${escapeHtml(prediction.prediction_label)}</span></td>
                <td>${confidenceHtml}</td>
                <td><span class="prediction-badge ${riskClass}">${riskLevel}</span></td>
                ${faultProbHtml}
            `;
            
            return row;
        }
        
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        function updatePaginationInfo(start, end, total) {
            const info = document.getElementById('paginationInfo');
            info.textContent = `Showing ${start} to ${end} of ${total} results`;
        }
        
        function renderPagination() {
            const totalPages = Math.ceil(filteredPredictions.length / pageSize);
            const pagination = document.getElementById('pagination');
            pagination.innerHTML = '';
            
            if (totalPages <= 1) return;
            
            // Previous button
            const prevBtn = document.createElement('button');
            prevBtn.textContent = '← Previous';
            prevBtn.disabled = currentPage === 1;
            prevBtn.onclick = () => goToPage(currentPage - 1);
            pagination.appendChild(prevBtn);
            
            // Page numbers
            const startPage = Math.max(1, currentPage - 2);
            const endPage = Math.min(totalPages, currentPage + 2);
            
            if (startPage > 1) {
                const firstBtn = document.createElement('button');
                firstBtn.textContent = '1';
                firstBtn.onclick = () => goToPage(1);
                pagination.appendChild(firstBtn);
                
                if (startPage > 2) {
                    const ellipsis = document.createElement('span');
                    ellipsis.className = 'ellipsis';
                    ellipsis.textContent = '...';
                    pagination.appendChild(ellipsis);
                }
            }
            
            for (let i = startPage; i <= endPage; i++) {
                const pageBtn = document.createElement('button');
                pageBtn.textContent = i;
                pageBtn.className = i === currentPage ? 'active' : '';
                pageBtn.onclick = () => goToPage(i);
                pagination.appendChild(pageBtn);
            }
            
            if (endPage < totalPages) {
                if (endPage < totalPages - 1) {
                    const ellipsis = document.createElement('span');
                    ellipsis.className = 'ellipsis';
                    ellipsis.textContent = '...';
                    pagination.appendChild(ellipsis);
                }
                
                const lastBtn = document.createElement('button');
                lastBtn.textContent = totalPages;
                lastBtn.onclick = () => goToPage(totalPages);
                pagination.appendChild(lastBtn);
            }
            
            // Next button
            const nextBtn = document.createElement('button');
            nextBtn.textContent = 'Next →';
            nextBtn.disabled = currentPage === totalPages;
            nextBtn.onclick = () => goToPage(currentPage + 1);
            pagination.appendChild(nextBtn);
        }
        
        function goToPage(page) {
            currentPage = page;
            renderTable();
        }
        
        function changePageSize(size) {
            pageSize = parseInt(size);
            currentPage = 1;
            renderTable();
        }
        
        function applyFilters() {
            filteredPredictions = allPredictions.filter(prediction => {
                // Apply prediction filter
                if (currentFilter !== 'all' && prediction.prediction_label.toLowerCase() !== currentFilter) {
                    return false;
                }
                
                // Apply search filter
                if (currentSearch) {
                    const name = prediction['name.1'] || prediction.name || prediction.class_name || 
                                prediction.module_name || prediction.file_name || '';
                    if (!name.toLowerCase().includes(currentSearch.toLowerCase())) {
                        return false;
                    }
                }
                
                return true;
            });
            
            currentPage = 1;
            renderTable();
        }
        
        // Filter functionality
        function filterResults(type) {
            const buttons = document.querySelectorAll('.filter-btn');
            buttons.forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');
            
            currentFilter = type;
            applyFilters();
        }
        
        // Search functionality
        function searchResults(query) {
            currentSearch = query;
            applyFilters();
        }
        
        // Chart.js configuration with fixed sizing
        const ctx1 = document.getElementById('pieChart').getContext('2d');
        const ctx2 = document.getElementById('confidenceChart').getContext('2d');
        
        // Pie Chart for Distribution
        new Chart(ctx1, {
            type: 'doughnut',
            data: {
                labels: ['Healthy', 'Faulty'],
                datasets: [{
                    data: [<?php echo $summary['healthy_items'] ?? 0; ?>, <?php echo $summary['faulty_items'] ?? 0; ?>],
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
                            padding: 15,
                            usePointStyle: true,
                            font: {
                                size: 12
                            }
                        }
                    }
                }
            }
        });
        
        // Confidence Distribution Chart
        <?php
        $confidence_ranges = ['0-20%' => 0, '21-40%' => 0, '41-60%' => 0, '61-80%' => 0, '81-100%' => 0];
        foreach ($predictions as $prediction) {
            if (isset($prediction['confidence'])) {
                $conf = floatval(str_replace('%', '', $prediction['confidence']));
                if ($conf <= 20) $confidence_ranges['0-20%']++;
                elseif ($conf <= 40) $confidence_ranges['21-40%']++;
                elseif ($conf <= 60) $confidence_ranges['41-60%']++;
                elseif ($conf <= 80) $confidence_ranges['61-80%']++;
                else $confidence_ranges['81-100%']++;
            }
        }
        ?>
        
        new Chart(ctx2, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_keys($confidence_ranges)); ?>,
                datasets: [{
                    label: 'Number of Items',
                    data: <?php echo json_encode(array_values($confidence_ranges)); ?>,
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
        
        // Export functionality
        function exportResults() {
            const results = allPredictions;
            const csvContent = generateCSV(results);
            downloadCSV(csvContent, 'fault_prediction_results.csv');
        }
        
        function generateCSV(data) {
            const headers = ['Index', 'Name', 'Prediction', 'Predicted_Faulty'];
            if (data.length > 0 && data[0].confidence) headers.push('Confidence');
            if (data.length > 0 && data[0].fault_probability) headers.push('Fault_Probability');
            
            let csv = headers.join(',') + '\n';
            
            data.forEach((item, index) => {
                const row = [
                    index + 1,
                    `"${item['name.1'] || item.name || 'Item_' + (index + 1)}"`,
                    item.prediction_label,
                    item.predicted_faulty
                ];
                
                if (item.confidence) row.push(item.confidence);
                if (item.fault_probability) row.push(item.fault_probability);
                
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
    </script>
</body>
</html>