<?php
/**
 * 图片上传处理程序
 * 用于富文本编辑器上传图片
 */
session_start();
header('Content-Type: application/json');

// 检查用户登录状态
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => '请先登录']);
    exit;
}

// 导入数据库连接
require_once '../includes/db.php';
require_once '../includes/functions.php';

// 检查是否有文件上传
if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    $error = isset($_FILES['file']) ? getUploadErrorMessage($_FILES['file']['error']) : '没有上传文件';
    echo json_encode(['success' => false, 'message' => $error]);
    exit;
}

// 获取文件信息
$file = $_FILES['file'];
$filename = $file['name'];
$tmp_name = $file['tmp_name'];
$file_size = $file['size'];

// 检查文件类型
$allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
$file_type = mime_content_type($tmp_name);

if (!in_array($file_type, $allowed_types)) {
    echo json_encode(['success' => false, 'message' => '不支持的文件类型，只允许JPG、JPEG、PNG和GIF']);
    exit;
}

// 限制文件大小（5MB）
$max_size = 5 * 1024 * 1024; // 5MB
if ($file_size > $max_size) {
    echo json_encode(['success' => false, 'message' => '文件大小不能超过5MB']);
    exit;
}

// 创建上传目录
$upload_dir = '../uploads/images/' . date('Y/m');
if (!is_dir($upload_dir) && !mkdir($upload_dir, 0777, true)) {
    echo json_encode(['success' => false, 'message' => '无法创建上传目录']);
    exit;
}

// 生成唯一文件名
$extension = pathinfo($filename, PATHINFO_EXTENSION);
$new_filename = uniqid() . '_' . time() . '.' . $extension;
$upload_path = $upload_dir . '/' . $new_filename;

// 上传文件
if (!move_uploaded_file($tmp_name, $upload_path)) {
    echo json_encode(['success' => false, 'message' => '文件上传失败']);
    exit;
}

// 获取相对于站点根目录的路径
$relative_path = str_replace('../', '', $upload_path);

// 将图片信息保存到数据库
$user_id = $_SESSION['user_id'];
$file_size_kb = round($file_size / 1024, 2);
$description = "从编辑器上传";

// 检查images表是否存在，不存在则创建
$table_query = "CREATE TABLE IF NOT EXISTS `images` (
    `image_id` int(11) NOT NULL AUTO_INCREMENT,
    `filename` varchar(255) NOT NULL,
    `file_path` varchar(255) NOT NULL,
    `file_type` varchar(50) NOT NULL,
    `file_size` float NOT NULL,
    `uploaded_by` int(11) NOT NULL,
    `uploaded_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `description` text,
    PRIMARY KEY (`image_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

$conn->query($table_query);

$query = "INSERT INTO images (filename, file_path, file_type, file_size, uploaded_by, description) 
          VALUES (?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($query);
$stmt->bind_param("sssdis", $filename, $relative_path, $file_type, $file_size_kb, $user_id, $description);

if ($stmt->execute()) {
    $image_id = $stmt->insert_id;
    $image_url = $relative_path;
    echo json_encode([
        'success' => true,
        'message' => '图片上传成功',
        'location' => $image_url,
        'id' => $image_id
    ]);
} else {
    // 删除已上传的文件
    unlink($upload_path);
    echo json_encode(['success' => false, 'message' => '保存图片信息失败: ' . $stmt->error]);
}

/**
 * 获取上传错误信息
 */
function getUploadErrorMessage($error_code) {
    switch ($error_code) {
        case UPLOAD_ERR_INI_SIZE:
            return '上传的文件超过了php.ini中upload_max_filesize指令的限制';
        case UPLOAD_ERR_FORM_SIZE:
            return '上传的文件超过了HTML表单中MAX_FILE_SIZE指令的限制';
        case UPLOAD_ERR_PARTIAL:
            return '上传的文件只有部分被上传';
        case UPLOAD_ERR_NO_FILE:
            return '没有文件被上传';
        case UPLOAD_ERR_NO_TMP_DIR:
            return '找不到临时文件夹';
        case UPLOAD_ERR_CANT_WRITE:
            return '无法写入磁盘';
        case UPLOAD_ERR_EXTENSION:
            return '文件上传被PHP扩展停止';
        default:
            return '未知上传错误';
    }
} 