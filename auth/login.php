<?php
require_once '../config/database.php';

if (isset($_SESSION['user_id'])) {
    header("Location: ../pages/dashboard.php");
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $conn->real_escape_string($_POST['username']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, username, password, role, nama_lengkap FROM tb_users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        // Cek password
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
            
            header("Location: ../pages/dashboard.php");
            exit;
        } else {
            $error = 'Password salah!';
        }
    } else {
        $error = 'Username tidak ditemukan!';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Login - Sistem Keuangan CTP</title>
    <!-- Custom fonts for this template-->
    <link href="<?= base_url('assets/vendor/fontawesome-free/css/all.min.css') ?>" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <!-- Custom styles for this template-->
    <link href="<?= base_url('assets/css/sb-admin-2.min.css') ?>" rel="stylesheet">
    <style>
        body {
            background-color: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            font-family: 'Nunito', sans-serif;
        }
        .login-wrapper {
            width: 100%;
            padding: 20px;
            display: flex;
            justify-content: center;
        }
        .login-container {
            background: #fff;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            overflow: hidden;
            width: 1000px;
            max-width: 100%;
            display: flex;
            flex-wrap: wrap;
        }
        .login-left {
            background: linear-gradient(135deg, #1fa299 0%, #158b83 100%);
            color: white;
            padding: 60px 40px;
            flex: 1;
            min-width: 300px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            position: relative;
        }
        .login-left::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background-image: url('https://images.unsplash.com/photo-1454165804606-c3d57bc86b40?q=80&w=1000&auto=format&fit=crop');
            background-size: cover;
            background-position: center;
            opacity: 0.15;
            z-index: 0;
        }
        .login-left > * {
            z-index: 1;
        }
        .login-left .logo-icon {
            font-size: 48px;
            margin-bottom: 10px;
        }
        .login-left h2 {
            font-weight: 700;
            margin-bottom: 5px;
            font-size: 28px;
        }
        .login-left p.tagline {
            font-size: 14px;
            opacity: 0.8;
            margin-bottom: 40px;
        }
        .login-left h3 {
            font-weight: 700;
            margin-bottom: 10px;
            font-size: 24px;
        }
        .login-left p.desc {
            font-size: 14px;
            opacity: 0.9;
        }
        .login-right {
            flex: 1.2;
            min-width: 350px;
            padding: 60px 50px;
            position: relative;
            background: white;
        }
        .create-account-link {
            position: absolute;
            top: 25px;
            right: 35px;
            color: #adb5bd;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
        }
        .create-account-link:hover {
            color: #1fa299;
            text-decoration: none;
        }
        .login-heading {
            font-weight: 800;
            color: #212529;
            margin-bottom: 40px;
            text-align: center;
            font-size: 32px;
        }
        .input-group {
            margin-bottom: 20px;
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid #ced4da;
            transition: border-color 0.2s;
        }
        .input-group:focus-within {
            border-color: #1fa299;
            box-shadow: 0 0 0 0.2rem rgba(31, 162, 153, 0.25);
        }
        .input-group-text {
            background-color: transparent;
            border: none;
            color: #adb5bd;
            padding-right: 0;
        }
        .form-control {
            border: none;
            height: 48px;
            box-shadow: none !important;
            color: #495057;
        }
        .form-control:focus {
            background-color: transparent;
        }
        .options-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 13px;
            margin-bottom: 30px;
            color: #6c757d;
        }
        .options-row a {
            color: #adb5bd;
            text-decoration: none;
        }
        .options-row a:hover {
            color: #1fa299;
        }
        .custom-control-input:checked ~ .custom-control-label::before {
            border-color: #1fa299;
            background-color: #1fa299;
        }
        .btn-login-container {
            text-align: center;
        }
        .btn-login {
            background-color: #1fa299;
            border: none;
            border-radius: 8px;
            padding: 12px 50px;
            font-size: 16px;
            font-weight: 700;
            color: white;
            transition: all 0.3s;
            box-shadow: 0 4px 6px rgba(31, 162, 153, 0.3);
            display: inline-block;
        }
        .btn-login:hover {
            background-color: #158b83;
            box-shadow: 0 6px 12px rgba(31, 162, 153, 0.4);
            color: white;
        }
        .or-divider {
            display: flex;
            align-items: center;
            text-align: center;
            color: #adb5bd;
            margin: 30px 0;
            font-size: 13px;
        }
        .or-divider::before, .or-divider::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid #e9ecef;
        }
        .or-divider span {
            padding: 0 15px;
        }
        .social-icons {
            display: flex;
            justify-content: center;
            gap: 15px;
        }
        .social-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            color: white;
            text-decoration: none;
            font-size: 18px;
            transition: transform 0.2s, opacity 0.2s;
        }
        .social-icon:hover {
            transform: translateY(-3px);
            opacity: 0.9;
            color: white;
        }
        .icon-google { background-color: #db4437; }
        .icon-facebook { background-color: #4267B2; }
        .icon-twitter { background-color: #1DA1F2; }
        
        @media (max-width: 768px) {
            .login-left {
                padding: 40px 20px;
            }
            .login-right {
                padding: 40px 20px;
            }
            .create-account-link {
                position: static;
                display: block;
                text-align: right;
                margin-bottom: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <div class="login-container">
            <div class="login-left">
                <div class="logo-icon mb-3">
                    <img src="<?= base_url('assets/img/logo.png') ?>" alt="Logo" style="width: 120px; height: 120px; border-radius: 50%; border: 4px solid rgba(255,255,255,0.8); box-shadow: 0 4px 15px rgba(0,0,0,0.2); object-fit: cover; background: white;">
                </div>
                <h2>Sistem Keuangan</h2>
                <p class="tagline">UMKM Cahaya Tani Poleang</p>
                
                <h3>Hey! Welcome</h3>
                <p class="desc">Kelola keuangan UMKM Anda dengan mudah dan efisien.</p>
            </div>
            <div class="login-right">
                <a href="#" class="create-account-link">Create a new Account</a>
                
                <h1 class="login-heading">Log in</h1>
                
                <?php if($error): ?>
                    <div class="alert alert-danger" style="border-radius: 8px; font-size: 14px; text-align: center;"><?= $error ?></div>
                <?php endif; ?>
                
                <form class="user" method="POST" action="">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                        </div>
                        <input type="text" class="form-control" name="username" placeholder="Username" required>
                    </div>
                    
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        </div>
                        <input type="password" class="form-control" name="password" id="password" placeholder="Password" required>
                    </div>
                    
                    <div class="options-row">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="showPassword">
                            <label class="custom-control-label" for="showPassword">Show my Password</label>
                        </div>
                        <a href="#">Forget Password ?</a>
                    </div>
                    
                    <div class="btn-login-container">
                        <button type="submit" class="btn btn-login">
                            Log in
                        </button>
                    </div>
                </form>
                
                <div class="or-divider">
                    <span>Or with</span>
                </div>
                
                <div class="social-icons">
                    <a href="#" class="social-icon icon-google"><i class="fab fa-google-plus-g"></i></a>
                    <a href="#" class="social-icon icon-facebook"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="social-icon icon-twitter"><i class="fab fa-twitter"></i></a>
                </div>
                
                <div class="text-center mt-4">
                    <small class="text-muted">Gunakan username: admin | pass: password</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap core JavaScript-->
    <script src="<?= base_url('assets/vendor/jquery/jquery.min.js') ?>"></script>
    <script src="<?= base_url('assets/vendor/bootstrap/js/bootstrap.bundle.min.js') ?>"></script>
    <!-- Core plugin JavaScript-->
    <script src="<?= base_url('assets/vendor/jquery-easing/jquery.easing.min.js') ?>"></script>
    <!-- Custom scripts for all pages-->
    <script src="<?= base_url('assets/js/sb-admin-2.min.js') ?>"></script>
    <script>
        $(document).ready(function() {
            $('#showPassword').change(function() {
                var passwordInput = $('#password');
                if ($(this).is(':checked')) {
                    passwordInput.attr('type', 'text');
                } else {
                    passwordInput.attr('type', 'password');
                }
            });
        });
    </script>
</body>
</html>
