<?php
/**
 * 数据库配置文件
 */

// 数据库连接配置
define('DB_HOST', 'localhost');  // 数据库主机
define('DB_USER', 'sports_center');       // 数据库用户名
define('DB_PASS', '');           // 数据库密码
define('DB_NAME', 'sports_center');  // 数据库名称

// 创建数据库连接
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// 检查连接
if ($conn->connect_error) {
    die("数据库连接失败: " . $conn->connect_error);
}

// 设置字符集
$conn->set_charset("utf8mb4");

// 全局连接变量
$GLOBALS['conn'] = $conn;

/**
 * 获取数据库连接
 * @return mysqli 数据库连接对象
 */
function getDbConnection() {
    return $GLOBALS['conn'];
}

/**
 * 安全处理输入，防止SQL注入
 * @param string $data 需要处理的数据
 * @return string 处理后的安全数据
 */
function sanitizeInput($data) {
    $conn = getDbConnection();
    return $conn->real_escape_string(trim($data));
} 