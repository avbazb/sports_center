<?php
/**
 * 公共函数文件
 */

// 引入数据库配置
require_once __DIR__ . '/../config/db.php';

/**
 * 用户注册函数
 * @param string $username 用户名
 * @param string $password 密码
 * @param string $email 邮箱
 * @return array 包含状态和消息的数组
 */
function registerUser($username, $password, $email) {
    $conn = getDbConnection();
    
    // 检查用户名是否已存在
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return ['status' => false, 'message' => '用户名已存在'];
    }
    
    // 检查邮箱是否已存在
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return ['status' => false, 'message' => '邮箱已被注册'];
    }
    
    // 密码加密
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // 插入新用户
    $stmt = $conn->prepare("INSERT INTO users (username, password, email) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $username, $hashedPassword, $email);
    
    if ($stmt->execute()) {
        return ['status' => true, 'message' => '注册成功', 'user_id' => $conn->insert_id];
    } else {
        return ['status' => false, 'message' => '注册失败: ' . $conn->error];
    }
}

/**
 * 用户登录函数
 * @param string $username 用户名
 * @param string $password 密码
 * @return array 包含状态和消息的数组
 */
function loginUser($username, $password) {
    $conn = getDbConnection();
    
    $stmt = $conn->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        if (password_verify($password, $user['password'])) {
            // 登录成功，设置session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_role'] = $user['role'];
            
            return ['status' => true, 'message' => '登录成功', 'user' => $user];
        } else {
            return ['status' => false, 'message' => '密码错误'];
        }
    } else {
        return ['status' => false, 'message' => '用户不存在'];
    }
}

/**
 * 检查用户是否已登录
 * @return bool 是否已登录
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * 检查当前用户是否为管理员
 * @return bool 是否为管理员
 */
function isAdmin() {
    return isLoggedIn() && $_SESSION['user_role'] === 'admin';
}

/**
 * 获取当前登录用户ID
 * @return int|null 用户ID，未登录返回null
 */
function getCurrentUserId() {
    return isLoggedIn() ? $_SESSION['user_id'] : null;
}

/**
 * 获取用户信息
 * @param int $userId 用户ID
 * @return array|null 用户信息数组，不存在返回null
 */
function getUserInfo($userId) {
    $conn = getDbConnection();
    
    $stmt = $conn->prepare("SELECT id, username, email, nickname, avatar, bio, certification, role, created_at FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        return $result->fetch_assoc();
    }
    
    return null;
}

/**
 * 更新用户信息
 * @param int $userId 用户ID
 * @param array $data 要更新的数据
 * @return bool 是否更新成功
 */
function updateUserInfo($userId, $data) {
    $conn = getDbConnection();
    
    $allowedFields = ['nickname', 'email', 'bio'];
    $updates = [];
    $params = [];
    $types = "";
    
    foreach ($allowedFields as $field) {
        if (isset($data[$field])) {
            $updates[] = "$field = ?";
            $params[] = $data[$field];
            $types .= "s";
        }
    }
    
    if (empty($updates)) {
        return false;
    }
    
    $query = "UPDATE users SET " . implode(", ", $updates) . " WHERE id = ?";
    $stmt = $conn->prepare($query);
    
    $params[] = $userId;
    $types .= "i";
    
    $stmt->bind_param($types, ...$params);
    return $stmt->execute();
}

/**
 * 保存上传的头像
 * @param int $userId 用户ID
 * @param array $file 上传的文件信息
 * @return array 包含状态和消息的数组
 */
function saveUserAvatar($userId, $file) {
    // 设置允许的文件类型和大小
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    $maxSize = 2 * 1024 * 1024; // 2MB
    
    if (!in_array($file['type'], $allowedTypes)) {
        return ['status' => false, 'message' => '只允许上传JPG、PNG和GIF图片'];
    }
    
    if ($file['size'] > $maxSize) {
        return ['status' => false, 'message' => '文件大小不能超过2MB'];
    }
    
    // 创建上传目录
    $uploadDir = __DIR__ . '/../assets/uploads/avatars/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    // 生成唯一文件名
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $fileName = 'avatar_' . $userId . '_' . time() . '.' . $extension;
    $targetPath = $uploadDir . $fileName;
    
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        // 更新数据库中的头像路径
        $conn = getDbConnection();
        $avatarPath = 'assets/uploads/avatars/' . $fileName;
        
        $stmt = $conn->prepare("UPDATE users SET avatar = ? WHERE id = ?");
        $stmt->bind_param("si", $avatarPath, $userId);
        
        if ($stmt->execute()) {
            return ['status' => true, 'message' => '头像上传成功', 'path' => $avatarPath];
        } else {
            return ['status' => false, 'message' => '数据库更新失败'];
        }
    } else {
        return ['status' => false, 'message' => '文件上传失败'];
    }
}

/**
 * 获取所有文章分类
 * @return array 分类数组
 */
function getAllCategories() {
    $conn = getDbConnection();
    
    $query = "SELECT * FROM categories ORDER BY name";
    $result = $conn->query($query);
    
    $categories = [];
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
    
    return $categories;
}

/**
 * 获取文章列表
 * @param int $categoryId 分类ID，可选
 * @param int $limit 限制数量，可选
 * @param int $offset 偏移量，可选
 * @return array 文章数组
 */
function getArticles($categoryId = null, $limit = 10, $offset = 0) {
    $conn = getDbConnection();
    
    $query = "SELECT a.*, c.name as category_name, u.username as author_name 
              FROM articles a 
              JOIN categories c ON a.category_id = c.id 
              JOIN users u ON a.author_id = u.id 
              WHERE a.is_published = 1";
    
    $params = [];
    $types = "";
    
    if ($categoryId) {
        $query .= " AND a.category_id = ?";
        $params[] = $categoryId;
        $types .= "i";
    }
    
    $query .= " ORDER BY a.created_at DESC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    $types .= "ii";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $articles = [];
    while ($row = $result->fetch_assoc()) {
        $articles[] = $row;
    }
    
    return $articles;
}

/**
 * 获取单篇文章详情
 * @param int $articleId 文章ID
 * @return array|null 文章详情，不存在返回null
 */
function getArticleById($articleId) {
    $conn = getDbConnection();
    
    $stmt = $conn->prepare("SELECT a.*, c.name as category_name, c.slug as category_slug, u.username as author_name, u.avatar as author_avatar 
                           FROM articles a 
                           JOIN categories c ON a.category_id = c.id 
                           JOIN users u ON a.author_id = u.id 
                           WHERE a.id = ? AND a.is_published = 1");
    $stmt->bind_param("i", $articleId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        // 更新阅读次数
        $conn->query("UPDATE articles SET view_count = view_count + 1 WHERE id = $articleId");
        
        return $result->fetch_assoc();
    }
    
    return null;
}

/**
 * 创建/更新文章
 * @param array $data 文章数据
 * @param int $articleId 文章ID，更新时提供
 * @return array 包含状态和消息的数组
 */
function saveArticle($data, $articleId = null) {
    $conn = getDbConnection();
    
    $isUpdate = $articleId !== null;
    
    if ($isUpdate) {
        // 更新文章
        $stmt = $conn->prepare("UPDATE articles SET title = ?, content = ?, category_id = ?, is_published = ? WHERE id = ? AND author_id = ?");
        $stmt->bind_param("ssiiii", $data['title'], $data['content'], $data['category_id'], $data['is_published'], $articleId, $data['author_id']);
        
        if ($stmt->execute()) {
            return ['status' => true, 'message' => '文章更新成功', 'article_id' => $articleId];
        } else {
            return ['status' => false, 'message' => '文章更新失败: ' . $conn->error];
        }
    } else {
        // 创建新文章
        $stmt = $conn->prepare("INSERT INTO articles (title, content, category_id, author_id, is_published) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssiii", $data['title'], $data['content'], $data['category_id'], $data['author_id'], $data['is_published']);
        
        if ($stmt->execute()) {
            return ['status' => true, 'message' => '文章创建成功', 'article_id' => $conn->insert_id];
        } else {
            return ['status' => false, 'message' => '文章创建失败: ' . $conn->error];
        }
    }
}

/**
 * 保存文章缩略图
 * @param int $articleId 文章ID
 * @param array $file 上传的文件信息
 * @return array 包含状态和消息的数组
 */
function saveArticleThumbnail($articleId, $file) {
    // 设置允许的文件类型和大小
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    $maxSize = 5 * 1024 * 1024; // 5MB
    
    if (!in_array($file['type'], $allowedTypes)) {
        return ['status' => false, 'message' => '只允许上传JPG、PNG和GIF图片'];
    }
    
    if ($file['size'] > $maxSize) {
        return ['status' => false, 'message' => '文件大小不能超过5MB'];
    }
    
    // 创建上传目录
    $uploadDir = __DIR__ . '/../assets/uploads/articles/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    // 生成唯一文件名
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $fileName = 'article_' . $articleId . '_' . time() . '.' . $extension;
    $targetPath = $uploadDir . $fileName;
    
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        // 更新数据库中的缩略图路径
        $conn = getDbConnection();
        $thumbnailPath = 'assets/uploads/articles/' . $fileName;
        
        $stmt = $conn->prepare("UPDATE articles SET thumbnail = ? WHERE id = ?");
        $stmt->bind_param("si", $thumbnailPath, $articleId);
        
        if ($stmt->execute()) {
            return ['status' => true, 'message' => '缩略图上传成功', 'path' => $thumbnailPath];
        } else {
            return ['status' => false, 'message' => '数据库更新失败'];
        }
    } else {
        return ['status' => false, 'message' => '文件上传失败'];
    }
}

/**
 * 获取文章评论
 * @param int $articleId 文章ID
 * @return array 评论数组
 */
function getArticleComments($articleId) {
    $conn = getDbConnection();
    
    $stmt = $conn->prepare("SELECT c.*, u.username, u.nickname, u.avatar, u.certification 
                           FROM comments c 
                           JOIN users u ON c.user_id = u.id 
                           WHERE c.article_id = ? 
                           ORDER BY c.created_at DESC");
    $stmt->bind_param("i", $articleId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $comments = [];
    while ($row = $result->fetch_assoc()) {
        // 如果用户没有设置昵称，使用用户名作为显示名
        $row['display_name'] = $row['nickname'] ? $row['nickname'] : $row['username'];
        $comments[] = $row;
    }
    
    return $comments;
}

/**
 * 添加评论
 * @param int $articleId 文章ID
 * @param int $userId 用户ID
 * @param string $content 评论内容
 * @return array 包含状态和消息的数组
 */
function addComment($articleId, $userId, $content) {
    $conn = getDbConnection();
    
    $stmt = $conn->prepare("INSERT INTO comments (article_id, user_id, content) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $articleId, $userId, $content);
    
    if ($stmt->execute()) {
        return ['status' => true, 'message' => '评论发表成功', 'comment_id' => $conn->insert_id];
    } else {
        return ['status' => false, 'message' => '评论发表失败: ' . $conn->error];
    }
}

/**
 * 获取校记录列表
 * @return array 校记录数组
 */
function getSchoolRecords() {
    $conn = getDbConnection();
    
    $query = "SELECT r.*, u.username, u.nickname, u.avatar 
              FROM school_records r 
              LEFT JOIN users u ON r.record_holder_id = u.id 
              ORDER BY r.event_name";
    $result = $conn->query($query);
    
    $records = [];
    while ($row = $result->fetch_assoc()) {
        $records[] = $row;
    }
    
    return $records;
}

/**
 * 保存校记录
 * @param array $data 记录数据
 * @param int $recordId 记录ID，更新时提供
 * @return array 包含状态和消息的数组
 */
function saveSchoolRecord($data, $recordId = null) {
    $conn = getDbConnection();
    
    $isUpdate = $recordId !== null;
    
    if ($isUpdate) {
        // 更新记录
        $stmt = $conn->prepare("UPDATE school_records SET 
                               event_name = ?, 
                               record = ?, 
                               record_holder_id = ?, 
                               record_holder_name = ?, 
                               record_date = ?, 
                               description = ? 
                               WHERE id = ?");
        $stmt->bind_param("ssisssi", 
                         $data['event_name'], 
                         $data['record'], 
                         $data['record_holder_id'], 
                         $data['record_holder_name'], 
                         $data['record_date'], 
                         $data['description'], 
                         $recordId);
        
        if ($stmt->execute()) {
            return ['status' => true, 'message' => '记录更新成功', 'record_id' => $recordId];
        } else {
            return ['status' => false, 'message' => '记录更新失败: ' . $conn->error];
        }
    } else {
        // 创建新记录
        $stmt = $conn->prepare("INSERT INTO school_records 
                               (event_name, record, record_holder_id, record_holder_name, record_date, description) 
                               VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssiss", 
                         $data['event_name'], 
                         $data['record'], 
                         $data['record_holder_id'], 
                         $data['record_holder_name'], 
                         $data['record_date'], 
                         $data['description']);
        
        if ($stmt->execute()) {
            return ['status' => true, 'message' => '记录创建成功', 'record_id' => $conn->insert_id];
        } else {
            return ['status' => false, 'message' => '记录创建失败: ' . $conn->error];
        }
    }
}

/**
 * 获取荣誉墙列表
 * @param int $limit 限制数量，可选
 * @return array 荣誉数组
 */
function getHonors($limit = null) {
    $conn = getDbConnection();
    
    $query = "SELECT * FROM honors ORDER BY honor_date DESC";
    
    if ($limit) {
        $query .= " LIMIT $limit";
    }
    
    $result = $conn->query($query);
    
    $honors = [];
    while ($row = $result->fetch_assoc()) {
        $honors[] = $row;
    }
    
    return $honors;
}

/**
 * 保存荣誉
 * @param array $data 荣誉数据
 * @param int $honorId 荣誉ID，更新时提供
 * @return array 包含状态和消息的数组
 */
function saveHonor($data, $honorId = null) {
    $conn = getDbConnection();
    
    $isUpdate = $honorId !== null;
    
    if ($isUpdate) {
        // 更新荣誉
        $stmt = $conn->prepare("UPDATE honors SET 
                               title = ?, 
                               description = ?, 
                               honor_date = ? 
                               WHERE id = ?");
        $stmt->bind_param("sssi", 
                         $data['title'], 
                         $data['description'], 
                         $data['honor_date'], 
                         $honorId);
        
        if ($stmt->execute()) {
            return ['status' => true, 'message' => '荣誉更新成功', 'honor_id' => $honorId];
        } else {
            return ['status' => false, 'message' => '荣誉更新失败: ' . $conn->error];
        }
    } else {
        // 创建新荣誉
        $stmt = $conn->prepare("INSERT INTO honors 
                               (title, description, honor_date, image) 
                               VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", 
                         $data['title'], 
                         $data['description'], 
                         $data['honor_date'],
                         $data['image']);
        
        if ($stmt->execute()) {
            return ['status' => true, 'message' => '荣誉创建成功', 'honor_id' => $conn->insert_id];
        } else {
            return ['status' => false, 'message' => '荣誉创建失败: ' . $conn->error];
        }
    }
}

/**
 * 保存荣誉图片
 * @param int $honorId 荣誉ID
 * @param array $file 上传的文件信息
 * @return array 包含状态和消息的数组
 */
function saveHonorImage($honorId, $file) {
    // 设置允许的文件类型和大小
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    $maxSize = 5 * 1024 * 1024; // 5MB
    
    if (!in_array($file['type'], $allowedTypes)) {
        return ['status' => false, 'message' => '只允许上传JPG、PNG和GIF图片'];
    }
    
    if ($file['size'] > $maxSize) {
        return ['status' => false, 'message' => '文件大小不能超过5MB'];
    }
    
    // 创建上传目录
    $uploadDir = __DIR__ . '/../assets/uploads/honors/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    // 生成唯一文件名
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $fileName = 'honor_' . $honorId . '_' . time() . '.' . $extension;
    $targetPath = $uploadDir . $fileName;
    
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        // 更新数据库中的图片路径
        $conn = getDbConnection();
        $imagePath = 'assets/uploads/honors/' . $fileName;
        
        $stmt = $conn->prepare("UPDATE honors SET image = ? WHERE id = ?");
        $stmt->bind_param("si", $imagePath, $honorId);
        
        if ($stmt->execute()) {
            return ['status' => true, 'message' => '图片上传成功', 'path' => $imagePath];
        } else {
            return ['status' => false, 'message' => '数据库更新失败'];
        }
    } else {
        return ['status' => false, 'message' => '文件上传失败'];
    }
} 