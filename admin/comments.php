<?php
/**
 * 评论管理页面
 */

// 页面信息
$pageTitle = '评论管理';

// 引入管理后台头部
require_once 'includes/admin_header.php';

// 获取操作类型
$action = isset($_GET['action']) ? $_GET['action'] : 'list';

// 处理评论操作
switch ($action) {
    case 'edit':
        // 编辑评论
        if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
            $_SESSION['alert'] = [
                'type' => 'danger',
                'message' => '无效的评论ID'
            ];
            header('Location: comments.php');
            exit;
        }
        
        $commentId = intval($_GET['id']);
        
        // 获取评论信息
        $conn = getDbConnection();
        $stmt = $conn->prepare("
            SELECT c.*, a.title as article_title, u.username, u.nickname
            FROM comments c
            JOIN articles a ON c.article_id = a.id
            JOIN users u ON c.user_id = u.id
            WHERE c.id = ?
        ");
        $stmt->bind_param("i", $commentId);
        $stmt->execute();
        $result = $stmt->get_result();
        $comment = $result->fetch_assoc();
        $stmt->close();
        
        if (!$comment) {
            $_SESSION['alert'] = [
                'type' => 'danger',
                'message' => '评论不存在或已被删除'
            ];
            header('Location: comments.php');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // 更新评论
            $content = sanitizeInput($_POST['content']);
            
            if (empty($content)) {
                $_SESSION['alert'] = [
                    'type' => 'danger',
                    'message' => '评论内容不能为空'
                ];
            } else {
                $stmt = $conn->prepare("UPDATE comments SET content = ?, updated_at = NOW() WHERE id = ?");
                $stmt->bind_param("si", $content, $commentId);
                $result = $stmt->execute();
                $stmt->close();
                
                if ($result) {
                    $_SESSION['alert'] = [
                        'type' => 'success',
                        'message' => '评论更新成功'
                    ];
                    header('Location: comments.php');
                    exit;
                } else {
                    $_SESSION['alert'] = [
                        'type' => 'danger',
                        'message' => '评论更新失败：' . $conn->error
                    ];
                }
            }
        }
        
        // 显示编辑表单
        showEditForm($comment);
        break;
        
    case 'delete':
        // 删除评论
        if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
            $_SESSION['alert'] = [
                'type' => 'danger',
                'message' => '无效的评论ID'
            ];
            header('Location: comments.php');
            exit;
        }
        
        $commentId = intval($_GET['id']);
        
        // 删除评论
        $conn = getDbConnection();
        $stmt = $conn->prepare("DELETE FROM comments WHERE id = ?");
        $stmt->bind_param("i", $commentId);
        $result = $stmt->execute();
        $stmt->close();
        
        if ($result) {
            $_SESSION['alert'] = [
                'type' => 'success',
                'message' => '评论删除成功'
            ];
        } else {
            $_SESSION['alert'] = [
                'type' => 'danger',
                'message' => '评论删除失败：' . $conn->error
            ];
        }
        
        header('Location: comments.php');
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
                        // 将评论ID转换为整数数组
                        $commentIds = array_map('intval', $selected);
                        $placeholders = implode(',', array_fill(0, count($commentIds), '?'));
                        
                        // 批量删除评论
                        $types = str_repeat('i', count($commentIds));
                        $stmt = $conn->prepare("DELETE FROM comments WHERE id IN ($placeholders)");
                        $stmt->bind_param($types, ...$commentIds);
                        $result = $stmt->execute();
                        $stmt->close();
                        
                        if ($result) {
                            $_SESSION['alert'] = [
                                'type' => 'success',
                                'message' => '选中的评论已成功删除'
                            ];
                        } else {
                            $_SESSION['alert'] = [
                                'type' => 'danger',
                                'message' => '批量删除评论失败：' . $conn->error
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
                    'message' => '请选择要操作的评论和操作类型'
                ];
            }
        }
        
        header('Location: comments.php');
        exit;
        break;
        
    case 'list':
    default:
        // 分页参数
        $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
        $perPage = 20;
        $offset = ($page - 1) * $perPage;
        
        // 筛选参数
        $articleId = isset($_GET['article_id']) ? intval($_GET['article_id']) : null;
        $userId = isset($_GET['user_id']) ? intval($_GET['user_id']) : null;
        $search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : null;
        
        // 获取评论总数
        $conn = getDbConnection();
        $sql = "SELECT COUNT(*) as total FROM comments c WHERE 1=1";
        $params = [];
        $types = "";
        
        if ($articleId) {
            $sql .= " AND c.article_id = ?";
            $params[] = $articleId;
            $types .= "i";
        }
        
        if ($userId) {
            $sql .= " AND c.user_id = ?";
            $params[] = $userId;
            $types .= "i";
        }
        
        if ($search) {
            $sql .= " AND c.content LIKE ?";
            $params[] = "%{$search}%";
            $types .= "s";
        }
        
        $stmt = $conn->prepare($sql);
        
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $totalComments = $result->fetch_assoc()['total'];
        $stmt->close();
        
        // 计算总页数
        $totalPages = ceil($totalComments / $perPage);
        
        // 获取评论列表
        $sql = "
            SELECT c.*, a.title as article_title, u.username, u.nickname, u.avatar
            FROM comments c
            JOIN articles a ON c.article_id = a.id
            JOIN users u ON c.user_id = u.id
            WHERE 1=1
        ";
        
        if ($articleId) {
            $sql .= " AND c.article_id = ?";
        }
        
        if ($userId) {
            $sql .= " AND c.user_id = ?";
        }
        
        if ($search) {
            $sql .= " AND c.content LIKE ?";
        }
        
        $sql .= " ORDER BY c.created_at DESC LIMIT ? OFFSET ?";
        
        $params[] = $perPage;
        $params[] = $offset;
        $types .= "ii";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $comments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        // 显示评论列表
        showCommentsList($comments, $articleId, $userId, $search, $page, $totalPages);
        break;
}

/**
 * 显示评论列表
 */
function showCommentsList($comments, $articleId, $userId, $search, $page, $totalPages) {
    ?>
    <div class="admin-content-header">
        <h2>评论管理</h2>
        <div class="header-actions">
            <a href="../index.php" class="btn btn-secondary" target="_blank">
                <i class="fas fa-external-link-alt"></i> 查看网站
            </a>
        </div>
    </div>
    
    <div class="admin-content-body">
        <!-- 筛选表单 -->
        <div class="filter-form">
            <form action="comments.php" method="get" class="form-inline">
                <?php if ($articleId): ?>
                <input type="hidden" name="article_id" value="<?php echo $articleId; ?>">
                <?php endif; ?>
                
                <?php if ($userId): ?>
                <input type="hidden" name="user_id" value="<?php echo $userId; ?>">
                <?php endif; ?>
                
                <div class="form-group">
                    <input type="text" name="search" class="form-control" placeholder="搜索评论内容" value="<?php echo $search; ?>">
                </div>
                
                <button type="submit" class="btn btn-secondary">
                    <i class="fas fa-search"></i> 筛选
                </button>
                
                <?php if ($search || $articleId || $userId): ?>
                <a href="comments.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> 清除筛选
                </a>
                <?php endif; ?>
            </form>
        </div>
        
        <!-- 筛选提示 -->
        <?php if ($articleId || $userId): ?>
        <div class="filter-info">
            <?php if ($articleId): ?>
            <div class="filter-badge">
                文章筛选: ID <?php echo $articleId; ?>
                <a href="comments.php<?php echo $userId ? '?user_id=' . $userId : ''; ?>" class="filter-remove"><i class="fas fa-times"></i></a>
            </div>
            <?php endif; ?>
            
            <?php if ($userId): ?>
            <div class="filter-badge">
                用户筛选: ID <?php echo $userId; ?>
                <a href="comments.php<?php echo $articleId ? '?article_id=' . $articleId : ''; ?>" class="filter-remove"><i class="fas fa-times"></i></a>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <!-- 评论列表 -->
        <div class="table-responsive">
            <form action="comments.php?action=bulk" method="post" class="bulk-action-form">
                <table class="table sortable-table" id="comments-table">
                    <thead>
                        <tr>
                            <th width="20">
                                <input type="checkbox" class="select-all">
                            </th>
                            <th class="sortable">ID</th>
                            <th>用户</th>
                            <th>评论内容</th>
                            <th class="sortable">文章</th>
                            <th class="sortable">发布日期</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($comments)): ?>
                        <tr>
                            <td colspan="7" class="text-center">没有找到评论</td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($comments as $comment): ?>
                            <tr>
                                <td>
                                    <input type="checkbox" name="selected[]" value="<?php echo $comment['id']; ?>" class="select-item">
                                </td>
                                <td><?php echo $comment['id']; ?></td>
                                <td>
                                    <div class="user-info">
                                        <?php if (!empty($comment['avatar'])): ?>
                                        <img src="../<?php echo $comment['avatar']; ?>" alt="用户头像" class="user-avatar-preview">
                                        <?php else: ?>
                                        <div class="no-avatar"><i class="fas fa-user"></i></div>
                                        <?php endif; ?>
                                        <div>
                                            <a href="comments.php?user_id=<?php echo $comment['user_id']; ?>" title="查看该用户的所有评论">
                                                <?php echo $comment['nickname'] ?: $comment['username']; ?>
                                            </a>
                                            <small>(ID: <?php echo $comment['user_id']; ?>)</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="comment-content">
                                        <?php echo htmlspecialchars($comment['content']); ?>
                                    </div>
                                </td>
                                <td>
                                    <a href="comments.php?article_id=<?php echo $comment['article_id']; ?>" title="查看该文章的所有评论">
                                        <?php echo $comment['article_title']; ?>
                                    </a>
                                    <small>(ID: <?php echo $comment['article_id']; ?>)</small>
                                </td>
                                <td><?php echo date('Y-m-d H:i', strtotime($comment['created_at'])); ?></td>
                                <td class="actions-cell">
                                    <a href="../article.php?id=<?php echo $comment['article_id']; ?>#comment-<?php echo $comment['id']; ?>" class="btn-icon" title="查看评论" target="_blank">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="comments.php?action=edit&id=<?php echo $comment['id']; ?>" class="btn-icon" title="编辑">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="comments.php?action=delete&id=<?php echo $comment['id']; ?>" class="btn-icon btn-delete" title="删除" data-confirm="确定要删除此评论吗？此操作不可撤销。">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
                
                <?php if (!empty($comments)): ?>
                <div class="bulk-actions">
                    <select name="action" class="form-control bulk-action-select">
                        <option value="">批量操作</option>
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
                <li><a href="comments.php?page=<?php echo ($page - 1); ?>&article_id=<?php echo $articleId; ?>&user_id=<?php echo $userId; ?>&search=<?php echo $search; ?>"><i class="fas fa-chevron-left"></i></a></li>
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
                    echo '<li><a href="comments.php?page=1&article_id=' . $articleId . '&user_id=' . $userId . '&search=' . $search . '">1</a></li>';
                    if ($startPage > 2) {
                        echo '<li class="separator">...</li>';
                    }
                }
                
                // 显示页码
                for ($i = $startPage; $i <= $endPage; $i++) {
                    if ($i === $page) {
                        echo '<li class="active"><span>' . $i . '</span></li>';
                    } else {
                        echo '<li><a href="comments.php?page=' . $i . '&article_id=' . $articleId . '&user_id=' . $userId . '&search=' . $search . '">' . $i . '</a></li>';
                    }
                }
                
                // 显示最后一页
                if ($endPage < $totalPages) {
                    if ($endPage < $totalPages - 1) {
                        echo '<li class="separator">...</li>';
                    }
                    echo '<li><a href="comments.php?page=' . $totalPages . '&article_id=' . $articleId . '&user_id=' . $userId . '&search=' . $search . '">' . $totalPages . '</a></li>';
                }
                ?>
                
                <?php if ($page < $totalPages): ?>
                <li><a href="comments.php?page=<?php echo ($page + 1); ?>&article_id=<?php echo $articleId; ?>&user_id=<?php echo $userId; ?>&search=<?php echo $search; ?>"><i class="fas fa-chevron-right"></i></a></li>
                <?php else: ?>
                <li class="disabled"><span><i class="fas fa-chevron-right"></i></span></li>
                <?php endif; ?>
            </ul>
        </div>
        <?php endif; ?>
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
    
    .filter-info {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-bottom: 20px;
    }
    
    .filter-badge {
        display: inline-flex;
        align-items: center;
        background-color: var(--admin-light-bg);
        padding: 5px 10px;
        border-radius: 20px;
        font-size: 14px;
    }
    
    .filter-remove {
        margin-left: 5px;
        color: var(--admin-danger);
    }
    
    .user-info {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .user-avatar-preview {
        width: 30px;
        height: 30px;
        object-fit: cover;
        border-radius: 50%;
    }
    
    .no-avatar {
        width: 30px;
        height: 30px;
        border-radius: 50%;
        background-color: #f4f6f9;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #ccc;
    }
    
    .comment-content {
        max-width: 300px;
        max-height: 80px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: normal;
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
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
 * 显示编辑评论表单
 */
function showEditForm($comment) {
    ?>
    <div class="admin-content-header">
        <h2>编辑评论</h2>
        <div class="header-actions">
            <a href="comments.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> 返回评论列表
            </a>
        </div>
    </div>
    
    <div class="admin-content-body">
        <div class="comment-header-info">
            <div class="comment-meta">
                <span class="comment-author">
                    <strong>评论者：</strong> <?php echo $comment['nickname'] ?: $comment['username']; ?>
                </span>
                <span class="comment-article">
                    <strong>文章：</strong> <a href="../article.php?id=<?php echo $comment['article_id']; ?>" target="_blank"><?php echo $comment['article_title']; ?></a>
                </span>
                <span class="comment-date">
                    <strong>评论时间：</strong> <?php echo date('Y-m-d H:i:s', strtotime($comment['created_at'])); ?>
                </span>
            </div>
        </div>
        
        <div class="form-container">
            <form action="comments.php?action=edit&id=<?php echo $comment['id']; ?>" method="post">
                <div class="form-group">
                    <label for="content">评论内容</label>
                    <textarea id="content" name="content" class="form-control" rows="5" required><?php echo $comment['content']; ?></textarea>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> 更新评论
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <style>
    .comment-header-info {
        margin-bottom: 20px;
        padding: 15px;
        background-color: var(--admin-light-bg);
        border-radius: 5px;
    }
    
    .comment-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 20px;
    }
    
    .comment-meta span {
        display: flex;
        align-items: center;
    }
    
    .comment-meta strong {
        margin-right: 5px;
    }
    </style>
    <?php
}

// 引入管理后台页脚
require_once 'includes/admin_footer.php';
?> 