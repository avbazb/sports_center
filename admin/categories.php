<?php
/**
 * 分类管理页面
 */

// 页面信息
$pageTitle = '分类管理';

// 引入管理后台头部
require_once 'includes/admin_header.php';

// 获取操作类型
$action = isset($_GET['action']) ? $_GET['action'] : 'list';

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_category']) || isset($_POST['edit_category'])) {
        $name = sanitizeInput($_POST['name']);
        $slug = sanitizeInput($_POST['slug']);
        $description = sanitizeInput($_POST['description']);
        
        // 验证输入
        $errors = [];
        if (empty($name)) {
            $errors[] = '分类名称不能为空';
        }
        if (empty($slug)) {
            $errors[] = '分类别名不能为空';
        }
        
        // 检查别名是否重复
        $categoryId = isset($_POST['category_id']) ? (int)$_POST['category_id'] : 0;
        $stmt = $conn->prepare("SELECT id FROM categories WHERE slug = ? AND id != ?");
        $stmt->bind_param("si", $slug, $categoryId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $errors[] = '该分类别名已存在，请使用其他别名';
        }
        
        if (empty($errors)) {
            if (isset($_POST['add_category'])) {
                // 添加分类
                $stmt = $conn->prepare("INSERT INTO categories (name, slug, description) VALUES (?, ?, ?)");
                $stmt->bind_param("sss", $name, $slug, $description);
                $stmt->execute();
                
                // 设置提示消息
                $_SESSION['alert'] = [
                    'type' => 'success',
                    'message' => '分类添加成功'
                ];
                
                // 重定向到列表页
                header('Location: categories.php');
                exit;
            } elseif (isset($_POST['edit_category'])) {
                // 更新分类
                $stmt = $conn->prepare("UPDATE categories SET name = ?, slug = ?, description = ? WHERE id = ?");
                $stmt->bind_param("sssi", $name, $slug, $description, $categoryId);
                $stmt->execute();
                
                // 设置提示消息
                $_SESSION['alert'] = [
                    'type' => 'success',
                    'message' => '分类更新成功'
                ];
                
                // 重定向到列表页
                header('Location: categories.php');
                exit;
            }
        }
    } elseif (isset($_POST['delete_category'])) {
        // 删除分类
        $categoryId = (int)$_POST['category_id'];
        
        // 检查是否有文章使用此分类
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM articles WHERE category_id = ?");
        $stmt->bind_param("i", $categoryId);
        $stmt->execute();
        $result = $stmt->get_result();
        $articleCount = $result->fetch_assoc()['count'];
        
        if ($articleCount > 0) {
            $_SESSION['alert'] = [
                'type' => 'danger',
                'message' => '该分类下有' . $articleCount . '篇文章，无法删除'
            ];
        } else {
            $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
            $stmt->bind_param("i", $categoryId);
            $stmt->execute();
            
            $_SESSION['alert'] = [
                'type' => 'success',
                'message' => '分类删除成功'
            ];
        }
        
        // 重定向到列表页
        header('Location: categories.php');
        exit;
    }
}

// 显示页面内容
switch ($action) {
    case 'add':
        showAddForm();
        break;
    
    case 'edit':
        $categoryId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $stmt = $conn->prepare("SELECT * FROM categories WHERE id = ?");
        $stmt->bind_param("i", $categoryId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $category = $result->fetch_assoc();
            showEditForm($category);
        } else {
            $_SESSION['alert'] = [
                'type' => 'danger',
                'message' => '分类不存在'
            ];
            header('Location: categories.php');
            exit;
        }
        break;
    
    default:
        showCategoriesList();
        break;
}

/**
 * 显示分类列表
 */
function showCategoriesList() {
    global $conn;
    
    // 获取所有分类
    $query = "SELECT c.*, COUNT(a.id) as article_count 
              FROM categories c 
              LEFT JOIN articles a ON c.id = a.category_id 
              GROUP BY c.id 
              ORDER BY c.name";
    $result = $conn->query($query);
    
    $categories = [];
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
    ?>
    <div class="admin-content-body">
        <div class="panel">
            <div class="panel-header">
                <h2>分类列表</h2>
                <div class="panel-actions">
                    <a href="categories.php?action=add" class="btn btn-primary">
                        <i class="fas fa-plus"></i> 添加分类
                    </a>
                </div>
            </div>
            
            <div class="panel-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>名称</th>
                                <th>别名</th>
                                <th>描述</th>
                                <th>文章数</th>
                                <th width="120">操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($categories)): ?>
                            <tr>
                                <td colspan="5" class="text-center">暂无分类</td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($categories as $category): ?>
                            <tr>
                                <td><?php echo $category['name']; ?></td>
                                <td><?php echo $category['slug']; ?></td>
                                <td><?php echo substr($category['description'], 0, 50) . (strlen($category['description']) > 50 ? '...' : ''); ?></td>
                                <td><?php echo $category['article_count']; ?></td>
                                <td>
                                    <a href="categories.php?action=edit&id=<?php echo $category['id']; ?>" class="btn btn-sm btn-info">
                                        <i class="fas fa-edit"></i> 编辑
                                    </a>
                                    <?php if ($category['article_count'] == 0): ?>
                                    <button type="button" class="btn btn-sm btn-danger" onclick="confirmDelete(<?php echo $category['id']; ?>, '<?php echo $category['name']; ?>')">
                                        <i class="fas fa-trash"></i> 删除
                                    </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- 删除确认弹窗 -->
    <div class="modal fade" id="deleteModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">确认删除</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>确定要删除分类 <span id="categoryName"></span> 吗？此操作不可撤销。</p>
                </div>
                <div class="modal-footer">
                    <form action="categories.php" method="post">
                        <input type="hidden" name="category_id" id="deleteCategoryId">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">取消</button>
                        <button type="submit" name="delete_category" class="btn btn-danger">确认删除</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script>
    function confirmDelete(id, name) {
        document.getElementById('deleteCategoryId').value = id;
        document.getElementById('categoryName').textContent = name;
        $('#deleteModal').modal('show');
    }
    </script>
    <?php
}

/**
 * 显示添加分类表单
 */
function showAddForm() {
    global $errors;
    ?>
    <div class="admin-content-body">
        <div class="panel">
            <div class="panel-header">
                <h2>添加分类</h2>
                <div class="panel-actions">
                    <a href="categories.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> 返回列表
                    </a>
                </div>
            </div>
            
            <div class="panel-body">
                <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                        <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
                
                <form action="categories.php" method="post">
                    <div class="form-group">
                        <label for="name">分类名称 <span class="text-danger">*</span></label>
                        <input type="text" id="name" name="name" class="form-control" value="<?php echo isset($_POST['name']) ? $_POST['name'] : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="slug">分类别名 <span class="text-danger">*</span></label>
                        <input type="text" id="slug" name="slug" class="form-control" value="<?php echo isset($_POST['slug']) ? $_POST['slug'] : ''; ?>" required>
                        <small class="form-text text-muted">用于URL中的标识，只能包含字母、数字、连字符和下划线</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">分类描述</label>
                        <textarea id="description" name="description" class="form-control" rows="4"><?php echo isset($_POST['description']) ? $_POST['description'] : ''; ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" name="add_category" class="btn btn-primary">
                            <i class="fas fa-save"></i> 保存
                        </button>
                        <a href="categories.php" class="btn btn-secondary">取消</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
    // 根据分类名称自动生成别名
    document.getElementById('name').addEventListener('input', function() {
        const slug = this.value
            .toLowerCase()
            .replace(/[^\w\s-]/g, '') // 移除特殊字符
            .replace(/\s+/g, '-') // 空格替换为连字符
            .replace(/--+/g, '-'); // 删除多余的连字符
        
        document.getElementById('slug').value = slug;
    });
    </script>
    <?php
}

/**
 * 显示编辑分类表单
 */
function showEditForm($category) {
    global $errors;
    ?>
    <div class="admin-content-body">
        <div class="panel">
            <div class="panel-header">
                <h2>编辑分类</h2>
                <div class="panel-actions">
                    <a href="categories.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> 返回列表
                    </a>
                </div>
            </div>
            
            <div class="panel-body">
                <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                        <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
                
                <form action="categories.php" method="post">
                    <input type="hidden" name="category_id" value="<?php echo $category['id']; ?>">
                    
                    <div class="form-group">
                        <label for="name">分类名称 <span class="text-danger">*</span></label>
                        <input type="text" id="name" name="name" class="form-control" value="<?php echo isset($_POST['name']) ? $_POST['name'] : $category['name']; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="slug">分类别名 <span class="text-danger">*</span></label>
                        <input type="text" id="slug" name="slug" class="form-control" value="<?php echo isset($_POST['slug']) ? $_POST['slug'] : $category['slug']; ?>" required>
                        <small class="form-text text-muted">用于URL中的标识，只能包含字母、数字、连字符和下划线</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">分类描述</label>
                        <textarea id="description" name="description" class="form-control" rows="4"><?php echo isset($_POST['description']) ? $_POST['description'] : $category['description']; ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" name="edit_category" class="btn btn-primary">
                            <i class="fas fa-save"></i> 保存
                        </button>
                        <a href="categories.php" class="btn btn-secondary">取消</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php
}

// 引入管理后台页脚
require_once 'includes/admin_footer.php';
?> 