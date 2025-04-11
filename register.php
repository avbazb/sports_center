<?php
/**
 * 注册页面
 */

// 页面信息
$pageTitle = '注册';
$pageBanner = 'assets/images/banner-register.jpg';
$pageBreadcrumb = [
    'register.php' => '注册'
];

// 引入头部
require_once 'includes/header.php';

// 处理注册表单
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['username']) && isset($_POST['email']) && isset($_POST['password']) && isset($_POST['confirm_password'])) {
        $username = sanitizeInput($_POST['username']);
        $email = sanitizeInput($_POST['email']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        
        // 验证密码是否匹配
        if ($password !== $confirm_password) {
            $error = '两次输入的密码不匹配';
        } else {
            // 注册用户
            $result = registerUser($username, $password, $email);
            
            if ($result['status']) {
                // 注册成功
                $success = $result['message'] . '，请登录';
                
                // 可选：自动登录用户
                // $_SESSION['user_id'] = $result['user_id'];
                // $_SESSION['username'] = $username;
                // $_SESSION['role'] = 'user';
                // header('Location: index.php');
                // exit;
            } else {
                // 注册失败
                $error = $result['message'];
            }
        }
    } else {
        $error = '请填写所有必填字段';
    }
}
?>

<div class="container">
    <div class="section">
        <div class="form-card animated fadeInUp">
            <h2 class="form-title">用户注册</h2>
            
            <?php if ($error): ?>
            <div class="alert alert-danger">
                <?php echo $error; ?>
            </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
            <div class="alert alert-success">
                <?php echo $success; ?>
                <script>
                    // 3秒后重定向到登录页面
                    setTimeout(function() {
                        window.location.href = 'login.php';
                    }, 3000);
                </script>
            </div>
            <?php endif; ?>
            
            <?php if (!$success): ?>
            <form action="register.php" method="post" data-validate>
                <div class="form-group">
                    <label for="username">用户名</label>
                    <input type="text" id="username" name="username" class="form-control" required>
                    <small class="form-text text-muted">用户名将用于登录，只能包含字母、数字和下划线</small>
                </div>
                
                <div class="form-group">
                    <label for="email">电子邮箱</label>
                    <input type="email" id="email" name="email" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="password">密码</label>
                    <input type="password" id="password" name="password" class="form-control" data-validate-strength required>
                    <small class="form-text text-muted">密码长度至少为8个字符</small>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">确认密码</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" data-validate-match="password" required>
                </div>
                
                <div class="form-group">
                    <div class="checkbox">
                        <input type="checkbox" id="agree_terms" name="agree_terms" required>
                        <label for="agree_terms">我已阅读并同意<a href="#">服务条款</a>和<a href="#">隐私政策</a></label>
                    </div>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary btn-block">注册</button>
                </div>
                
                <div class="form-footer">
                    <p>已有账号？ <a href="login.php">立即登录</a></p>
                </div>
            </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
// 引入页脚
require_once 'includes/footer.php';
?> 