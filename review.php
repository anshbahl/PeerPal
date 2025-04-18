<?php
require_once '../config/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$error = '';
$success = '';
$assignment = null;

if (!isset($_GET['id'])) {
    header('Location: ../index.php');
    exit();
}

$assignment_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

$conn = getDBConnection();

// Check if the assignment exists and user hasn't reviewed it yet
$stmt = $conn->prepare("
    SELECT a.*, u.username 
    FROM assignments a 
    JOIN users u ON a.user_id = u.id 
    WHERE a.id = ? AND a.user_id != ? 
    AND NOT EXISTS (
        SELECT 1 FROM reviews r 
        WHERE r.assignment_id = a.id 
        AND r.reviewer_id = ?
    )
");
$stmt->execute([$assignment_id, $user_id, $user_id]);
$assignment = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$assignment) {
    header('Location: ../index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rating = $_POST['rating'] ?? '';
    $feedback = $_POST['feedback'] ?? '';
    $is_anonymous = isset($_POST['is_anonymous']) ? 1 : 0;

    if (empty($rating) || empty($feedback)) {
        $error = 'Please fill in all fields';
    } elseif ($rating < 1 || $rating > 5) {
        $error = 'Invalid rating';
    } else {
        $stmt = $conn->prepare("INSERT INTO reviews (assignment_id, reviewer_id, rating, feedback, is_anonymous) VALUES (?, ?, ?, ?, ?)");
        
        try {
            $stmt->execute([$assignment_id, $user_id, $rating, $feedback, $is_anonymous]);
            
            // Update assignment status
            $stmt = $conn->prepare("UPDATE assignments SET status = 'under_review' WHERE id = ?");
            $stmt->execute([$assignment_id]);
            
            $success = 'Review submitted successfully!';
        } catch (PDOException $e) {
            $error = 'Failed to submit review. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review Assignment - Peer Review System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
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
            <h1>Review Assignment</h1>

            <?php if ($error): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <div class="assignment-details">
                <h2><?php echo htmlspecialchars($assignment['title']); ?></h2>
                <p class="description"><?php echo nl2br(htmlspecialchars($assignment['description'])); ?></p>
                
                <?php if ($assignment['file_path']): ?>
                    <p class="attachment">
                        <a href="../<?php echo htmlspecialchars($assignment['file_path']); ?>" target="_blank" class="btn">
                            <i class="fas fa-download"></i> Download Attachment
                        </a>
                    </p>
                <?php endif; ?>
            </div>

            <form method="POST" action="" class="review-form">
                <div class="form-group">
                    <label>Rating</label>
                    <div class="rating">
                        <?php for ($i = 5; $i >= 1; $i--): ?>
                            <input type="radio" id="star<?php echo $i; ?>" name="rating" value="<?php echo $i; ?>" required>
                            <label for="star<?php echo $i; ?>"><i class="fas fa-star"></i></label>
                        <?php endfor; ?>
                    </div>
                </div>

                <div class="form-group">
                    <label for="feedback">Feedback</label>
                    <textarea id="feedback" name="feedback" rows="6" required 
                        placeholder="Provide constructive feedback about the assignment..."></textarea>
                </div>

                <div class="form-group checkbox-group">
                    <input type="checkbox" id="is_anonymous" name="is_anonymous" checked>
                    <label for="is_anonymous">Submit review anonymously</label>
                </div>

                <button type="submit" class="btn">Submit Review</button>
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

        .assignment-details {
            margin-bottom: 2rem;
            padding-bottom: 2rem;
            border-bottom: 1px solid #eee;
        }

        .description {
            margin: 1rem 0;
            line-height: 1.6;
            color: #666;
        }

        .attachment {
            margin-top: 1rem;
        }

        .rating {
            display: flex;
            flex-direction: row-reverse;
            justify-content: flex-end;
        }

        .rating input {
            display: none;
        }

        .rating label {
            cursor: pointer;
            padding: 0 0.2rem;
            font-size: 1.5rem;
            color: #ddd;
        }

        .rating label:hover,
        .rating label:hover ~ label,
        .rating input:checked ~ label {
            color: #ffd700;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .checkbox-group input[type="checkbox"] {
            width: auto;
        }

        textarea {
            resize: vertical;
            min-height: 120px;
        }
    </style>
</body>
</html>