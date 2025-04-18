<?php
session_start();
include 'includes/db.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Get user information
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['name'];

// Handle work submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_work'])) {
    $title = $_POST["title"];
    $content = $_POST["content"];

    $stmt = $conn->prepare("INSERT INTO submissions (user_id, title, content) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $user_id, $title, $content);

    if ($stmt->execute()) {
        $message = "Work submitted successfully!";
    } else {
        $message = "Error: " . $stmt->error;
    }
}

// Get all submissions by the logged-in user
$submitted_works = [];
$sql = "SELECT * FROM submissions WHERE user_id = $user_id";
$result = $conn->query($sql);
while ($row = $result->fetch_assoc()) {
    $submitted_works[] = $row;
}

// Get all works to review (those submitted by others)
$works_to_review = [];
$sql = "SELECT * FROM submissions WHERE user_id != $user_id";
$result = $conn->query($sql);
while ($row = $result->fetch_assoc()) {
    $works_to_review[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Peer Review System</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">

    <div class="max-w-4xl mx-auto my-10 bg-white p-6 rounded shadow-md">
        <h1 class="text-3xl font-semibold text-center mb-8">Welcome, <?= $user_name ?>!</h1>

        <!-- Message -->
        <?php if (isset($message)): ?>
            <div class="bg-green-100 text-green-700 p-3 rounded mb-6">
                <?= $message ?>
            </div>
        <?php endif; ?>

        <!-- Submit New Work -->
        <h2 class="text-2xl mb-4">Submit Your Work</h2>
        <form method="POST" class="mb-6">
            <input type="text" name="title" placeholder="Work Title" class="w-full p-3 mb-4 border rounded" required />
            <textarea name="content" placeholder="Write your work here..." class="w-full p-3 mb-4 border rounded" rows="6" required></textarea>
            <button type="submit" name="submit_work" class="bg-blue-500 text-white p-3 rounded">Submit Work</button>
        </form>

        <!-- Your Submitted Works -->
        <h2 class="text-2xl mb-4">Your Submitted Works</h2>
        <div class="space-y-4">
            <?php if (count($submitted_works) > 0): ?>
                <?php foreach ($submitted_works as $work): ?>
                    <div class="p-4 border rounded bg-gray-50">
                        <h3 class="font-semibold"><?= htmlspecialchars($work['title']) ?></h3>
                        <p><?= nl2br(htmlspecialchars($work['content'])) ?></p>
                        <p class="text-sm text-gray-500">Submitted on: <?= $work['created_at'] ?></p>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No work submitted yet.</p>
            <?php endif; ?>
        </div>

        <!-- Works to Review -->
        <h2 class="text-2xl mb-4 mt-8">Works to Review</h2>
        <div class="space-y-4">
            <?php if (count($works_to_review) > 0): ?>
                <?php foreach ($works_to_review as $work): ?>
                    <div class="p-4 border rounded bg-gray-50">
                        <h3 class="font-semibold"><?= htmlspecialchars($work['title']) ?></h3>
                        <p><?= nl2br(htmlspecialchars($work['content'])) ?></p>
                        <p class="text-sm text-gray-500">Submitted by: <?= $work['user_id'] ?> on <?= $work['created_at'] ?></p>
                        <a href="review.php?work_id=<?= $work['id'] ?>" class="text-blue-600">Review this work</a>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No works available for review at the moment.</p>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>
