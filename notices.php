<?php
/**
 * 通知公告页面
 */

// 页面信息
$pageTitle = '通知公告';
$bannerImage = 'assets/images/banners/notices.jpg';
$breadcrumb = [
    ['name' => '首页', 'link' => 'index.php'],
    ['name' => '通知公告', 'link' => null]
];

// 引入头部
require_once 'includes/header.php';

// 分页参数
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

// 获取通知分类ID
$conn = getDbConnection();
$stmt = $conn->prepare("SELECT id FROM categories WHERE slug = 'notices'");
$stmt->execute();
$result = $stmt->get_result();
$categoryId = $result->fetch_assoc()['id'] ?? null;
$stmt->close();

// 如果分类不存在，创建新的分类
if (!$categoryId) {
    $stmt = $conn->prepare("INSERT INTO categories (name, slug, description) VALUES ('通知公告', 'notices', '学校体育中心的通知公告')");
    $stmt->execute();
    $categoryId = $conn->insert_id;
    $stmt->close();
}

// 获取通知总数
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM articles WHERE category_id = ? AND is_published = 1");
$stmt->bind_param("i", $categoryId);
$stmt->execute();
$total = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

// 计算总页数
$totalPages = ceil($total / $perPage);

// 获取通知列表
$stmt = $conn->prepare("SELECT a.*, u.username as author_name 
                       FROM articles a 
                       LEFT JOIN users u ON a.author_id = u.id 
                       WHERE a.category_id = ? AND a.is_published = 1 
                       ORDER BY a.created_at DESC 
                       LIMIT ? OFFSET ?");
$stmt->bind_param("iii", $categoryId, $perPage, $offset);
$stmt->execute();
$notices = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// 关闭数据库连接
$conn->close();
?>

<!-- 页面内容 -->
<main class="container">
    <div class="notices-header">
        <h1>通知公告</h1>
        <p>体育中心的重要通知和公告信息</p>
    </div>
    
    <?php if (empty($notices)): ?>
    <div class="empty-content">
        <div class="empty-icon">
            <i class="fas fa-bullhorn"></i>
        </div>
        <h3>暂无通知</h3>
        <p>目前还没有发布任何通知公告，请稍后再来查看。</p>
    </div>
    <?php else: ?>
    
    <div class="notices-list">
        <?php foreach ($notices as $notice): ?>
        <div class="notice-item">
            <div class="notice-date">
                <div class="date-day"><?php echo date('d', strtotime($notice['created_at'])); ?></div>
                <div class="date-month"><?php echo date('m', strtotime($notice['created_at'])); ?>月</div>
                <div class="date-year"><?php echo date('Y', strtotime($notice['created_at'])); ?></div>
            </div>
            <div class="notice-content">
                <h3 class="notice-title">
                    <a href="article.php?id=<?php echo $notice['id']; ?>"><?php echo $notice['title']; ?></a>
                </h3>
                <div class="notice-excerpt">
                    <?php 
                    // 提取纯文本摘要
                    $excerpt = strip_tags($notice['content']);
                    echo mb_substr($excerpt, 0, 150) . (mb_strlen($excerpt) > 150 ? '...' : '');
                    ?>
                </div>
                <div class="notice-meta">
                    <span class="notice-author"><i class="far fa-user"></i> <?php echo $notice['author_name']; ?></span>
                    <a href="article.php?id=<?php echo $notice['id']; ?>" class="read-more">查看详情 <i class="fas fa-angle-right"></i></a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <!-- 分页 -->
    <?php if ($totalPages > 1): ?>
    <div class="pagination">
        <ul>
            <?php if ($page > 1): ?>
            <li><a href="notices.php?page=<?php echo ($page - 1); ?>"><i class="fas fa-chevron-left"></i> 上一页</a></li>
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
                echo '<li><a href="notices.php?page=1">1</a></li>';
                if ($startPage > 2) {
                    echo '<li class="ellipsis">...</li>';
                }
            }
            
            // 输出页码
            for ($i = $startPage; $i <= $endPage; $i++) {
                if ($i == $page) {
                    echo '<li class="active"><span>' . $i . '</span></li>';
                } else {
                    echo '<li><a href="notices.php?page=' . $i . '">' . $i . '</a></li>';
                }
            }
            
            // 输出最后页码和省略号
            if ($endPage < $totalPages) {
                if ($endPage < $totalPages - 1) {
                    echo '<li class="ellipsis">...</li>';
                }
                echo '<li><a href="notices.php?page=' . $totalPages . '">' . $totalPages . '</a></li>';
            }
            ?>
            
            <?php if ($page < $totalPages): ?>
            <li><a href="notices.php?page=<?php echo ($page + 1); ?>">下一页 <i class="fas fa-chevron-right"></i></a></li>
            <?php endif; ?>
        </ul>
    </div>
    <?php endif; ?>
    
    <?php endif; ?>
</main>

<style>
.notices-header {
    text-align: center;
    margin-bottom: 40px;
    padding-bottom: 20px;
    border-bottom: 1px solid #eee;
}

.notices-header h1 {
    font-size: 2.5rem;
    margin-bottom: 15px;
    color: var(--primary-color);
}

.notices-header p {
    font-size: 1.1rem;
    color: #666;
    max-width: 700px;
    margin: 0 auto;
}

.notices-list {
    display: flex;
    flex-direction: column;
    gap: 25px;
    margin-bottom: 50px;
}

.notice-item {
    display: flex;
    background-color: #fff;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 3px 15px rgba(0, 0, 0, 0.08);
    transition: transform 0.3s ease;
}

.notice-item:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.12);
}

.notice-date {
    width: 100px;
    padding: 20px 15px;
    background-color: var(--primary-color);
    color: #fff;
    text-align: center;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
}

.date-day {
    font-size: 2.2rem;
    font-weight: 700;
    line-height: 1;
    margin-bottom: 5px;
}

.date-month {
    font-size: 1.1rem;
    margin-bottom: 3px;
}

.date-year {
    font-size: 0.9rem;
    opacity: 0.8;
}

.notice-content {
    flex: 1;
    padding: 25px;
}

.notice-title {
    font-size: 1.4rem;
    margin-bottom: 15px;
    line-height: 1.4;
}

.notice-title a {
    color: #333;
    text-decoration: none;
    transition: color 0.2s;
}

.notice-title a:hover {
    color: var(--primary-color);
}

.notice-excerpt {
    color: #666;
    margin-bottom: 15px;
    line-height: 1.6;
}

.notice-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.9rem;
}

.notice-author {
    color: #888;
    display: flex;
    align-items: center;
}

.notice-author i {
    margin-right: 5px;
}

.read-more {
    display: inline-flex;
    align-items: center;
    color: var(--primary-color);
    font-weight: 600;
    text-decoration: none;
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
    .notice-item {
        flex-direction: column;
    }
    
    .notice-date {
        width: 100%;
        padding: 10px;
        flex-direction: row;
        gap: 10px;
    }
    
    .notices-header h1 {
        font-size: 2rem;
    }
    
    .notice-title {
        font-size: 1.2rem;
    }
    
    .pagination a, .pagination span {
        padding: 6px 12px;
    }
}
</style>

<?php require_once 'includes/footer.php'; ?> 