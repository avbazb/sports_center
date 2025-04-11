<?php
/**
 * 登录页面
 */

// 页面信息
$pageTitle = '登录';
$pageBanner = 'assets/images/banner-login.jpg';
$pageBreadcrumb = [
    'login.php' => '登录'
];

// 引入头部
require_once 'includes/header.php';

// 处理登录表单
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['username']) && isset($_POST['password'])) {
        $username = sanitizeInput($_POST['username']);
        $password = $_POST['password'];
        
        // 登录验证
        $result = loginUser($username, $password);
        
        if ($result['status']) {
            // 登录成功，重定向到首页
            $success = $result['message'];
            header('Location: index.php');
            exit;
        } else {
            // 登录失败
            $error = $result['message'];
        }
    } else {
        $error = '请填写所有必填字段';
    }
}
?>

<div class="container">
    <div class="section">
        <div class="form-card animated fadeInUp">
            <h2 class="form-title">用户登录</h2>
            
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
            
            <form action="login.php" method="post" data-validate>
                <div class="form-group">
                    <label for="username">用户名</label>
                    <input type="text" id="username" name="username" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="password">密码</label>
                    <input type="password" id="password" name="password" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <div class="checkbox">
                        <input type="checkbox" id="remember" name="remember">
                        <label for="remember">记住我</label>
                    </div>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary btn-block">登录</button>
                </div>
                
                <div class="form-footer">
                    <p>还没有账号？ <a href="register.php">立即注册</a></p>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
// 引入页脚
require_once 'includes/footer.php';
?> 