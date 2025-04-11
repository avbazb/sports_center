<?php
/**
 * 文章管理页面
 */

// 页面信息
$pageTitle = '文章管理';

// 引入管理后台头部
require_once 'includes/admin_header.php';

// 获取操作类型
$action = isset($_GET['action']) ? $_GET['action'] : 'list';

// 获取分类列表
$categories = getAllCategories();

// 处理文章操作
switch ($action) {
    case 'add':
        // 添加文章
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // 验证数据
            if (empty($_POST['title']) || empty($_POST['content']) || empty($_POST['category_id'])) {
                $_SESSION['alert'] = [
                    'type' => 'danger',
                    'message' => '请填写所有必填字段'
                ];
            } else {
                // 准备数据
                $data = [
                    'title' => sanitizeInput($_POST['title']),
                    'content' => $_POST['content'],
                    'category_id' => intval($_POST['category_id']),
                    'author_id' => getCurrentUserId(),
                    'is_published' => isset($_POST['is_published']) ? 1 : 0
                ];
                
                // 保存文章
                $result = saveArticle($data);
                
                if ($result['status']) {
                    // 处理缩略图上传
                    if (!empty($_FILES['thumbnail']['name'])) {
                        $imageResult = saveArticleThumbnail($result['article_id'], $_FILES['thumbnail']);
                        
                        if (!$imageResult['status']) {
                            $_SESSION['alert'] = [
                                'type' => 'warning',
                                'message' => '文章已保存，但缩略图上传失败：' . $imageResult['message']
                            ];
                            header('Location: articles.php');
                            exit;
                        }
                    }
                    
                    $_SESSION['alert'] = [
                        'type' => 'success',
                        'message' => '文章添加成功'
                    ];
                    header('Location: articles.php');
                    exit;
                } else {
                    $_SESSION['alert'] = [
                        'type' => 'danger',
                        'message' => '文章添加失败：' . $result['message']
                    ];
                }
            }
        }
        
        // 显示添加表单
        showAddForm();
        break;
        
    case 'edit':
        // 编辑文章
        if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
            $_SESSION['alert'] = [
                'type' => 'danger',
                'message' => '无效的文章ID'
            ];
            header('Location: articles.php');
            exit;
        }
        
        $articleId = intval($_GET['id']);
        $article = getArticleById($articleId);
        
        if (!$article) {
            $_SESSION['alert'] = [
                'type' => 'danger',
                'message' => '文章不存在或已被删除'
            ];
            header('Location: articles.php');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // 验证数据
            if (empty($_POST['title']) || empty($_POST['content']) || empty($_POST['category_id'])) {
                $_SESSION['alert'] = [
                    'type' => 'danger',
                    'message' => '请填写所有必填字段'
                ];
            } else {
                // 准备数据
                $data = [
                    'title' => sanitizeInput($_POST['title']),
                    'content' => $_POST['content'],
                    'category_id' => intval($_POST['category_id']),
                    'is_published' => isset($_POST['is_published']) ? 1 : 0
                ];
                
                // 保存文章
                $result = saveArticle($data, $articleId);
                
                if ($result['status']) {
                    // 处理缩略图上传
                    if (!empty($_FILES['thumbnail']['name'])) {
                        $imageResult = saveArticleThumbnail($articleId, $_FILES['thumbnail']);
                        
                        if (!$imageResult['status']) {
                            $_SESSION['alert'] = [
                                'type' => 'warning',
                                'message' => '文章已保存，但缩略图上传失败：' . $imageResult['message']
                            ];
                            header('Location: articles.php');
                            exit;
                        }
                    }
                    
                    $_SESSION['alert'] = [
                        'type' => 'success',
                        'message' => '文章更新成功'
                    ];
                    header('Location: articles.php');
                    exit;
                } else {
                    $_SESSION['alert'] = [
                        'type' => 'danger',
                        'message' => '文章更新失败：' . $result['message']
                    ];
                }
            }
        }
        
        // 显示编辑表单
        showEditForm($article);
        break;
        
    case 'delete':
        // 删除文章
        if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
            $_SESSION['alert'] = [
                'type' => 'danger',
                'message' => '无效的文章ID'
            ];
            header('Location: articles.php');
            exit;
        }
        
        $articleId = intval($_GET['id']);
        
        // 检查文章是否存在
        $article = getArticleById($articleId);
        
        if (!$article) {
            $_SESSION['alert'] = [
                'type' => 'danger',
                'message' => '文章不存在或已被删除'
            ];
            header('Location: articles.php');
            exit;
        }
        
        // 删除文章
        $conn = getDbConnection();
        
        // 先删除相关的评论
        $stmt = $conn->prepare("DELETE FROM comments WHERE article_id = ?");
        $stmt->bind_param("i", $articleId);
        $stmt->execute();
        $stmt->close();
        
        // 删除文章
        $stmt = $conn->prepare("DELETE FROM articles WHERE id = ?");
        $stmt->bind_param("i", $articleId);
        $result = $stmt->execute();
        $stmt->close();
        
        if ($result) {
            // 删除缩略图文件
            if (!empty($article['thumbnail']) && file_exists('../' . $article['thumbnail'])) {
                unlink('../' . $article['thumbnail']);
            }
            
            $_SESSION['alert'] = [
                'type' => 'success',
                'message' => '文章删除成功'
            ];
        } else {
            $_SESSION['alert'] = [
                'type' => 'danger',
                'message' => '文章删除失败'
            ];
        }
        
        header('Location: articles.php');
        exit;
        break;
        
    case 'list':
    default:
        // 分页参数
        $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
        $perPage = 20;
        $offset = ($page - 1) * $perPage;
        
        // 筛选参数
        $categoryId = isset($_GET['category']) ? intval($_GET['category']) : null;
        $search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : null;
        
        // 获取文章总数
        $conn = getDbConnection();
        $sql = "SELECT COUNT(*) as total FROM articles a 
                WHERE 1=1";
        $params = [];
        $types = "";
        
        if ($categoryId) {
            $sql .= " AND a.category_id = ?";
            $params[] = $categoryId;
            $types .= "i";
        }
        
        if ($search) {
            $sql .= " AND (a.title LIKE ? OR a.content LIKE ?)";
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
        $totalArticles = $result->fetch_assoc()['total'];
        $stmt->close();
        
        // 获取文章列表
        $sql = "SELECT a.*, c.name as category_name, u.username as author_name 
                FROM articles a 
                LEFT JOIN categories c ON a.category_id = c.id 
                LEFT JOIN users u ON a.author_id = u.id 
                WHERE 1=1";
        
        if ($categoryId) {
            $sql .= " AND a.category_id = ?";
        }
        
        if ($search) {
            $sql .= " AND (a.title LIKE ? OR a.content LIKE ?)";
        }
        
        $sql .= " ORDER BY a.created_at DESC LIMIT ? OFFSET ?";
        
        $params[] = $perPage;
        $params[] = $offset;
        $types .= "ii";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $articles = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        // 计算总页数
        $totalPages = ceil($totalArticles / $perPage);
        
        // 显示文章列表
        showArticlesList($articles, $categories, $categoryId, $search, $page, $totalPages);
        break;
}

/**
 * 显示文章列表
 */
function showArticlesList($articles, $categories, $categoryId, $search, $page, $totalPages) {
    ?>
    <div class="admin-content-header">
        <h2>文章管理</h2>
        <div class="header-actions">
            <a href="articles.php?action=add" class="btn btn-primary">
                <i class="fas fa-plus"></i> 添加文章
            </a>
        </div>
    </div>
    
    <div class="admin-content-body">
        <!-- 筛选表单 -->
        <div class="filter-form">
            <form action="articles.php" method="get" class="form-inline">
                <div class="form-group">
                    <select name="category" class="form-control">
                        <option value="">所有分类</option>
                        <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category['id']; ?>" <?php echo $categoryId == $category['id'] ? 'selected' : ''; ?>>
                            <?php echo $category['name']; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <input type="text" name="search" class="form-control" placeholder="搜索标题或内容" value="<?php echo $search; ?>">
                </div>
                
                <button type="submit" class="btn btn-secondary">
                    <i class="fas fa-search"></i> 筛选
                </button>
                
                <?php if ($categoryId || $search): ?>
                <a href="articles.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> 清除筛选
                </a>
                <?php endif; ?>
            </form>
        </div>
        
        <!-- 文章列表 -->
        <div class="table-responsive">
            <form action="articles.php?action=bulk" method="post" class="bulk-action-form">
                <table class="table sortable-table" id="articles-table">
                    <thead>
                        <tr>
                            <th width="20">
                                <input type="checkbox" class="select-all">
                            </th>
                            <th class="sortable">ID</th>
                            <th>缩略图</th>
                            <th class="sortable">标题</th>
                            <th class="sortable">分类</th>
                            <th class="sortable">作者</th>
                            <th class="sortable">发布日期</th>
                            <th class="sortable">状态</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($articles)): ?>
                        <tr>
                            <td colspan="9" class="text-center">没有找到文章</td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($articles as $article): ?>
                            <tr>
                                <td>
                                    <input type="checkbox" name="selected[]" value="<?php echo $article['id']; ?>" class="select-item">
                                </td>
                                <td><?php echo $article['id']; ?></td>
                                <td>
                                    <?php if (!empty($article['thumbnail'])): ?>
                                    <img src="../<?php echo $article['thumbnail']; ?>" alt="缩略图" class="thumbnail-preview">
                                    <?php else: ?>
                                    <div class="no-thumbnail"><i class="fas fa-image"></i></div>
                                    <?php endif; ?>
                                </td>
                                <td class="title-cell">
                                    <a href="../article.php?id=<?php echo $article['id']; ?>" target="_blank">
                                        <?php echo $article['title']; ?>
                                    </a>
                                </td>
                                <td><?php echo $article['category_name']; ?></td>
                                <td><?php echo $article['author_name']; ?></td>
                                <td><?php echo date('Y-m-d', strtotime($article['created_at'])); ?></td>
                                <td>
                                    <?php if ($article['is_published']): ?>
                                    <span class="badge badge-success">已发布</span>
                                    <?php else: ?>
                                    <span class="badge badge-secondary">草稿</span>
                                    <?php endif; ?>
                                </td>
                                <td class="actions-cell">
                                    <a href="articles.php?action=edit&id=<?php echo $article['id']; ?>" class="btn-icon" title="编辑">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="articles.php?action=delete&id=<?php echo $article['id']; ?>" class="btn-icon btn-delete" title="删除" data-confirm="确定要删除这篇文章吗？此操作不可撤销。">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
                
                <?php if (!empty($articles)): ?>
                <div class="bulk-actions">
                    <select name="action" class="form-control bulk-action-select">
                        <option value="">批量操作</option>
                        <option value="publish">发布</option>
                        <option value="unpublish">撤回</option>
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
                <li><a href="articles.php?page=<?php echo ($page - 1); ?>&category=<?php echo $categoryId; ?>&search=<?php echo $search; ?>"><i class="fas fa-chevron-left"></i></a></li>
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
                    echo '<li><a href="articles.php?page=1&category=' . $categoryId . '&search=' . $search . '">1</a></li>';
                    if ($startPage > 2) {
                        echo '<li class="separator">...</li>';
                    }
                }
                
                // 显示页码
                for ($i = $startPage; $i <= $endPage; $i++) {
                    if ($i === $page) {
                        echo '<li class="active"><span>' . $i . '</span></li>';
                    } else {
                        echo '<li><a href="articles.php?page=' . $i . '&category=' . $categoryId . '&search=' . $search . '">' . $i . '</a></li>';
                    }
                }
                
                // 显示最后一页
                if ($endPage < $totalPages) {
                    if ($endPage < $totalPages - 1) {
                        echo '<li class="separator">...</li>';
                    }
                    echo '<li><a href="articles.php?page=' . $totalPages . '&category=' . $categoryId . '&search=' . $search . '">' . $totalPages . '</a></li>';
                }
                ?>
                
                <?php if ($page < $totalPages): ?>
                <li><a href="articles.php?page=<?php echo ($page + 1); ?>&category=<?php echo $categoryId; ?>&search=<?php echo $search; ?>"><i class="fas fa-chevron-right"></i></a></li>
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
    
    .thumbnail-preview {
        width: 60px;
        height: 40px;
        object-fit: cover;
        border-radius: 4px;
    }
    
    .no-thumbnail {
        width: 60px;
        height: 40px;
        background-color: #f4f6f9;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #ccc;
        border-radius: 4px;
    }
    
    .badge {
        display: inline-block;
        padding: 3px 7px;
        border-radius: 3px;
        font-size: 12px;
        font-weight: 600;
    }
    
    .badge-success {
        background-color: var(--admin-success);
        color: #fff;
    }
    
    .badge-secondary {
        background-color: var(--admin-gray);
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
 * 显示添加文章表单
 */
function showAddForm() {
    global $categories;
    ?>
    <div class="admin-content-header">
        <h2>添加文章</h2>
        <div class="header-actions">
            <a href="articles.php" class="btn btn-secondary">
                <i class="fa fa-arrow-left"></i> 返回文章列表
            </a>
        </div>
    </div>
    
    <div class="admin-content-body">
        <div class="form-container">
            <form action="articles.php?action=add" method="post" enctype="multipart/form-data">
                <div class="form-row">
                    <div class="form-group">
                        <label for="title">标题 <span class="required">*</span></label>
                        <input type="text" id="title" name="title" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="category_id">分类 <span class="required">*</span></label>
                        <select id="category_id" name="category_id" class="form-control" required>
                            <option value="">选择分类</option>
                            <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>">
                                <?php echo $category['name']; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="thumbnail">缩略图</label>
                    <input type="file" id="thumbnail" name="thumbnail" class="form-control" accept="image/*" data-preview="image-preview">
                    <div class="help-text">建议尺寸：800x600像素，最大文件大小：2MB</div>
                    <img id="image-preview" class="image-preview" src="" alt="缩略图预览">
                </div>
                
                <div class="form-group">
                    <label for="content">内容 <span class="required">*</span></label>
                    <div class="editor-container">
                        <textarea id="content" name="content" class="form-control tinymce-editor" style="min-height:300px;" required></textarea>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="is_published" value="1" checked>
                        立即发布
                    </label>
                    <div class="help-text">取消勾选将保存为草稿</div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fa fa-save"></i> 保存文章
                    </button>
                </div>
            </form>
        </div>
    </div>
    <?php
}

/**
 * 显示编辑文章表单
 */
function showEditForm($article) {
    global $categories;
    ?>
    <div class="admin-content-header">
        <h2>编辑文章</h2>
        <div class="header-actions">
            <a href="articles.php" class="btn btn-secondary">
                <i class="fa fa-arrow-left"></i> 返回文章列表
            </a>
        </div>
    </div>
    
    <div class="admin-content-body">
        <div class="form-container">
            <form action="articles.php?action=edit&id=<?php echo $article['id']; ?>" method="post" enctype="multipart/form-data">
                <div class="form-row">
                    <div class="form-group">
                        <label for="title">标题 <span class="required">*</span></label>
                        <input type="text" id="title" name="title" class="form-control" value="<?php echo $article['title']; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="category_id">分类 <span class="required">*</span></label>
                        <select id="category_id" name="category_id" class="form-control" required>
                            <option value="">选择分类</option>
                            <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>" <?php echo $article['category_id'] == $category['id'] ? 'selected' : ''; ?>>
                                <?php echo $category['name']; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="thumbnail">缩略图</label>
                    <input type="file" id="thumbnail" name="thumbnail" class="form-control" accept="image/*" data-preview="image-preview">
                    <div class="help-text">建议尺寸：800x600像素，最大文件大小：2MB</div>
                    <?php if (!empty($article['thumbnail'])): ?>
                    <div class="current-thumbnail">
                        <p>当前缩略图：</p>
                        <img id="image-preview" class="image-preview" src="../<?php echo $article['thumbnail']; ?>" alt="缩略图预览" style="display: block;">
                    </div>
                    <?php else: ?>
                    <img id="image-preview" class="image-preview" src="" alt="缩略图预览">
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="content">内容 <span class="required">*</span></label>
                    <div class="editor-container">
                        <textarea id="content" name="content" class="form-control tinymce-editor" style="min-height:300px;" required><?php echo htmlspecialchars($article['content']); ?></textarea>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="is_published" value="1" <?php echo $article['is_published'] ? 'checked' : ''; ?>>
                        发布文章
                    </label>
                    <div class="help-text">取消勾选将保存为草稿</div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fa fa-save"></i> 更新文章
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <style>
    .current-thumbnail {
        margin-top: 10px;
    }
    
    .current-thumbnail p {
        margin-bottom: 5px;
        font-size: 14px;
        color: var(--admin-gray);
    }
    
    .checkbox-label {
        display: flex;
        align-items: center;
        gap: 10px;
        cursor: pointer;
    }
    
    .required {
        color: var(--admin-danger);
    }
    </style>
    <?php
}

// 引入管理后台页脚
require_once 'includes/admin_footer.php';
?> 