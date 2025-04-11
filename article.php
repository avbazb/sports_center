<?php
/**
 * 文章详情页
 */

// 引入头部
require_once 'includes/header.php';

// 检查文章ID是否存在
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo '<div class="container"><div class="alert alert-danger">无效的文章ID</div></div>';
    require_once 'includes/footer.php';
    exit;
}

$articleId = intval($_GET['id']);

// 获取文章详情
$article = getArticleById($articleId);

// 如果文章不存在，显示错误信息
if (!$article) {
    echo '<div class="container"><div class="alert alert-danger">文章不存在或已被删除</div></div>';
    require_once 'includes/footer.php';
    exit;
}

// 更新页面信息
$pageTitle = $article['title'];
$pageBanner = !empty($article['thumbnail']) ? $article['thumbnail'] : 'assets/images/banner-article.jpg';
$pageBreadcrumb = [
    (isset($article['category_slug']) ? $article['category_slug'] : 'news') . '.php' => $article['category_name'],
    'article.php?id=' . $article['id'] => $article['title']
];

// 处理评论提交
$commentError = '';
$commentSuccess = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isLoggedIn()) {
    if (isset($_POST['comment_content']) && !empty($_POST['comment_content'])) {
        $content = sanitizeInput($_POST['comment_content']);
        $userId = getCurrentUserId();
        
        // 添加评论
        $result = addComment($articleId, $userId, $content);
        
        if ($result['status']) {
            $commentSuccess = '评论发表成功';
        } else {
            $commentError = $result['message'];
        }
    } else {
        $commentError = '评论内容不能为空';
    }
}

// 获取文章评论
$comments = getArticleComments($articleId);

// 获取相关文章
$relatedArticles = getArticles($article['category_id'], 3);
// 过滤掉当前文章
$relatedArticles = array_filter($relatedArticles, function($a) use ($articleId) {
    return $a['id'] != $articleId;
});
?>

<div class="container">
    <div class="section">
        <div class="row">
            <div class="col-md-8">
                <!-- 文章详情 -->
                <div class="article-details animated fadeIn">
                    <div class="article-header">
                        <h1 class="article-title"><?php echo $article['title']; ?></h1>
                        
                        <div class="article-meta">
                            <div class="article-category">
                                <i class="fas fa-folder"></i> <?php echo $article['category_name']; ?>
                            </div>
                            <div class="article-author">
                                <i class="fas fa-user"></i> <?php echo $article['author_name']; ?>
                            </div>
                            <div class="article-date">
                                <i class="fas fa-calendar"></i> <?php echo date('Y-m-d', strtotime($article['created_at'])); ?>
                            </div>
                            <div class="article-views">
                                <i class="fas fa-eye"></i> <?php echo $article['view_count']; ?>
                            </div>
                        </div>
                    </div>
                    
                    <?php if (!empty($article['thumbnail'])): ?>
                    <div class="article-main-image">
                        <img src="<?php echo $article['thumbnail']; ?>" alt="<?php echo $article['title']; ?>">
                    </div>
                    <?php endif; ?>
                    
                    <div class="article-content">
                        <?php echo $article['content']; ?>
                    </div>
                </div>
                
                <!-- 评论区 -->
                <div class="comments-section animated fadeIn" style="animation-delay: 0.2s;">
                    <h3 class="comments-title">评论 (<?php echo count($comments); ?>)</h3>
                    
                    <?php if (isLoggedIn()): ?>
                    <!-- 评论表单 -->
                    <div class="comment-form">
                        <?php if ($commentError): ?>
                        <div class="alert alert-danger">
                            <?php echo $commentError; ?>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($commentSuccess): ?>
                        <div class="alert alert-success">
                            <?php echo $commentSuccess; ?>
                        </div>
                        <?php endif; ?>
                        
                        <form action="article.php?id=<?php echo $articleId; ?>" method="post" data-validate>
                            <div class="form-group">
                                <label for="comment_content">发表评论</label>
                                <textarea id="comment_content" name="comment_content" class="form-control" rows="4" required></textarea>
                            </div>
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary">提交评论</button>
                            </div>
                        </form>
                    </div>
                    <?php else: ?>
                    <div class="comment-login-prompt">
                        <p>请<a href="login.php">登录</a>后发表评论</p>
                    </div>
                    <?php endif; ?>
                    
                    <!-- 评论列表 -->
                    <div class="comment-list">
                        <?php if (empty($comments)): ?>
                        <p class="text-center">暂无评论，快来发表第一条评论吧！</p>
                        <?php else: ?>
                        <?php foreach ($comments as $comment): ?>
                        <div class="comment-item animated fadeInUp">
                            <div class="comment-author">
                                <div class="comment-avatar">
                                    <?php if (!empty($comment['avatar'])): ?>
                                    <img src="<?php echo $comment['avatar']; ?>" alt="<?php echo $comment['display_name']; ?>">
                                    <?php else: ?>
                                    <div class="avatar-placeholder">
                                        <i class="fas fa-user"></i>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <div class="comment-info">
                                    <div class="comment-name">
                                        <?php echo $comment['display_name']; ?>
                                        <?php if (!empty($comment['certification'])): ?>
                                        <span class="comment-certification"><?php echo $comment['certification']; ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="comment-date">
                                        <?php echo date('Y-m-d H:i', strtotime($comment['created_at'])); ?>
                                    </div>
                                </div>
                            </div>
                            <div class="comment-text">
                                <?php echo nl2br($comment['content']); ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <!-- 侧边栏 -->
                <div class="sidebar">
                    <!-- 作者信息 -->
                    <div class="sidebar-widget animated fadeInRight" style="animation-delay: 0.3s;">
                        <h3 class="widget-title">作者信息</h3>
                        <div class="author-card">
                            <div class="author-avatar">
                                <?php if (!empty($article['author_avatar'])): ?>
                                <img src="<?php echo $article['author_avatar']; ?>" alt="<?php echo $article['author_name']; ?>">
                                <?php else: ?>
                                <div class="avatar-placeholder">
                                    <i class="fas fa-user"></i>
                                </div>
                                <?php endif; ?>
                            </div>
                            <div class="author-info">
                                <h4 class="author-name"><?php echo $article['author_name']; ?></h4>
                                <a href="profile.php?id=<?php echo $article['author_id']; ?>" class="btn btn-sm btn-outline">查看个人主页</a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 相关文章 -->
                    <div class="sidebar-widget animated fadeInRight" style="animation-delay: 0.5s;">
                        <h3 class="widget-title">相关文章</h3>
                        <?php if (empty($relatedArticles)): ?>
                        <p>暂无相关文章</p>
                        <?php else: ?>
                        <div class="related-articles">
                            <?php foreach ($relatedArticles as $relatedArticle): ?>
                            <div class="related-article">
                                <?php if (!empty($relatedArticle['thumbnail'])): ?>
                                <div class="related-article-image">
                                    <img src="<?php echo $relatedArticle['thumbnail']; ?>" alt="<?php echo $relatedArticle['title']; ?>">
                                </div>
                                <?php endif; ?>
                                <div class="related-article-content">
                                    <h4 class="related-article-title">
                                        <a href="article.php?id=<?php echo $relatedArticle['id']; ?>"><?php echo $relatedArticle['title']; ?></a>
                                    </h4>
                                    <div class="related-article-date">
                                        <i class="fas fa-calendar"></i> <?php echo date('Y-m-d', strtotime($relatedArticle['created_at'])); ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* 文章详情页样式 */
.row {
    display: flex;
    flex-wrap: wrap;
    margin: 0 -15px;
}

.col-md-8 {
    width: 66.666%;
    padding: 0 15px;
}

.col-md-4 {
    width: 33.333%;
    padding: 0 15px;
}

.article-main-image {
    margin-bottom: 30px;
}

.article-main-image img {
    width: 100%;
    height: auto;
    border-radius: 8px;
    box-shadow: var(--box-shadow);
}

.article-content {
    line-height: 1.8;
    color: var(--text-color);
}

.article-content p {
    margin-bottom: 20px;
}

.article-content img {
    max-width: 100%;
    height: auto;
    border-radius: 8px;
    margin: 20px 0;
    box-shadow: var(--box-shadow);
}

.article-content h2 {
    margin-top: 40px;
    margin-bottom: 20px;
    font-size: 28px;
}

.article-content h3 {
    margin-top: 30px;
    margin-bottom: 15px;
    font-size: 24px;
}

.article-content ul,
.article-content ol {
    margin-bottom: 20px;
    padding-left: 20px;
}

.article-content li {
    margin-bottom: 10px;
}

.comment-login-prompt {
    text-align: center;
    margin-bottom: 30px;
    padding: 15px;
    background-color: #f8f9fa;
    border-radius: 4px;
}

.sidebar-widget {
    background-color: #fff;
    border-radius: 8px;
    box-shadow: var(--box-shadow);
    padding: 20px;
    margin-bottom: 30px;
}

.widget-title {
    font-size: 20px;
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 2px solid var(--primary-color);
}

.author-card {
    display: flex;
    align-items: center;
    gap: 15px;
}

.author-avatar {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    overflow: hidden;
}

.author-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.author-name {
    font-size: 18px;
    margin-bottom: 10px;
}

.related-article {
    display: flex;
    gap: 15px;
    margin-bottom: 15px;
    padding-bottom: 15px;
    border-bottom: 1px solid var(--border-color);
}

.related-article:last-child {
    margin-bottom: 0;
    padding-bottom: 0;
    border-bottom: none;
}

.related-article-image {
    width: 80px;
    height: 60px;
    border-radius: 4px;
    overflow: hidden;
    flex-shrink: 0;
}

.related-article-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.related-article-title {
    font-size: 16px;
    margin-bottom: 5px;
    line-height: 1.4;
}

.related-article-date {
    font-size: 14px;
    color: var(--lighter-text);
}

/* 响应式调整 */
@media (max-width: 992px) {
    .col-md-8,
    .col-md-4 {
        width: 100%;
    }
    
    .col-md-4 {
        margin-top: 30px;
    }
}
</style>

<?php
// 引入页脚
require_once 'includes/footer.php';
?>
 