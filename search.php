<?php
include "config.php";

// Get search parameters
$search_type = isset($_GET['search_type']) ? $_GET['search_type'] : 'student';
$search_keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
$selected_filiere = isset($_GET['filiere']) ? $_GET['filiere'] : '';
$selected_annee = isset($_GET['annee']) ? $_GET['annee'] : '';

$results = [];
$result_count = 0;

// Search based on type
if (!empty($search_keyword) || !empty($selected_filiere) || !empty($selected_annee)) {
    
    if ($search_type == 'student') {
        // Search in students table
        $sql = "SELECT * FROM eleve WHERE 1=1";
        $params = [];
        $types = "";
        
        if (!empty($search_keyword)) {
            $sql .= " AND (Code LIKE ? OR Nom LIKE ? OR Prenom LIKE ?)";
            $like = "%$search_keyword%";
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $types .= "sss";
        }
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
        
        $sql .= " ORDER BY Code ASC";
        
        $stmt = $conn->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $results = $stmt->get_result();
        $result_count = $results->num_rows;
        
    } elseif ($search_type == 'subject') {
        // Search in subjects table
        $sql = "SELECT * FROM matiere WHERE 1=1";
        $params = [];
        $types = "";
        
        if (!empty($search_keyword)) {
            $sql .= " AND (CodeM LIKE ? OR NomM LIKE ?)";
            $like = "%$search_keyword%";
            $params[] = $like;
            $params[] = $like;
            $types .= "ss";
        }
        if (!empty($selected_filiere)) {
            $sql .= " AND Filiere = ?";
            $params[] = $selected_filiere;
            $types .= "s";
        }
        if (!empty($selected_annee)) {
            $sql .= " AND AnneeM = ?";
            $params[] = $selected_annee;
            $types .= "s";
        }
        
        $sql .= " ORDER BY CodeM ASC";
        
        $stmt = $conn->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $results = $stmt->get_result();
        $result_count = $results->num_rows;
        
    } elseif ($search_type == 'result') {
        // Search in results 
        $sql = "SELECT resultat.Code, resultat.CodeM, resultat.Note, 
                       eleve.Nom, eleve.Prenom, eleve.Filiere, eleve.Annee,
                       matiere.NomM, matiere.Coefficient
                FROM resultat 
                INNER JOIN eleve ON resultat.Code = eleve.Code 
                INNER JOIN matiere ON resultat.CodeM = matiere.CodeM
                WHERE 1=1";
        $params = [];
        $types = "";
        
        if (!empty($search_keyword)) {
            $sql .= " AND (eleve.Code LIKE ? OR eleve.Nom LIKE ? OR eleve.Prenom LIKE ? OR matiere.NomM LIKE ?)";
            $like = "%$search_keyword%";
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $types .= "ssss";
        }
        if (!empty($selected_filiere)) {
            $sql .= " AND eleve.Filiere = ?";
            $params[] = $selected_filiere;
            $types .= "s";
        }
        if (!empty($selected_annee)) {
            $sql .= " AND eleve.Annee = ?";
            $params[] = $selected_annee;
            $types .= "s";
        }
        
        $sql .= " ORDER BY resultat.Code ASC";
        
        $stmt = $conn->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $results = $stmt->get_result();
        $result_count = $results->num_rows;
    }
}

// Get distinct values for filters
$filieres = $conn->query("SELECT DISTINCT Filiere FROM eleve WHERE Filiere IS NOT NULL AND Filiere != '' ORDER BY Filiere");
$years = $conn->query("SELECT DISTINCT Annee FROM eleve WHERE Annee IS NOT NULL AND Annee != '' ORDER BY Annee");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Advanced Search - SGM</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="search.css">
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
          <li class="nav-item"><a href="student.php">🎓Student</a></li>
          <li class="nav-item"><a href="subject.php" >📚Subject</a></li>
          <li class="nav-item"><a href="result.php" >📋Result</a></li>
          <li class="nav-item"><a href="search.php" aria-current="page">🔍Search</a></li>
          <li class="nav-item"><a href="ranking.php">🥇Ranking</a></li>
        </ul>
    </nav>
</header>

<div class="container">
    <div class="header-section"id ="up">
        <h1> Advanced Search</h1>
        <p>Search across students, subjects, and results with powerful filters</p>
    </div>

    <div class="glass-card">
        <!-- Search Type Tabs -->
        <div class="search-type-tabs">
            <a href="?search_type=student&keyword=<?= urlencode($search_keyword) ?>&filiere=<?= urlencode($selected_filiere) ?>&annee=<?= urlencode($selected_annee) ?>" class="tab-link <?= $search_type == 'student' ? 'active' : '' ?>">👨‍🎓 Students</a>
            <a href="?search_type=subject&keyword=<?= urlencode($search_keyword) ?>&filiere=<?= urlencode($selected_filiere) ?>&annee=<?= urlencode($selected_annee) ?>" class="tab-link <?= $search_type == 'subject' ? 'active' : '' ?>">📚 Subjects</a>
            <a href="?search_type=result&keyword=<?= urlencode($search_keyword) ?>&filiere=<?= urlencode($selected_filiere) ?>&annee=<?= urlencode($selected_annee) ?>" class="tab-link <?= $search_type == 'result' ? 'active' : '' ?>">📊 Results</a>
        </div>

        <!-- Search Form -->
        <form method="GET" class="search-form">
            <input type="hidden" name="search_type" value="<?= $search_type ?>">
            
            <div class="search-row">
                <div class="search-input-group">
                    <input type="text" name="keyword" placeholder="Enter search keyword..." value="<?= htmlspecialchars($search_keyword) ?>">
                </div>
                <div class="filter-group">
                    <select name="filiere">
                        <option value="">All Majors</option>
                        <?php 
                        $filieres->data_seek(0);
                        while($f = $filieres->fetch_assoc()): 
                        ?>
                            <option value="<?= htmlspecialchars($f['Filiere']) ?>" <?= $selected_filiere == $f['Filiere'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($f['Filiere']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <select name="annee">
                        <option value="">All Years</option>
                        <?php 
                        $years->data_seek(0);
                        while($y = $years->fetch_assoc()): 
                        ?>
                            <option value="<?= htmlspecialchars($y['Annee']) ?>" <?= $selected_annee == $y['Annee'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($y['Annee']) ?> Year
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <button type="submit" class="search-btn">🔍 Search</button>
                <a href="search.php" class="search-btn reset-btn">🔄 Reset</a>
            </div>
        </form>
    </div>

    <!-- Results Section -->
    <?php if (!empty($search_keyword) || !empty($selected_filiere) || !empty($selected_annee)): ?>
    <div class="glass-card">
        <div class="results-header">
            <h2>
                <?php if ($search_type == 'student'): ?>👨‍🎓 Student Results
                <?php elseif ($search_type == 'subject'): ?>📚 Subject Results
                <?php else: ?>📊 Result Results
                <?php endif; ?>
            </h2>
            <span class="results-count"><?= $result_count ?> result(s) found</span>
        </div>

        <?php if ($result_count > 0): ?>
        <div style="overflow-x: auto;">
            <table class="modern-table">
                <thead>
                    <tr>
                        <?php if ($search_type == 'student'): ?>
                            <th>Code</th>
                            <th>Full Name</th>
                            <th>Birth Date</th>
                            <th>Gender</th>
                            <th>Year</th>
                            <th>Major</th>
                            <th>Average</th>
                        <?php elseif ($search_type == 'subject'): ?>
                            <th>Code</th>
                            <th>Subject Name</th>
                            <th>Coefficient</th>
                            <th>Year</th>
                            <th>Major</th>
                        <?php else: ?>
                            <th>Student Code</th>
                            <th>Student Name</th>
                            <th>Subject</th>
                            <th>Coefficient</th>
                            <th>Grade</th>
                            <th>Year</th>
                            <th>Major</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($search_type == 'student'): 
                        while($row = $results->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($row['Code']) ?></strong></td>
                            <td><?= htmlspecialchars($row['Prenom']) ?> <?= htmlspecialchars($row['Nom']) ?></td>
                            <td><?= htmlspecialchars($row['Date_nais']) ?></td>
                            <td><span class="badge <?= $row['Sexe'] == 'M' ? 'badge-gender-male' : 'badge-gender-female' ?>">
                                <?= $row['Sexe'] == 'M' ? '♂ Male' : '♀ Female' ?>
                            </span></td>
                            <td><span class="badge badge-year"><?= htmlspecialchars($row['Annee']) ?>ère</span></td>
                            <td><span class="badge badge-filiere"><?= htmlspecialchars($row['Filiere']) ?></span></td>
                            <td><span class="badge badge-grade"><?= number_format($row['Moyenne'], 2) ?></span></td>
                        </tr>
                        <?php endwhile; 
                    elseif ($search_type == 'subject'): 
                        while($row = $results->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($row['CodeM']) ?></strong></td>
                            <td><?= htmlspecialchars($row['NomM']) ?></td>
                            <td><span class="badge badge-coeff">× <?= htmlspecialchars($row['Coefficient']) ?></span></td>
                            <td><span class="badge badge-year"><?= htmlspecialchars($row['AnneeM']) ?>ère</span></td>
                            <td><span class="badge badge-filiere"><?= htmlspecialchars($row['Filiere']) ?></span></td>
                        </tr>
                        <?php endwhile; 
                    else: 
                        while($row = $results->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($row['Code']) ?></strong></td>
                            <td><?= htmlspecialchars($row['Prenom']) ?> <?= htmlspecialchars($row['Nom']) ?></td>
                            <td><?= htmlspecialchars($row['NomM']) ?></td>
                            <td><span class="badge badge-coeff">× <?= htmlspecialchars($row['Coefficient']) ?></span></td>
                            <td><span class="badge badge-grade"><?= number_format($row['Note'], 2) ?> / 20</span></td>
                            <td><span class="badge badge-year"><?= htmlspecialchars($row['Annee']) ?>ère</span></td>
                            <td><span class="badge badge-filiere"><?= htmlspecialchars($row['Filiere']) ?></span></td>
                        </tr>
                        <?php endwhile; 
                    endif; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="empty-state">
            <i class="fa-solid fa-search"></i>
            <p>No results found matching your search criteria.</p>
            <p style="font-size: 0.85rem; margin-top: 0.5rem;">Try different keywords or adjust your filters.</p>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Quick Tips Section -->
    <div class="glass-card">
        <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
            <i class="fa-solid fa-lightbulb" style="font-size: 1.8rem; color: var(--warning);"></i>
            <h3 style="color: var(--navy);">Search Tips</h3>
        </div>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">
            <div>
                <strong>👨‍🎓 Students:</strong>
                <ul style="margin-top: 0.5rem; margin-left: 1rem; color: var(--muted);">
                    <li>Search by student code (e.g., E001)</li>
                    <li>Search by first or last name</li>
                    <li>Filter by year and major</li>
                </ul>
            </div>
            <div>
                <strong>📚 Subjects:</strong>
                <ul style="margin-top: 0.5rem; margin-left: 1rem; color: var(--muted);">
                    <li>Search by subject code (e.g., MATH101)</li>
                    <li>Search by subject name (e.g., Mathematics)</li>
                    <li>Filter by year and major</li>
                </ul>
            </div>
            <div>
                <strong>📊 Results:</strong>
                <ul style="margin-top: 0.5rem; margin-left: 1rem; color: var(--muted);">
                    <li>Search by student name or code</li>
                    <li>Search by subject name</li>
                    <li>Find all results for a specific student</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<footer class="footer">
    <p>© 2026 SGM | All rights reserved | Students Grades Management System</p>
    <button class="up-button"><a href="#up"><i class="fa-solid fa-circle-arrow-up"></i></a></button>
</footer>

</body>
</html>