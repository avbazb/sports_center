<?php
/**
 * 用户个人主页
 */

// 页面信息
$pageTitle = '个人主页';
$pageBanner = 'assets/images/banner-profile.jpg';

// 引入头部
require_once 'includes/header.php';

// 检查用户是否登录
if (!isLoggedIn() && !isset($_GET['id'])) {
    // 未登录且未指定用户ID，重定向到登录页面
    header('Location: login.php');
    exit;
}

// 获取要查看的用户ID
$userId = isset($_GET['id']) ? intval($_GET['id']) : getCurrentUserId();

// 获取用户信息
$user = getUserInfo($userId);

// 如果用户不存在，显示错误信息
if (!$user) {
    echo '<div class="container"><div class="alert alert-danger">用户不存在</div></div>';
    require_once 'includes/footer.php';
    exit;
}

// 更新页面标题
$pageTitle = isset($user['nickname']) && !empty($user['nickname']) ? $user['nickname'] . ' 的个人主页' : $user['username'] . ' 的个人主页';
$pageBreadcrumb = [
    'profile.php' => '个人主页',
    'profile.php?id=' . $userId => $user['username']
];

// 处理个人信息更新表单
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $userId === getCurrentUserId()) {
    // 只允许更新自己的个人信息
    if (isset($_POST['update_profile'])) {
        $nickname = sanitizeInput($_POST['nickname']);
        $email = sanitizeInput($_POST['email']);
        $bio = sanitizeInput($_POST['bio']);
        
        // 更新用户信息
        $data = [
            'nickname' => $nickname,
            'email' => $email,
            'bio' => $bio
        ];
        
        if (updateUserInfo($userId, $data)) {
            $success = '个人信息更新成功';
            
            // 刷新用户信息
            $user = getUserInfo($userId);
        } else {
            $error = '个人信息更新失败';
        }
    }
    
    // 处理头像上传
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $result = saveUserAvatar($userId, $_FILES['avatar']);
        
        if ($result['status']) {
            $success = '头像上传成功';
            
            // 刷新用户信息
            $user = getUserInfo($userId);
        } else {
            $error = $result['message'];
        }
    }
}

// 获取用户的校记录
$userRecords = [];
$conn = getDbConnection();
$stmt = $conn->prepare("SELECT * FROM school_records WHERE record_holder_id = ? ORDER BY record_date DESC");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $userRecords[] = $row;
}

// 是否是当前登录用户
$isCurrentUser = isLoggedIn() && getCurrentUserId() === $userId;
?>

<div class="container">
    <div class="section">
        <!-- 个人主页头部 -->
        <div class="profile-header">
            <div class="profile-avatar">
                <?php if (!empty($user['avatar'])): ?>
                <img src="<?php echo $user['avatar']; ?>" alt="<?php echo $user['username']; ?>">
                <?php else: ?>
                <div class="avatar-placeholder">
                    <i class="fas fa-user"></i>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="profile-info">
                <h1 class="profile-name">
                    <?php echo (!empty($user['nickname'])) ? $user['nickname'] : $user['username']; ?>
                    <?php if (!empty($user['certification'])): ?>
                    <span class="profile-certification"><?php echo $user['certification']; ?></span>
                    <?php endif; ?>
                </h1>
                <div class="profile-meta">
                    <div class="profile-username">@<?php echo $user['username']; ?></div>
                    <div class="profile-joined">
                        <i class="fas fa-calendar-alt"></i> 
                        加入于 <?php echo date('Y年m月d日', strtotime($user['created_at'])); ?>
                    </div>
                </div>
                <div class="profile-bio">
                    <?php echo !empty($user['bio']) ? nl2br($user['bio']) : '这个人很懒，什么都没有留下...'; ?>
                </div>
            </div>
        </div>
        
        <?php if ($error): ?>
        <div class="alert alert-danger">
            <?php echo $error; ?>
        </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
        <div class="alert alert-success">
            <?php echo $success; ?>
        </div>
        <?php endif; ?>
        
        <!-- 编辑个人资料表单 -->
        <?php if ($isCurrentUser): ?>
        <div class="profile-actions">
            <button id="editProfileBtn" class="btn btn-primary">编辑个人资料</button>
        </div>
        
        <div id="editProfileForm" class="edit-profile-form" style="display: none;">
            <h2 class="form-title">编辑个人资料</h2>
            
            <form action="profile.php" method="post" enctype="multipart/form-data" data-validate>
                <div class="form-group">
                    <label for="avatar">头像</label>
                    <div class="avatar-upload">
                        <div class="avatar-preview">
                            <?php if (!empty($user['avatar'])): ?>
                            <img src="<?php echo $user['avatar']; ?>" alt="<?php echo $user['username']; ?>" id="avatarPreview">
                            <?php else: ?>
                            <div class="avatar-placeholder" id="avatarPreview">
                                <i class="fas fa-user"></i>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="avatar-edit">
                            <label for="avatarUpload" class="btn btn-outline btn-sm">选择图片</label>
                            <input type="file" id="avatarUpload" name="avatar" accept="image/*" style="display: none;">
                            <small class="form-text text-muted">支持JPG、PNG和GIF格式，最大不超过2MB</small>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="nickname">昵称</label>
                    <input type="text" id="nickname" name="nickname" class="form-control" value="<?php echo isset($user['nickname']) ? $user['nickname'] : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="email">电子邮箱</label>
                    <input type="email" id="email" name="email" class="form-control" value="<?php echo $user['email']; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="bio">个人简介</label>
                    <textarea id="bio" name="bio" class="form-control" rows="5"><?php echo isset($user['bio']) ? $user['bio'] : ''; ?></textarea>
                </div>
                
                <div class="form-group">
                    <button type="submit" name="update_profile" class="btn btn-primary">保存</button>
                    <button type="button" id="cancelEditBtn" class="btn btn-secondary">取消</button>
                </div>
            </form>
        </div>
        <?php endif; ?>
        
        <!-- 用户校记录 -->
        <div class="profile-records section">
            <h2 class="section-title">持有的校记录</h2>
            
            <?php if (empty($userRecords)): ?>
            <p class="text-center">暂无校记录</p>
            <?php else: ?>
            <div class="records-table-container">
                <table class="records-table">
                    <thead>
                        <tr>
                            <th>项目</th>
                            <th>记录</th>
                            <th>创建日期</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($userRecords as $record): ?>
                        <tr>
                            <td><?php echo $record['event_name']; ?></td>
                            <td><?php echo $record['record']; ?></td>
                            <td><?php echo date('Y-m-d', strtotime($record['record_date'])); ?></td>
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
/* 个人主页样式 */
.profile-header {
    display: flex;
    gap: 30px;
    margin-bottom: 30px;
    background: #fff;
    border-radius: 8px;
    box-shadow: var(--box-shadow);
    padding: 30px;
}

.profile-avatar {
    width: 150px;
    height: 150px;
    border-radius: 50%;
    overflow: hidden;
    flex-shrink: 0;
}

.profile-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.avatar-placeholder {
    width: 100%;
    height: 100%;
    background-color: #e9ecef;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #adb5bd;
    font-size: 60px;
}

.profile-info {
    flex: 1;
}

.profile-name {
    font-size: 32px;
    font-weight: 600;
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    gap: 15px;
}

.profile-certification {
    font-size: 14px;
    background-color: #ffe4c4;
    color: #ff6b00;
    padding: 3px 10px;
    border-radius: 4px;
    font-weight: normal;
}

.profile-meta {
    display: flex;
    gap: 20px;
    margin-bottom: 15px;
    color: var(--lighter-text);
}

.profile-username {
    font-weight: 500;
}

.profile-bio {
    color: var(--light-text);
    line-height: 1.6;
    white-space: pre-line;
}

.profile-actions {
    margin-bottom: 30px;
    text-align: right;
}

.edit-profile-form {
    background: #fff;
    border-radius: 8px;
    box-shadow: var(--box-shadow);
    padding: 30px;
    margin-bottom: 30px;
}

.form-title {
    font-size: 24px;
    margin-bottom: 30px;
    padding-bottom: 10px;
    border-bottom: 1px solid var(--border-color);
}

.avatar-upload {
    display: flex;
    gap: 20px;
    align-items: center;
}

.avatar-preview {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    overflow: hidden;
}

.avatar-preview img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.avatar-edit {
    flex: 1;
}

.profile-records {
    margin-top: 40px;
}

/* 响应式调整 */
@media (max-width: 768px) {
    .profile-header {
        flex-direction: column;
        align-items: center;
        text-align: center;
    }
    
    .profile-meta {
        flex-direction: column;
        gap: 10px;
        align-items: center;
    }
    
    .profile-name {
        flex-direction: column;
        gap: 10px;
        align-items: center;
    }
    
    .avatar-upload {
        flex-direction: column;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 编辑个人资料表单
    const editProfileBtn = document.getElementById('editProfileBtn');
    const cancelEditBtn = document.getElementById('cancelEditBtn');
    const editProfileForm = document.getElementById('editProfileForm');
    
    if (editProfileBtn && cancelEditBtn && editProfileForm) {
        editProfileBtn.addEventListener('click', function() {
            editProfileForm.style.display = 'block';
            editProfileBtn.style.display = 'none';
        });
        
        cancelEditBtn.addEventListener('click', function() {
            editProfileForm.style.display = 'none';
            editProfileBtn.style.display = 'inline-block';
        });
    }
    
    // 头像预览
    const avatarUpload = document.getElementById('avatarUpload');
    const avatarPreview = document.getElementById('avatarPreview');
    
    if (avatarUpload && avatarPreview) {
        avatarUpload.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    if (avatarPreview.tagName === 'IMG') {
                        avatarPreview.src = e.target.result;
                    } else {
                        // 如果没有图片，创建一个
                        const img = document.createElement('img');
                        img.id = 'avatarPreview';
                        img.src = e.target.result;
                        
                        // 替换占位符
                        avatarPreview.parentNode.replaceChild(img, avatarPreview);
                    }
                };
                
                reader.readAsDataURL(this.files[0]);
            }
        });
    }
});
</script>

<?php
// 引入页脚
require_once 'includes/footer.php';
?> 