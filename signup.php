<?php
// Start the session
session_start();

// Include database connection file
include('db_connection.php');

// Initialize session variables if not set
if (!isset($_SESSION['attempts'])) {
    $_SESSION['attempts'] = 0;
    $_SESSION['last_attempt_time'] = time();
}

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Calculate the time difference since the last attempt
    $time_since_last_attempt = time() - $_SESSION['last_attempt_time'];

    // Check if the user has exceeded the maximum attempts
    if ($_SESSION['attempts'] >= 3) {
        // If less than 30 seconds have passed since the last attempt
        if ($time_since_last_attempt < 30) {
            $remaining_time = 30 - $time_since_last_attempt;
            $error = "Too many failed attempts. Please try again in " . $remaining_time . " seconds.";
        } else {
            // Reset the attempt counter after 30 seconds
            $_SESSION['attempts'] = 0;
        }
    }

    if ($_SESSION['attempts'] < 3) {
        // Get form inputs
        $username = isset($_POST['username']) ? $_POST['username'] : '';
        $password = isset($_POST['password']) ? $_POST['password'] : '';
        $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
        $role = isset($_POST['role']) ? $_POST['role'] : 'employee'; // Default role is 'employee'

        // Check if password and confirm password match
        if ($password !== $confirm_password) {
            $error = "Passwords do not match.";
        } else {
            // Password validation (at least one special character, one number, no spaces)
            if (!preg_match('/[A-Za-z]/', $password)) {
                $error = "Password must contain at least one letter.";
            } elseif (!preg_match('/[0-9]/', $password)) {
                $error = "Password must contain at least one number.";
            } elseif (!preg_match('/[\W_]/', $password)) {
                $error = "Password must contain at least one special character.";
            } elseif (preg_match('/\s/', $password)) {
                $error = "Password cannot contain spaces.";
            } else {
                // Check if the username already exists in the database
                $stmt = $conn->prepare("SELECT user_name FROM tb_admin WHERE user_name = ?");
                if (!$stmt) {
                    die("Prepare failed: " . $conn->error);
                }

                $stmt->bind_param('s', $username);
                $stmt->execute();
                $stmt->store_result();

                // If the username already exists, show an error message
                if ($stmt->num_rows > 0) {
                    $error = "Username already taken. Please choose a different one.";
                } else {
                    // Hash the password
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                    // Prepare SQL query to insert new user with role
                    $stmt = $conn->prepare("INSERT INTO tb_admin (user_name, password, role) VALUES (?, ?, ?)");
                    if (!$stmt) {
                        die("Prepare failed: " . $conn->error);
                    }

                    $stmt->bind_param('sss', $username, $hashed_password, $role); // Bind role parameter
                    if ($stmt->execute()) {
                        // Successful registration
                        $_SESSION['attempts'] = 0;
                        $_SESSION['loggedin'] = true;
                        $_SESSION['username'] = $username;

                        // Set a success message in session
                        $_SESSION['success_message'] = "Registration successful! You can now log in.";

                        // Redirect to login page after successful registration
                        echo "<script>alert('Registration successful! You can now log in.'); window.location.href = 'login.php';</script>";
                        exit();
                    } else {
                        // Registration failed
                        $error = "An error occurred. Please try again.";
                    }

                    $stmt->close();
                }

                $stmt->close();
            }
        }
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - GSL25 Steel Trading</title>
    <link rel="icon" href="img/GSL25_transparent 2.png">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            margin: 0;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Poppins', Arial, sans-serif;
            background: url('img/steelbg.jpg') no-repeat center center/cover;
        }

        .container {
            width: 90%;
            max-width: 1200px;
            height: auto;
            display: flex;
            flex-direction: row-reverse;
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.2);
            border-radius: 15px;
            overflow: hidden;
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(10px);
        }

        .left-section, .right-section {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 40px;
            flex-direction: column;
        }

        .left-section {
            background: white;
            text-align: center;
        }

        .right-section {
            text-align: center;
            color: white;
            background: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.8)), 
                        url('img/LOGIN1-removebg.png') no-repeat center center;
            background-size: cover;
            padding: 50px;
        }

        .right-section h1 {
            font-size: 2.5rem;
            font-weight: bold;
            text-shadow: 3px 3px 6px rgba(0, 0, 0, 0.7);
        }

        .signupBody {
            width: 100%;
            max-width: 400px;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
            background: rgba(255, 255, 255, 0.95);
            color: #333;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .signupBody h2 {
            text-align: center;
            font-weight: 600;
            margin-bottom: 20px;
            color: #007BFF;
        }

        .signupBody label {
            display: block;
            font-size: 1rem;
            margin-bottom: 5px;
            font-weight: 500;
        }

        .signupBody input, .signupBody select {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 5px;
            transition: box-shadow 0.3s ease;
        }

        .signupBody input:focus, .signupBody select:focus {
            outline: none;
            box-shadow: 0 0 5px #007BFF;
            border-color: #007BFF;
        }

        .signupBody button {
            width: 100%;
            padding: 12px;
            background-color: #007BFF;
            color: white;
            border: none;
            border-radius: 5px;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.2s ease;
        }

        .signupBody button:hover {
            background-color: #0056b3;
            transform: scale(1.02);
        }

        .signup-link {
            text-align: center;
            margin-top: 10px;
        }

        .signup-link a {
            color: #007BFF;
            text-decoration: none;
        }

        .signup-link a:hover {
            text-decoration: underline;
        }

        @media (max-width: 768px) {
            .container {
                flex-direction: column;
                width: 95%;
                height: auto;
                margin: 20px 0;
                align-items: center;
                justify-content: center;
            }

            .right-section {
                padding: 30px;
                background-size: cover;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
            }

            .right-section h1 {
                font-size: 2rem;
                margin-bottom: 10px;
            }

            .right-section p {
                font-size: 1rem;
                padding: 0 10px;
            }

            .left-section, .right-section {
                width: 100%;
                padding: 30px 20px;
            }

            .signupBody {
                max-width: 90%;
                padding: 20px;
                box-shadow: none;
                border-radius: 10px;
                display: flex;
                flex-direction: column;
                align-items: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="left-section">
            <div class="signupBody">
                <h2>Sign Up</h2>
                <form action="signup.php" method="post">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required>
                    
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                    
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>

                    <label for="role">Role</label>
                    <select id="role" name="role">
                        <option value="employee" selected>Employee</option>
                        <option value="admin">Admin</option>
                    </select>
                    
                    <button type="submit">Sign Up</button>
                </form>
                <div class="signup-link">
                    <p>Already have an account? <a href="login.php">Login here</a></p>
                </div>
            </div>
        </div>
        <div class="right-section">
            <h1>Welcome to GSL25 Steel Trading</h1>
            <p>Your trusted source for <strong>quality steel products</strong> and <strong>construction supplies</strong>.</p>
        </div>
    </div>
</body>
</html>
