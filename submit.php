<?php
require_once '../config/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $user_id = $_SESSION['user_id'];

    if (empty($title) || empty($description)) {
        $error = 'Please fill in all fields';
    } else {
        $file_path = null;
        
        // Handle file upload
        if (isset($_FILES['assignment_file']) && $_FILES['assignment_file']['error'] === UPLOAD_ERR_OK) {
            $file_info = pathinfo($_FILES['assignment_file']['name']);
            $file_extension = strtolower($file_info['extension']);
            $allowed_extensions = ['pdf', 'doc', 'docx', 'txt'];

            if (!in_array($file_extension, $allowed_extensions)) {
                $error = 'Invalid file type. Allowed types: PDF, DOC, DOCX, TXT';
            } else {
                $upload_dir = '../uploads/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }

                $file_name = uniqid() . '_' . $file_info['basename'];
                $file_path = 'uploads/' . $file_name;

                if (move_uploaded_file($_FILES['assignment_file']['tmp_name'], '../' . $file_path)) {
                    // File uploaded successfully
                } else {
                    $error = 'Failed to upload file';
                }
            }
        }

        if (empty($error)) {
            $conn = getDBConnection();
            $stmt = $conn->prepare("INSERT INTO assignments (title, description, user_id, file_path) VALUES (?, ?, ?, ?)");
            
            try {
                $stmt->execute([$title, $description, $user_id, $file_path]);
                $success = 'Assignment submitted successfully!';
            } catch (PDOException $e) {
                $error = 'Failed to submit assignment. Please try again.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Assignment - Peer Review System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <header>
        <nav>
            <div class="logo">Peer Review System</div>
            <ul>
                <li><a href="../index.php">Dashboard</a></li>
                <li><a href="submit-assignment.php">Submit Assignment</a></li>
                <li><a href="my-reviews.php">My Reviews</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <div class="container">
            <h1>Submit Assignment</h1>

            <?php if ($error): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <form method="POST" action="" enctype="multipart/form-data" class="submit-form">
                <div class="form-group">
                    <label for="title">Assignment Title</label>
                    <input type="text" id="title" name="title" required>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="6" required></textarea>
                </div>

                <div class="form-group">
                    <label for="assignment_file">Upload File (Optional)</label>
                    <input type="file" id="assignment_file" name="assignment_file">
                    <small class="file-info">Allowed file types: PDF, DOC, DOCX, TXT (Max size: 5MB)</small>
                </div>

                <button type="submit" class="btn">Submit Assignment</button>
            </form>
        </div>
    </main>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> Peer Review System. All rights reserved.</p>
    </footer>

    <style>
        .container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 2rem;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .submit-form {
            max-width: 100%;
        }

        textarea {
            resize: vertical;
            min-height: 120px;
        }

        .file-info {
            display: block;
            color: #666;
            margin-top: 0.5rem;
            font-size: 0.9rem;
        }

        input[type="file"] {
            padding: 0.5rem 0;
        }

        .success-message {
            background-color: #51cf66;
            color: #fff;
            padding: 0.75rem;
            border-radius: 4px;
            margin-bottom: 1rem;
        }
    </style>
</body>
</html>