<?php
/**
 * 校记录管理页面
 */

// 页面信息
$pageTitle = '校记录管理';

// 引入管理后台头部
require_once 'includes/admin_header.php';

// 获取操作类型
$action = isset($_GET['action']) ? $_GET['action'] : 'list';

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_record']) || isset($_POST['edit_record'])) {
        $event_name = sanitizeInput($_POST['event_name']);
        $record = sanitizeInput($_POST['record']);
        $record_holder_name = sanitizeInput($_POST['record_holder_name']);
        $record_holder_id = !empty($_POST['record_holder_id']) ? (int)$_POST['record_holder_id'] : null;
        $record_date = sanitizeInput($_POST['record_date']);
        $description = sanitizeInput($_POST['description']);
        
        // 验证输入
        $errors = [];
        if (empty($event_name)) {
            $errors[] = '项目名称不能为空';
        }
        if (empty($record)) {
            $errors[] = '记录成绩不能为空';
        }
        if (empty($record_holder_name)) {
            $errors[] = '记录保持者不能为空';
        }
        if (empty($record_date)) {
            $errors[] = '记录日期不能为空';
        }
        
        if (empty($errors)) {
            if (isset($_POST['add_record'])) {
                // 添加校记录
                $stmt = $conn->prepare("INSERT INTO school_records (event_name, record, record_holder_id, record_holder_name, record_date, description) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssss", $event_name, $record, $record_holder_id, $record_holder_name, $record_date, $description);
                $stmt->execute();
                
                // 设置提示消息
                $_SESSION['alert'] = [
                    'type' => 'success',
                    'message' => '校记录添加成功'
                ];
                
                // 重定向到列表页
                header('Location: records.php');
                exit;
            } elseif (isset($_POST['edit_record'])) {
                // 更新校记录
                $recordId = (int)$_POST['record_id'];
                $stmt = $conn->prepare("UPDATE school_records SET event_name = ?, record = ?, record_holder_id = ?, record_holder_name = ?, record_date = ?, description = ? WHERE id = ?");
                $stmt->bind_param("ssisssi", $event_name, $record, $record_holder_id, $record_holder_name, $record_date, $description, $recordId);
                $stmt->execute();
                
                // 设置提示消息
                $_SESSION['alert'] = [
                    'type' => 'success',
                    'message' => '校记录更新成功'
                ];
                
                // 重定向到列表页
                header('Location: records.php');
                exit;
            }
        }
    } elseif (isset($_POST['delete_record'])) {
        // 删除校记录
        $recordId = (int)$_POST['record_id'];
        $stmt = $conn->prepare("DELETE FROM school_records WHERE id = ?");
        $stmt->bind_param("i", $recordId);
        $stmt->execute();
        
        $_SESSION['alert'] = [
            'type' => 'success',
            'message' => '校记录删除成功'
        ];
        
        // 重定向到列表页
        header('Location: records.php');
        exit;
    }
}

// 显示页面内容
switch ($action) {
    case 'add':
        showAddForm();
        break;
    
    case 'edit':
        $recordId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $stmt = $conn->prepare("SELECT * FROM school_records WHERE id = ?");
        $stmt->bind_param("i", $recordId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $record = $result->fetch_assoc();
            showEditForm($record);
        } else {
            $_SESSION['alert'] = [
                'type' => 'danger',
                'message' => '校记录不存在'
            ];
            header('Location: records.php');
            exit;
        }
        break;
    
    default:
        showRecordsList();
        break;
}

/**
 * 显示校记录列表
 */
function showRecordsList() {
    global $conn;
    
    // 获取所有校记录
    $query = "SELECT r.*, u.username, u.nickname  
              FROM school_records r 
              LEFT JOIN users u ON r.record_holder_id = u.id 
              ORDER BY r.event_name";
    $result = $conn->query($query);
    
    $records = [];
    while ($row = $result->fetch_assoc()) {
        $records[] = $row;
    }
    ?>
    <div class="admin-content-body">
        <div class="panel">
            <div class="panel-header">
                <h2>校记录列表</h2>
                <div class="panel-actions">
                    <a href="records.php?action=add" class="btn btn-primary">
                        <i class="fas fa-plus"></i> 添加校记录
                    </a>
                </div>
            </div>
            
            <div class="panel-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>项目名称</th>
                                <th>成绩</th>
                                <th>记录保持者</th>
                                <th>记录日期</th>
                                <th>描述</th>
                                <th width="120">操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($records)): ?>
                            <tr>
                                <td colspan="6" class="text-center">暂无校记录</td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($records as $record): ?>
                            <tr>
                                <td><?php echo $record['event_name']; ?></td>
                                <td><?php echo $record['record']; ?></td>
                                <td>
                                    <?php 
                                    echo $record['record_holder_name'];
                                    if ($record['record_holder_id'] && !empty($record['nickname'])) {
                                        echo ' (' . ($record['nickname'] ?: $record['username']) . ')';
                                    }
                                    ?>
                                </td>
                                <td><?php echo date('Y-m-d', strtotime($record['record_date'])); ?></td>
                                <td><?php echo substr($record['description'], 0, 50) . (strlen($record['description']) > 50 ? '...' : ''); ?></td>
                                <td>
                                    <a href="records.php?action=edit&id=<?php echo $record['id']; ?>" class="btn btn-sm btn-info">
                                        <i class="fas fa-edit"></i> 编辑
                                    </a>
                                    <button type="button" class="btn btn-sm btn-danger" onclick="confirmDelete(<?php echo $record['id']; ?>, '<?php echo $record['event_name']; ?>')">
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
                    <p>确定要删除"<span id="recordName"></span>"的校记录吗？此操作不可撤销。</p>
                </div>
                <div class="modal-footer">
                    <form action="records.php" method="post">
                        <input type="hidden" name="record_id" id="deleteRecordId">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">取消</button>
                        <button type="submit" name="delete_record" class="btn btn-danger">确认删除</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script>
    function confirmDelete(id, name) {
        document.getElementById('deleteRecordId').value = id;
        document.getElementById('recordName').textContent = name;
        $('#deleteModal').modal('show');
    }
    </script>
    <?php
}

/**
 * 显示添加校记录表单
 */
function showAddForm() {
    global $conn, $errors;
    
    // 获取所有用户
    $query = "SELECT id, username, nickname FROM users ORDER BY username";
    $result = $conn->query($query);
    
    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    ?>
    <div class="admin-content-body">
        <div class="panel">
            <div class="panel-header">
                <h2>添加校记录</h2>
                <div class="panel-actions">
                    <a href="records.php" class="btn btn-secondary">
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
                
                <form action="records.php" method="post">
                    <div class="form-group">
                        <label for="event_name">项目名称 <span class="text-danger">*</span></label>
                        <input type="text" id="event_name" name="event_name" class="form-control" value="<?php echo isset($_POST['event_name']) ? $_POST['event_name'] : ''; ?>" required>
                        <small class="form-text text-muted">例如：男子100米、女子跳高</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="record">记录成绩 <span class="text-danger">*</span></label>
                        <input type="text" id="record" name="record" class="form-control" value="<?php echo isset($_POST['record']) ? $_POST['record'] : ''; ?>" required>
                        <small class="form-text text-muted">例如：10.5秒、1米85</small>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="record_holder_name">记录保持者 <span class="text-danger">*</span></label>
                            <input type="text" id="record_holder_name" name="record_holder_name" class="form-control" value="<?php echo isset($_POST['record_holder_name']) ? $_POST['record_holder_name'] : ''; ?>" required>
                        </div>
                        
                        <div class="form-group col-md-6">
                            <label for="record_holder_id">关联用户</label>
                            <select id="record_holder_id" name="record_holder_id" class="form-control">
                                <option value="">-- 无关联用户 --</option>
                                <?php foreach ($users as $user): ?>
                                <option value="<?php echo $user['id']; ?>" <?php echo (isset($_POST['record_holder_id']) && $_POST['record_holder_id'] == $user['id']) ? 'selected' : ''; ?>>
                                    <?php echo $user['username'] . ($user['nickname'] ? ' (' . $user['nickname'] . ')' : ''); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="form-text text-muted">可选，关联平台用户</small>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="record_date">记录日期 <span class="text-danger">*</span></label>
                        <input type="date" id="record_date" name="record_date" class="form-control" value="<?php echo isset($_POST['record_date']) ? $_POST['record_date'] : date('Y-m-d'); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">描述</label>
                        <textarea id="description" name="description" class="form-control" rows="4"><?php echo isset($_POST['description']) ? $_POST['description'] : ''; ?></textarea>
                        <small class="form-text text-muted">可选，记录的详细情况</small>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" name="add_record" class="btn btn-primary">
                            <i class="fas fa-save"></i> 保存
                        </button>
                        <a href="records.php" class="btn btn-secondary">取消</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php
}

/**
 * 显示编辑校记录表单
 */
function showEditForm($record) {
    global $conn, $errors;
    
    // 获取所有用户
    $query = "SELECT id, username, nickname FROM users ORDER BY username";
    $result = $conn->query($query);
    
    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    ?>
    <div class="admin-content-body">
        <div class="panel">
            <div class="panel-header">
                <h2>编辑校记录</h2>
                <div class="panel-actions">
                    <a href="records.php" class="btn btn-secondary">
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
                
                <form action="records.php" method="post">
                    <input type="hidden" name="record_id" value="<?php echo $record['id']; ?>">
                    
                    <div class="form-group">
                        <label for="event_name">项目名称 <span class="text-danger">*</span></label>
                        <input type="text" id="event_name" name="event_name" class="form-control" value="<?php echo isset($_POST['event_name']) ? $_POST['event_name'] : $record['event_name']; ?>" required>
                        <small class="form-text text-muted">例如：男子100米、女子跳高</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="record">记录成绩 <span class="text-danger">*</span></label>
                        <input type="text" id="record" name="record" class="form-control" value="<?php echo isset($_POST['record']) ? $_POST['record'] : $record['record']; ?>" required>
                        <small class="form-text text-muted">例如：10.5秒、1米85</small>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="record_holder_name">记录保持者 <span class="text-danger">*</span></label>
                            <input type="text" id="record_holder_name" name="record_holder_name" class="form-control" value="<?php echo isset($_POST['record_holder_name']) ? $_POST['record_holder_name'] : $record['record_holder_name']; ?>" required>
                        </div>
                        
                        <div class="form-group col-md-6">
                            <label for="record_holder_id">关联用户</label>
                            <select id="record_holder_id" name="record_holder_id" class="form-control">
                                <option value="">-- 无关联用户 --</option>
                                <?php foreach ($users as $user): ?>
                                <option value="<?php echo $user['id']; ?>" <?php echo ((isset($_POST['record_holder_id']) && $_POST['record_holder_id'] == $user['id']) || (!isset($_POST['record_holder_id']) && $record['record_holder_id'] == $user['id'])) ? 'selected' : ''; ?>>
                                    <?php echo $user['username'] . ($user['nickname'] ? ' (' . $user['nickname'] . ')' : ''); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="form-text text-muted">可选，关联平台用户</small>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="record_date">记录日期 <span class="text-danger">*</span></label>
                        <input type="date" id="record_date" name="record_date" class="form-control" value="<?php echo isset($_POST['record_date']) ? $_POST['record_date'] : date('Y-m-d', strtotime($record['record_date'])); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">描述</label>
                        <textarea id="description" name="description" class="form-control" rows="4"><?php echo isset($_POST['description']) ? $_POST['description'] : $record['description']; ?></textarea>
                        <small class="form-text text-muted">可选，记录的详细情况</small>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" name="edit_record" class="btn btn-primary">
                            <i class="fas fa-save"></i> 保存
                        </button>
                        <a href="records.php" class="btn btn-secondary">取消</a>
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