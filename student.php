<?php
include "config.php";

/* DELETE STUDENT*/
if(isset($_GET['delete'])) {
    $code = $_GET['delete'];
    $conn->query("DELETE FROM resultat WHERE Code='$code'");
    $conn->query("DELETE FROM eleve WHERE Code='$code'");
    header("Location: student.php?msg=deleted");
    exit();
}

/* ADD STUDENT */
if(isset($_POST['save'])) {
    $code = $_POST['code'];
    $nom = $_POST['nom'];
    $prenom = $_POST['prenom'];
    $date_nais = $_POST['date_nais'];
    $sexe = $_POST['sexe'];
    $annee = $_POST['annee'];
    $filiere = $_POST['filiere'];
    $moyenne = $_POST['moyenne'];
    
    $sql = "INSERT INTO eleve (Code, Nom, Prenom, Date_nais, Sexe, Annee, Filiere, Moyenne) VALUES ('$code', '$nom', '$prenom', '$date_nais', '$sexe', '$annee', '$filiere', '$moyenne')";
    if($conn->query($sql)) {
        header("Location: student.php?msg=added");
    } else {
        header("Location: student.php?msg=error");
    }
    exit();
}

/* UPDATE STUDENT */
if(isset($_POST['update'])) {
    $old_code = $_POST['old_code'];
    $code = $_POST['code'];
    $nom = $_POST['nom'];
    $prenom = $_POST['prenom'];
    $date_nais = $_POST['date_nais'];
    $sexe = $_POST['sexe'];
    $annee = $_POST['annee'];
    $filiere = $_POST['filiere'];
    $moyenne = $_POST['moyenne'];
    
    $sql = "UPDATE eleve SET Code='$code', Nom='$nom', Prenom='$prenom', Date_nais='$date_nais', Sexe='$sexe', Annee='$annee', Filiere='$filiere', Moyenne='$moyenne' WHERE Code='$old_code'";
    if($conn->query($sql)) {
        header("Location: student.php?msg=updated");
    } else {
        header("Location: student.php?msg=error");
    }
    exit();
}

if(isset($_GET['close_modal'])) {
    header("Location: student.php");
    exit();
}

/* GET FILTER VALUES & STATS */
$selected_annee = isset($_GET['annee']) ? $_GET['annee'] : '';
$selected_filiere = isset($_GET['filiere']) ? $_GET['filiere'] : '';
$selected_sexe = isset($_GET['sexe']) ? $_GET['sexe'] : '';

if (isset($_GET['reset'])) {
    header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
    exit();
}

// Statistics queries 
$total_students_query = $conn->query("SELECT COUNT(*) as total FROM eleve");
$total_students = $total_students_query->fetch_assoc()['total'];

$avg_moyenne_query = $conn->query("SELECT AVG(Moyenne) as avg FROM eleve");
$avg_moyenne = $avg_moyenne_query->fetch_assoc()['avg'];

$male_count_query = $conn->query("SELECT COUNT(*) as total FROM eleve WHERE Sexe='M'");
$male_count = $male_count_query->fetch_assoc()['total'];

$female_count_query = $conn->query("SELECT COUNT(*) as total FROM eleve WHERE Sexe='F'");
$female_count = $female_count_query->fetch_assoc()['total'];

// Build query with filters
$sql = "SELECT * FROM eleve WHERE 1=1";
$params = [];
$types = "";

if (!empty($selected_annee)) {
    $sql .= " AND Annee = ?";
    $params[] = $selected_annee;
    $types .= "s";
}
if (!empty($selected_filiere)) {
    $sql .= " AND Filiere = ?";
    $params[] = $selected_filiere;
    $types .= "s";
}
if (!empty($selected_sexe)) {
    $sql .= " AND Sexe = ?";
    $params[] = $selected_sexe;
    $types .= "s";
}

$sql .= " ORDER BY Code ASC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$show_edit_modal = isset($_GET['edit']);
$edit_student = null;
if ($show_edit_modal) {
    $edit_code = $_GET['edit'];
    $edit_result = $conn->query("SELECT * FROM eleve WHERE Code='$edit_code'");
    $edit_student = $edit_result->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Management - SGM</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="student.css">
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
            <li class="nav-item"><a href="student.php" aria-current="page">🎓 Student</a></li>
            <li class="nav-item"><a href="subject.php">📚 Subject</a></li>
            <li class="nav-item"><a href="result.php">📋 Result</a></li>
            <li class="nav-item"><a href="search.php">🔍 Search</a></li>
            <li class="nav-item"><a href="ranking.php">🥇 Ranking</a></li>
        </ul>
    </nav>
</header>

<div class="container">
    <?php
    if(isset($_GET['msg'])) {
        if($_GET['msg'] == 'added') echo '<div class="message-container"><div class="success-message">✅ Student added successfully!</div></div>';
        if($_GET['msg'] == 'updated') echo '<div class="message-container"><div class="success-message">✅ Student updated successfully!</div></div>';
        if($_GET['msg'] == 'deleted') echo '<div class="message-container"><div class="success-message">✅ Student deleted successfully!</div></div>';
        if($_GET['msg'] == 'error') echo '<div class="message-container"><div class="error-message">❌ Error occurred!</div></div>';
    }
    ?>

    <!-- Header Section -->
    <div class="header-section"id ="up">
        <h1>Student Management</h1>
        <p>Manage student records, filter by year, major, and gender</p>
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
                <label>📚 Major</label>
                <select name="filiere">
                    <option value="">All Majors</option>
                    <option value="Experimental Sciences" <?= $selected_filiere == 'Experimental Sciences' ? 'selected' : '' ?>>🔬 Experimental Sciences</option>
                    <option value="Mathematics" <?= $selected_filiere == 'Mathematics' ? 'selected' : '' ?>>📐 Mathematics</option>
                    <option value="Technical Mathematics" <?= $selected_filiere == 'Technical Mathematics' ? 'selected' : '' ?>>⚙️ Technical Mathematics</option>
                    <option value="Foreign Languages" <?= $selected_filiere == 'Foreign Languages' ? 'selected' : '' ?>>🌍 Foreign Languages</option>
                    <option value="Literature and Philosophy" <?= $selected_filiere == 'Literature and Philosophy' ? 'selected' : '' ?>>📚 Literature and Philosophy</option>
                </select>
            </div>
            <div class="filter-group">
                <label>👤 Gender</label>
                <select name="sexe">
                    <option value="">All Genders</option>
                    <option value="M" <?= $selected_sexe == 'M' ? 'selected' : '' ?>>Male</option>
                    <option value="F" <?= $selected_sexe == 'F' ? 'selected' : '' ?>>Female</option>
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
            <div class="stat-number"><?= number_format($avg_moyenne, 1) ?></div>
            <div class="stat-label">Overall Avg Grade</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?= number_format($male_count) ?></div>
            <div class="stat-label">Male Students</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?= number_format($female_count) ?></div>
            <div class="stat-label">Female Students</div>
        </div>
    </div>

    <!-- Student List Card -->
    <div class="glass-card">
        <div class="card-header">
            <div>
                <h2>📋 Student List</h2>
                <p>Showing <?= $result->num_rows ?> student(s)</p>
            </div>
            <a href="#addModal" class="btn" onclick="document.getElementById('addModal').classList.remove('hidden'); return false;">+ New Student</a>
        </div>

        <?php if($result->num_rows > 0): ?>
        <div style="overflow-x: auto;">
            <table class="modern-table">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Last Name</th>
                        <th>First Name</th>
                        <th>Birth Date</th>
                        <th>Gender</th>
                        <th>Year</th>
                        <th>Major</th>
                        <th>Average</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($row['Code']) ?></strong></td>
                            <td><?= htmlspecialchars($row['Nom']) ?></td>
                            <td><?= htmlspecialchars($row['Prenom']) ?></td>
                            <td><?= htmlspecialchars($row['Date_nais']) ?></td>
                            <td>
                                <span class="gender-badge <?= $row['Sexe'] == 'M' ? 'gender-male' : 'gender-female' ?>">
                                    <?= $row['Sexe'] == 'M' ? '♂ Male' : '♀ Female' ?>
                                </span>
                            </td>
                            <td><span class="year-badge"><?= htmlspecialchars($row['Annee']) ?></span></td>
                            <td><span class="filiere-badge"><?= htmlspecialchars($row['Filiere']) ?></span></td>
                            <td class="grade-cell"><?= number_format($row['Moyenne'], 2) ?></td>
                            <td class="actions">
                                <a href="?edit=<?= $row['Code'] ?>" class="edit">✎ Edit</a>
                                <a href="?delete=<?= $row['Code'] ?>" class="delete" onclick="return confirm('Delete this student? This will also delete all their results.')">🗑 Delete</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
            <div class="empty-state">
                <p>📭 No students found matching the selected filters.</p>
                <p><a href="#addModal" style="color: var(--navy);" onclick="document.getElementById('addModal').classList.remove('hidden'); return false;">+ Add a new student</a> or <a href="?reset=1" style="color: var(--navy);">reset filters</a></p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Add Student Modal -->
<div id="addModal" class="modal-overlay hidden">
    <div class="modal-window">
        <a href="#" class="modal-close" onclick="document.getElementById('addModal').classList.add('hidden'); return false;">&times;</a>
        <h3>➕ Add New Student</h3>
        <form method="POST">
            <div class="form-row">
                <div class="form-group">
                    <label>Code *</label>
                    <input type="text" name="code" required placeholder="E001">
                </div>
                <div class="form-group">
                    <label>First Name *</label>
                    <input type="text" name="prenom" required placeholder="John">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Last Name *</label>
                    <input type="text" name="nom" required placeholder="Doe">
                </div>
                <div class="form-group">
                    <label>Birth Date</label>
                    <input type="date" name="date_nais">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Gender</label>
                    <select name="sexe">
                        <option value="F">Female</option>
                        <option value="M">Male</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>📅 Year</label>
                    <select name="annee">
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
            <div class="form-group">
                <label>Average</label>
                <input type="text" name="moyenne" value="0" step="any" placeholder="0.00">
            </div>
            <button type="submit" name="save" class="save-btn">💾 Save Student</button>
        </form>
    </div>
</div>

<!-- Edit Student Modal -->
<?php if($show_edit_modal && $edit_student): ?>
<div id="editModal" class="modal-overlay" style="display: flex;">
    <div class="modal-window">
        <a href="student.php" class="modal-close">&times;</a>
        <h3>✏️ Edit Student</h3>
        <form method="POST">
            <input type="hidden" name="old_code" value="<?= htmlspecialchars($edit_student['Code']) ?>">
            <div class="form-row">
                <div class="form-group">
                    <label>Code *</label>
                    <input type="text" name="code" value="<?= htmlspecialchars($edit_student['Code']) ?>" required>
                </div>
                <div class="form-group">
                    <label>First Name *</label>
                    <input type="text" name="prenom" value="<?= htmlspecialchars($edit_student['Prenom']) ?>" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Last Name *</label>
                    <input type="text" name="nom" value="<?= htmlspecialchars($edit_student['Nom']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Birth Date</label>
                    <input type="date" name="date_nais" value="<?= htmlspecialchars($edit_student['Date_nais']) ?>">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Gender</label>
                    <select name="sexe">
                        <option value="F" <?= $edit_student['Sexe'] == 'F' ? 'selected' : '' ?>>Female</option>
                        <option value="M" <?= $edit_student['Sexe'] == 'M' ? 'selected' : '' ?>>Male</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>📅 Year</label>
                    <select name="annee">
                        <option value="">Select Year</option>
                        <option value="1" <?= $edit_student['Annee'] == '1' ? 'selected' : '' ?>>1st Year</option>
                        <option value="2" <?= $edit_student['Annee'] == '2' ? 'selected' : '' ?>>2nd Year</option>
                        <option value="3" <?= $edit_student['Annee'] == '3' ? 'selected' : '' ?>>3rd Year</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>🎓 Major</label>
                <select name="filiere">
                    <option value="">Select Filiere</option>
                    <option value="Experimental Sciences" <?= $edit_student['Filiere'] == 'Experimental Sciences' ? 'selected' : '' ?>>🔬 Experimental Sciences</option>
                    <option value="Mathematics" <?= $edit_student['Filiere'] == 'Mathematics' ? 'selected' : '' ?>>📐 Mathematics</option>
                    <option value="Technical Mathematics" <?= $edit_student['Filiere'] == 'Technical Mathematics' ? 'selected' : '' ?>>⚙️ Technical Mathematics</option>
                    <option value="Foreign Languages" <?= $edit_student['Filiere'] == 'Foreign Languages' ? 'selected' : '' ?>>🌍 Foreign Languages</option>
                    <option value="Literature and Philosophy" <?= $edit_student['Filiere'] == 'Literature and Philosophy' ? 'selected' : '' ?>>📚 Literature and Philosophy</option>
                </select>
            </div>
            <div class="form-group">
                <label>Average</label>
                <input type="text" name="moyenne" value="<?= htmlspecialchars($edit_student['Moyenne']) ?>" step="any">
            </div>
            <button type="submit" name="update" class="save-btn">💾 Update Student</button>
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