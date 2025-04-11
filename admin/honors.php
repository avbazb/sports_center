<?php
/**
 * 荣誉墙管理页面
 */

// 页面信息
$pageTitle = '荣誉墙管理';

// 引入管理后台头部
require_once 'includes/admin_header.php';

// 获取操作类型
$action = isset($_GET['action']) ? $_GET['action'] : 'list';

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_honor']) || isset($_POST['edit_honor'])) {
        $title = sanitizeInput($_POST['title']);
        $description = sanitizeInput($_POST['description']);
        $honor_date = sanitizeInput($_POST['honor_date']);
        
        // 验证输入
        $errors = [];
        if (empty($title)) {
            $errors[] = '荣誉标题不能为空';
        }
        
        if (empty($errors)) {
            if (isset($_POST['add_honor'])) {
                // 添加荣誉
                // 处理图片上传
                $image = '';
                if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                    $uploadDir = '../assets/uploads/honors/';
                    if (!file_exists($uploadDir)) {
                        mkdir($uploadDir, 0777, true);
                    }
                    
                    $fileName = time() . '_' . basename($_FILES['image']['name']);
                    $targetPath = $uploadDir . $fileName;
                    
                    if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
                        $image = 'assets/uploads/honors/' . $fileName;
                    } else {
                        $errors[] = '图片上传失败';
                    }
                } else {
                    $errors[] = '请上传荣誉图片';
                }
                
                if (empty($errors)) {
                    $stmt = $conn->prepare("INSERT INTO honors (title, image, description, honor_date) VALUES (?, ?, ?, ?)");
                    $stmt->bind_param("ssss", $title, $image, $description, $honor_date);
                    $stmt->execute();
                    
                    // 设置提示消息
                    $_SESSION['alert'] = [
                        'type' => 'success',
                        'message' => '荣誉添加成功'
                    ];
                    
                    // 重定向到列表页
                    header('Location: honors.php');
                    exit;
                }
            } elseif (isset($_POST['edit_honor'])) {
                // 更新荣誉
                $honorId = (int)$_POST['honor_id'];
                
                // 获取当前荣誉信息
                $stmt = $conn->prepare("SELECT * FROM honors WHERE id = ?");
                $stmt->bind_param("i", $honorId);
                $stmt->execute();
                $result = $stmt->get_result();
                $honor = $result->fetch_assoc();
                
                $image = $honor['image']; // 默认使用原图
                
                // 处理图片上传
                if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                    $uploadDir = '../assets/uploads/honors/';
                    if (!file_exists($uploadDir)) {
                        mkdir($uploadDir, 0777, true);
                    }
                    
                    $fileName = time() . '_' . basename($_FILES['image']['name']);
                    $targetPath = $uploadDir . $fileName;
                    
                    if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
                        // 如果原来有图片，删除
                        if (!empty($honor['image']) && file_exists('../' . $honor['image'])) {
                            unlink('../' . $honor['image']);
                        }
                        
                        $image = 'assets/uploads/honors/' . $fileName;
                    } else {
                        $errors[] = '图片上传失败';
                    }
                }
                
                if (empty($errors)) {
                    $stmt = $conn->prepare("UPDATE honors SET title = ?, image = ?, description = ?, honor_date = ? WHERE id = ?");
                    $stmt->bind_param("ssssi", $title, $image, $description, $honor_date, $honorId);
                    $stmt->execute();
                    
                    // 设置提示消息
                    $_SESSION['alert'] = [
                        'type' => 'success',
                        'message' => '荣誉更新成功'
                    ];
                    
                    // 重定向到列表页
                    header('Location: honors.php');
                    exit;
                }
            }
        }
    } elseif (isset($_POST['delete_honor'])) {
        // 删除荣誉
        $honorId = (int)$_POST['honor_id'];
        
        // 获取荣誉信息
        $stmt = $conn->prepare("SELECT * FROM honors WHERE id = ?");
        $stmt->bind_param("i", $honorId);
        $stmt->execute();
        $result = $stmt->get_result();
        $honor = $result->fetch_assoc();
        
        // 删除关联图片
        if (!empty($honor['image']) && file_exists('../' . $honor['image'])) {
            unlink('../' . $honor['image']);
        }
        
        // 删除荣誉记录
        $stmt = $conn->prepare("DELETE FROM honors WHERE id = ?");
        $stmt->bind_param("i", $honorId);
        $stmt->execute();
        
        $_SESSION['alert'] = [
            'type' => 'success',
            'message' => '荣誉删除成功'
        ];
        
        // 重定向到列表页
        header('Location: honors.php');
        exit;
    }
}

// 显示页面内容
switch ($action) {
    case 'add':
        showAddForm();
        break;
    
    case 'edit':
        $honorId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $stmt = $conn->prepare("SELECT * FROM honors WHERE id = ?");
        $stmt->bind_param("i", $honorId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $honor = $result->fetch_assoc();
            showEditForm($honor);
        } else {
            $_SESSION['alert'] = [
                'type' => 'danger',
                'message' => '荣誉不存在'
            ];
            header('Location: honors.php');
            exit;
        }
        break;
    
    default:
        showHonorsList();
        break;
}

/**
 * 显示荣誉列表
 */
function showHonorsList() {
    global $conn;
    
    // 获取所有荣誉
    $query = "SELECT * FROM honors ORDER BY honor_date DESC";
    $result = $conn->query($query);
    
    $honors = [];
    while ($row = $result->fetch_assoc()) {
        $honors[] = $row;
    }
    ?>
    <div class="admin-content-body">
        <div class="panel">
            <div class="panel-header">
                <h2>荣誉墙列表</h2>
                <div class="panel-actions">
                    <a href="honors.php?action=add" class="btn btn-primary">
                        <i class="fas fa-plus"></i> 添加荣誉
                    </a>
                </div>
            </div>
            
            <div class="panel-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th width="80">图片</th>
                                <th>标题</th>
                                <th>描述</th>
                                <th>日期</th>
                                <th width="120">操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($honors)): ?>
                            <tr>
                                <td colspan="5" class="text-center">暂无荣誉</td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($honors as $honor): ?>
                            <tr>
                                <td>
                                    <?php if (!empty($honor['image'])): ?>
                                    <img src="../<?php echo $honor['image']; ?>" alt="<?php echo $honor['title']; ?>" style="width: 60px; height: 60px; object-fit: cover;">
                                    <?php else: ?>
                                    <div class="no-image">无图片</div>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $honor['title']; ?></td>
                                <td><?php echo substr($honor['description'], 0, 50) . (strlen($honor['description']) > 50 ? '...' : ''); ?></td>
                                <td><?php echo !empty($honor['honor_date']) ? date('Y-m-d', strtotime($honor['honor_date'])) : '无日期'; ?></td>
                                <td>
                                    <a href="honors.php?action=edit&id=<?php echo $honor['id']; ?>" class="btn btn-sm btn-info">
                                        <i class="fas fa-edit"></i> 编辑
                                    </a>
                                    <button type="button" class="btn btn-sm btn-danger" onclick="confirmDelete(<?php echo $honor['id']; ?>, '<?php echo $honor['title']; ?>')">
                                        <i class="fas fa-trash"></i> 删除
                                    </button>
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
                    <p>确定要删除"<span id="honorTitle"></span>"吗？此操作不可撤销。</p>
                </div>
                <div class="modal-footer">
                    <form action="honors.php" method="post">
                        <input type="hidden" name="honor_id" id="deleteHonorId">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">取消</button>
                        <button type="submit" name="delete_honor" class="btn btn-danger">确认删除</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script>
    function confirmDelete(id, title) {
        document.getElementById('deleteHonorId').value = id;
        document.getElementById('honorTitle').textContent = title;
        $('#deleteModal').modal('show');
    }
    </script>
    <?php
}

/**
 * 显示添加荣誉表单
 */
function showAddForm() {
    global $errors;
    ?>
    <div class="admin-content-body">
        <div class="panel">
            <div class="panel-header">
                <h2>添加荣誉</h2>
                <div class="panel-actions">
                    <a href="honors.php" class="btn btn-secondary">
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
                
                <form action="honors.php" method="post" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="title">荣誉标题 <span class="text-danger">*</span></label>
                        <input type="text" id="title" name="title" class="form-control" value="<?php echo isset($_POST['title']) ? $_POST['title'] : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="image">荣誉图片 <span class="text-danger">*</span></label>
                        <input type="file" id="image" name="image" class="form-control-file" required>
                        <small class="form-text text-muted">支持JPG、PNG格式，建议尺寸800x600像素以上</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="honor_date">荣誉日期</label>
                        <input type="date" id="honor_date" name="honor_date" class="form-control" value="<?php echo isset($_POST['honor_date']) ? $_POST['honor_date'] : date('Y-m-d'); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="description">荣誉描述</label>
                        <div class="editor-container">
                            <textarea id="description" name="description" class="form-control tinymce-editor" style="min-height:200px;"><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" name="add_honor" class="btn btn-primary">
                            <i class="fas fa-save"></i> 保存
                        </button>
                        <a href="honors.php" class="btn btn-secondary">取消</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php
}

/**
 * 显示编辑荣誉表单
 */
function showEditForm($honor) {
    global $errors;
    ?>
    <div class="admin-content-body">
        <div class="panel">
            <div class="panel-header">
                <h2>编辑荣誉</h2>
                <div class="panel-actions">
                    <a href="honors.php" class="btn btn-secondary">
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
                
                <form action="honors.php" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="honor_id" value="<?php echo $honor['id']; ?>">
                    
                    <div class="form-group">
                        <label for="title">荣誉标题 <span class="text-danger">*</span></label>
                        <input type="text" id="title" name="title" class="form-control" value="<?php echo isset($_POST['title']) ? $_POST['title'] : $honor['title']; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="image">荣誉图片</label>
                        <?php if (!empty($honor['image'])): ?>
                        <div class="current-image mb-2">
                            <img src="../<?php echo $honor['image']; ?>" alt="<?php echo $honor['title']; ?>" style="max-width: 200px; max-height: 200px;">
                            <p class="mt-1">当前图片</p>
                        </div>
                        <?php endif; ?>
                        <input type="file" id="image" name="image" class="form-control-file">
                        <small class="form-text text-muted">如需更换图片请上传新图片，否则保留原图</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="honor_date">荣誉日期</label>
                        <input type="date" id="honor_date" name="honor_date" class="form-control" value="<?php echo isset($_POST['honor_date']) ? $_POST['honor_date'] : date('Y-m-d', strtotime($honor['honor_date'])); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="description">荣誉描述</label>
                        <div class="editor-container">
                            <textarea id="description" name="description" class="form-control tinymce-editor" style="min-height:200px;"><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : htmlspecialchars($honor['description']); ?></textarea>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" name="edit_honor" class="btn btn-primary">
                            <i class="fas fa-save"></i> 保存
                        </button>
                        <a href="honors.php" class="btn btn-secondary">取消</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php
}

// 引入管理后台页脚
 