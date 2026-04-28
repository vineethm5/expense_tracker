<?php
include 'db.php';
session_start();
$error = "";
$success = "";

// Handle Signup Logic
if (isset($_POST['signup'])) {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    try 
    {
        $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $username, $email, $password);
        
        if ($stmt->execute()) {
            $success = "Account created! You can now log in.";
        } 
    }
    catch (mysqli_sql_exception $e) {
    // Check if the error code is 1062 (Duplicate Entry)
    if ($e->getCode() === 1062) {
        $error = "This email is already registered. Please try logging in.";
    } else {
        $error = "Something went wrong. Please try again later.";
    }
    }
}

// Handle Login Logic
if (isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, password FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            header("Location: index.php");
            exit();
        } else {
            $error = "Invalid password.";
        }
    } else {
        $error = "No user found with that email.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to TrackIt</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .form-container { transition: all 0.5s ease-in-out; }
        .hidden-form { display: none; }
    </style>
</head>
<body class="bg-gradient-to-br from-indigo-500 to-purple-600 min-h-screen flex items-center justify-center p-4">

    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-4xl overflow-hidden flex flex-col md:flex-row">
        <div class="md:w-1/2 bg-indigo-600 p-12 text-white flex flex-col justify-center">
            <div class="flex items-center gap-2.5 group cursor-pointer">
            <div class="relative w-8 h-8 flex-shrink-0">
                <div class="absolute inset-1 bg-gradient-to-tr from-indigo-600 to-indigo-400 rounded-md rotate-3 group-hover:rotate-6 transition-transform duration-300 opacity-80"></div>
                <div class="relative w-8 h-8 bg-white rounded-md flex items-center justify-center border border-slate-200 shadow-sm">
                    <svg viewBox="0 0 24 24" fill="none" class="w-5 h-5" xmlns="http://www.w3.org/2000/svg">
                        <path d="M6 7h12v3H6z" fill="#4338ca"/>
                        <path d="M10 10h4v8h-4z" fill="#6366f1"/>
                    </svg>
                </div>
            </div>
            <div class="flex flex-col leading-none">
                <span class="text-lg font-black text-white tracking-tight uppercase">Track<span class="text-indigo-400">it</span></span>
                <span class="text-[7px] font-bold text-slate-500 uppercase tracking-[0.4em] mt-0.5">Track you</span>
            </div>
        </div>
        <br>
            <p class="text-indigo-100 text-lg">The world's most intuitive way to manage your personal finances and daily expenses.</p>
            <div class="mt-8 space-y-4">
                <div class="flex items-center space-x-3">
                    <span class="bg-indigo-500 p-2 rounded-lg">✔</span>
                    <span>Real-time spending charts</span>
                </div>
                <div class="flex items-center space-x-3">
                    <span class="bg-indigo-500 p-2 rounded-lg">✔</span>
                    <span>Categorized logging</span>
                </div>
            </div>
        </div>

        <div class="md:w-1/2 p-12">
            <?php if($error): ?>
                <div class="bg-red-50 text-red-500 p-3 rounded-xl mb-6 text-sm border border-red-100"><?php echo $error; ?></div>
            <?php endif; ?>
            <?php if($success): ?>
                <div class="bg-green-50 text-green-600 p-3 rounded-xl mb-6 text-sm border border-green-100"><?php echo $success; ?></div>
            <?php endif; ?>

            <div id="loginForm" class="form-container">
                <h2 class="text-2xl font-bold text-gray-800 mb-2">Sign In</h2>
                <p class="text-gray-500 mb-8">Enter your details to access your account</p>
                <form method="POST" class="space-y-4">
                    <input type="email" name="email" placeholder="Email" required class="w-full p-4 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition">
                    <input type="password" name="password" placeholder="Password" required class="w-full p-4 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition">
                    <button type="submit" name="login" class="w-full bg-indigo-600 text-white py-4 rounded-xl font-bold hover:bg-indigo-700 transition transform hover:scale-[1.01]">Login</button>
                </form>
                <p class="mt-8 text-center text-sm text-gray-600">
                    New to TrackIt? <button onclick="toggleForms()" class="text-indigo-600 font-bold hover:underline">Create an account</button>
                </p>
            </div>

            <div id="signupForm" class="form-container hidden-form">
                <h2 class="text-2xl font-bold text-gray-800 mb-2">Join Us</h2>
                <p class="text-gray-500 mb-8">Create your account in seconds</p>
                <form method="POST" class="space-y-4">
                    <input type="text" name="username" placeholder="Full Name" required class="w-full p-4 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition">
                    <input type="email" name="email" placeholder="Email" required class="w-full p-4 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition">
                    <input type="password" name="password" placeholder="Create Password" required class="w-full p-4 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition">
                    <button type="submit" name="signup" class="w-full bg-indigo-600 text-white py-4 rounded-xl font-bold hover:bg-indigo-700 transition transform hover:scale-[1.01]">Sign Up</button>
                </form>
                <p class="mt-8 text-center text-sm text-gray-600">
                    Already a member? <button onclick="toggleForms()" class="text-indigo-600 font-bold hover:underline">Log in</button>
                </p>
            </div>
        </div>
    </div>

    <script>
        function toggleForms() {
            const login = document.getElementById('loginForm');
            const signup = document.getElementById('signupForm');
            
            login.classList.toggle('hidden-form');
            signup.classList.toggle('hidden-form');
        }
    </script>
</body>
</html>