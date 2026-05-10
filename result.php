<?php
session_start();
ob_start();

/* DATABASE CONNECTION  */

try {
    $host = 'localhost';
    $dbname = 'sgm';
    $username = 'root';
    $password = '';
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

/* DELETE RESULT */

$success_message = '';
$error_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["delete"])) {
    $code = $_POST["code"];
    $codeM = $_POST["codeM"];

    try {
        $delete = $pdo->prepare("DELETE FROM resultat WHERE Code = ? AND CodeM = ?");
        $delete->execute([$code, $codeM]);
        
        if ($delete->rowCount() > 0) {
            $success_message = "✅ Result deleted successfully!";
        } else {
            $error_message = "⚠️ No result found to delete.";
        }
    } catch(PDOException $e) {
        $error_message = "❌ Error deleting result: " . $e->getMessage();
    }
}

/* EDIT RESULT*/

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["edit"])) {
    $code = $_POST["code"];
    $codeM = $_POST["codeM"];
    $new_note = trim($_POST["new_note"]);
    
    try {
        $update = $pdo->prepare("UPDATE resultat SET Note = ? WHERE Code = ? AND CodeM = ?");
        $update->execute([$new_note, $code, $codeM]);
        
        if ($update->rowCount() > 0) {
            $success_message = "✅ Result updated successfully!";
        } else {
            $error_message = "⚠️ No changes made or result not found.";
        }
    } catch(PDOException $e) {
        $error_message = "❌ Error updating result: " . $e->getMessage();
    }
}

/* ADD RESULT*/

$errors = [];

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["add"])) {
    $student_code = trim($_POST["student_code"]);
    $first_name = trim($_POST["first_name"]);
    $last_name = trim($_POST["last_name"]);
    $filiere = trim($_POST["filiere"]);
    $annee = trim($_POST["annee"]);
    $subject = trim($_POST["subject"]);
    $note = trim($_POST["note"]);

    if (empty($student_code)) $errors[] = "Student code is required";
    if (empty($first_name)) $errors[] = "First name is required";
    if (empty($last_name)) $errors[] = "Last name is required";
    if (empty($filiere)) $errors[] = "Filiere is required";
    if (empty($annee)) $errors[] = "Year is required";
    if (empty($subject)) $errors[] = "Subject is required";
    if ($note === "") $errors[] = "Grade is required";

    $subjects_map = [
        'Mathematics' => 'MATH', 'Physics' => 'PHY', 'Natural Sciences' => 'SCI',
        'Arabic' => 'AR', 'English' => 'EN', 'French' => 'FR',
        'History' => 'HIST', 'Geography' => 'GEO', 'Philosophy' => 'PHIL',
        'sports' => 'SPORT', 'islamic education' => 'ISL', 'Engineering' => 'ENG'
    ];

    if (empty($errors)) {
        try {
            $check_student = $pdo->prepare("SELECT Code FROM eleve WHERE Code = ?");
            $check_student->execute([$student_code]);
            
            if ($check_student->rowCount() == 0) {
                $insert_student = $pdo->prepare("INSERT INTO eleve (Code, Nom, Prenom, Filiere, Annee) VALUES (?, ?, ?, ?, ?)");
                $insert_student->execute([$student_code, $last_name, $first_name, $filiere, $annee]);
            } else {
                $update_student = $pdo->prepare("UPDATE eleve SET Nom = ?, Prenom = ?, Filiere = ?, Annee = ? WHERE Code = ?");
                $update_student->execute([$last_name, $first_name, $filiere, $annee, $student_code]);
            }

            $subject_code = $subjects_map[$subject];
            $check_subject = $pdo->prepare("SELECT CodeM FROM matiere WHERE CodeM = ?");
            $check_subject->execute([$subject_code]);
            
            if ($check_subject->rowCount() == 0) {
                $insert_subject = $pdo->prepare("INSERT INTO matiere (CodeM, NomM) VALUES (?, ?)");
                $insert_subject->execute([$subject_code, $subject]);
            }

            $check_result = $pdo->prepare("SELECT * FROM resultat WHERE Code = ? AND CodeM = ?");
            $check_result->execute([$student_code, $subject_code]);
            
            if ($check_result->rowCount() > 0) {
                $error_message = "⚠️ Result already exists for this student and subject!";
            } else {
                $insert = $pdo->prepare("INSERT INTO resultat (Code, CodeM, Note) VALUES (?, ?, ?)");
                $insert->execute([$student_code, $subject_code, $note]);
                $success_message = "✅ Result added successfully!";
            }
        } catch(PDOException $e) {
            $error_message = "❌ Database error: " . $e->getMessage();
        }
    }
}

/* GET FILTER VALUES*/

$selected_annee = isset($_GET['annee']) ? $_GET['annee'] : '';
$selected_filiere = isset($_GET['filiere']) ? $_GET['filiere'] : '';
$selected_subject = isset($_GET['subject']) ? $_GET['subject'] : '';

if (isset($_GET['reset'])) {
    header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
    ob_end_flush();
    exit();
}

/* DISPLAY RESULTS */

try {
    $sql = "SELECT resultat.Code, eleve.Nom, eleve.Prenom, eleve.Filiere, eleve.Annee, 
                   resultat.CodeM, matiere.NomM, resultat.Note
            FROM resultat
            INNER JOIN eleve ON resultat.Code = eleve.Code
            INNER JOIN matiere ON resultat.CodeM = matiere.CodeM
            WHERE 1=1";
    
    $params = [];
    
    if (!empty($selected_annee)) {
        $sql .= " AND eleve.Annee = ?";
        $params[] = $selected_annee;
    }
    if (!empty($selected_filiere)) {
        $sql .= " AND eleve.Filiere = ?";
        $params[] = $selected_filiere;
    }
    if (!empty($selected_subject)) {
        $sql .= " AND matiere.NomM = ?";
        $params[] = $selected_subject;
    }
    
    $sql .= " ORDER BY resultat.Code";
    
    $query = $pdo->prepare($sql);
    $query->execute($params);
    $results = $query->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    $results = [];
    $error_message = "Database error: " . $e->getMessage();
}

$all_subjects = [
    'Mathematics', 'Physics', 'Natural Sciences', 'Arabic', 'English', 'French',
    'History', 'Geography', 'Philosophy', 'sports', 'islamic education', 'Engineering'
];

try {
    $total_all_students_query = $pdo->query("SELECT COUNT(DISTINCT Code) FROM eleve");
    $total_all_students = $total_all_students_query->fetchColumn();
    
    $total_all_results_query = $pdo->query("SELECT COUNT(*) FROM resultat");
    $total_all_results = $total_all_results_query->fetchColumn();
    
    $avg_all_grade_query = $pdo->query("SELECT AVG(Note) FROM resultat");
    $avg_all_grade = $avg_all_grade_query->fetchColumn();
} catch(PDOException $e) {
    $total_all_students = 0;
    $total_all_results = 0;
    $avg_all_grade = 0;
}

$total_students = $total_all_students;
$total_results = $total_all_results;
$average_grade = $avg_all_grade ? number_format($avg_all_grade, 1) : 0;

ob_end_flush();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
<link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><rect width='100' height='100' rx='20' fill='%23035772'/><text x='50' y='65' text-anchor='middle' fill='white' font-size='38' font-weight='bold'>SGM</text></svg>">
<link rel="stylesheet" href="result.css">
<title>Results Management - SGM</title>

</head>

<body>
   <header class="header-container" id="home">
      <a href="student.html" class="header-brand">
        <div class="text-logo">SG<span>M</span></div>
        <div class="logo-sub">Students Management System</div>
      </a>
      <nav aria-label="Main">
        <ul class="nav-bar">
          <li class="nav-item"><a href="student.php">🎓Student</a></li>
          <li class="nav-item"><a href="subject.php" >📚Subject</a></li>
          <li class="nav-item"><a href="result.php" aria-current="page">📋Result</a></li>
          <li class="nav-item"><a href="search.php">🔍Search</a></li>
          <li class="nav-item"><a href="ranking.php">🥇Ranking</a></li>
        </ul>
  
      </nav>
    </header>

<div class="container">

    <?php if(!empty($success_message)): ?>
        <div class="message-container">
            <div class="success-message"><?= htmlspecialchars($success_message) ?></div>
        </div>
    <?php endif; ?>

    <?php if(!empty($error_message)): ?>
        <div class="message-container">
            <div class="error-message"><?= htmlspecialchars($error_message) ?></div>
        </div>
    <?php endif; ?>

    <!-- Header -->
    <div class="header-section">
        <h1>Results Management</h1>
        <p>Manage student grades by year, filiere, and subject</p>
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
                <label>📚 Filiere</label>
                <select name="filiere">
                    <option value="">All Filieres</option>
                    <option value="Experimental Sciences" <?= $selected_filiere == 'Experimental Sciences' ? 'selected' : '' ?>>🔬 Experimental Sciences</option>
                    <option value="Mathematics" <?= $selected_filiere == 'Mathematics' ? 'selected' : '' ?>>📐 Mathematics</option>
                    <option value="Technical Mathematics" <?= $selected_filiere == 'Technical Mathematics' ? 'selected' : '' ?>>⚙️ Technical Mathematics</option>
                    <option value="Foreign Languages" <?= $selected_filiere == 'Foreign Languages' ? 'selected' : '' ?>>🌍 Foreign Languages</option>
                    <option value="Literature and Philosophy" <?= $selected_filiere == 'Literature and Philosophy' ? 'selected' : '' ?>>📚 Literature and Philosophy</option>
                </select>
            </div>
            <div class="filter-group">
                <label>📖 Subject</label>
                <select name="subject">
                    <option value="">All Subjects</option>
                    <?php foreach($all_subjects as $subject): ?>
                        <option value="<?= htmlspecialchars($subject) ?>" <?= $selected_subject == $subject ? 'selected' : '' ?>>
                            <?= htmlspecialchars($subject) ?>
                        </option>
                    <?php endforeach; ?>
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
            <div class="stat-number"><?= number_format($total_results) ?></div>
            <div class="stat-label">Total Results</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?= number_format($average_grade, 1) ?></div>
            <div class="stat-label">Overall Average</div>
        </div>
    </div>

    <!-- Results Table -->
    <div class="glass-card">
        <div class="table-header">
            <div>
                <h2>📋 Results List</h2>
                <p>Showing <?= count($results) ?> result(s) from database</p>
            </div>
            <a href="#addModal" class="add-btn">+ Add New Result</a>
        </div>

        <?php if(count($results) > 0): ?>
        <table class="modern-table">
            <thead>
                <tr>
                    <th>Student Code</th>
                    <th>Student Name</th>
                    <th>Year</th>
                    <th>Filiere</th>
                    <th>Subject</th>
                    <th>Grade</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach($results as $row): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($row["Code"]) ?></strong></td>
                    <td><?= htmlspecialchars($row["Prenom"]) ?> <?= htmlspecialchars($row["Nom"]) ?></td>
                    <td><span class="year-badge"><?= htmlspecialchars($row["Annee"] ?? 'N/A') ?>ère</span></td>
                    <td><span class="filiere-badge"><?= htmlspecialchars($row["Filiere"] ?? 'N/A') ?></span></td>
                    <td><?= htmlspecialchars($row["NomM"]) ?></td>
                    <td class="grade-cell"><?= number_format($row["Note"],2) ?> / 20</td>
                    <td>
                        <div class="action-buttons">
                            <a href="#editModal_<?= $row["Code"] ?>_<?= $row["CodeM"] ?>" class="edit-btn">✏️ Edit</a>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="code" value="<?= $row["Code"] ?>">
                                <input type="hidden" name="codeM" value="<?= $row["CodeM"] ?>">
                                <button class="delete-btn" type="submit" name="delete">🗑</button>
                            </form>
                        </div>
                    </td>
                </tr>

                <!-- Edit Modal  -->
                <div id="editModal_<?= $row["Code"] ?>_<?= $row["CodeM"] ?>" class="modal-overlay">
                    <div class="modal-window">
                        <a href="#" class="modal-close">&times;</a>
                        <h3>✏️ Edit Result</h3>
                        <form method="POST">
                            <div class="form-group">
                                <label>Student</label>
                                <input type="text" value="<?= htmlspecialchars($row["Prenom"]) ?> <?= htmlspecialchars($row["Nom"]) ?> (<?= htmlspecialchars($row["Code"]) ?>)" readonly>
                            </div>
                            <div class="form-group">
                                <label>Subject</label>
                                <input type="text" value="<?= htmlspecialchars($row["NomM"]) ?>" readonly>
                            </div>
                            <div class="form-group">
                                <label>Current Grade</label>
                                <input type="text" value="<?= number_format($row["Note"],2) ?> / 20" readonly>
                            </div>
                            <div class="form-group">
                                <label>New Grade (0-20)</label>
                                <input type="number" name="new_note" step="0.01" min="0" max="20" value="<?= $row["Note"] ?>" required>
                            </div>
                            <input type="hidden" name="code" value="<?= $row["Code"] ?>">
                            <input type="hidden" name="codeM" value="<?= $row["CodeM"] ?>">
                            <button type="submit" name="edit" class="save-btn">💾 Update Grade</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
            <div class="empty-state">
                <p>📭 No results found in the database.</p>
                <p><a href="#addModal">+ Add your first result</a></p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!--  the add Modal  -->
<div id="addModal" class="modal-overlay">
    <div class="modal-window">
        <a href="#" class="modal-close">&times;</a>
        <h3>➕ Add New Result</h3>

        <?php if(!empty($errors)): ?>
            <div class="error-box">
                <?php foreach($errors as $e): ?>
                    <p>⚠️ <?= htmlspecialchars($e) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Student Code</label>
                <input type="text" name="student_code" placeholder="E001" value="<?= isset($_POST['student_code']) ? htmlspecialchars($_POST['student_code']) : '' ?>" required>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>First Name</label>
                    <input type="text" name="first_name" placeholder="John" value="<?= isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : '' ?>" required>
                </div>
                <div class="form-group">
                    <label>Last Name</label>
                    <input type="text" name="last_name" placeholder="Smith" value="<?= isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : '' ?>" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>📅 Year</label>
                    <select name="annee" required>
                        <option value="">Select Year</option>
                        <option value="1">1st Year</option>
                        <option value="2">2nd Year</option>
                        <option value="3">3rd Year</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>🎓 Major</label>
                    <select name="filiere" required>
                        <option value="">Select Filiere</option>
                        <option value="Experimental Sciences">🔬 Experimental Sciences</option>
                        <option value="Mathematics">📐 Mathematics</option>
                        <option value="Technical Mathematics">⚙️ Technical Mathematics</option>
                        <option value="Foreign Languages">🌍 Foreign Languages</option>
                        <option value="Literature and Philosophy">📚 Literature and Philosophy</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>📚 Subject</label>
                    <select name="subject" required>
                        <option value="">Select Subject</option>
                        <option value="Mathematics">📐 Mathematics</option>
                        <option value="Physics">⚡ Physics</option>
                        <option value="Natural Sciences">🔬 Natural Sciences</option>
                        <option value="Arabic">📖 Arabic</option>
                        <option value="English">🇬🇧 English</option>
                        <option value="French">🇫🇷 French</option>
                        <option value="History">🏛️ History</option>
                        <option value="Geography">🌍 Geography</option>
                        <option value="Philosophy">💭 Philosophy</option>
                        <option value="sports">⚽ Sports</option>
                        <option value="islamic education">🕌 Islamic Education</option>
                        <option value="Engineering">🔧 Engineering</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>⭐ Grade (0-20)</label>
                    <input type="number" name="note" step="0.01" min="0" max="20" placeholder="15.50" value="<?= isset($_POST['note']) ? htmlspecialchars($_POST['note']) : '' ?>" required>
                </div>
            </div>

            <button type="submit" name="add" class="save-btn">💾 Save Result</button>
        </form>
    </div>
</div>

<footer class="footer">
    <p>© 2026 SGM | All rights reserved | Students Grades Management System</p>
    <button class="up-button"><a href="#home"><i class="fa-solid fa-circle-arrow-up"></i></a></button>
</footer>

</body>
</html>    