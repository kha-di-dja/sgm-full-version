<?php
include "config.php";

/*  DELETE SUBJECT*/
if(isset($_GET['delete'])) {
    $codeM = $_GET['delete'];
    $conn->query("DELETE FROM resultat WHERE CodeM='$codeM'");
    $conn->query("DELETE FROM matiere WHERE CodeM='$codeM'");
    header("Location: subject.php?msg=deleted");
    exit();
}

/*ADD SUBJECT */
if(isset($_POST['save'])) {
    $codeM = $_POST['codeM'];
    $nomM = $_POST['nomM'];
    $coefficient = $_POST['coefficient'];
    $anneeM = $_POST['anneeM'];
    $filiere = $_POST['filiere'];
    
    $sql = "INSERT INTO matiere VALUES ('$codeM', '$nomM', '$coefficient', '$anneeM', '$filiere')";
    if($conn->query($sql)) {
        header("Location: subject.php?msg=added");
    } else {
        header("Location: subject.php?msg=error");
    }
    exit();
}

/* UPDATE SUBJECT */
if(isset($_POST['update'])) {
    $old_codeM = $_POST['old_codeM'];
    $codeM = $_POST['codeM'];
    $nomM = $_POST['nomM'];
    $coefficient = $_POST['coefficient'];
    $anneeM = $_POST['anneeM'];
    $filiere = $_POST['filiere'];
    
    $sql = "UPDATE matiere SET CodeM='$codeM', NomM='$nomM', Coefficient='$coefficient', AnneeM='$anneeM', Filiere='$filiere' WHERE CodeM='$old_codeM'";
    if($conn->query($sql)) {
        header("Location: subject.php?msg=updated");
    } else {
        header("Location: subject.php?msg=error");
    }
    exit();
}

/*  GET FILTER VALUES & STATS */
$selected_annee = isset($_GET['annee']) ? $_GET['annee'] : '';
$selected_filiere = isset($_GET['filiere']) ? $_GET['filiere'] : '';

if (isset($_GET['reset'])) {
    header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
    exit();
}

// Statistics queries (ignoring filters for global stats)
$total_subjects_query = $conn->query("SELECT COUNT(*) as total FROM matiere");
$total_subjects = $total_subjects_query->fetch_assoc()['total'];

$total_years_query = $conn->query("SELECT COUNT(DISTINCT AnneeM) as total FROM matiere");
$total_years = $total_years_query->fetch_assoc()['total'];

$total_filieres_query = $conn->query("SELECT COUNT(DISTINCT Filiere) as total FROM matiere");
$total_filieres = $total_filieres_query->fetch_assoc()['total'];

// Build query with filters
$sql = "SELECT * FROM matiere WHERE 1=1";
$params = [];
$types = "";

if (!empty($selected_annee)) {
    $sql .= " AND AnneeM = ?";
    $params[] = $selected_annee;
    $types .= "s";
}
if (!empty($selected_filiere)) {
    $sql .= " AND Filiere = ?";
    $params[] = $selected_filiere;
    $types .= "s";
}

$sql .= " ORDER BY CodeM ASC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Check if we should show edit modal
$show_edit_modal = isset($_GET['edit']);
$edit_subject = null;
if ($show_edit_modal) {
    $edit_codeM = $_GET['edit'];
    $edit_result = $conn->query("SELECT * FROM matiere WHERE CodeM='$edit_codeM'");
    $edit_subject = $edit_result->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subject Management - SGM</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="subject.css">
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
            <li class="nav-item"><a href="subject.php" aria-current="page">📚 Subject</a></li>
            <li class="nav-item"><a href="result.php">📋 Result</a></li>
            <li class="nav-item"><a href="search.php">🔍 Search</a></li>
            <li class="nav-item"><a href="ranking.php">🥇 Ranking</a></li>
        </ul>
    </nav>
</header>

<div class="container">
    <?php
    if(isset($_GET['msg'])) {
        if($_GET['msg'] == 'added') echo '<div class="message-container"><div class="success-message">✅ Subject added successfully!</div></div>';
        if($_GET['msg'] == 'updated') echo '<div class="message-container"><div class="success-message">✅ Subject updated successfully!</div></div>';
        if($_GET['msg'] == 'deleted') echo '<div class="message-container"><div class="success-message">✅ Subject deleted successfully!</div></div>';
        if($_GET['msg'] == 'error') echo '<div class="message-container"><div class="error-message">❌ Error occurred!</div></div>';
    }
    ?>

    <!-- Header Section -->
    <div class="header-section"id ="up">
        <h1>Subject Management</h1>
        <p>Manage academic subjects, filter by year and major</p>
    </div>

    <!-- Filter Bar -->
    <div class="filter-bar">
        <form method="GET" style="display: flex; gap: 20px; width: 100%; flex-wrap: wrap;">
            <div class="filter-group">
                <label>🎓 Year</label>
                <select name="annee">
                    <option value="">All Years</option>
                    <option value="1" <?= $selected_annee == '1' ? 'selected' : '' ?>>1st Year</option>
                    <option value="2" <?= $selected_annee == '2' ? 'selected' : '' ?>>2nd Year</option>
                    <option value="3" <?= $selected_annee == '3' ? 'selected' : '' ?>>3rd Year</option>
                </select>
            </div>
            <div class="filter-group">
                <label>📚 Major </label>
                <select name="filiere">
                    <option value="">All Majors</option>
                    <option value="Experimental Sciences" <?= $selected_filiere == 'Experimental Sciences' ? 'selected' : '' ?>>🔬 Experimental Sciences</option>
                    <option value="Mathematics" <?= $selected_filiere == 'Mathematics' ? 'selected' : '' ?>>📐 Mathematics</option>
                    <option value="Technical Mathematics" <?= $selected_filiere == 'Technical Mathematics' ? 'selected' : '' ?>>⚙️ Technical Mathematics</option>
                    <option value="Foreign Languages" <?= $selected_filiere == 'Foreign Languages' ? 'selected' : '' ?>>🌍 Foreign Languages</option>
                    <option value="Literature and Philosophy" <?= $selected_filiere == 'Literature and Philosophy' ? 'selected' : '' ?>>📚 Literature and Philosophy</option>
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
            <div class="stat-number"><?= number_format($total_subjects) ?></div>
            <div class="stat-label">Total Subjects</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?= number_format($total_years) ?></div>
            <div class="stat-label">Academic Years</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?= number_format($total_filieres) ?></div>
            <div class="stat-label">Majors</div>
        </div>
    </div>

    <!-- Subject List Card -->
    <div class="glass-card">
        <div class="card-header">
            <div>
                <h2>📋 Subject List</h2>
                <p>Showing <?= $result->num_rows ?> subject(s)</p>
            </div>
            <a href="#" class="btn" onclick="document.getElementById('addModal').classList.remove('hidden'); return false;">+ New Subject</a>
        </div>

        <?php if($result->num_rows > 0): ?>
        <div style="overflow-x: auto;">
            <table class="modern-table">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Subject Name</th>
                        <th>Coefficient</th>
                        <th>Year</th>
                        <th>Major</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($row['CodeM']) ?></strong></td>
                            <td><?= htmlspecialchars($row['NomM']) ?></td>
                            <td><span class="coeff-badge">× <?= htmlspecialchars($row['Coefficient']) ?></span></td>
                            <td><span class="year-badge"><?= htmlspecialchars($row['AnneeM']) ?>ère</span></td>
                            <td><span class="filiere-badge"><?= htmlspecialchars($row['Filiere']) ?></span></td>
                            <td class="actions">
                                <a href="?edit=<?= $row['CodeM'] ?>" class="edit">✎ Edit</a>
                                <a href="?delete=<?= $row['CodeM'] ?>" class="delete" onclick="return confirm('Delete this subject? This will also delete all results for this subject.')">🗑 Delete</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
            <div class="empty-state">
                <p>📭 No subjects found matching the selected filters.</p>
                <p><a href="#" style="color: var(--navy);" onclick="document.getElementById('addModal').classList.remove('hidden'); return false;">+ Add a new subject</a> or <a href="?reset=1" style="color: var(--navy);">reset filters</a></p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Add Subject Modal -->
<div id="addModal" class="modal-overlay hidden">
    <div class="modal-window">
        <a href="#" class="modal-close" onclick="document.getElementById('addModal').classList.add('hidden'); return false;">&times;</a>
        <h3>➕ Add New Subject</h3>
        <form method="POST">
            <div class="form-row">
                <div class="form-group">
                    <label>Code *</label>
                    <input type="text" name="codeM" required placeholder="MATH101">
                </div>
                <div class="form-group">
                    <label>Subject Name *</label>
                    <input type="text" name="nomM" required placeholder="Mathematics">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Coefficient</label>
                    <input type="text" name="coefficient" placeholder="e.g., 3">
                </div>
                <div class="form-group">
                    <label>📅 Year</label>
                    <select name="anneeM">
                        <option value="">Select Year</option>
                        <option value="1">1st Year</option>
                        <option value="2">2nd Year</option>
                        <option value="3">3rd Year</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>🎓 Major</label>
                <select name="filiere">
                    <option value="">Select Filiere</option>
                    <option value="Experimental Sciences">🔬 Experimental Sciences</option>
                    <option value="Mathematics">📐 Mathematics</option>
                    <option value="Technical Mathematics">⚙️ Technical Mathematics</option>
                    <option value="Foreign Languages">🌍 Foreign Languages</option>
                    <option value="Literature and Philosophy">📚 Literature and Philosophy</option>
                </select>
            </div>
            <button type="submit" name="save" class="save-btn">💾 Save Subject</button>
        </form>
    </div>
</div>

<!-- Edit Subject Modal -->
<?php if($show_edit_modal && $edit_subject): ?>
<div id="editModal" class="modal-overlay" style="display: flex;">
    <div class="modal-window">
        <a href="subject.php" class="modal-close">&times;</a>
        <h3>✏️ Edit Subject</h3>
        <form method="POST">
            <input type="hidden" name="old_codeM" value="<?= htmlspecialchars($edit_subject['CodeM']) ?>">
            <div class="form-row">
                <div class="form-group">
                    <label>Code *</label>
                    <input type="text" name="codeM" value="<?= htmlspecialchars($edit_subject['CodeM']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Subject Name *</label>
                    <input type="text" name="nomM" value="<?= htmlspecialchars($edit_subject['NomM']) ?>" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Coefficient</label>
                    <input type="text" name="coefficient" value="<?= htmlspecialchars($edit_subject['Coefficient']) ?>" placeholder="e.g., 3">
                </div>
                <div class="form-group">
                    <label>📅 Year</label>
                    <select name="anneeM">
                        <option value="">Select Year</option>
                        <option value="1" <?= $edit_subject['AnneeM'] == '1' ? 'selected' : '' ?>>1st Year</option>
                        <option value="2" <?= $edit_subject['AnneeM'] == '2' ? 'selected' : '' ?>>2nd Year</option>
                        <option value="3" <?= $edit_subject['AnneeM'] == '3' ? 'selected' : '' ?>>3rd Year</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>🎓 Major</label>
                <select name="filiere">
                    <option value="">Select Filiere</option>
                    <option value="Experimental Sciences" <?= $edit_subject['Filiere'] == 'Experimental Sciences' ? 'selected' : '' ?>>🔬 Experimental Sciences</option>
                    <option value="Mathematics" <?= $edit_subject['Filiere'] == 'Mathematics' ? 'selected' : '' ?>>📐 Mathematics</option>
                    <option value="Technical Mathematics" <?= $edit_subject['Filiere'] == 'Technical Mathematics' ? 'selected' : '' ?>>⚙️ Technical Mathematics</option>
                    <option value="Foreign Languages" <?= $edit_subject['Filiere'] == 'Foreign Languages' ? 'selected' : '' ?>>🌍 Foreign Languages</option>
                    <option value="Literature and Philosophy" <?= $edit_subject['Filiere'] == 'Literature and Philosophy' ? 'selected' : '' ?>>📚 Literature and Philosophy</option>
                </select>
            </div>
            <button type="submit" name="update" class="save-btn">💾 Update Subject</button>
        </form>
    </div>
</div>
<?php endif; ?>

<footer class="footer">
    <p>© 2026 SGM | All rights reserved | Students Grades Management System</p>
    <button class="up-button"><a href="#up"><i class="fa-solid fa-circle-arrow-up"></i></a></button>
</footer>

</body>
</html>