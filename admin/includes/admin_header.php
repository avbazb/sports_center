<?php
/**
 * 管理后台头部
 */
// 启动输出缓冲，解决header()函数调用问题
ob_start();

// 启动会话
session_start();

// 检查用户是否登录且有管理员权限
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] != 'admin') {
    header('Location: ../login.php');
    exit;
}

// 引入函数库
require_once '../includes/functions.php';

// 默认页面标题
if (!isset($pageTitle)) {
    $pageTitle = '管理后台';
}

// 获取当前用户信息
$currentUser = getUserInfo($_SESSION['user_id']);

// 获取当前页面
$currentPage = basename($_SERVER['PHP_SELF']);

// 检查是否有提示消息
$alert = isset($_SESSION['alert']) ? $_SESSION['alert'] : null;
// 使用后清除提示消息
if (isset($_SESSION['alert'])) {
    unset($_SESSION['alert']);
}

?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - 体育中心管理系统</title>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- 主样式表 -->
    <link rel="stylesheet" href="assets/css/admin.css">
    <!-- 可能的页面特定样式 -->
    <?php if (file_exists("assets/css/{$currentPage}.css")): ?>
    <link rel="stylesheet" href="assets/css/<?php echo $currentPage; ?>.css">
    <?php endif; ?>
    <!-- TinyMCE 富文本编辑器 -->
    <script src="assets/js/tinymce/tinymce/tinymce/js/tinymce/tinymce.min.js"></script>
    <script>
    // TinyMCE初始化配置
    document.addEventListener('DOMContentLoaded', function() {
        if (document.querySelector('.tinymce-editor')) {
            tinymce.init({
                selector: '.tinymce-editor',
                language: 'zh_CN',
                height: 500,
                plugins: 'print preview paste searchreplace autolink directionality code visualblocks visualchars fullscreen image link media template codesample table charmap hr pagebreak nonbreaking anchor insertdatetime advlist lists wordcount textpattern noneditable help charmap quickbars emoticons',
                toolbar: 'undo redo | bold italic underline strikethrough | fontselect fontsizeselect formatselect | alignleft aligncenter alignright alignjustify | outdent indent | numlist bullist | forecolor backcolor removeformat | pagebreak | charmap emoticons | fullscreen preview print | image media template link anchor codesample | ltr rtl',
                toolbar_sticky: true,
                image_advtab: true,
                image_caption: true,
                quickbars_selection_toolbar: 'bold italic | quicklink h2 h3 blockquote quickimage quicktable',
                noneditable_noneditable_class: "mceNonEditable",
                toolbar_mode: 'sliding',
                contextmenu: "link image table",
                content_style: 'body { font-family:Helvetica,Arial,sans-serif; font-size:14px }',
                hidden_input: false,
                setup: function(editor) {
                    editor.on('change', function() {
                        editor.save();
                    });
                },
                images_upload_handler: function (blobInfo, success, failure) {
                    var xhr, formData;
                    xhr = new XMLHttpRequest();
                    xhr.withCredentials = false;
                    xhr.open('POST', 'upload_image.php');
                    xhr.onload = function() {
                        var json;
                        if (xhr.status != 200) {
                            failure('HTTP Error: ' + xhr.status);
                            return;
                        }
                        json = JSON.parse(xhr.responseText);
                        if (!json || typeof json.location != 'string') {
                            failure('Invalid JSON: ' + xhr.responseText);
                            return;
                        }
                        success(json.location);
                    };
                    formData = new FormData();
                    formData.append('file', blobInfo.blob(), blobInfo.filename());
                    xhr.send(formData);
                }
            });
        }
    });
    </script>
</head>
<body class="admin-body">
    <!-- 侧边栏 -->
    <div class="admin-sidebar">
        <div class="sidebar-header">
            <h1>体育中心管理</h1>
        </div>
        
        <nav class="sidebar-nav">
            <ul>
                <li <?php echo $currentPage == 'index.php' ? 'class="active"' : ''; ?>>
                    <a href="index.php">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>控制面板</span>
                    </a>
                </li>
                
                <li class="nav-header">内容管理</li>
                
                <li <?php echo $currentPage == 'articles.php' ? 'class="active"' : ''; ?>>
                    <a href="articles.php">
                        <i class="fas fa-newspaper"></i>
                        <span>文章管理</span>
                    </a>
                </li>
                
                <li <?php echo $currentPage == 'categories.php' ? 'class="active"' : ''; ?>>
                    <a href="categories.php">
                        <i class="fas fa-tags"></i>
                        <span>分类管理</span>
                    </a>
                </li>
                
                <li <?php echo $currentPage == 'records.php' ? 'class="active"' : ''; ?>>
                    <a href="records.php">
                        <i class="fas fa-trophy"></i>
                        <span>校记录管理</span>
                    </a>
                </li>
                
                <li <?php echo $currentPage == 'honors.php' ? 'class="active"' : ''; ?>>
                    <a href="honors.php">
                        <i class="fas fa-award"></i>
                        <span>荣誉墙管理</span>
                    </a>
                </li>
                
                <li class="nav-header">系统管理</li>
                
                <li <?php echo $currentPage == 'users.php' ? 'class="active"' : ''; ?>>
                    <a href="users.php">
                        <i class="fas fa-users"></i>
                        <span>用户管理</span>
                    </a>
                </li>
                
                <li <?php echo $currentPage == 'comments.php' ? 'class="active"' : ''; ?>>
                    <a href="comments.php">
                        <i class="fas fa-comments"></i>
                        <span>评论管理</span>
                    </a>
                </li>
                
                <li <?php echo $currentPage == 'settings.php' ? 'class="active"' : ''; ?>>
                    <a href="settings.php">
                        <i class="fas fa-cog"></i>
                        <span>系统设置</span>
                    </a>
                </li>
            </ul>
        </nav>
    </div>
    
    <!-- 主内容区 -->
    <div class="admin-main">
        <!-- 顶部导航栏 -->
        <header class="admin-header">
            <div class="header-search">
                <form action="search.php" method="get">
                    <input type="text" name="q" placeholder="全站搜索...">
                    <button type="submit">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
            </div>
            
            <div class="header-right">
                <div class="header-actions">
                    <a href="../index.php" class="btn-icon" title="查看网站" target="_blank">
                        <i class="fas fa-external-link-alt"></i>
                    </a>
                </div>
                
                <div class="user-dropdown">
                    <a href="#" class="dropdown-toggle">
                        <div class="user-avatar">
                            <?php if (!empty($currentUser['avatar'])): ?>
                            <img src="<?php echo $currentUser['avatar']; ?>" alt="用户头像">
                            <?php else: ?>
                            <div class="avatar-placeholder">
                                <i class="fas fa-user"></i>
                            </div>
                            <?php endif; ?>
                        </div>
                        <span><?php echo $currentUser['nickname'] ?? $currentUser['username']; ?></span>
                        <i class="fas fa-chevron-down"></i>
                    </a>
                    <div class="dropdown-menu">
                        <a href="../profile.php?id=<?php echo $currentUser['id']; ?>">
                            <i class="fas fa-user-circle"></i> 个人资料
                        </a>
                        <a href="settings.php">
                            <i class="fas fa-cog"></i> 设置
                        </a>
                        <div class="dropdown-divider"></div>
                        <a href="../logout.php">
                            <i class="fas fa-sign-out-alt"></i> 退出登录
                        </a>
                    </div>
                </div>
            </div>
        </header>
        
        <!-- 内容区 -->
        <div class="admin-content">
            <?php if ($alert): ?>
            <div class="alert alert-<?php echo $alert['type']; ?>" id="alert-message">
                <div class="alert-icon">
                    <?php if ($alert['type'] === 'success'): ?>
                    <i class="fas fa-check-circle"></i>
                    <?php elseif ($alert['type'] === 'danger'): ?>
                    <i class="fas fa-exclamation-circle"></i>
                    <?php elseif ($alert['type'] === 'warning'): ?>
                    <i class="fas fa-exclamation-triangle"></i>
                    <?php elseif ($alert['type'] === 'info'): ?>
                    <i class="fas fa-info-circle"></i>
                    <?php endif; ?>
                </div>
                <div class="alert-content">
                    <?php echo $alert['message']; ?>
                </div>
                <button type="button" class="alert-close" onclick="this.parentElement.style.display='none'">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <?php endif; ?> 