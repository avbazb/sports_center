<?php
/**
 * 图片管理页面
 */

// 页面信息
$pageTitle = '图片管理';

// 引入管理后台头部
require_once 'includes/admin_header.php';

// 获取操作类型
$action = isset($_GET['action']) ? $_GET['action'] : 'list';

// 图片上传目录
$uploadDir = '../assets/uploads/images/';
$uploadUrl = 'assets/uploads/images/';

// 确保上传目录存在
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// 处理图片操作
switch ($action) {
    case 'upload':
        // 上传图片
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $uploadedFiles = [];
            $errors = [];
            
            // 处理上传的文件
            if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
                $files = $_FILES['images'];
                
                // 循环处理所有上传的文件
                for ($i = 0; $i < count($files['name']); $i++) {
                    if ($files['error'][$i] === UPLOAD_ERR_OK) {
                        $tmpName = $files['tmp_name'][$i];
                        $name = basename($files['name'][$i]);
                        $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                        
                        // 检查文件类型
                        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
                        if (!in_array($extension, $allowedTypes)) {
                            $errors[] = "文件 {$name} 不是允许的图片类型";
                            continue;
                        }
                        
                        // 检查文件大小
                        $maxFileSize = 5 * 1024 * 1024; // 5MB
                        if ($files['size'][$i] > $maxFileSize) {
                            $errors[] = "文件 {$name} 超过了最大限制（5MB）";
                            continue;
                        }
                        
                        // 生成唯一文件名
                        $newName = uniqid() . '_' . $name;
                        $destination = $uploadDir . $newName;
                        
                        if (move_uploaded_file($tmpName, $destination)) {
                            // 保存图片信息到数据库
                            $conn = getDbConnection();
                            $userId = getCurrentUserId();
                            $fileUrl = $uploadUrl . $newName;
                            $fileSize = $files['size'][$i];
                            $fileType = $files['type'][$i];
                            
                            $stmt = $conn->prepare("
                                INSERT INTO images (filename, filepath, filesize, filetype, user_id, upload_date) 
                                VALUES (?, ?, ?, ?, ?, NOW())
                            ");
                            $stmt->bind_param("ssisi", $name, $fileUrl, $fileSize, $fileType, $userId);
                            $stmt->execute();
                            $stmt->close();
                            
                            $uploadedFiles[] = $fileUrl;
                        } else {
                            $errors[] = "上传 {$name} 失败";
                        }
                    } else {
                        $errors[] = "上传文件时出错，错误代码：" . $files['error'][$i];
                    }
                }
            } else {
                $errors[] = "请选择要上传的图片";
            }
            
            if (empty($errors)) {
                $_SESSION['alert'] = [
                    'type' => 'success',
                    'message' => '图片上传成功'
                ];
            } else {
                $_SESSION['alert'] = [
                    'type' => 'danger',
                    'message' => implode('<br>', $errors)
                ];
            }
            
            header('Location: images.php');
            exit;
        }
        break;
        
    case 'delete':
        // 删除图片
        if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
            $_SESSION['alert'] = [
                'type' => 'danger',
                'message' => '无效的图片ID'
            ];
            header('Location: images.php');
            exit;
        }
        
        $imageId = intval($_GET['id']);
        
        // 获取图片信息
        $conn = getDbConnection();
        $stmt = $conn->prepare("SELECT id, filename, filepath FROM images WHERE id = ?");
        $stmt->bind_param("i", $imageId);
        $stmt->execute();
        $result = $stmt->get_result();
        $image = $result->fetch_assoc();
        $stmt->close();
        
        if (!$image) {
            $_SESSION['alert'] = [
                'type' => 'danger',
                'message' => '图片不存在或已被删除'
            ];
            header('Location: images.php');
            exit;
        }
        
        // 删除文件
        $filePath = '../' . $image['filepath'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }
        
        // 从数据库中删除
        $stmt = $conn->prepare("DELETE FROM images WHERE id = ?");
        $stmt->bind_param("i", $imageId);
        $result = $stmt->execute();
        $stmt->close();
        
        if ($result) {
            $_SESSION['alert'] = [
                'type' => 'success',
                'message' => '图片删除成功'
            ];
        } else {
            $_SESSION['alert'] = [
                'type' => 'danger',
                'message' => '图片删除失败：' . $conn->error
            ];
        }
        
        header('Location: images.php');
        exit;
        break;
        
    case 'bulk':
        // 批量操作
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['selected'])) {
            $action = sanitizeInput($_POST['action']);
            $selected = $_POST['selected'];
            
            if (!empty($selected) && !empty($action)) {
                $conn = getDbConnection();
                
                switch ($action) {
                    case 'delete':
                        // 获取图片信息
                        $imageIds = array_map('intval', $selected);
                        $placeholders = implode(',', array_fill(0, count($imageIds), '?'));
                        
                        $types = str_repeat('i', count($imageIds));
                        $stmt = $conn->prepare("SELECT id, filepath FROM images WHERE id IN ($placeholders)");
                        $stmt->bind_param($types, ...$imageIds);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        $images = $result->fetch_all(MYSQLI_ASSOC);
                        $stmt->close();
                        
                        // 删除文件
                        foreach ($images as $image) {
                            $filePath = '../' . $image['filepath'];
                            if (file_exists($filePath)) {
                                unlink($filePath);
                            }
                        }
                        
                        // 从数据库中删除
                        $stmt = $conn->prepare("DELETE FROM images WHERE id IN ($placeholders)");
                        $stmt->bind_param($types, ...$imageIds);
                        $result = $stmt->execute();
                        $stmt->close();
                        
                        if ($result) {
                            $_SESSION['alert'] = [
                                'type' => 'success',
                                'message' => '选中的图片已成功删除'
                            ];
                        } else {
                            $_SESSION['alert'] = [
                                'type' => 'danger',
                                'message' => '批量删除图片失败：' . $conn->error
                            ];
                        }
                        break;
                        
                    default:
                        $_SESSION['alert'] = [
                            'type' => 'warning',
                            'message' => '未知操作'
                        ];
                        break;
                }
            } else {
                $_SESSION['alert'] = [
                    'type' => 'warning',
                    'message' => '请选择要操作的图片和操作类型'
                ];
            }
        }
        
        header('Location: images.php');
        exit;
        break;
        
    case 'list':
    default:
        // 检查数据库中是否有images表，如果没有则创建
        $conn = getDbConnection();
        $checkTable = $conn->query("SHOW TABLES LIKE 'images'");
        if ($checkTable->num_rows == 0) {
            $createTable = "CREATE TABLE `images` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `filename` varchar(255) NOT NULL,
                `filepath` varchar(255) NOT NULL,
                `filesize` int(11) NOT NULL,
                `filetype` varchar(100) NOT NULL,
                `description` text DEFAULT NULL,
                `user_id` int(11) NOT NULL,
                `upload_date` datetime NOT NULL,
                PRIMARY KEY (`id`),
                KEY `user_id` (`user_id`),
                CONSTRAINT `fk_image_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
            
            $conn->query($createTable);
        }
        
        // 分页参数
        $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
        $perPage = 24; // 每页显示24张图片
        $offset = ($page - 1) * $perPage;
        
        // 筛选参数
        $search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : null;
        
        // 获取图片总数
        $sql = "SELECT COUNT(*) as total FROM images WHERE 1=1";
        $params = [];
        $types = "";
        
        if ($search) {
            $sql .= " AND (filename LIKE ? OR description LIKE ?)";
            $searchParam = "%{$search}%";
            $params[] = $searchParam;
            $params[] = $searchParam;
            $types .= "ss";
        }
        
        $stmt = $conn->prepare($sql);
        
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $totalImages = $result->fetch_assoc()['total'];
        $stmt->close();
        
        // 计算总页数
        $totalPages = ceil($totalImages / $perPage);
        
        // 获取图片列表
        $sql = "
            SELECT i.*, u.username, u.nickname 
            FROM images i
            JOIN users u ON i.user_id = u.id
            WHERE 1=1
        ";
        
        if ($search) {
            $sql .= " AND (i.filename LIKE ? OR i.description LIKE ?)";
        }
        
        $sql .= " ORDER BY i.upload_date DESC LIMIT ? OFFSET ?";
        
        $params[] = $perPage;
        $params[] = $offset;
        $types .= "ii";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $images = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        // 显示图片列表
        showImagesList($images, $search, $page, $totalPages);
        break;
}

/**
 * 显示图片列表
 */
function showImagesList($images, $search, $page, $totalPages) {
    ?>
    <div class="admin-content-header">
        <h2>图片管理</h2>
        <div class="header-actions">
            <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#uploadModal">
                <i class="fas fa-upload"></i> 上传图片
            </button>
        </div>
    </div>
    
    <div class="admin-content-body">
        <!-- 筛选表单 -->
        <div class="filter-form">
            <form action="images.php" method="get" class="form-inline">
                <div class="form-group">
                    <input type="text" name="search" class="form-control" placeholder="搜索文件名或描述" value="<?php echo $search; ?>">
                </div>
                
                <button type="submit" class="btn btn-secondary">
                    <i class="fas fa-search"></i> 筛选
                </button>
                
                <?php if ($search): ?>
                <a href="images.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> 清除筛选
                </a>
                <?php endif; ?>
            </form>
        </div>
        
        <!-- 图片网格 -->
        <?php if (empty($images)): ?>
        <div class="empty-data">
            <i class="fas fa-images"></i>
            <p>没有找到图片</p>
            <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#uploadModal">
                上传第一张图片
            </button>
        </div>
        <?php else: ?>
        
        <form action="images.php?action=bulk" method="post" class="bulk-action-form">
            <div class="bulk-actions">
                <select name="action" class="form-control bulk-action-select">
                    <option value="">批量操作</option>
                    <option value="delete">删除</option>
                </select>
                <button type="button" class="btn btn-secondary bulk-action-btn">应用</button>
            </div>
            
            <div class="image-grid">
                <?php foreach ($images as $image): ?>
                <div class="image-item">
                    <div class="image-checkbox">
                        <input type="checkbox" name="selected[]" value="<?php echo $image['id']; ?>" class="select-item">
                    </div>
                    <div class="image-preview" style="background-image: url('../<?php echo $image['filepath']; ?>');">
                        <div class="image-actions">
                            <a href="../<?php echo $image['filepath']; ?>" class="btn-icon" title="查看图片" target="_blank">
                                <i class="fas fa-eye"></i>
                            </a>
                            <button type="button" class="btn-icon btn-copy" title="复制链接" data-url="<?php echo $image['filepath']; ?>">
                                <i class="fas fa-link"></i>
                            </button>
                            <a href="images.php?action=delete&id=<?php echo $image['id']; ?>" class="btn-icon btn-delete" title="删除" data-confirm="确定要删除此图片吗？此操作不可撤销。">
                                <i class="fas fa-trash"></i>
                            </a>
                        </div>
                    </div>
                    <div class="image-info">
                        <div class="image-name" title="<?php echo $image['filename']; ?>">
                            <?php echo strlen($image['filename']) > 20 ? substr($image['filename'], 0, 17) . '...' : $image['filename']; ?>
                        </div>
                        <div class="image-meta">
                            <span class="image-size"><?php echo formatFileSize($image['filesize']); ?></span>
                            <span class="image-date"><?php echo date('Y-m-d', strtotime($image['upload_date'])); ?></span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </form>
        
        <!-- 分页 -->
        <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <ul>
                <?php if ($page > 1): ?>
                <li><a href="images.php?page=<?php echo ($page - 1); ?>&search=<?php echo $search; ?>"><i class="fas fa-chevron-left"></i></a></li>
                <?php else: ?>
                <li class="disabled"><span><i class="fas fa-chevron-left"></i></span></li>
                <?php endif; ?>
                
                <?php
                // 计算分页范围
                $startPage = max(1, $page - 2);
                $endPage = min($totalPages, $page + 2);
                
                // 确保显示至少5页（如果有）
                if ($endPage - $startPage + 1 < 5) {
                    if ($startPage === 1) {
                        $endPage = min($totalPages, 5);
                    } elseif ($endPage === $totalPages) {
                        $startPage = max(1, $totalPages - 4);
                    }
                }
                
                // 显示第一页
                if ($startPage > 1) {
                    echo '<li><a href="images.php?page=1&search=' . $search . '">1</a></li>';
                    if ($startPage > 2) {
                        echo '<li class="separator">...</li>';
                    }
                }
                
                // 显示页码
                for ($i = $startPage; $i <= $endPage; $i++) {
                    if ($i === $page) {
                        echo '<li class="active"><span>' . $i . '</span></li>';
                    } else {
                        echo '<li><a href="images.php?page=' . $i . '&search=' . $search . '">' . $i . '</a></li>';
                    }
                }
                
                // 显示最后一页
                if ($endPage < $totalPages) {
                    if ($endPage < $totalPages - 1) {
                        echo '<li class="separator">...</li>';
                    }
                    echo '<li><a href="images.php?page=' . $totalPages . '&search=' . $search . '">' . $totalPages . '</a></li>';
                }
                ?>
                
                <?php if ($page < $totalPages): ?>
                <li><a href="images.php?page=<?php echo ($page + 1); ?>&search=<?php echo $search; ?>"><i class="fas fa-chevron-right"></i></a></li>
                <?php else: ?>
                <li class="disabled"><span><i class="fas fa-chevron-right"></i></span></li>
                <?php endif; ?>
            </ul>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
    
    <!-- 上传图片模态框 -->
    <div class="modal" id="uploadModal">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">上传图片</h4>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <form action="images.php?action=upload" method="post" enctype="multipart/form-data" id="uploadForm">
                        <div class="form-group">
                            <label for="images">选择图片</label>
                            <input type="file" name="images[]" id="images" class="form-control" multiple accept="image/*" required>
                            <div class="help-text">可以同时上传多张图片，每张图片最大5MB，支持JPG、JPEG、PNG、GIF格式</div>
                        </div>
                        
                        <div id="preview-container" class="upload-preview-container"></div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-upload"></i> 上传
                            </button>
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">
                                <i class="fas fa-times"></i> 取消
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <style>
    .filter-form {
        margin-bottom: 20px;
    }
    
    .form-inline {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
    }
    
    .empty-data {
        text-align: center;
        padding: 50px 0;
    }
    
    .empty-data i {
        font-size: 48px;
        color: var(--admin-gray);
        margin-bottom: 10px;
    }
    
    .empty-data p {
        color: var(--admin-gray);
        margin-bottom: 20px;
    }
    
    .image-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 20px;
        margin-top: 20px;
    }
    
    .image-item {
        position: relative;
        border: 1px solid var(--admin-border-color);
        border-radius: 5px;
        overflow: hidden;
        background-color: var(--admin-light-bg);
    }
    
    .image-checkbox {
        position: absolute;
        top: 10px;
        left: 10px;
        z-index: 2;
    }
    
    .image-checkbox input {
        cursor: pointer;
    }
    
    .image-preview {
        height: 150px;
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
        position: relative;
    }
    
    .image-actions {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        background-color: rgba(0, 0, 0, 0.5);
        display: flex;
        justify-content: center;
        gap: 10px;
        padding: 5px;
        opacity: 0;
        transition: opacity 0.3s ease;
    }
    
    .image-preview:hover .image-actions {
        opacity: 1;
    }
    
    .image-actions .btn-icon {
        color: #fff;
        background-color: rgba(0, 0, 0, 0.3);
    }
    
    .image-actions .btn-icon:hover {
        background-color: rgba(0, 0, 0, 0.5);
    }
    
    .image-info {
        padding: 10px;
    }
    
    .image-name {
        font-weight: 500;
        margin-bottom: 5px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    .image-meta {
        display: flex;
        justify-content: space-between;
        font-size: 12px;
        color: var(--admin-gray);
    }
    
    .upload-preview-container {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 10px;
    }
    
    .upload-preview {
        width: 100px;
        height: 100px;
        border-radius: 5px;
        overflow: hidden;
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
        position: relative;
    }
    
    .upload-preview-name {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        background-color: rgba(0, 0, 0, 0.5);
        color: #fff;
        font-size: 12px;
        padding: 3px 5px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    .modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: rgba(0, 0, 0, 0.5);
        z-index: 1000;
        overflow: auto;
    }
    
    .modal.show {
        display: block;
    }
    
    .modal-dialog {
        margin: 30px auto;
        max-width: 600px;
        width: 100%;
    }
    
    .modal-content {
        background-color: #fff;
        border-radius: 5px;
        overflow: hidden;
    }
    
    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 15px 20px;
        border-bottom: 1px solid var(--admin-border-color);
    }
    
    .modal-title {
        font-size: 18px;
        font-weight: 600;
        margin: 0;
    }
    
    .close {
        font-size: 24px;
        font-weight: 700;
        background: none;
        border: none;
        cursor: pointer;
        color: var(--admin-gray);
    }
    
    .modal-body {
        padding: 20px;
    }
    
    .copied-message {
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 10px 15px;
        background-color: var(--admin-success);
        color: #fff;
        border-radius: 5px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        z-index: 1000;
        opacity: 0;
        transform: translateY(-20px);
        transition: opacity 0.3s ease, transform 0.3s ease;
    }
    
    .copied-message.show {
        opacity: 1;
        transform: translateY(0);
    }
    
    .bulk-actions {
        display: flex;
        gap: 10px;
        margin-bottom: 20px;
    }
    </style>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // 模态框功能
        const modal = document.getElementById('uploadModal');
        const modalToggle = document.querySelector('[data-toggle="modal"]');
        const modalClose = document.querySelector('.close');
        const modalDismiss = document.querySelector('[data-dismiss="modal"]');
        
        if (modalToggle) {
            modalToggle.addEventListener('click', function() {
                modal.classList.add('show');
            });
        }
        
        if (modalClose) {
            modalClose.addEventListener('click', function() {
                modal.classList.remove('show');
            });
        }
        
        if (modalDismiss) {
            modalDismiss.addEventListener('click', function() {
                modal.classList.remove('show');
            });
        }
        
        // 点击模态框背景关闭
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                modal.classList.remove('show');
            }
        });
        
        // 图片预览功能
        const imageInput = document.getElementById('images');
        const previewContainer = document.getElementById('preview-container');
        
        if (imageInput) {
            imageInput.addEventListener('change', function() {
                previewContainer.innerHTML = '';
                
                if (this.files) {
                    for (let i = 0; i < this.files.length; i++) {
                        const file = this.files[i];
                        const reader = new FileReader();
                        
                        reader.onload = function(e) {
                            const preview = document.createElement('div');
                            preview.className = 'upload-preview';
                            preview.style.backgroundImage = `url(${e.target.result})`;
                            
                            const name = document.createElement('div');
                            name.className = 'upload-preview-name';
                            name.textContent = file.name.length > 15 ? file.name.substring(0, 12) + '...' : file.name;
                            
                            preview.appendChild(name);
                            previewContainer.appendChild(preview);
                        };
                        
                        reader.readAsDataURL(file);
                    }
                }
            });
        }
        
        // 复制链接功能
        const copyButtons = document.querySelectorAll('.btn-copy');
        
        copyButtons.forEach(button => {
            button.addEventListener('click', function() {
                const url = this.dataset.url;
                const fullUrl = window.location.origin + '/' + url;
                
                // 创建临时输入框
                const tempInput = document.createElement('input');
                tempInput.value = fullUrl;
                document.body.appendChild(tempInput);
                tempInput.select();
                document.execCommand('copy');
                document.body.removeChild(tempInput);
                
                // 显示复制成功消息
                let message = document.querySelector('.copied-message');
                
                if (!message) {
                    message = document.createElement('div');
                    message.className = 'copied-message';
                    message.textContent = '链接已复制到剪贴板';
                    document.body.appendChild(message);
                }
                
                message.classList.add('show');
                
                setTimeout(() => {
                    message.classList.remove('show');
                }, 2000);
            });
        });
    });
    </script>
    <?php
}

/**
 * 格式化文件大小
 */
function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}

// 引入管理后台页脚
require_once 'includes/admin_footer.php';
?> 