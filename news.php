<?php
/**
 * 新闻消息页面
 */

// 页面信息
$pageTitle = '新闻消息';
$bannerImage = 'assets/images/banners/news.jpg';
$breadcrumb = [
    ['name' => '首页', 'link' => 'index.php'],
    ['name' => '新闻消息', 'link' => null]
];

// 引入头部
require_once 'includes/header.php';

// 分页参数
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$perPage = 9;
$offset = ($page - 1) * $perPage;

// 获取新闻分类ID
$conn = getDbConnection();
$stmt = $conn->prepare("SELECT id FROM categories WHERE slug = 'news'");
$stmt->execute();
$result = $stmt->get_result();
$categoryId = $result->fetch_assoc()['id'] ?? null;
$stmt->close();

// 如果分类不存在，创建新的分类
if (!$categoryId) {
    $stmt = $conn->prepare("INSERT INTO categories (name, slug, description) VALUES ('新闻消息', 'news', '学校体育中心的新闻消息')");
    $stmt->execute();
    $categoryId = $conn->insert_id;
    $stmt->close();
}

// 获取新闻总数
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM articles WHERE category_id = ? AND is_published = 1");
$stmt->bind_param("i", $categoryId);
$stmt->execute();
$total = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

// 计算总页数
$totalPages = ceil($total / $perPage);

// 获取新闻列表
$stmt = $conn->prepare("SELECT a.*, u.username as author_name 
                       FROM articles a 
                       LEFT JOIN users u ON a.author_id = u.id 
                       WHERE a.category_id = ? AND a.is_published = 1 
                       ORDER BY a.created_at DESC 
                       LIMIT ? OFFSET ?");
$stmt->bind_param("iii", $categoryId, $perPage, $offset);
$stmt->execute();
$news = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// 关闭数据库连接
$conn->close();
?>

<!-- 页面内容 -->
<main class="container">
    <div class="news-header">
        <h1>新闻消息</h1>
        <p>了解体育中心的最新动态和新闻</p>
    </div>
    
    <?php if (empty($news)): ?>
    <div class="empty-content">
        <div class="empty-icon">
            <i class="fas fa-newspaper"></i>
        </div>
        <h3>暂无新闻</h3>
        <p>目前还没有发布任何新闻消息，请稍后再来查看。</p>
    </div>
    <?php else: ?>
    
    <div class="articles-grid">
        <?php foreach ($news as $article): ?>
        <div class="article-card">
            <div class="article-image">
                <?php if (!empty($article['thumbnail'])): ?>
                <img src="<?php echo $article['thumbnail']; ?>" alt="<?php echo $article['title']; ?>">
                <?php else: ?>
                <div class="no-image">
                    <i class="fas fa-newspaper"></i>
                </div>
                <?php endif; ?>
            </div>
            <div class="article-content">
                <div class="article-meta">
                    <span class="article-date"><i class="far fa-calendar-alt"></i> <?php echo date('Y-m-d', strtotime($article['created_at'])); ?></span>
                    <span class="article-author"><i class="far fa-user"></i> <?php echo $article['author_name']; ?></span>
                </div>
                <h3 class="article-title">
                    <a href="article.php?id=<?php echo $article['id']; ?>"><?php echo $article['title']; ?></a>
                </h3>
                <div class="article-excerpt">
                    <?php 
                    // 提取纯文本摘要
                    $excerpt = strip_tags($article['content']);
                    echo mb_substr($excerpt, 0, 120) . (mb_strlen($excerpt) > 120 ? '...' : '');
                    ?>
                </div>
                <a href="article.php?id=<?php echo $article['id']; ?>" class="read-more">阅读更多 <i class="fas fa-angle-right"></i></a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <!-- 分页 -->
    <?php if ($totalPages > 1): ?>
    <div class="pagination">
        <ul>
            <?php if ($page > 1): ?>
            <li><a href="news.php?page=<?php echo ($page - 1); ?>"><i class="fas fa-chevron-left"></i> 上一页</a></li>
            <?php endif; ?>
            
            <?php
            // 计算显示的页码范围
            $startPage = max(1, $page - 2);
            $endPage = min($totalPages, $page + 2);
            
            // 确保始终显示 5 个页码（如果有足够的页数）
            if ($endPage - $startPage < 4) {
                if ($startPage == 1) {
                    $endPage = min($totalPages, 5);
                } elseif ($endPage == $totalPages) {
                    $startPage = max(1, $totalPages - 4);
                }
            }
            
            // 输出第一页和省略号
            if ($startPage > 1) {
                echo '<li><a href="news.php?page=1">1</a></li>';
                if ($startPage > 2) {
                    echo '<li class="ellipsis">...</li>';
                }
            }
            
            // 输出页码
            for ($i = $startPage; $i <= $endPage; $i++) {
                if ($i == $page) {
                    echo '<li class="active"><span>' . $i . '</span></li>';
                } else {
                    echo '<li><a href="news.php?page=' . $i . '">' . $i . '</a></li>';
                }
            }
            
            // 输出最后页码和省略号
            if ($endPage < $totalPages) {
                if ($endPage < $totalPages - 1) {
                    echo '<li class="ellipsis">...</li>';
                }
                echo '<li><a href="news.php?page=' . $totalPages . '">' . $totalPages . '</a></li>';
            }
            ?>
            
            <?php if ($page < $totalPages): ?>
            <li><a href="news.php?page=<?php echo ($page + 1); ?>">下一页 <i class="fas fa-chevron-right"></i></a></li>
            <?php endif; ?>
        </ul>
    </div>
    <?php endif; ?>
    
    <?php endif; ?>
</main>

<style>
.news-header {
    text-align: center;
    margin-bottom: 40px;
    padding-bottom: 20px;
    border-bottom: 1px solid #eee;
}

.news-header h1 {
    font-size: 2.5rem;
    margin-bottom: 15px;
    color: var(--primary-color);
}

.news-header p {
    font-size: 1.1rem;
    color: #666;
    max-width: 700px;
    margin: 0 auto;
}

.articles-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 30px;
    margin-bottom: 50px;
}

.article-card {
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 3px 15px rgba(0, 0, 0, 0.08);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    background-color: #fff;
}

.article-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.12);
}

.article-image {
    height: 200px;
    overflow: hidden;
}

.article-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.5s ease;
}

.article-card:hover .article-image img {
    transform: scale(1.05);
}

.no-image {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: #f5f5f5;
    color: #ccc;
    font-size: 3rem;
}

.article-content {
    padding: 20px;
}

.article-meta {
    display: flex;
    justify-content: space-between;
    margin-bottom: 10px;
    font-size: 0.9rem;
    color: #888;
}

.article-meta span {
    display: flex;
    align-items: center;
}

.article-meta i {
    margin-right: 5px;
}

.article-title {
    font-size: 1.3rem;
    margin-bottom: 15px;
    line-height: 1.4;
}

.article-title a {
    color: #333;
    text-decoration: none;
    transition: color 0.2s;
}

.article-title a:hover {
    color: var(--primary-color);
}

.article-excerpt {
    color: #666;
    margin-bottom: 15px;
    line-height: 1.6;
}

.read-more {
    display: inline-flex;
    align-items: center;
    color: var(--primary-color);
    font-weight: 600;
    text-decoration: none;
    font-size: 0.95rem;
    transition: all 0.2s;
}

.read-more i {
    margin-left: 5px;
    transition: transform 0.2s;
}

.read-more:hover {
    color: var(--secondary-color);
}

.read-more:hover i {
    transform: translateX(3px);
}

.empty-content {
    text-align: center;
    padding: 80px 0;
    color: #888;
}

.empty-icon {
    font-size: 4rem;
    margin-bottom: 20px;
    color: #ddd;
}

.empty-content h3 {
    font-size: 1.8rem;
    margin-bottom: 15px;
    color: #555;
}

.pagination {
    margin-top: 40px;
    margin-bottom: 60px;
}

.pagination ul {
    display: flex;
    justify-content: center;
    align-items: center;
    list-style: none;
    padding: 0;
    margin: 0;
}

.pagination li {
    margin: 0 5px;
}

.pagination a, .pagination span {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 8px 16px;
    border-radius: 5px;
    text-decoration: none;
    transition: all 0.2s;
}

.pagination a {
    background-color: #f5f5f5;
    color: #555;
}

.pagination a:hover {
    background-color: var(--primary-color);
    color: white;
}

.pagination .active span {
    background-color: var(--primary-color);
    color: white;
}

.pagination .ellipsis {
    padding: 8px 5px;
    color: #888;
}

@media (max-width: 768px) {
    .articles-grid {
        grid-template-columns: 1fr;
    }
    
    .news-header h1 {
        font-size: 2rem;
    }
    
    .pagination a, .pagination span {
        padding: 6px 12px;
    }
}
</style>

<?php require_once 'includes/footer.php'; ?> 