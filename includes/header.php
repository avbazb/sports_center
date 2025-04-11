<?php
/**
 * 页头文件
 */

// 开启会话
session_start();

// 引入必要的文件
require_once __DIR__ . '/functions.php';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' : ''; ?>体育中心网站</title>
    
    <!-- CSS 文件 -->
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/responsive.css">
    <link rel="stylesheet" href="assets/css/animations.css">
    <link rel="stylesheet" href="assets/css/custom.css">
    
    <!-- 字体图标 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- 引入jQuery -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
</head>
<body>
    <div class="page-wrapper">
        <!-- 头部区域 -->
        <header class="site-header">
            <div class="top-nav">
                <div class="container">
                    <div class="top-nav-inner">
                        <div class="logo-container">
                            <a href="index.php" class="logo">
                                <img src="assets/images/logo.png" alt="网站标志">
                                <span class="site-title">体育中心</span>
                            </a>
                        </div>
                        
                        <!-- 主导航菜单 -->
                        <nav class="main-nav">
                            <ul class="main-menu">
                                <li><a href="index.php">主页</a></li>
                                <li><a href="news.php">新闻消息</a></li>
                                <li><a href="notices.php">通知公告</a></li>
                                <li><a href="updates.php">最新动态</a></li>
                                <li><a href="records.php">运动记录</a></li>
                                <li><a href="honors.php">荣誉墙</a></li>
                                <li><a href="events.php">体育赛事</a></li>
                            </ul>
                        </nav>
                        
                        <!-- 用户区域 -->
                        <div class="user-area">
                            <?php if (isLoggedIn()): ?>
                                <div class="user-dropdown">
                                    <a href="javascript:void(0);" class="user-toggle">
                                        <?php
                                        $user = getUserInfo(getCurrentUserId());
                                        $displayName = $user['nickname'] ? $user['nickname'] : $user['username'];
                                        
                                        if (!empty($user['avatar'])) {
                                            echo '<img src="' . $user['avatar'] . '" alt="' . $displayName . '" class="user-avatar">';
                                        } else {
                                            echo '<i class="fas fa-user-circle"></i>';
                                        }
                                        
                                        echo '<span>' . $displayName . '</span>';
                                        ?>
                                        <i class="fas fa-chevron-down"></i>
                                    </a>
                                    <ul class="dropdown-menu">
                                        <li><a href="profile.php"><i class="fas fa-user"></i> 个人主页</a></li>
                                        <?php if (isAdmin()): ?>
                                        <li><a href="admin/index.php"><i class="fas fa-cog"></i> 管理后台</a></li>
                                        <?php endif; ?>
                                        <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> 退出登录</a></li>
                                    </ul>
                                </div>
                            <?php else: ?>
                                <div class="auth-buttons">
                                    <a href="login.php" class="btn btn-login">登录</a>
                                    <a href="register.php" class="btn btn-register">注册</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- 移动端菜单按钮 -->
            <div class="mobile-menu-container">
                <div class="container">
                    <div class="mobile-nav-toggle">
                        <span></span>
                        <span></span>
                        <span></span>
                    </div>
                </div>
            </div>
        </header>
        
        <!-- 页面内容区域 -->
        <main class="site-main"><?php if (isset($pageBanner) && $pageBanner): ?>
            <div class="page-banner" style="background-image: url('<?php echo $pageBanner; ?>')">
                <div class="container">
                    <h1 class="page-title animated fadeInUp"><?php echo $pageTitle; ?></h1>
                    <?php if (isset($pageBreadcrumb) && $pageBreadcrumb): ?>
                    <nav class="breadcrumb animated fadeInUp" style="animation-delay: 0.2s;">
                        <ol>
                            <li><a href="index.php">首页</a></li>
                            <?php foreach ($pageBreadcrumb as $key => $value): ?>
                            <li><?php echo $key === array_key_last($pageBreadcrumb) ? $value : '<a href="' . $key . '">' . $value . '</a>'; ?></li>
                            <?php endforeach; ?>
                        </ol>
                    </nav>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?> 