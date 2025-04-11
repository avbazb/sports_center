<?php
/**
 * 用户管理页面
 */

// 页面信息
$pageTitle = '用户管理';

// 引入管理后台头部
require_once 'includes/admin_header.php';

// 获取操作类型
$action = isset($_GET['action']) ? $_GET['action'] : 'list';

// 处理用户操作
switch ($action) {
    case 'add':
        // 添加用户
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // 验证数据
            $error = false;
            $errorMessage = '';
            
            if (empty($_POST['username']) || empty($_POST['email']) || empty($_POST['password'])) {
                $error = true;
                $errorMessage = '请填写所有必填字段';
            } elseif ($_POST['password'] !== $_POST['confirm_password']) {
                $error = true;
                $errorMessage = '两次输入的密码不匹配';
            } else {
                // 准备数据
                $username = sanitizeInput($_POST['username']);
                $email = sanitizeInput($_POST['email']);
                $password = $_POST['password'];
                $role = isset($_POST['role']) ? sanitizeInput($_POST['role']) : 'user';
                
                // 注册用户
                $result = registerUser($username, $password, $email, $role);
                
                if ($result['status']) {
                    $_SESSION['alert'] = [
                        'type' => 'success',
                        'message' => '用户添加成功'
                    ];
                    header('Location: users.php');
                    exit;
                } else {
                    $error = true;
                    $errorMessage = '用户添加失败：' . $result['message'];
                }
            }
            
            if ($error) {
                $_SESSION['alert'] = [
                    'type' => 'danger',
                    'message' => $errorMessage
                ];
            }
        }
        
        // 显示添加表单
        showAddForm();
        break;
        
    case 'edit':
        // 编辑用户
        if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
            $_SESSION['alert'] = [
                'type' => 'danger',
                'message' => '无效的用户ID'
            ];
            header('Location: users.php');
            exit;
        }
        
        $userId = intval($_GET['id']);
        $user = getUserInfo($userId);
        
        if (!$user) {
            $_SESSION['alert'] = [
                'type' => 'danger',
                'message' => '用户不存在或已被删除'
            ];
            header('Location: users.php');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // 验证数据
            $error = false;
            $errorMessage = '';
            
            if (empty($_POST['username']) || empty($_POST['email'])) {
                $error = true;
                $errorMessage = '请填写所有必填字段';
            } else {
                // 准备数据
                $data = [
                    'username' => sanitizeInput($_POST['username']),
                    'email' => sanitizeInput($_POST['email']),
                    'nickname' => sanitizeInput($_POST['nickname']),
                    'bio' => sanitizeInput($_POST['bio']),
                    'role' => isset($_POST['role']) ? sanitizeInput($_POST['role']) : 'user'
                ];
                
                // 如果提供了新密码
                if (!empty($_POST['password']) && !empty($_POST['confirm_password'])) {
                    if ($_POST['password'] !== $_POST['confirm_password']) {
                        $error = true;
                        $errorMessage = '两次输入的密码不匹配';
                    } else {
                        $data['password'] = $_POST['password'];
                    }
                }
                
                if (!$error) {
                    // 更新用户
                    $result = updateUserInfo($userId, $data);
                    
                    if ($result['status']) {
                        // 处理头像上传
                        if (!empty($_FILES['avatar']['name'])) {
                            $imageResult = saveUserAvatar($userId, $_FILES['avatar']);
                            
                            if (!$imageResult['status']) {
                                $_SESSION['alert'] = [
                                    'type' => 'warning',
                                    'message' => '用户信息已更新，但头像上传失败：' . $imageResult['message']
                                ];
                                header('Location: users.php');
                                exit;
                            }
                        }
                        
                        $_SESSION['alert'] = [
                            'type' => 'success',
                            'message' => '用户信息更新成功'
                        ];
                        header('Location: users.php');
                        exit;
                    } else {
                        $error = true;
                        $errorMessage = '用户信息更新失败：' . $result['message'];
                    }
                }
            }
            
            if ($error) {
                $_SESSION['alert'] = [
                    'type' => 'danger',
                    'message' => $errorMessage
                ];
            }
        }
        
        // 显示编辑表单
        showEditForm($user);
        break;
        
    case 'delete':
        // 删除用户
        if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
            $_SESSION['alert'] = [
                'type' => 'danger',
                'message' => '无效的用户ID'
            ];
            header('Location: users.php');
            exit;
        }
        
        $userId = intval($_GET['id']);
        
        // 不能删除自己
        if ($userId === getCurrentUserId()) {
            $_SESSION['alert'] = [
                'type' => 'danger',
                'message' => '不能删除当前登录的用户'
            ];
            header('Location: users.php');
            exit;
        }
        
        // 检查用户是否存在
        $user = getUserInfo($userId);
        
        if (!$user) {
            $_SESSION['alert'] = [
                'type' => 'danger',
                'message' => '用户不存在或已被删除'
            ];
            header('Location: users.php');
            exit;
        }
        
        // 删除用户
        $conn = getDbConnection();
        
        // 开始事务
        $conn->begin_transaction();
        
        try {
            // 删除用户的评论
            $stmt = $conn->prepare("DELETE FROM comments WHERE user_id = ?");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $stmt->close();
            
            // 删除用户的文章
            $stmt = $conn->prepare("DELETE FROM articles WHERE author_id = ?");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $stmt->close();
            
            // 删除用户
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            $stmt->bind_param("i", $userId);
            $result = $stmt->execute();
            $stmt->close();
            
            if ($result) {
                // 提交事务
                $conn->commit();
                
                // 删除头像文件
                if (!empty($user['avatar']) && file_exists('../' . $user['avatar'])) {
                    unlink('../' . $user['avatar']);
                }
                
                $_SESSION['alert'] = [
                    'type' => 'success',
                    'message' => '用户删除成功'
                ];
            } else {
                // 回滚事务
                $conn->rollback();
                
                $_SESSION['alert'] = [
                    'type' => 'danger',
                    'message' => '用户删除失败'
                ];
            }
        } catch (Exception $e) {
            // 回滚事务
            $conn->rollback();
            
            $_SESSION['alert'] = [
                'type' => 'danger',
                'message' => '用户删除失败：' . $e->getMessage()
            ];
        }
        
        header('Location: users.php');
        exit;
        break;
        
    case 'certifications':
        // 用户认证管理
        if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
            $_SESSION['alert'] = [
                'type' => 'danger',
                'message' => '无效的用户ID'
            ];
            header('Location: users.php');
            exit;
        }
        
        $userId = intval($_GET['id']);
        $user = getUserInfo($userId);
        
        if (!$user) {
            $_SESSION['alert'] = [
                'type' => 'danger',
                'message' => '用户不存在或已被删除'
            ];
            header('Location: users.php');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_POST['add_certification'])) {
                // 添加认证
                $certName = sanitizeInput($_POST['cert_name']);
                $certDate = sanitizeInput($_POST['cert_date']);
                $certStatus = sanitizeInput($_POST['cert_status']);
                
                // 检查必填字段
                if (empty($certName)) {
                    $_SESSION['alert'] = [
                        'type' => 'danger',
                        'message' => '认证名称不能为空'
                    ];
                } else {
                    // 处理证书文件上传
                    $certFile = '';
                    if (isset($_FILES['cert_file']) && $_FILES['cert_file']['error'] === UPLOAD_ERR_OK) {
                        $uploadDir = '../assets/uploads/certifications/';
                        if (!file_exists($uploadDir)) {
                            mkdir($uploadDir, 0777, true);
                        }
                        
                        $fileName = time() . '_' . basename($_FILES['cert_file']['name']);
                        $targetPath = $uploadDir . $fileName;
                        
                        if (move_uploaded_file($_FILES['cert_file']['tmp_name'], $targetPath)) {
                            $certFile = 'assets/uploads/certifications/' . $fileName;
                        } else {
                            $_SESSION['alert'] = [
                                'type' => 'danger',
                                'message' => '证书文件上传失败'
                            ];
                            break;
                        }
                    }
                    
                    // 保存认证信息
                    $conn = getDbConnection();
                    
                    // 检查认证表是否存在
                    $tableExists = $conn->query("SHOW TABLES LIKE 'user_certifications'")->num_rows > 0;
                    
                    if (!$tableExists) {
                        // 创建认证表
                        $conn->query("
                            CREATE TABLE IF NOT EXISTS `user_certifications` (
                              `id` int(11) NOT NULL AUTO_INCREMENT,
                              `user_id` int(11) NOT NULL,
                              `cert_name` varchar(255) NOT NULL,
                              `cert_file` varchar(255) DEFAULT NULL,
                              `cert_date` date DEFAULT NULL,
                              `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
                              `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                              PRIMARY KEY (`id`),
                              KEY `user_id` (`user_id`)
                            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
                        ");
                    }
                    
                    $stmt = $conn->prepare("INSERT INTO user_certifications (user_id, cert_name, cert_file, cert_date, status) VALUES (?, ?, ?, ?, ?)");
                    $stmt->bind_param("issss", $userId, $certName, $certFile, $certDate, $certStatus);
                    
                    if ($stmt->execute()) {
                        $_SESSION['alert'] = [
                            'type' => 'success',
                            'message' => '认证添加成功'
                        ];
                    } else {
                        $_SESSION['alert'] = [
                            'type' => 'danger',
                            'message' => '认证添加失败：' . $conn->error
                        ];
                    }
                    
                    $stmt->close();
                }
            } elseif (isset($_POST['update_certification'])) {
                // 更新认证
                $certId = intval($_POST['cert_id']);
                $certStatus = sanitizeInput($_POST['cert_status']);
                
                $conn = getDbConnection();
                $stmt = $conn->prepare("UPDATE user_certifications SET status = ? WHERE id = ? AND user_id = ?");
                $stmt->bind_param("sii", $certStatus, $certId, $userId);
                
                if ($stmt->execute()) {
                    $_SESSION['alert'] = [
                        'type' => 'success',
                        'message' => '认证状态更新成功'
                    ];
                } else {
                    $_SESSION['alert'] = [
                        'type' => 'danger',
                        'message' => '认证状态更新失败：' . $conn->error
                    ];
                }
                
                $stmt->close();
            } elseif (isset($_POST['delete_certification'])) {
                // 删除认证
                $certId = intval($_POST['cert_id']);
                
                $conn = getDbConnection();
                
                // 获取认证文件信息
                $stmt = $conn->prepare("SELECT cert_file FROM user_certifications WHERE id = ? AND user_id = ?");
                $stmt->bind_param("ii", $certId, $userId);
                $stmt->execute();
                $result = $stmt->get_result();
                $certificationFile = $result->fetch_assoc();
                $stmt->close();
                
                // 删除认证记录
                $stmt = $conn->prepare("DELETE FROM user_certifications WHERE id = ? AND user_id = ?");
                $stmt->bind_param("ii", $certId, $userId);
                
                if ($stmt->execute()) {
                    // 删除证书文件
                    if (!empty($certificationFile['cert_file']) && file_exists('../' . $certificationFile['cert_file'])) {
                        unlink('../' . $certificationFile['cert_file']);
                    }
                    
                    $_SESSION['alert'] = [
                        'type' => 'success',
                        'message' => '认证删除成功'
                    ];
                } else {
                    $_SESSION['alert'] = [
                        'type' => 'danger',
                        'message' => '认证删除失败：' . $conn->error
                    ];
                }
                
                $stmt->close();
            }
        }
        
        // 显示认证管理页面
        showCertificationsPage($user);
        break;
        
    case 'list':
    default:
        // 分页参数
        $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
        $perPage = 20;
        $offset = ($page - 1) * $perPage;
        
        // 筛选参数
        $role = isset($_GET['role']) ? sanitizeInput($_GET['role']) : null;
        $search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : null;
        
        // 获取用户总数
        $conn = getDbConnection();
        $sql = "SELECT COUNT(*) as total FROM users WHERE 1=1";
        $params = [];
        $types = "";
        
        if ($role) {
            $sql .= " AND role = ?";
            $params[] = $role;
            $types .= "s";
        }
        
        if ($search) {
            $sql .= " AND (username LIKE ? OR email LIKE ? OR nickname LIKE ?)";
            $searchParam = "%{$search}%";
            $params[] = $searchParam;
            $params[] = $searchParam;
            $params[] = $searchParam;
            $types .= "sss";
        }
        
        $stmt = $conn->prepare($sql);
        
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $totalUsers = $result->fetch_assoc()['total'];
        $stmt->close();
        
        // 获取用户列表
        $sql = "SELECT * FROM users WHERE 1=1";
        
        if ($role) {
            $sql .= " AND role = ?";
        }
        
        if ($search) {
            $sql .= " AND (username LIKE ? OR email LIKE ? OR nickname LIKE ?)";
        }
        
        $sql .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
        
        $params[] = $perPage;
        $params[] = $offset;
        $types .= "ii";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        // 计算总页数
        $totalPages = ceil($totalUsers / $perPage);
        
        // 显示用户列表
        showUsersList($users, $role, $search, $page, $totalPages);
        break;
}

/**
 * 显示用户列表
 */
function showUsersList($users, $role, $search, $page, $totalPages) {
    ?>
    <div class="admin-content-header">
        <h2>用户管理</h2>
        <div class="header-actions">
            <a href="users.php?action=add" class="btn btn-primary">
                <i class="fas fa-plus"></i> 添加用户
            </a>
        </div>
    </div>
    
    <div class="admin-content-body">
        <!-- 筛选表单 -->
        <div class="filter-form">
            <form action="users.php" method="get" class="form-inline">
                <div class="form-group">
                    <select name="role" class="form-control">
                        <option value="">所有角色</option>
                        <option value="admin" <?php echo $role === 'admin' ? 'selected' : ''; ?>>管理员</option>
                        <option value="user" <?php echo $role === 'user' ? 'selected' : ''; ?>>普通用户</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <input type="text" name="search" class="form-control" placeholder="搜索用户名、邮箱或昵称" value="<?php echo $search; ?>">
                </div>
                
                <button type="submit" class="btn btn-secondary">
                    <i class="fas fa-search"></i> 筛选
                </button>
                
                <?php if ($role || $search): ?>
                <a href="users.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> 清除筛选
                </a>
                <?php endif; ?>
            </form>
        </div>
        
        <!-- 用户列表 -->
        <div class="table-responsive">
            <form action="users.php?action=bulk" method="post" class="bulk-action-form">
                <table class="table sortable-table" id="users-table">
                    <thead>
                        <tr>
                            <th width="20">
                                <input type="checkbox" class="select-all">
                            </th>
                            <th class="sortable">ID</th>
                            <th>头像</th>
                            <th class="sortable">用户名</th>
                            <th class="sortable">邮箱</th>
                            <th class="sortable">昵称</th>
                            <th class="sortable">角色</th>
                            <th class="sortable">注册日期</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="9" class="text-center">没有找到用户</td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td>
                                    <input type="checkbox" name="selected[]" value="<?php echo $user['id']; ?>" class="select-item">
                                </td>
                                <td><?php echo $user['id']; ?></td>
                                <td>
                                    <?php if (!empty($user['avatar'])): ?>
                                    <img src="../<?php echo $user['avatar']; ?>" alt="用户头像" class="user-avatar-preview">
                                    <?php else: ?>
                                    <div class="no-avatar"><i class="fas fa-user"></i></div>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $user['username']; ?></td>
                                <td><?php echo $user['email']; ?></td>
                                <td><?php echo $user['nickname'] ?: '-'; ?></td>
                                <td>
                                    <?php if ($user['role'] === 'admin'): ?>
                                    <span class="badge badge-primary">管理员</span>
                                    <?php else: ?>
                                    <span class="badge badge-secondary">普通用户</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('Y-m-d', strtotime($user['created_at'])); ?></td>
                                <td class="actions-cell">
                                    <a href="../profile.php?id=<?php echo $user['id']; ?>" class="btn-icon" title="查看主页" target="_blank">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="users.php?action=edit&id=<?php echo $user['id']; ?>" class="btn-icon" title="编辑">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="users.php?action=certifications&id=<?php echo $user['id']; ?>" class="btn-icon" title="认证管理">
                                        <i class="fas fa-certificate"></i>
                                    </a>
                                    <?php if ($user['id'] !== getCurrentUserId()): ?>
                                    <a href="users.php?action=delete&id=<?php echo $user['id']; ?>" class="btn-icon btn-delete" title="删除" data-confirm="确定要删除此用户吗？此操作将同时删除该用户的所有文章和评论，且不可撤销。">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
                
                <?php if (!empty($users)): ?>
                <div class="bulk-actions">
                    <select name="action" class="form-control bulk-action-select">
                        <option value="">批量操作</option>
                        <option value="set_role_user">设为普通用户</option>
                        <option value="set_role_admin">设为管理员</option>
                        <option value="delete">删除</option>
                    </select>
                    <button type="button" class="btn btn-secondary bulk-action-btn">应用</button>
                </div>
                <?php endif; ?>
            </form>
        </div>
        
        <!-- 分页 -->
        <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <ul>
                <?php if ($page > 1): ?>
                <li><a href="users.php?page=<?php echo ($page - 1); ?>&role=<?php echo $role; ?>&search=<?php echo $search; ?>"><i class="fas fa-chevron-left"></i></a></li>
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
                    echo '<li><a href="users.php?page=1&role=' . $role . '&search=' . $search . '">1</a></li>';
                    if ($startPage > 2) {
                        echo '<li class="separator">...</li>';
                    }
                }
                
                // 显示页码
                for ($i = $startPage; $i <= $endPage; $i++) {
                    if ($i === $page) {
                        echo '<li class="active"><span>' . $i . '</span></li>';
                    } else {
                        echo '<li><a href="users.php?page=' . $i . '&role=' . $role . '&search=' . $search . '">' . $i . '</a></li>';
                    }
                }
                
                // 显示最后一页
                if ($endPage < $totalPages) {
                    if ($endPage < $totalPages - 1) {
                        echo '<li class="separator">...</li>';
                    }
                    echo '<li><a href="users.php?page=' . $totalPages . '&role=' . $role . '&search=' . $search . '">' . $totalPages . '</a></li>';
                }
                ?>
                
                <?php if ($page < $totalPages): ?>
                <li><a href="users.php?page=<?php echo ($page + 1); ?>&role=<?php echo $role; ?>&search=<?php echo $search; ?>"><i class="fas fa-chevron-right"></i></a></li>
                <?php else: ?>
                <li class="disabled"><span><i class="fas fa-chevron-right"></i></span></li>
                <?php endif; ?>
            </ul>
        </div>
        <?php endif; ?>
    </div>
    
    <style>
    .filter-form {
        display: flex;
        margin-bottom: 20px;
    }
    
    .form-inline {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
    }
    
    .user-avatar-preview {
        width: 40px;
        height: 40px;
        object-fit: cover;
        border-radius: 50%;
    }
    
    .no-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background-color: #f4f6f9;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #ccc;
    }
    
    .badge {
        display: inline-block;
        padding: 3px 7px;
        border-radius: 3px;
        font-size: 12px;
        font-weight: 600;
    }
    
    .badge-primary {
        background-color: var(--admin-primary);
        color: #fff;
    }
    
    .badge-secondary {
        background-color: var(--admin-secondary);
        color: #fff;
    }
    
    .bulk-actions {
        display: flex;
        gap: 10px;
        margin-top: 10px;
    }
    </style>
    <?php
}

/**
 * 显示添加用户表单
 */
function showAddForm() {
    ?>
    <div class="admin-content-header">
        <h2>添加用户</h2>
        <div class="header-actions">
            <a href="users.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> 返回用户列表
            </a>
        </div>
    </div>
    
    <div class="admin-content-body">
        <div class="form-container">
            <form action="users.php?action=add" method="post" enctype="multipart/form-data">
                <div class="form-row">
                    <div class="form-group">
                        <label for="username">用户名 <span class="required">*</span></label>
                        <input type="text" id="username" name="username" class="form-control" required>
                        <div class="help-text">用户名只能包含字母、数字和下划线</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">电子邮箱 <span class="required">*</span></label>
                        <input type="email" id="email" name="email" class="form-control" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="password">密码 <span class="required">*</span></label>
                        <input type="password" id="password" name="password" class="form-control" required>
                        <div class="help-text">密码长度至少为6个字符</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">确认密码 <span class="required">*</span></label>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="nickname">昵称</label>
                        <input type="text" id="nickname" name="nickname" class="form-control">
                        <div class="help-text">昵称将显示在网站上，留空则使用用户名</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="role">用户角色</label>
                        <select id="role" name="role" class="form-control">
                            <option value="user">普通用户</option>
                            <option value="admin">管理员</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> 添加用户
                    </button>
                </div>
            </form>
        </div>
    </div>
    <?php
}

/**
 * 显示编辑用户表单
 */
function showEditForm($user) {
    ?>
    <div class="admin-content-header">
        <h2>编辑用户</h2>
        <div class="header-actions">
            <a href="users.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> 返回用户列表
            </a>
        </div>
    </div>
    
    <div class="admin-content-body">
        <div class="form-container">
            <form action="users.php?action=edit&id=<?php echo $user['id']; ?>" method="post" enctype="multipart/form-data">
                <div class="form-row">
                    <div class="form-group">
                        <label for="username">用户名 <span class="required">*</span></label>
                        <input type="text" id="username" name="username" class="form-control" value="<?php echo $user['username']; ?>" required>
                        <div class="help-text">用户名只能包含字母、数字和下划线</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">电子邮箱 <span class="required">*</span></label>
                        <input type="email" id="email" name="email" class="form-control" value="<?php echo $user['email']; ?>" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="password">新密码</label>
                        <input type="password" id="password" name="password" class="form-control">
                        <div class="help-text">如果不修改密码，请留空</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">确认密码</label>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="nickname">昵称</label>
                        <input type="text" id="nickname" name="nickname" class="form-control" value="<?php echo $user['nickname']; ?>">
                        <div class="help-text">昵称将显示在网站上，留空则使用用户名</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="role">用户角色</label>
                        <select id="role" name="role" class="form-control">
                            <option value="user" <?php echo $user['role'] === 'user' ? 'selected' : ''; ?>>普通用户</option>
                            <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>管理员</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="avatar">头像</label>
                    <input type="file" id="avatar" name="avatar" class="form-control" accept="image/*" data-preview="avatar-preview">
                    <div class="help-text">建议尺寸：200x200像素，最大文件大小：2MB</div>
                    
                    <?php if (!empty($user['avatar'])): ?>
                    <div class="current-avatar">
                        <p>当前头像：</p>
                        <img id="avatar-preview" src="../<?php echo $user['avatar']; ?>" alt="用户头像" class="avatar-preview">
                    </div>
                    <?php else: ?>
                    <img id="avatar-preview" src="" alt="用户头像" class="avatar-preview">
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="bio">个人简介</label>
                    <textarea id="bio" name="bio" class="form-control" rows="4"><?php echo $user['bio']; ?></textarea>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> 更新用户
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <style>
    .current-avatar {
        margin-top: 10px;
    }
    
    .current-avatar p {
        margin-bottom: 5px;
        font-size: 14px;
        color: var(--admin-gray);
    }
    
    .avatar-preview {
        width: 100px;
        height: 100px;
        object-fit: cover;
        border-radius: 50%;
        margin-top: 10px;
        display: <?php echo !empty($user['avatar']) ? 'block' : 'none'; ?>;
    }
    
    .required {
        color: var(--admin-danger);
    }
    </style>
    <?php
}

/**
 * 显示用户认证管理页面
 */
function showCertificationsPage($user) {
    // 获取当前用户的认证列表
    $conn = getDbConnection();
    
    // 检查认证表是否存在
    $tableExists = $conn->query("SHOW TABLES LIKE 'user_certifications'")->num_rows > 0;
    
    $certifications = [];
    if ($tableExists) {
        $stmt = $conn->prepare("SELECT * FROM user_certifications WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->bind_param("i", $user['id']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $certifications[] = $row;
        }
        
        $stmt->close();
    }
    ?>
    <div class="admin-content-header">
        <h2>用户认证管理 - <?php echo $user['username']; ?></h2>
        <div class="header-actions">
            <a href="users.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> 返回用户列表
            </a>
        </div>
    </div>
    
    <div class="admin-content-body">
        <div class="user-info-card">
            <div class="user-avatar">
                <?php if (!empty($user['avatar'])): ?>
                <img src="../<?php echo $user['avatar']; ?>" alt="用户头像">
                <?php else: ?>
                <div class="no-avatar"><i class="fas fa-user"></i></div>
                <?php endif; ?>
            </div>
            <div class="user-details">
                <h3><?php echo $user['nickname'] ?: $user['username']; ?></h3>
                <p><strong>用户名：</strong><?php echo $user['username']; ?></p>
                <p><strong>邮箱：</strong><?php echo $user['email']; ?></p>
                <p><strong>角色：</strong>
                    <?php if ($user['role'] === 'admin'): ?>
                    <span class="badge badge-primary">管理员</span>
                    <?php else: ?>
                    <span class="badge badge-secondary">普通用户</span>
                    <?php endif; ?>
                </p>
                <p><strong>注册时间：</strong><?php echo date('Y-m-d H:i', strtotime($user['created_at'])); ?></p>
                <p><strong>最后登录：</strong><?php echo !empty($user['last_login']) ? date('Y-m-d H:i', strtotime($user['last_login'])) : '从未登录'; ?></p>
            </div>
        </div>
        
        <div class="certification-section">
            <div class="section-header">
                <h3>添加新认证</h3>
            </div>
            
            <div class="section-body">
                <form action="users.php?action=certifications&id=<?php echo $user['id']; ?>" method="post" enctype="multipart/form-data">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="cert_name">认证名称 <span class="required">*</span></label>
                            <input type="text" id="cert_name" name="cert_name" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="cert_date">认证日期</label>
                            <input type="date" id="cert_date" name="cert_date" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="cert_file">证书文件</label>
                            <input type="file" id="cert_file" name="cert_file" class="form-control">
                            <div class="help-text">支持PDF、JPG、PNG格式，最大文件大小：10MB</div>
                        </div>
                        
                        <div class="form-group">
                            <label for="cert_status">认证状态</label>
                            <select id="cert_status" name="cert_status" class="form-control">
                                <option value="pending">待审核</option>
                                <option value="approved">已通过</option>
                                <option value="rejected">已拒绝</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" name="add_certification" class="btn btn-primary">
                            <i class="fas fa-plus"></i> 添加认证
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="certification-list-section">
            <div class="section-header">
                <h3>认证列表</h3>
            </div>
            
            <div class="section-body">
                <?php if (empty($certifications)): ?>
                <div class="empty-message">该用户暂无认证记录</div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>认证名称</th>
                                <th>证书文件</th>
                                <th>认证日期</th>
                                <th>状态</th>
                                <th>添加时间</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($certifications as $cert): ?>
                            <tr>
                                <td><?php echo $cert['cert_name']; ?></td>
                                <td>
                                    <?php if (!empty($cert['cert_file'])): ?>
                                    <a href="../<?php echo $cert['cert_file']; ?>" target="_blank" class="btn-icon" title="查看证书">
                                        <i class="fas fa-file-alt"></i> 查看
                                    </a>
                                    <?php else: ?>
                                    <span class="no-file">无文件</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo !empty($cert['cert_date']) ? date('Y-m-d', strtotime($cert['cert_date'])) : '-'; ?></td>
                                <td>
                                    <?php 
                                    $statusClass = '';
                                    $statusText = '';
                                    
                                    switch ($cert['status']) {
                                        case 'approved':
                                            $statusClass = 'status-approved';
                                            $statusText = '已通过';
                                            break;
                                        case 'rejected':
                                            $statusClass = 'status-rejected';
                                            $statusText = '已拒绝';
                                            break;
                                        default:
                                            $statusClass = 'status-pending';
                                            $statusText = '待审核';
                                    }
                                    ?>
                                    <span class="status-badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                                </td>
                                <td><?php echo date('Y-m-d', strtotime($cert['created_at'])); ?></td>
                                <td class="actions-cell">
                                    <div class="dropdown">
                                        <button class="btn-icon dropdown-toggle" data-toggle="dropdown">
                                            <i class="fas fa-ellipsis-v"></i>
                                        </button>
                                        <div class="dropdown-menu">
                                            <form action="users.php?action=certifications&id=<?php echo $user['id']; ?>" method="post">
                                                <input type="hidden" name="cert_id" value="<?php echo $cert['id']; ?>">
                                                
                                                <div class="dropdown-item">
                                                    <label>
                                                        <select name="cert_status" class="form-control">
                                                            <option value="pending" <?php echo $cert['status'] === 'pending' ? 'selected' : ''; ?>>待审核</option>
                                                            <option value="approved" <?php echo $cert['status'] === 'approved' ? 'selected' : ''; ?>>已通过</option>
                                                            <option value="rejected" <?php echo $cert['status'] === 'rejected' ? 'selected' : ''; ?>>已拒绝</option>
                                                        </select>
                                                    </label>
                                                </div>
                                                
                                                <div class="dropdown-item">
                                                    <button type="submit" name="update_certification" class="btn btn-sm btn-info">
                                                        <i class="fas fa-save"></i> 更新状态
                                                    </button>
                                                </div>
                                                
                                                <div class="dropdown-divider"></div>
                                                
                                                <div class="dropdown-item">
                                                    <button type="submit" name="delete_certification" class="btn btn-sm btn-danger" onclick="return confirm('确定要删除此认证记录吗？此操作不可撤销。')">
                                                        <i class="fas fa-trash"></i> 删除认证
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <style>
    .user-info-card {
        display: flex;
        background-color: #f8f9fa;
        border-radius: 5px;
        padding: 20px;
        margin-bottom: 30px;
    }
    
    .user-avatar {
        flex: 0 0 100px;
        margin-right: 20px;
    }
    
    .user-avatar img {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        object-fit: cover;
    }
    
    .user-avatar .no-avatar {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        background-color: #e9ecef;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 40px;
        color: #adb5bd;
    }
    
    .user-details {
        flex: 1;
    }
    
    .user-details h3 {
        margin-top: 0;
        margin-bottom: 15px;
    }
    
    .user-details p {
        margin-bottom: 8px;
    }
    
    .certification-section,
    .certification-list-section {
        background-color: #fff;
        border-radius: 5px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        margin-bottom: 30px;
    }
    
    .section-header {
        padding: 15px 20px;
        border-bottom: 1px solid #e9ecef;
    }
    
    .section-header h3 {
        margin: 0;
        font-size: 18px;
    }
    
    .section-body {
        padding: 20px;
    }
    
    .empty-message {
        padding: 20px;
        text-align: center;
        color: #6c757d;
    }
    
    .status-badge {
        display: inline-block;
        padding: 2px 8px;
        border-radius: 3px;
        font-size: 12px;
    }
    
    .status-approved {
        background-color: #d4edda;
        color: #155724;
    }
    
    .status-rejected {
        background-color: #f8d7da;
        color: #721c24;
    }
    
    .status-pending {
        background-color: #fff3cd;
        color: #856404;
    }
    
    .no-file {
        color: #6c757d;
        font-style: italic;
    }
    
    .dropdown {
        position: relative;
        display: inline-block;
    }
    
    .dropdown-toggle {
        background: none;
        border: none;
        cursor: pointer;
    }
    
    .dropdown-menu {
        display: none;
        position: absolute;
        right: 0;
        background-color: #fff;
        min-width: 200px;
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        z-index: 1;
        border-radius: 3px;
        padding: 8px 0;
    }
    
    .dropdown-item {
        padding: 8px 15px;
    }
    
    .dropdown-divider {
        border-top: 1px solid #e9ecef;
        margin: 8px 0;
    }
    
    .dropdown:hover .dropdown-menu {
        display: block;
    }
    </style>
    
    <script>
    $(document).ready(function() {
        // 初始化下拉菜单
        $('.dropdown-toggle').click(function(e) {
            e.preventDefault();
            $(this).siblings('.dropdown-menu').toggle();
        });
        
        // 点击其他区域关闭下拉菜单
        $(document).click(function(e) {
            var dropdown = $('.dropdown');
            if (!dropdown.is(e.target) && dropdown.has(e.target).length === 0) {
                $('.dropdown-menu').hide();
            }
        });
    });
    </script>
    <?php
}

// 引入管理后台页脚
require_once 'includes/admin_footer.php';
?> 