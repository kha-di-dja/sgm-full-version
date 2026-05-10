<?php
include "config.php";

// Get filter values
$selected_filiere = isset($_GET['filiere']) ? $_GET['filiere'] : '';
$selected_annee = isset($_GET['annee']) ? $_GET['annee'] : '';

if (isset($_GET['reset'])) {
    header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
    exit();
}

// Build query with filters
$sql = "SELECT * FROM eleve WHERE 1=1";
$params = [];
$types = "";

if (!empty($selected_filiere)) {
    $sql .= " AND Filiere = ?";
    $params[] = $selected_filiere;
    $types .= "s";
}
if (!empty($selected_annee)) {
    $sql .= " AND Annee = ?";
    $params[] = $selected_annee;
    $types .= "s";
}

$sql .= " ORDER BY Moyenne DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$students = $result->fetch_all(MYSQLI_ASSOC);

// Calculate statistics
$total_students = count($students);
$passed = 0;
$failed = 0;
$sum_averages = 0;
$highest_avg = 0;
$lowest_avg = 20;

foreach ($students as $student) {
    $avg = $student['Moyenne'];
    $sum_averages += $avg;
    if ($avg >= 10) {
        $passed++;
    } else {
        $failed++;
    }
    if ($avg > $highest_avg) $highest_avg = $avg;
    if ($avg < $lowest_avg) $lowest_avg = $avg;
}

$overall_average = $total_students > 0 ? $sum_averages / $total_students : 0;
$pass_rate = $total_students > 0 ? ($passed / $total_students) * 100 : 0;
$top_3 = array_slice($students, 0, 3);

// Get distinct values for filters
$filieres = $conn->query("SELECT DISTINCT Filiere FROM eleve WHERE Filiere IS NOT NULL AND Filiere != '' ORDER BY Filiere");
$years = $conn->query("SELECT DISTINCT Annee FROM eleve WHERE Annee IS NOT NULL AND Annee != '' ORDER BY Annee");

// Helper functions
function getMedal($rank) {
    switch ($rank) {
        case 1: return '🥇';
        case 2: return '🥈';
        case 3: return '🥉';
        default: return '🏅';
    }
}

function getStatusBadge($avg) {
    if ($avg >= 10) {
        return '<span class="status-badge status-pass">✓ Passed</span>';
    } else {
        return '<span class="status-badge status-fail">✗ Failed</span>';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Rankings - SGM</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="ranking.css">
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><rect width='100' height='100' rx='20' fill='%23035772'/><text x='50' y='65' text-anchor='middle' fill='white' font-size='38' font-weight='bold'>SGM</text></svg>">

    
       
    
</head>
<body>

<header class="header-container" id="home">
    <a href="home.html" class="header-brand">
        <div class="text-logo">SG<span>M</span></div>
        <div class="logo-sub">Students Management System</div>
    </a>
    <nav aria-label="Main">
        <ul class="nav-bar">
            <li class="nav-item"><a href="student.php">🎓 Student</a></li>
            <li class="nav-item"><a href="subject.php">📚 Subject</a></li>
            <li class="nav-item"><a href="result.php">📋 Result</a></li>
            <li class="nav-item"><a href="search.php">🔍 Search</a></li>
            <li class="nav-item"><a href="ranking.php" aria-current="page">🥇 Ranking</a></li>
        </ul>
    </nav>
</header>

<div class="container">
    <div class="header-section"id ="up">
        <h1> Student Rankings</h1>
        <p>Academic performance ranking and student statistics</p>
    </div>

    <!-- Filter Bar -->
    <div class="filter-bar">
        <form method="GET" style="display: flex; gap: 20px; width: 100%; flex-wrap: wrap;">
            <div class="filter-group">
                <label>📚 Major</label>
                <select name="filiere">
                    <option value="">All Majors</option>
                    <?php while($f = $filieres->fetch_assoc()): ?>
                        <option value="<?= htmlspecialchars($f['Filiere']) ?>" <?= $selected_filiere == $f['Filiere'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($f['Filiere']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="filter-group">
                <label>🎓 Year</label>
                <select name="annee">
                    <option value="">All Years</option>
                    <?php while($y = $years->fetch_assoc()): ?>
                        <option value="<?= htmlspecialchars($y['Annee']) ?>" <?= $selected_annee == $y['Annee'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($y['Annee']) ?> Year
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="filter-buttons">
                <button type="submit" class="filter-btn">🔍 Apply Filters</button>
                <a href="?reset=1" class="filter-btn reset-btn">🔄 Reset</a>
            </div>
        </form>
    </div>

    <!-- Statistics Grid -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-number"><?= number_format($total_students) ?></div>
            <div class="stat-label">Total Students</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?= number_format($passed) ?></div>
            <div class="stat-label">Passed</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?= number_format($failed) ?></div>
            <div class="stat-label">Failed</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?= number_format($pass_rate, 0) ?>%</div>
            <div class="stat-label">Pass Rate</div>
        </div>
    </div>

    <?php if ($total_students > 0): ?>

    <!-- Top 3 Podium -->
    <?php if (count($top_3) > 0): ?>
    <div class="glass-card">
        <h2 style="color: var(--navy); margin-bottom: 1.5rem; font-size: 1.4rem;">🏆 Top 3 Students</h2>
        <div class="podium">
            <?php foreach ($top_3 as $index => $student): 
                $rank = $index + 1;
                $medal = getMedal($rank);
                $podium_class = $rank == 1 ? 'rank-1' : ($rank == 2 ? 'rank-2' : 'rank-3');
            ?>
            <div class="podium-card <?= $podium_class ?>">
                <div class="podium-medal"><?= $medal ?></div>
                <div class="podium-name"><?= htmlspecialchars($student['Prenom']) ?> <?= htmlspecialchars($student['Nom']) ?></div>
                <div class="podium-code"><?= htmlspecialchars($student['Code']) ?></div>
                <div class="podium-grade"><?= number_format($student['Moyenne'], 2) ?></div>
                <div class="podium-label">Average</div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Ranking Table -->
    <div class="glass-card">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
            <h2 style="color: var(--navy); font-size: 1.4rem;">📊 Complete Ranking List</h2>
            <span class="results-count" style="background: var(--sky); padding: 0.3rem 0.8rem; border-radius: 20px; font-size: 0.8rem; font-weight: 600; color: var(--navy);"><?= $total_students ?> students</span>
        </div>
        
        <div style="overflow-x: auto;">
            <table class="modern-table">
                <thead>
                    <tr>
                        <th>Rank</th>
                        <th>Code</th>
                        <th>Last Name</th>
                        <th>First Name</th>
                        <th>Year</th>
                        <th>Major</th>
                        <th>Average</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $index => $student): 
                        $rank = $index + 1;
                        $rank_class = $rank == 1 ? 'rank-1-cell' : ($rank == 2 ? 'rank-2-cell' : ($rank == 3 ? 'rank-3-cell' : ''));
                    ?>
                        <tr>
                            <td class="rank-cell <?= $rank_class ?>">
                                <?= getMedal($rank) ?> <?= $rank ?>
                            </td>
                            <td><strong><?= htmlspecialchars($student['Code']) ?></strong></td>
                            <td><?= htmlspecialchars($student['Nom']) ?></td>
                            <td><?= htmlspecialchars($student['Prenom']) ?></td>
                            <td><span class="badge badge-year"><?= htmlspecialchars($student['Annee']) ?>ère</span></td>
                            <td><span class="badge badge-filiere"><?= htmlspecialchars($student['Filiere']) ?></span></td>
                            <td class="badge-grade"><?= number_format($student['Moyenne'], 2) ?></td>
                            <td><?= getStatusBadge($student['Moyenne']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php else: ?>
    <div class="glass-card">
        <div class="empty-state">
            <i class="fa-solid fa-chart-simple"></i>
            <p>No students found matching the selected filters.</p>
            <p style="font-size: 0.85rem; margin-top: 0.5rem;">Try adjusting your filters or add some students first.</p>
        </div>
    </div>
    <?php endif; ?>
</div>

<footer class="footer">
    <p>© 2026 SGM | All rights reserved | Students Grades Management System</p>
    <button class="up-button"><a href="#up"><i class="fa-solid fa-circle-arrow-up"></i></a></button>
</footer>

</body>
</html>