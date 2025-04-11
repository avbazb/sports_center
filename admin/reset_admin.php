<?php
/**
 * 管理员账户重置脚本
 * 请使用后立即删除此文件
 */

// 引入数据库配置
require_once '../config/db.php';

// 设置默认管理员信息
$adminUsername = 'admin';
$adminPassword = password_hash('123456', PASSWORD_DEFAULT);
$adminEmail = 'admin@example.com';
$adminRole = 'admin';

// 检查管理员账号是否存在
$checkAdmin = "SELECT id FROM users WHERE username = 'admin'";
$result = $conn->query($checkAdmin);

if ($result->num_rows > 0) {
    // 管理员账号存在，更新密码
    $adminId = $result->fetch_assoc()['id'];
    $updateAdmin = "UPDATE users SET password = ?, role = 'admin' WHERE id = ?";
    $stmt = $conn->prepare($updateAdmin);
    $stmt->bind_param("si", $adminPassword, $adminId);
    $stmt->execute();
    
    if ($stmt->affected_rows > 0) {
        echo "<p>管理员账号已重置！</p>";
        echo "<p>用户名: admin</p>";
        echo "<p>密码: 123456</p>";
    } else {
        echo "<p>管理员账号更新失败: " . $conn->error . "</p>";
    }
} else {
    // 管理员账号不存在，创建新账号
    $createAdmin = "INSERT INTO users (username, password, email, role, nickname, created_at) 
                    VALUES (?, ?, ?, ?, '管理员', NOW())";
    $stmt = $conn->prepare($createAdmin);
    $stmt->bind_param("ssss", $adminUsername, $adminPassword, $adminEmail, $adminRole);
    $stmt->execute();
    
    if ($stmt->affected_rows > 0) {
        echo "<p>管理员账号已创建！</p>";
        echo "<p>用户名: admin</p>";
        echo "<p>密码: 123456</p>";
    } else {
        echo "<p>管理员账号创建失败: " . $conn->error . "</p>";
    }
}

// 关闭数据库连接
if ($conn instanceof mysqli) {
    $conn->close();
}

echo "<p><strong>警告：请立即删除此文件！</strong></p>";
echo "<p><a href='login.php'>前往登录页面</a></p>";
?> 