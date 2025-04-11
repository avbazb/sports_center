<?php
/**
 * 退出登录页面
 */

// 开启会话
session_start();

// 清除所有会话变量
$_SESSION = array();

// 如果要使用会话Cookie，则清除会话Cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 销毁会话
session_destroy();

// 重定向到首页
header("Location: index.php");
exit;
?> 