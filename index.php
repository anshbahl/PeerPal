<?php
session_start();
include 'includes/db.php';

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['signup'])) {
        // Handle sign-up (registration)
        $name = $_POST["name"];
        $email = $_POST["email"];
        $password = password_hash($_POST["password"], PASSWORD_DEFAULT);  // Hash the password

        // Prepare the SQL statement to prevent SQL injection
        $stmt = $conn->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $name, $email, $password);  // 'sss' means 3 strings

        if ($stmt->execute()) {
            $message = "Registration successful! <a href='#' class='text-blue-600 underline' onclick='toggleForm()'>Login here</a>";
        } else {
            $message = "Error: " . $stmt->error;
        }
    } elseif (isset($_POST['login'])) {
        // Handle login
        $email = $_POST["email"];
        $password = $_POST["password"];

        // Query the database to fetch the user with the given email
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);  // 's' for string
        $stmt->execute();
        $result = $stmt->get_result();

        // If user exists, check the password
        if ($row = $result->fetch_assoc()) {
            if (password_verify($password, $row['password'])) {
                // Password matches, set session variables
                $_SESSION['user_id'] = $row['id'];  // Save the user id in session
                $_SESSION['name'] = $row['name'];  // Save the user name in session
                header("Location: dashboard.php");  // Redirect to dashboard
                exit();
            } else {
                $message = "❌ Incorrect password.";
            }
        } else {
            $message = "❌ No account found with this email.";
        }
    }
}
?>

<!-- HTML Page: Login/Sign-up -->
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login / Sign-Up</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    /* Initially hide the sign-up form */
    .signup-form { display: none; }
  </style>
</head>
<body class="bg-gray-100 flex items-center justify-center h-screen">
  <div class="bg-white p-6 rounded shadow-md w-96">
    <h2 class="text-2xl font-semibold mb-4 text-center" id="form-title">Login</h2>

    <!-- Error Message -->
    <?php if ($message): ?>
      <div class="bg-red-100 text-red-800 text-sm p-2 rounded mb-4">
        <?= $message ?>
      </div>
    <?php endif; ?>

    <!-- Login Form -->
    <form method="POST" id="login-form">
      <input type="email" name="email" placeholder="Email" required class="mb-3 w-full px-3 py-2 border rounded" />
      <input type="password" name="password" placeholder="Password" required class="mb-3 w-full px-3 py-2 border rounded" />
      <button type="submit" name="login" class="bg-blue-500 text-white w-full px-3 py-2 rounded">Login</button>
    </form>

    <!-- Sign-Up Form (Initially hidden) -->
    <form method="POST" id="signup-form" class="signup-form">
      <input type="text" name="name" placeholder="Full Name" required class="mb-3 w-full px-3 py-2 border rounded" />
      <input type="email" name="email" placeholder="Email" required class="mb-3 w-full px-3 py-2 border rounded" />
      <input type="password" name="password" placeholder="Password" required class="mb-3 w-full px-3 py-2 border rounded" />
      <button type="submit" name="signup" class="bg-green-500 text-white w-full px-3 py-2 rounded">Sign Up</button>
    </form>

    <!-- Toggle Links -->
    <p class="mt-4 text-sm text-center">
      <span id="toggle-text" class="text-blue-600 underline cursor-pointer" onclick="toggleForm()">Don't have an account? Sign Up</span>
    </p>
    <p class="mt-4 text-sm text-center">
      <span id="toggle-text-login" class="text-blue-600 underline cursor-pointer hidden" onclick="toggleForm()">Already have an account? Login</span>
    </p>
  </div>

  <script>
    function toggleForm() {
      var loginForm = document.getElementById("login-form");
      var signupForm = document.getElementById("signup-form");
      var formTitle = document.getElementById("form-title");
      var toggleText = document.getElementById("toggle-text");
      var toggleTextLogin = document.getElementById("toggle-text-login");

      if (loginForm.style.display === "none") {
        loginForm.style.display = "block";
        signupForm.style.display = "none";
        formTitle.innerHTML = "Login";
        toggleTextLogin.style.display = "block";
        toggleText.style.display = "none";
      } else {
        loginForm.style.display = "none";
        signupForm.style.display = "block";
        formTitle.innerHTML = "Sign Up";
        toggleTextLogin.style.display = "none";
        toggleText.style.display = "block";
      }
    }
  </script>
</body>
</html>
