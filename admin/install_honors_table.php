<?php
/**
 * 荣誉墙表安装脚本
 */

// 引入数据库连接
require_once '../config/db.php';

// 创建荣誉墙表
$query = "CREATE TABLE IF NOT EXISTS `honors` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `image` varchar(255) NOT NULL,
  `description` text,
  `honor_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

if ($conn->query($query) === TRUE) {
    echo "荣誉墙表创建成功！<br>";
} else {
    echo "创建荣誉墙表时出错: " . $conn->error . "<br>";
}

echo "<a href='honors.php'>返回荣誉墙管理</a>"; 