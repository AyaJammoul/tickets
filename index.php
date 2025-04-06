<?php
ob_start();
session_start(); // Start the session

require_once './src/Database.php'; // Include the database connection file
$db = Database::getInstance();

// Clear previous error messages
if (isset($_SESSION['error'])) {
    $err = $_SESSION['error'];
    unset($_SESSION['error']); // Clear the error after displaying it
} else {
    $err = ''; // Initialize an error variable
}

if (isset($_POST['submit'])) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Input validation
    if (empty($email)) {
        $_SESSION['error'] = 'Please enter an email address';
        header('Location: ./sign_in');
        exit();
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = 'Please enter a valid email address';
        header('Location: ./sign_in');
        exit();
    } elseif (empty($password)) {
        $_SESSION['error'] = 'Please enter your password';
        header('Location: ./sign_in');
        exit();
    } else {
        // Function to check credentials in all tables
        function checkCredentials($db, $email, $password) {
            $tables = ['admin', 'users', 'manager'];
            
            foreach ($tables as $table) {
                $stmt = $db->prepare("SELECT id, email, password, role, branch_id FROM $table WHERE email = ?");
                if ($stmt === false) {
                    return 'Database error: Unable to prepare statement for ' . $table;
                }
                
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $res = $stmt->get_result();
                
                if ($res->num_rows > 0) {
                    $user = $res->fetch_object();
                    if (password_verify($password, $user->password)) {
                        return [
                            'id' => $user->id,
                            'email' => $user->email, // Include email here
                            'role' => $user->role,
                            'branch_id' => $user->branch_id,
                            'table' => $table
                        ];
                    } else {
                        return 'Invalid password';
                    }
                }
                $stmt->close();
            }
            return 'No user found';
        }

        // Check credentials
        $user = checkCredentials($db, $email, $password);
        if (is_array($user)) {
            $_SESSION['logged-in'] = true;
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['branch_id'] = $user['branch_id'];
            $_SESSION['email'] = $user['email']; // Store email

            // Redirect based on role
            if ($user['role'] === 'admin') {
                header('Location: ./mydash-board');
            } elseif ($user['role'] === 'member') {
                header('Location: ./view-open-tickets');
            } elseif ($user['role'] === 'manager') {
                header('Location: ./all-ticket-manager');
            } else {
                $_SESSION['error'] = 'Unknown user role';
                header('Location: ./sign_in');
            }
            exit();
        } else {
            $_SESSION['error'] = $user;
            header('Location: ./sign_in');
            exit();
        }
    }
}
ob_end_flush();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>Helpdesk - Login</title>

    <!-- Custom fonts for this template-->
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">

    <!-- Custom styles for this template-->
    <link href="css/sb-admin.css" rel="stylesheet">
</head>
<body class="bg-dark"> 
    <div class="container"  style="margin-top:8%">
        <div class="card card-login mx-auto mt-5">
            <div class="card-header text-center font-weight-bold">
                <img src="BiTS.Logo.png"  width="180" height="50">
                <br>
                <h4 style="margin-top:4%">Login</h4></div>
            <div class="card-body">
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                    <div class="form-group">
                        <label for="inputEmail">Email address</label>
                        <input type="text" name="email" class="form-control" placeholder="Email address" autofocus="autofocus">
                    </div>
                    <div class="form-group">
                        <label for="inputPassword">Password</label>
                        <input type="password" name="password" class="form-control" placeholder="Password">
                    </div>
                    <div class="form-group">
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" value="remember-me">
                                Remember Password
                            </label>
                        </div>
                    </div>
                    <button type="submit" name="submit" class="btn btn-primary btn-block">Login</button>
                </form>

                <!-- Error message display -->
                <?php if (!empty($err)) : ?>
                <div class="alert alert-danger text-center mt-3" role="alert"><strong>Failed! </strong> <?php echo htmlspecialchars($err); ?></div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Bootstrap core JavaScript-->
    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

    <!-- Core plugin JavaScript-->
    <script src="vendor/jquery-easing/jquery.easing.min.js"></script>
</body>
</html>
