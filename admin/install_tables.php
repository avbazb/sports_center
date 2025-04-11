<?php
/**
 * 数据库表安装脚本
 * 用于创建网站运行所需的所有数据库表
 */

// 设置页面标题
$pageTitle = '数据库表安装';

// 引入管理后台头部
require_once 'includes/admin_header.php';

// 要创建的表
$tables = [
    // 荣誉墙表
    'honors' => "CREATE TABLE IF NOT EXISTS `honors` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `title` varchar(255) NOT NULL,
        `image` varchar(255) NOT NULL,
        `description` text,
        `honor_date` date DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
    
    // 用户认证表
    'user_certifications' => "CREATE TABLE IF NOT EXISTS `user_certifications` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `user_id` int(11) NOT NULL,
        `cert_name` varchar(255) NOT NULL,
        `cert_file` varchar(255) DEFAULT NULL,
        `cert_date` date DEFAULT NULL,
        `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `user_id` (`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
    
    // 校记录表
    'school_records' => "CREATE TABLE IF NOT EXISTS `school_records` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `event_name` varchar(255) NOT NULL,
        `record` varchar(255) NOT NULL,
        `record_holder` varchar(255) NOT NULL,
        `record_date` date DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
    
    // 分类表
    'categories' => "CREATE TABLE IF NOT EXISTS `categories` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `name` varchar(255) NOT NULL,
        `slug` varchar(255) NOT NULL,
        `description` text,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `slug` (`slug`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
];

// 检查并创建上传目录
$uploadDirs = [
    '../assets/uploads/',
    '../assets/uploads/honors/',
    '../assets/uploads/certifications/',
    '../assets/uploads/articles/',
    '../assets/uploads/avatars/'
];

foreach ($uploadDirs as $dir) {
    if (!file_exists($dir)) {
        if (mkdir($dir, 0777, true)) {
            echo "<div class='alert alert-success'>目录 {$dir} 创建成功</div>";
        } else {
            echo "<div class='alert alert-danger'>无法创建目录 {$dir}</div>";
        }
    } else {
        echo "<div class='alert alert-info'>目录 {$dir} 已存在</div>";
    }
}

// 创建表
$results = [];

foreach ($tables as $tableName => $sql) {
    $tableExists = $conn->query("SHOW TABLES LIKE '{$tableName}'")->num_rows > 0;
    
    if ($tableExists) {
        $results[$tableName] = [
            'status' => 'info',
            'message' => "表 {$tableName} 已存在"
        ];
    } else {
        if ($conn->query($sql) === TRUE) {
            $results[$tableName] = [
                'status' => 'success',
                'message' => "表 {$tableName} 创建成功"
            ];
        } else {
            $results[$tableName] = [
                'status' => 'danger',
                'message' => "创建表 {$tableName} 时出错: " . $conn->error
            ];
        }
    }
}

// 输出结果
?>
<div class="admin-content-header">
    <h2>数据库表安装</h2>
    <div class="header-actions">
        <a href="index.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> 返回仪表盘
        </a>
    </div>
</div>

<div class="admin-content-body">
    <div class="panel">
        <div class="panel-header">
            <h3>安装结果</h3>
        </div>
        <div class="panel-body">
            <?php foreach ($results as $tableName => $result): ?>
            <div class="alert alert-<?php echo $result['status']; ?>">
                <?php echo $result['message']; ?>
            </div>
            <?php endforeach; ?>
            
            <div class="mt-4">
                <a href="index.php" class="btn btn-primary">
                    <i class="fas fa-arrow-left"></i> 返回仪表盘
                </a>
            </div>
        </div>
    </div>
</div>

<?php
// 引入管理后台页脚
require_once 'includes/admin_footer.php';
?> 