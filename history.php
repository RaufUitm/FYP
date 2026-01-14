<?php
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$username = $_SESSION['username'];
$user_id = $_SESSION['user_id'];

// Fetch prediction history from database (if table exists)
$history = [];
if (isset($pdo)) {
    try {
        $stmt = $pdo->prepare("
            SELECT id, results_data, file_name, total_predictions, faulty_count, healthy_count, created_at 
            FROM prediction_history 
            WHERE user_id = ? 
            ORDER BY created_at DESC 
            LIMIT 20
        ");
        $stmt->execute([$user_id]);
        $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Table might not exist, that's okay
        $history = [];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prediction History - Fault Prediction System</title>
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
        
        .nav-btn {
            background: #4b7bec;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            transition: background 0.3s ease;
        }
        
        .nav-btn:hover {
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
        
        .logout-btn {
            background: #ff6b6b;
        }
        
        .logout-btn:hover {
            background: #ff5252;
        }
        
        .container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 0 20px;
        }
        
        .header-card {
            background: rgba(255, 255, 255, 0.95);
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            backdrop-filter: blur(10px);
            text-align: center;
        }
        
        .header-card h1 {
            color: #2c3e50;
            font-size: 32px;
            margin-bottom: 10px;
        }
        
        .header-card p {
            color: #666;
            font-size: 18px;
        }
        
        .history-section {
            background: rgba(255, 255, 255, 0.95);
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            backdrop-filter: blur(10px);
        }
        
        .history-section h2 {
            color: #2c3e50;
            margin-bottom: 20px;
            font-size: 24px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .history-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .history-table th,
        .history-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .history-table th {
            background: rgba(75, 123, 236, 0.1);
            color: #2c3e50;
            font-weight: 600;
        }
        
        .history-table tr:hover {
            background: rgba(75, 123, 236, 0.05);
        }
        
        .date-badge {
            background: rgba(75, 123, 236, 0.1);
            color: #4b7bec;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .stats-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
            margin: 2px;
        }
        
        .stats-total {
            background: rgba(75, 123, 236, 0.1);
            color: #4b7bec;
        }
        
        .stats-healthy {
            background: rgba(38, 222, 129, 0.1);
            color: #20bf6b;
        }
        
        .stats-faulty {
            background: rgba(255, 107, 107, 0.1);
            color: #ff5252;
        }
        
        .view-btn {
            background: #26de81;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            text-decoration: none;
            transition: background 0.3s ease;
        }
        
        .view-btn:hover {
            background: #20bf6b;
        }
        
        .no-history {
            text-align: center;
            padding: 40px;
            color: #666;
            font-size: 16px;
        }
        
        .empty-icon {
            font-size: 48px;
            margin-bottom: 15px;
            opacity: 0.5;
        }
        
        @media (max-width: 768px) {
            .history-table {
                font-size: 14px;
            }
            
            .history-table th,
            .history-table td {
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <h1>📊 Prediction History</h1>
        <div class="nav-links">
            <a href="dashboard.php" class="nav-btn">← Dashboard</a>
            <?php if (isset($_SESSION['prediction_results'])): ?>
            <a href="results.php" class="nav-btn">Latest Results</a>
            <?php endif; ?>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
    </nav>
    
    <div class="container">
        <div class="header-card">
            <h1>📈 Your Prediction History</h1>
            <p>Track your previous fault prediction analyses</p>
        </div>
        
        <div class="history-section">
            <h2>🕐 Recent Predictions</h2>
            
            <?php if (empty($history)): ?>
            <div class="no-history">
                <div class="empty-icon">📭</div>
                <h3>No prediction history found</h3>
                <p>Start by uploading a CSV file for fault prediction analysis</p>
                <br>
                <a href="dashboard.php" class="view-btn" style="padding: 12px 24px; font-size: 14px;">
                    Create First Prediction
                </a>
            </div>
            <?php else: ?>
            <div style="overflow-x: auto;">
                <table class="history-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>File Name</th>
                            <th>Statistics</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($history as $record): ?>
                        <tr>
                            <td>
                                <span class="date-badge">
                                    <?php echo date('M j, Y H:i', strtotime($record['created_at'])); ?>
                                </span>
                            </td>
                            <td>
                                <strong>
                                    <?php echo htmlspecialchars($record['file_name'] ?: 'Unknown File'); ?>
                                </strong>
                            </td>
                            <td>
                                <?php if ($record['total_predictions']): ?>
                                <span class="stats-badge stats-total">
                                    Total: <?php echo $record['total_predictions']; ?>
                                </span>
                                <span class="stats-badge stats-healthy">
                                    Healthy: <?php echo $record['healthy_count']; ?>
                                </span>
                                <span class="stats-badge stats-faulty">
                                    Faulty: <?php echo $record['faulty_count']; ?>
                                </span>
                                <?php else: ?>
                                <?php 
                                    $data = json_decode($record['results_data'], true);
                                    if ($data && isset($data['summary'])):
                                ?>
                                <span class="stats-badge stats-total">
                                    Total: <?php echo $data['summary']['total_items'] ?? 0; ?>
                                </span>
                                <span class="stats-badge stats-healthy">
                                    Healthy: <?php echo $data['summary']['healthy_items'] ?? 0; ?>
                                </span>
                                <span class="stats-badge stats-faulty">
                                    Faulty: <?php echo $data['summary']['faulty_items'] ?? 0; ?>
                                </span>
                                <?php endif; ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="view_history.php?id=<?php echo $record['id']; ?>" class="view-btn">
                                    View Details
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>