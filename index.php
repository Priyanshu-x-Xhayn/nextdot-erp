<?php
session_start();
// Check if user is already logged in
if(isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}
include ('backend/connect.php');

$msg = '';

if(isset($_POST['login'])){
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    // First try to authenticate against the employee table with the new fields
    // Changed system_role to role to match your database structure
   // First try to authenticate against the employee table with the new fields
$stmt = $conn->prepare("SELECT id, name, password, role, depart, Image FROM employee WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        // Verify password (using password_verify for hashed passwords)
        if (password_verify($password, $user['password'])) {
          // Password is correct, start a new session
          $_SESSION['user_id'] = $user['id'];
          $_SESSION['username'] = $username;
          $_SESSION['name'] = $user['name'];
          $_SESSION['role'] = $user['role'] ?? 'User'; // Default to User if not set
          
          // Get department name from the ID
          $dept_id = $user['depart'];
          $dept_query = "SELECT department_name FROM departments WHERE id = ?";
          $dept_stmt = $conn->prepare($dept_query);
          $dept_stmt->bind_param("i", $dept_id);
          $dept_stmt->execute();
          $dept_result = $dept_stmt->get_result();
          
          if($dept_result && $dept_result->num_rows > 0) {
              $dept_row = $dept_result->fetch_assoc();
              $_SESSION['department'] = $dept_row['department_name'];
          } else {
              $_SESSION['department'] = 'General'; // Default if not found
          }
          
          $_SESSION['user_type'] = 'employee'; // Track which table the user is from
          $_SESSION['user_image'] = $user['Image']; // Store the image filename in session
          
          // Redirect to dashboard
          header("Location: dashboard.php");
          exit();
      }
      else {
            $msg = '<div class="error">Invalid password</div>';
        }
    } else {
        // If not found in employee table, try the original users table as fallback
        $sql = "SELECT * FROM users WHERE email = ? AND password = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $username, $password);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Set session variables for legacy user
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['email'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['role'] = 'Admin'; // Default legacy users to Admin
            $_SESSION['department'] = $user['postion'] ?? 'General';
            $_SESSION['user_type'] = 'legacy_user';
            // After successful login, add this code
$_SESSION['user_image'] = $user_row['Image']; 
            
            $msg = 'Login successful';
            header("Location: dashboard.php");
            exit();
        } else {
            $msg = '<div class="error">Invalid username or password</div>';
        }
    }
}

// Handle successful login message from registration
if(isset($_GET['registered']) && $_GET['registered'] == 'success') {
    $msg = '<div class="success">Registration successful! You can now login.</div>';
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Employee Management System</title>
    <!-- Add jQuery for the animation -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>

<style>
  /* Import fonts */
@import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&family=Orbitron:wght@400;500;600;700&display=swap');

/* Base styles */
body {
  margin: 0;
  padding: 0;
  font-family: 'Montserrat', sans-serif;
  background-color: #f0f2f5;
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 100vh;
  background: linear-gradient(135deg, #0a4c95 0%, #072e63 50%, #2389da 100%);
  overflow-x: hidden;
}

/* Animated background */
body::before {
  content: '';
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: url("data:image/svg+xml,%3Csvg width='100' height='100' viewBox='0 0 100 100' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M11 18c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm48 25c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm-43-7c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm63 31c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM34 90c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm56-76c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM12 86c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm28-65c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm23-11c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-6 60c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm29 22c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zM32 63c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm57-13c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-9-21c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM60 91c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM35 41c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM12 60c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2z' fill='%23072e63' fill-opacity='0.05' fill-rule='evenodd'/%3E%3C/svg%3E");
  pointer-events: none;
  z-index: -1;
}

/* Login container */
.login-page {
  width: 100%;
  max-width: 480px;
  padding: 20px;
  margin: 30px auto;
  position: relative;
  z-index: 1;
}

/* Glow effect */
.login-page::before {
  content: '';
  position: absolute;
  top: -5px;
  left: -5px;
  right: -5px;
  bottom: -5px;
  background: linear-gradient(45deg, #2389da, #072e63, #2389da);
  border-radius: 20px;
  z-index: -1;
  filter: blur(15px);
  opacity: 0.7;
  animation: glowing 10s linear infinite;
}

@keyframes glowing {
  0% { filter: blur(15px) hue-rotate(0deg); }
  100% { filter: blur(15px) hue-rotate(360deg); }
}

/* Form container */
.form {
  background: rgba(7, 46, 99, 0.85);
  backdrop-filter: blur(10px);
  border-radius: 15px;
  padding: 40px 30px;
  box-shadow: 0 15px 25px rgba(0, 0, 0, 0.6);
  border: 1px solid rgba(255, 255, 255, 0.1);
  overflow: hidden;
  position: relative;
  z-index: 2;
}

/* Form title */
.form::before {
  content: "Nextdot";
  position: absolute;
  top: 15px;
  left: 0;
  width: 100%;
  text-align: center;
  font-family: 'Orbitron', sans-serif;
  font-size: 24px;
  letter-spacing: 4px;
  color: #2389da;
  font-weight: 700;
  margin-left : 12px;
}

/* Form fields */
form {
  margin-top: 15px;
}

input {
  width: 90%;
  padding: 15px 20px;
  margin-bottom: 25px;
  background: rgba(255, 255, 255, 0.05);
  border: none;
  outline: none;
  border-radius: 10px;
  font-size: 16px;
  letter-spacing: 1px;
  color: #ffffff;
  border: 1px solid rgba(255, 255, 255, 0.1);
  box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
  transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
}

input::placeholder {
  color: rgba(255, 255, 255, 0.5);
}

input:focus {
  border-color: #2389da;
  box-shadow: 0 0 15px rgba(248, 195, 0, 0.4);
}

/* Button styling */
button {
  position: relative;
  width: 100%;
  padding: 15px 0;
  background: #072e63;
  border: none;
  border-radius: 10px;
  color: #ffffff;
  font-size: 16px;
  font-weight: 600;
  letter-spacing: 1.5px;
  text-transform: uppercase;
  cursor: pointer;
  overflow: hidden;
  margin-bottom: 20px;
  box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
  transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
  border: 1px solid rgba(248, 195, 0, 0.3);
  font-family: 'Montserrat', sans-serif;
}

button:hover {
  background: #051e42;
  box-shadow: 0 8px 20px rgba(0, 0, 0, 0.4);
  transform: translateY(-2px);
}

button::before {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
  transition: all 0.6s;
}

button:hover::before {
  left: 100%;
}

button:active {
  transform: translateY(1px);
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
}

/* Message */
.message {
  margin: 15px 0 0;
  color: #cccccc;
  font-size: 14px;
  text-align: center;
}

.message a {
  color: #2389da;
  text-decoration: none;
  font-weight: 600;
  transition: all 0.3s ease;
}

.message a:hover {
  text-shadow: 0 0 8px rgba(248, 195, 0, 0.6);
}

/* Form toggle */
.register-form {
  display: none;
}

/* Error messages */
.login-page .error {
  background-color: rgba(255, 62, 62, 0.1);
  color: #ff3e3e;
  padding: 10px 15px;
  margin-bottom: 20px;
  border-radius: 8px;
  font-size: 14px;
  text-align: center;
  border-left: 3px solid #ff3e3e;
}

.login-page .success {
  background-color: rgba(0, 210, 106, 0.1);
  color: #00d26a;
  padding: 10px 15px;
  margin-bottom: 20px;
  border-radius: 8px;
  font-size: 14px;
  text-align: center;
  border-left: 3px solid #00d26a;
}

/* Responsive design */
@media (max-width: 480px) {
  .login-page {
    padding: 10px;
  }
  
  .form {
    padding: 30px 20px;
  }
  
  input {
    padding: 12px 15px;
    margin-bottom: 20px;
  }
  
  button {
    padding: 12px 0;
  }
}

/* Animation for form switching */
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(20px); }
  to { opacity: 1; transform: translateY(0); }
}

.login-form, .register-form {
  animation: fadeIn 0.5s ease-in-out;
}
</style>

<body>
    <div class="login-page">
        <?php echo $msg; ?>
        <div class="form">
            <form class="login-form" method="post">
                <input type="text" placeholder="Username" name="username" required />
                <input type="password" placeholder="Password" name="password" required />
                <button type="submit" name="login">Login</button>
                <p class="message">Not registered? Contact your administrator to create an account.</p>
            </form>
        </div>
    </div>
</body>

<script>
    // This animation code is only needed if you have registration form toggle
    $('.message a').click(function() {
        $('form').animate({
            height: "toggle",
            opacity: "toggle"
        }, "slow");
    });
</script>

</html>