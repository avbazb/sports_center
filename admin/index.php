<?php
/**
 * 管理后台首页
 */

// 页面信息
$page_title = '仪表盘';

// 引入管理后台头部
require_once 'includes/admin_header.php';

// 检查管理员登录状态
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: login.php");
    exit();
}

// 获取用户总数
$query = "SELECT COUNT(*) as total FROM users";
$result = $conn->query($query);
$users_count = $result->fetch_assoc()['total'];

// 获取文章总数
$query = "SELECT COUNT(*) as total FROM articles";
$result = $conn->query($query);
$articles_count = $result->fetch_assoc()['total'];

// 获取评论总数
$query = "SELECT COUNT(*) as total FROM comments";
$result = $conn->query($query);
$comments_count = $result->fetch_assoc()['total'];

// 获取图片总数
// 先检查images表是否存在
$query = "SHOW TABLES LIKE 'images'";
$tableExists = $conn->query($query)->num_rows > 0;

if ($tableExists) {
    $query = "SELECT COUNT(*) as total FROM images";
    $result = $conn->query($query);
    $images_count = $result->fetch_assoc()['total'];
} else {
    $images_count = 0; // 表不存在时设置默认值
}

// 获取最近文章
$query = "SELECT a.id, a.title, a.created_at, u.username, u.nickname, c.name as category_name 
          FROM articles a 
          LEFT JOIN users u ON a.author_id = u.id
          LEFT JOIN categories c ON a.category_id = c.id 
          ORDER BY a.created_at DESC 
          LIMIT 5";
$result = $conn->query($query);
$recent_articles = [];
while ($row = $result->fetch_assoc()) {
    $recent_articles[] = $row;
}

// 获取最近评论
$query = "SELECT c.id as comment_id, c.content, c.created_at, a.id as article_id, a.title, u.username, u.nickname 
          FROM comments c 
          LEFT JOIN articles a ON c.article_id = a.id 
          LEFT JOIN users u ON c.user_id = u.id 
          ORDER BY c.created_at DESC 
          LIMIT 5";
$result = $conn->query($query);
$recent_comments = [];
while ($row = $result->fetch_assoc()) {
    $recent_comments[] = $row;
}
?>

<div class="admin-content">
    <div class="admin-content-header">
        <h2>欢迎使用体育中心管理系统</h2>
    </div>
    
    <div class="dashboard-container">
        <div class="welcome-section">
            <h1>你好，<?php echo $_SESSION['username']; ?>！</h1>
            <p>欢迎来到体育中心管理后台，您可以在这里管理网站的内容和用户。</p>
        </div>
        
        <div class="stats-cards">
            <div class="stat-card primary">
                <div class="stat-icon">
                    <i class="fa fa-file-text"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value"><?php echo $articles_count; ?></div>
                    <div class="stat-label">文章总数</div>
                </div>
            </div>
            
            <div class="stat-card secondary">
                <div class="stat-icon">
                    <i class="fa fa-users"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value"><?php echo $users_count; ?></div>
                    <div class="stat-label">用户总数</div>
                </div>
            </div>
            
            <div class="stat-card success">
                <div class="stat-icon">
                    <i class="fa fa-comments"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value"><?php echo $comments_count; ?></div>
                    <div class="stat-label">评论总数</div>
                </div>
            </div>
            
            <div class="stat-card warning">
                <div class="stat-icon">
                    <i class="fa fa-image"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value"><?php echo $images_count; ?></div>
                    <div class="stat-label">图片总数</div>
                </div>
            </div>
        </div>
        
        <div class="quick-actions">
            <h2>快捷操作</h2>
            <div class="actions-grid">
                <a href="articles.php?action=add" class="action-card">
                    <div class="action-icon"><i class="fa fa-plus-circle"></i></div>
                    <div class="action-text">发布文章</div>
                </a>
                
                <a href="comments.php" class="action-card">
                    <div class="action-icon"><i class="fa fa-comment"></i></div>
                    <div class="action-text">管理评论</div>
                </a>
                
                <a href="users.php" class="action-card">
                    <div class="action-icon"><i class="fa fa-user"></i></div>
                    <div class="action-text">管理用户</div>
                </a>
                
                <a href="images.php" class="action-card">
                    <div class="action-icon"><i class="fa fa-image"></i></div>
                    <div class="action-text">图片管理</div>
                </a>
                
                <a href="../index.php" target="_blank" class="action-card">
                    <div class="action-icon"><i class="fa fa-external-link"></i></div>
                    <div class="action-text">查看网站</div>
                </a>
                
                <a href="settings.php" class="action-card">
                    <div class="action-icon"><i class="fa fa-cog"></i></div>
                    <div class="action-text">系统设置</div>
                </a>
            </div>
        </div>
        
        <div class="dashboard-row">
            <div class="dashboard-panel">
                <div class="panel-header">
                    <h2>最近发布的文章</h2>
                    <a href="articles.php" class="view-all">查看全部</a>
                </div>
                <div class="panel-body">
                    <div class="data-table">
                        <table>
                            <thead>
                                <tr>
                                    <th>标题</th>
                                    <th>分类</th>
                                    <th>作者</th>
                                    <th>发布时间</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recent_articles)): ?>
                                <tr>
                                    <td colspan="5" class="empty-data">暂无文章</td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($recent_articles as $article): ?>
                                <tr>
                                    <td class="title-cell"><?php echo $article['title']; ?></td>
                                    <td><?php echo $article['category_name']; ?></td>
                                    <td><?php echo $article['nickname'] ?: $article['username']; ?></td>
                                    <td><?php echo date('Y-m-d', strtotime($article['created_at'])); ?></td>
                                    <td class="actions-cell">
                                        <a href="articles.php?action=edit&id=<?php echo $article['id']; ?>" class="btn-icon" title="编辑">
                                            <i class="fa fa-edit"></i>
                                        </a>
                                        <a href="../article.php?id=<?php echo $article['id']; ?>" target="_blank" class="btn-icon" title="查看">
                                            <i class="fa fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="dashboard-panel">
                <div class="panel-header">
                    <h2>最近评论</h2>
                    <a href="comments.php" class="view-all">查看全部</a>
                </div>
                <div class="panel-body">
                    <div class="comments-list">
                        <?php if (empty($recent_comments)): ?>
                        <div class="empty-data">暂无评论</div>
                        <?php else: ?>
                        <?php foreach ($recent_comments as $comment): ?>
                        <div class="comment-item">
                            <div class="comment-header">
                                <span class="comment-author"><?php echo $comment['nickname'] ?: $comment['username']; ?></span>
                                <span class="comment-date"><?php echo date('Y-m-d H:i', strtotime($comment['created_at'])); ?></span>
                            </div>
                            <div class="comment-text"><?php echo mb_substr(strip_tags($comment['content']), 0, 100, 'UTF-8') . (mb_strlen($comment['content'], 'UTF-8') > 100 ? '...' : ''); ?></div>
                            <a href="../article.php?id=<?php echo $comment['article_id']; ?>" class="comment-article" target="_blank">
                                评论于：<?php echo $comment['title']; ?>
                            </a>
                            <div class="comment-actions">
                                <a href="comments.php?action=edit&id=<?php echo $comment['comment_id']; ?>" class="btn-icon" title="编辑">
                                    <i class="fa fa-edit"></i>
                                </a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// 引入管理后台页脚
require_once 'includes/admin_footer.php';
?> 