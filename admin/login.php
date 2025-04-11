<?php
/**
 * 管理后台登录页面
 */
session_start();

// 如果已经登录且是管理员，直接跳转到后台首页
if (isset($_SESSION['user_id']) && isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'admin') {
    header("Location: index.php");
    exit();
}

require_once '../config/db.php'; // 如果文件在config目录
require_once '../includes/functions.php';

$error = '';
$success = '';

// 处理登录请求
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    
    if (empty($username) || empty($password)) {
        $error = '请输入用户名和密码';
    } else {
        // 查询用户信息
        $query = "SELECT id, username, password, role FROM users WHERE username = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // 验证密码
            if (password_verify($password, $user['password'])) {
                // 检查是否为管理员
                if ($user['role'] === 'admin') {
                    // 设置会话变量
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['user_role'] = $user['role'];
                    
                    // 跳转到管理后台首页
                    header("Location: index.php");
                    exit();
                } else {
                    $error = '您没有管理员权限，无法登录后台';
                }
            } else {
                $error = '密码错误';
            }
        } else {
            $error = '用户不存在';
        }
    }
}

// 检查数据库中是否存在默认管理员账号
$checkAdmin = "SELECT COUNT(*) as count FROM users WHERE username = 'admin' AND role = 'admin'";
$result = $conn->query($checkAdmin);
$adminExists = $result->fetch_assoc()['count'] > 0;

// 如果不存在，则创建默认管理员账号
if (!$adminExists) {
    $adminUsername = 'admin';
    $adminPassword = password_hash('123456', PASSWORD_DEFAULT);
    $adminEmail = 'admin@example.com';
    $adminRole = 'admin';
    
    $createAdmin = "INSERT INTO users (username, password, email, role, nickname, created_at) 
                    VALUES (?, ?, ?, ?, '管理员', NOW())";
    $stmt = $conn->prepare($createAdmin);
    $stmt->bind_param("ssss", $adminUsername, $adminPassword, $adminEmail, $adminRole);
    $stmt->execute();
    
    if ($stmt->affected_rows > 0) {
        $success = '默认管理员账号已创建，用户名：admin，密码：123456';
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理员登录 - 体育中心管理系统</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/font-awesome@4.7.0/css/font-awesome.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background: linear-gradient(135deg, #00356B, #00112B);
        }
        .login-container {
            width: 360px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        .login-header {
            background-color: #00356B;
            color: white;
            padding: 20px;
            text-align: center;
        }
        .login-header h1 {
            font-size: 24px;
            margin-bottom: 5px;
        }
        .login-header p {
            font-size: 14px;
            opacity: 0.8;
        }
        .login-body {
            padding: 30px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #333;
        }
        .form-control {
            width: 100%;
            padding: 12px 15px;
            font-size: 14px;
            border: 1px solid #ddd;
            border-radius: 4px;
            transition: border-color 0.3s;
        }
        .form-control:focus {
            border-color: #00356B;
            outline: none;
            box-shadow: 0 0 0 2px rgba(0, 53, 107, 0.1);
        }
        .btn {
            display: inline-block;
            padding: 12px 15px;
            font-size: 14px;
            font-weight: 500;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            transition: background-color 0.3s, transform 0.1s;
            text-align: center;
            width: 100%;
        }
        .btn-primary {
            background-color: #00356B;
            color: white;
        }
        .btn-primary:hover {
            background-color: #002548;
        }
        .btn-primary:active {
            transform: translateY(1px);
        }
        .alert {
            padding: 12px 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            font-size: 14px;
        }
        .alert-danger {
            background-color: #fef2f2;
            color: #991b1b;
            border-left: 4px solid #B91C1C;
        }
        .alert-success {
            background-color: #ecfdf5;
            color: #065f46;
            border-left: 4px solid #10b981;
        }
        .back-link {
            text-align: center;
            margin-top: 15px;
            font-size: 14px;
        }
        .back-link a {
            color: #00356B;
            text-decoration: none;
        }
        .back-link a:hover {
            text-decoration: underline;
        }
        .logo {
            font-size: 40px;
            margin-bottom: 10px;
            color: #fff;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="logo"><i class="fa fa-dumbbell"></i></div>
            <h1>体育中心管理系统</h1>
            <p>登录后台管理</p>
        </div>
        <div class="login-body">
            <?php if ($error): ?>
            <div class="alert alert-danger">
                <?php echo $error; ?>
            </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
            <div class="alert alert-success">
                <?php echo $success; ?>
            </div>
            <?php endif; ?>
            
            <form action="login.php" method="post">
                <div class="form-group">
                    <label for="username">用户名</label>
                    <input type="text" name="username" id="username" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="password">密码</label>
                    <input type="password" name="password" id="password" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fa fa-sign-in"></i> 登录
                    </button>
                </div>
            </form>
            
            <div class="back-link">
                <a href="../index.php"><i class="fa fa-arrow-left"></i> 返回网站首页</a>
            </div>
        </div>
    </div>
</body>
</html> 