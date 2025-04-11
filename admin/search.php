<?php
/**
 * 搜索功能页面
 */
$page_title = "搜索结果";
include 'includes/admin_header.php';

// 检查管理员登录状态
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

// 获取搜索关键词
$search = isset($_GET['q']) ? trim($_GET['q']) : '';
$search = htmlspecialchars($search, ENT_QUOTES, 'UTF-8');

// 如果搜索为空则显示搜索表单
if (empty($search)) {
    ?>
    <div class="admin-content">
        <div class="admin-content-header">
            <h2><i class="fa fa-search"></i> 搜索</h2>
        </div>
        <div class="admin-content-body">
            <div class="search-form-container">
                <form action="search.php" method="get" class="search-form">
                    <div class="form-group">
                        <label for="search-input">请输入搜索关键词</label>
                        <div class="search-input-wrapper">
                            <input type="text" name="q" id="search-input" class="form-control" placeholder="搜索文章、评论、用户或图片..." required>
                            <button type="submit" class="btn btn-primary"><i class="fa fa-search"></i> 搜索</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php
    include 'includes/admin_footer.php';
    exit();
}

// 初始化结果数组
$article_results = [];
$comment_results = [];
$user_results = [];
$image_results = [];
$total_results = 0;

// 连接数据库
require_once '../includes/db.php';

// 搜索文章
$stmt = $conn->prepare("SELECT a.*, c.category_name, u.username, u.nickname 
                        FROM articles a 
                        LEFT JOIN categories c ON a.category_id = c.category_id 
                        LEFT JOIN users u ON a.author_id = u.user_id 
                        WHERE (a.title LIKE ? OR a.content LIKE ?) 
                        ORDER BY a.created_at DESC 
                        LIMIT 20");
$search_pattern = '%' . $search . '%';
$stmt->bind_param("ss", $search_pattern, $search_pattern);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $article_results[] = $row;
}
$articles_count = count($article_results);

// 搜索评论
$stmt = $conn->prepare("SELECT c.*, a.title as article_title, u.username, u.nickname 
                        FROM comments c 
                        LEFT JOIN articles a ON c.article_id = a.article_id 
                        LEFT JOIN users u ON c.user_id = u.user_id 
                        WHERE c.content LIKE ? 
                        ORDER BY c.created_at DESC 
                        LIMIT 20");
$stmt->bind_param("s", $search_pattern);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $comment_results[] = $row;
}
$comments_count = count($comment_results);

// 搜索用户
$stmt = $conn->prepare("SELECT * FROM users 
                        WHERE username LIKE ? 
                        OR email LIKE ? 
                        OR nickname LIKE ? 
                        OR bio LIKE ? 
                        ORDER BY created_at DESC 
                        LIMIT 20");
$stmt->bind_param("ssss", $search_pattern, $search_pattern, $search_pattern, $search_pattern);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $user_results[] = $row;
}
$users_count = count($user_results);

// 搜索图片
$stmt = $conn->prepare("SELECT * FROM images 
                        WHERE filename LIKE ? 
                        OR description LIKE ? 
                        ORDER BY uploaded_at DESC 
                        LIMIT 20");
$stmt->bind_param("ss", $search_pattern, $search_pattern);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $image_results[] = $row;
}
$images_count = count($image_results);

// 计算总结果数
$total_results = $articles_count + $comments_count + $users_count + $images_count;

// 辅助函数：截断文本
function truncateText($text, $length = 100) {
    if (mb_strlen($text, 'UTF-8') > $length) {
        return mb_substr($text, 0, $length, 'UTF-8') . '...';
    }
    return $text;
}

// 辅助函数：高亮搜索关键词
function highlightText($text, $search) {
    return str_replace($search, '<span class="highlight">' . $search . '</span>', $text);
}

// 获取当前活动的标签页
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'all';
?>

<div class="admin-content">
    <div class="admin-content-header">
        <h2><i class="fa fa-search"></i> 搜索结果</h2>
        <div>
            <a href="search.php" class="btn btn-secondary"><i class="fa fa-redo"></i> 新搜索</a>
        </div>
    </div>
    <div class="admin-content-body">
        <div class="search-form-container">
            <form action="search.php" method="get" class="search-form">
                <div class="search-input-wrapper">
                    <input type="text" name="q" value="<?php echo $search; ?>" class="form-control" placeholder="搜索文章、评论、用户或图片...">
                    <button type="submit" class="btn btn-primary"><i class="fa fa-search"></i> 搜索</button>
                </div>
            </form>
        </div>

        <?php if ($total_results == 0): ?>
        <div class="empty-results">
            <div class="empty-icon"><i class="fa fa-search"></i></div>
            <h3>未找到结果</h3>
            <p>没有找到与 "<?php echo $search; ?>" 相关的内容</p>
            <div class="search-suggestions">
                <h4>建议：</h4>
                <ul>
                    <li>检查您的搜索关键词是否有拼写错误</li>
                    <li>尝试使用更少或更通用的关键词</li>
                    <li>尝试使用相关的关键词</li>
                </ul>
            </div>
        </div>
        <?php else: ?>
        <div class="search-results-summary">
            找到 <strong><?php echo $total_results; ?></strong> 个与 "<strong><?php echo $search; ?></strong>" 相关的结果
        </div>

        <div class="search-tabs">
            <ul>
                <li class="<?php echo $active_tab == 'all' ? 'active' : ''; ?>">
                    <a href="search.php?q=<?php echo urlencode($search); ?>&tab=all">全部 (<?php echo $total_results; ?>)</a>
                </li>
                <?php if ($articles_count > 0): ?>
                <li class="<?php echo $active_tab == 'articles' ? 'active' : ''; ?>">
                    <a href="search.php?q=<?php echo urlencode($search); ?>&tab=articles">文章 (<?php echo $articles_count; ?>)</a>
                </li>
                <?php endif; ?>
                <?php if ($comments_count > 0): ?>
                <li class="<?php echo $active_tab == 'comments' ? 'active' : ''; ?>">
                    <a href="search.php?q=<?php echo urlencode($search); ?>&tab=comments">评论 (<?php echo $comments_count; ?>)</a>
                </li>
                <?php endif; ?>
                <?php if ($users_count > 0): ?>
                <li class="<?php echo $active_tab == 'users' ? 'active' : ''; ?>">
                    <a href="search.php?q=<?php echo urlencode($search); ?>&tab=users">用户 (<?php echo $users_count; ?>)</a>
                </li>
                <?php endif; ?>
                <?php if ($images_count > 0): ?>
                <li class="<?php echo $active_tab == 'images' ? 'active' : ''; ?>">
                    <a href="search.php?q=<?php echo urlencode($search); ?>&tab=images">图片 (<?php echo $images_count; ?>)</a>
                </li>
                <?php endif; ?>
            </ul>
        </div>

        <div class="search-results">
            <?php if ($active_tab == 'all' || $active_tab == 'articles'): ?>
            <?php if ($articles_count > 0): ?>
            <div class="search-section">
                <div class="search-section-header">
                    <h3>文章 (<?php echo $articles_count; ?>)</h3>
                    <?php if ($articles_count > 5 && $active_tab == 'all'): ?>
                    <a href="search.php?q=<?php echo urlencode($search); ?>&tab=articles" class="view-all">查看全部</a>
                    <?php endif; ?>
                </div>
                <div class="result-list">
                    <?php 
                    $display_limit = ($active_tab == 'all') ? 5 : count($article_results);
                    for ($i = 0; $i < min($display_limit, count($article_results)); $i++): 
                        $article = $article_results[$i];
                    ?>
                    <div class="result-item">
                        <div class="result-title">
                            <a href="articles.php?action=edit&id=<?php echo $article['article_id']; ?>">
                                <?php echo highlightText($article['title'], $search); ?>
                            </a>
                        </div>
                        <div class="result-meta">
                            <span><i class="fa fa-folder"></i> <?php echo $article['category_name']; ?></span>
                            <span><i class="fa fa-user"></i> <?php echo $article['nickname'] ?: $article['username']; ?></span>
                            <span><i class="fa fa-clock"></i> <?php echo date('Y-m-d', strtotime($article['created_at'])); ?></span>
                        </div>
                        <div class="result-content">
                            <?php 
                            $content = strip_tags($article['content']);
                            echo highlightText(truncateText($content), $search); 
                            ?>
                        </div>
                        <div class="result-actions">
                            <a href="articles.php?action=edit&id=<?php echo $article['article_id']; ?>" class="btn-icon" title="编辑"><i class="fa fa-edit"></i></a>
                            <a href="../article.php?id=<?php echo $article['article_id']; ?>" target="_blank" class="btn-icon" title="查看"><i class="fa fa-eye"></i></a>
                        </div>
                    </div>
                    <?php endfor; ?>
                </div>
            </div>
            <?php endif; ?>
            <?php endif; ?>

            <?php if ($active_tab == 'all' || $active_tab == 'comments'): ?>
            <?php if ($comments_count > 0): ?>
            <div class="search-section">
                <div class="search-section-header">
                    <h3>评论 (<?php echo $comments_count; ?>)</h3>
                    <?php if ($comments_count > 5 && $active_tab == 'all'): ?>
                    <a href="search.php?q=<?php echo urlencode($search); ?>&tab=comments" class="view-all">查看全部</a>
                    <?php endif; ?>
                </div>
                <div class="result-list">
                    <?php 
                    $display_limit = ($active_tab == 'all') ? 5 : count($comment_results);
                    for ($i = 0; $i < min($display_limit, count($comment_results)); $i++): 
                        $comment = $comment_results[$i];
                    ?>
                    <div class="result-item">
                        <div class="result-title">
                            评论于文章: <a href="../article.php?id=<?php echo $comment['article_id']; ?>" target="_blank">
                                <?php echo $comment['article_title']; ?>
                            </a>
                        </div>
                        <div class="result-meta">
                            <span><i class="fa fa-user"></i> <?php echo $comment['nickname'] ?: $comment['username']; ?></span>
                            <span><i class="fa fa-clock"></i> <?php echo date('Y-m-d', strtotime($comment['created_at'])); ?></span>
                        </div>
                        <div class="result-content">
                            <?php echo highlightText($comment['content'], $search); ?>
                        </div>
                        <div class="result-actions">
                            <a href="comments.php?action=edit&id=<?php echo $comment['comment_id']; ?>" class="btn-icon" title="编辑"><i class="fa fa-edit"></i></a>
                            <a href="../article.php?id=<?php echo $comment['article_id']; ?>#comment-<?php echo $comment['comment_id']; ?>" target="_blank" class="btn-icon" title="查看"><i class="fa fa-eye"></i></a>
                        </div>
                    </div>
                    <?php endfor; ?>
                </div>
            </div>
            <?php endif; ?>
            <?php endif; ?>

            <?php if ($active_tab == 'all' || $active_tab == 'users'): ?>
            <?php if ($users_count > 0): ?>
            <div class="search-section">
                <div class="search-section-header">
                    <h3>用户 (<?php echo $users_count; ?>)</h3>
                    <?php if ($users_count > 5 && $active_tab == 'all'): ?>
                    <a href="search.php?q=<?php echo urlencode($search); ?>&tab=users" class="view-all">查看全部</a>
                    <?php endif; ?>
                </div>
                <div class="result-list result-list-grid">
                    <?php 
                    $display_limit = ($active_tab == 'all') ? 5 : count($user_results);
                    for ($i = 0; $i < min($display_limit, count($user_results)); $i++): 
                        $user = $user_results[$i];
                    ?>
                    <div class="result-item result-item-user">
                        <div class="user-avatar">
                            <?php if (!empty($user['avatar'])): ?>
                            <img src="<?php echo $user['avatar']; ?>" alt="<?php echo $user['username']; ?>">
                            <?php else: ?>
                            <div class="avatar-placeholder"><i class="fa fa-user"></i></div>
                            <?php endif; ?>
                        </div>
                        <div class="user-info">
                            <div class="result-title">
                                <?php echo highlightText($user['nickname'] ?: $user['username'], $search); ?>
                                <?php if ($user['role'] == 'admin'): ?><span class="badge-admin">管理员</span><?php endif; ?>
                            </div>
                            <div class="result-meta">
                                <span><i class="fa fa-user"></i> <?php echo highlightText($user['username'], $search); ?></span>
                                <span><i class="fa fa-envelope"></i> <?php echo highlightText($user['email'], $search); ?></span>
                            </div>
                            <?php if (!empty($user['bio'])): ?>
                            <div class="result-content">
                                <?php echo highlightText(truncateText($user['bio']), $search); ?>
                            </div>
                            <?php endif; ?>
                            <div class="result-actions">
                                <a href="users.php?action=edit&id=<?php echo $user['user_id']; ?>" class="btn-icon" title="编辑"><i class="fa fa-edit"></i></a>
                                <a href="../profile.php?id=<?php echo $user['user_id']; ?>" target="_blank" class="btn-icon" title="查看"><i class="fa fa-eye"></i></a>
                            </div>
                        </div>
                    </div>
                    <?php endfor; ?>
                </div>
            </div>
            <?php endif; ?>
            <?php endif; ?>

            <?php if ($active_tab == 'all' || $active_tab == 'images'): ?>
            <?php if ($images_count > 0): ?>
            <div class="search-section">
                <div class="search-section-header">
                    <h3>图片 (<?php echo $images_count; ?>)</h3>
                    <?php if ($images_count > 5 && $active_tab == 'all'): ?>
                    <a href="search.php?q=<?php echo urlencode($search); ?>&tab=images" class="view-all">查看全部</a>
                    <?php endif; ?>
                </div>
                <div class="result-list result-list-images">
                    <?php 
                    $display_limit = ($active_tab == 'all') ? 5 : count($image_results);
                    for ($i = 0; $i < min($display_limit, count($image_results)); $i++): 
                        $image = $image_results[$i];
                    ?>
                    <div class="result-item result-item-image">
                        <div class="image-preview">
                            <img src="<?php echo $image['file_path']; ?>" alt="<?php echo $image['filename']; ?>">
                        </div>
                        <div class="image-info">
                            <div class="result-title">
                                <?php echo highlightText($image['filename'], $search); ?>
                            </div>
                            <div class="result-meta">
                                <span><i class="fa fa-calendar"></i> <?php echo date('Y-m-d', strtotime($image['uploaded_at'])); ?></span>
                                <span><i class="fa fa-image"></i> <?php echo $image['file_size']; ?> KB</span>
                            </div>
                            <?php if (!empty($image['description'])): ?>
                            <div class="result-content">
                                <?php echo highlightText($image['description'], $search); ?>
                            </div>
                            <?php endif; ?>
                            <div class="result-actions">
                                <a href="images.php?action=edit&id=<?php echo $image['image_id']; ?>" class="btn-icon" title="编辑"><i class="fa fa-edit"></i></a>
                                <button class="btn-icon copy-link" data-link="<?php echo $image['file_path']; ?>" title="复制链接"><i class="fa fa-link"></i></button>
                                <a href="<?php echo $image['file_path']; ?>" target="_blank" class="btn-icon" title="预览"><i class="fa fa-eye"></i></a>
                            </div>
                        </div>
                    </div>
                    <?php endfor; ?>
                </div>
            </div>
            <?php endif; ?>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<style>
.search-form-container {
    margin-bottom: 20px;
}

.search-form {
    max-width: 600px;
    margin: 0 auto;
}

.search-input-wrapper {
    display: flex;
    gap: 10px;
}

.search-input-wrapper input {
    flex: 1;
}

.empty-results {
    text-align: center;
    padding: 50px 20px;
}

.empty-icon {
    font-size: 48px;
    color: var(--admin-gray);
    margin-bottom: 20px;
}

.empty-results h3 {
    font-size: 24px;
    margin-bottom: 10px;
    color: var(--admin-text);
}

.empty-results p {
    color: var(--admin-text-light);
    margin-bottom: 20px;
}

.search-suggestions {
    max-width: 400px;
    margin: 0 auto;
    text-align: left;
}

.search-suggestions h4 {
    margin-bottom: 10px;
    color: var(--admin-text);
}

.search-suggestions ul {
    list-style-type: disc;
    padding-left: 20px;
}

.search-suggestions li {
    margin-bottom: 5px;
    color: var(--admin-text-light);
}

.search-results-summary {
    margin-bottom: 20px;
    padding: 10px;
    background-color: var(--admin-light-bg);
    border-radius: 4px;
}

.search-tabs {
    margin-bottom: 20px;
    border-bottom: 1px solid var(--admin-border-color);
}

.search-tabs ul {
    display: flex;
    list-style: none;
    padding: 0;
    margin: 0;
}

.search-tabs li {
    margin-right: 5px;
}

.search-tabs li a {
    display: block;
    padding: 10px 15px;
    text-decoration: none;
    color: var(--admin-text-light);
    border-bottom: 2px solid transparent;
}

.search-tabs li.active a {
    color: var(--admin-primary);
    border-bottom-color: var(--admin-primary);
}

.search-tabs li a:hover {
    color: var(--admin-primary);
}

.search-section {
    margin-bottom: 30px;
}

.search-section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.search-section-header h3 {
    font-size: 18px;
    margin: 0;
    color: var(--admin-text);
}

.result-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.result-item {
    background-color: var(--admin-white);
    border: 1px solid var(--admin-border-color);
    border-radius: 5px;
    padding: 15px;
    transition: all 0.2s ease;
}

.result-item:hover {
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.result-title {
    font-size: 16px;
    font-weight: 500;
    margin-bottom: 10px;
}

.result-title a {
    color: var(--admin-primary);
    text-decoration: none;
}

.result-title a:hover {
    text-decoration: underline;
}

.result-meta {
    display: flex;
    gap: 15px;
    font-size: 12px;
    color: var(--admin-gray);
    margin-bottom: 10px;
}

.result-content {
    color: var(--admin-text-light);
    margin-bottom: 10px;
    line-height: 1.5;
}

.result-actions {
    display: flex;
    gap: 5px;
    justify-content: flex-end;
}

.highlight {
    background-color: rgba(255, 215, 0, 0.3);
    padding: 0 2px;
    border-radius: 2px;
}

.result-list-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 15px;
}

.result-item-user {
    display: flex;
    gap: 15px;
}

.user-avatar img,
.user-avatar .avatar-placeholder {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    object-fit: cover;
}

.user-avatar .avatar-placeholder {
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: #e1e1e1;
    color: #999;
    font-size: 24px;
}

.user-info {
    flex: 1;
}

.badge-admin {
    display: inline-block;
    background-color: var(--admin-primary);
    color: white;
    font-size: 10px;
    padding: 2px 6px;
    border-radius: 10px;
    margin-left: 5px;
    vertical-align: middle;
}

.result-list-images {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 15px;
}

.result-item-image {
    display: flex;
    flex-direction: column;
}

.result-item-image .image-preview {
    height: 150px;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    margin-bottom: 10px;
    border-radius: 4px;
    background-color: #f0f0f0;
}

.result-item-image .image-preview img {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
}

@media (max-width: 768px) {
    .search-tabs ul {
        overflow-x: auto;
        white-space: nowrap;
        padding-bottom: 5px;
    }
    
    .result-list-grid,
    .result-list-images {
        grid-template-columns: 1fr;
    }
    
    .search-input-wrapper {
        flex-direction: column;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 复制链接功能
    const copyButtons = document.querySelectorAll('.copy-link');
    copyButtons.forEach(button => {
        button.addEventListener('click', function() {
            const link = this.getAttribute('data-link');
            navigator.clipboard.writeText(link)
                .then(() => {
                    showCopyMessage(this, '链接已复制');
                })
                .catch(err => {
                    console.error('复制失败: ', err);
                    showCopyMessage(this, '复制失败', true);
                });
        });
    });

    function showCopyMessage(element, message, isError = false) {
        const messageElement = document.createElement('div');
        messageElement.className = `copy-message ${isError ? 'error' : ''}`;
        messageElement.textContent = message;
        
        // 获取按钮相对于视口的位置
        const rect = element.getBoundingClientRect();
        
        // 设置消息框的位置
        messageElement.style.position = 'fixed';
        messageElement.style.top = `${rect.top - 30}px`;
        messageElement.style.left = `${rect.left + rect.width / 2}px`;
        messageElement.style.transform = 'translateX(-50%)';
        messageElement.style.backgroundColor = isError ? '#f44336' : '#4caf50';
        messageElement.style.color = 'white';
        messageElement.style.padding = '5px 10px';
        messageElement.style.borderRadius = '3px';
        messageElement.style.zIndex = '1000';
        messageElement.style.boxShadow = '0 2px 5px rgba(0,0,0,0.2)';
        messageElement.style.animation = 'fadeIn 0.3s ease-out';
        
        document.body.appendChild(messageElement);
        
        // 2秒后移除消息
        setTimeout(() => {
            messageElement.style.animation = 'fadeOut 0.3s ease-out';
            setTimeout(() => {
                document.body.removeChild(messageElement);
            }, 300);
        }, 2000);
    }
});
</script>

<?php include 'includes/admin_footer.php'; ?> 