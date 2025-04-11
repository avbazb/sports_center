<?php
/**
 * 最新动态页面
 */

// 页面信息
$pageTitle = '最新动态';
$bannerImage = 'assets/images/banners/updates.jpg';
$breadcrumb = [
    ['name' => '首页', 'link' => 'index.php'],
    ['name' => '最新动态', 'link' => null]
];

// 引入头部
require_once 'includes/header.php';

// 分页参数
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$perPage = 6;
$offset = ($page - 1) * $perPage;

// 获取动态分类ID
$conn = getDbConnection();
$stmt = $conn->prepare("SELECT id FROM categories WHERE slug = 'updates'");
$stmt->execute();
$result = $stmt->get_result();
$categoryId = $result->fetch_assoc()['id'] ?? null;
$stmt->close();

// 如果分类不存在，创建新的分类
if (!$categoryId) {
    $stmt = $conn->prepare("INSERT INTO categories (name, slug, description) VALUES ('最新动态', 'updates', '学校体育中心的最新动态')");
    $stmt->execute();
    $categoryId = $conn->insert_id;
    $stmt->close();
}

// 获取动态总数
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM articles WHERE category_id = ? AND is_published = 1");
$stmt->bind_param("i", $categoryId);
$stmt->execute();
$total = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

// 计算总页数
$totalPages = ceil($total / $perPage);

// 获取动态列表
$stmt = $conn->prepare("SELECT a.*, u.username as author_name 
                       FROM articles a 
                       LEFT JOIN users u ON a.author_id = u.id 
                       WHERE a.category_id = ? AND a.is_published = 1 
                       ORDER BY a.created_at DESC 
                       LIMIT ? OFFSET ?");
$stmt->bind_param("iii", $categoryId, $perPage, $offset);
$stmt->execute();
$updates = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// 关闭数据库连接
$conn->close();
?>

<!-- 页面内容 -->
<main class="container">
    <div class="updates-header">
        <h1>最新动态</h1>
        <p>了解体育中心的最新活动与发展</p>
    </div>
    
    <?php if (empty($updates)): ?>
    <div class="empty-content">
        <div class="empty-icon">
            <i class="fas fa-running"></i>
        </div>
        <h3>暂无动态</h3>
        <p>目前还没有发布任何动态内容，请稍后再来查看。</p>
    </div>
    <?php else: ?>
    
    <div class="timeline">
        <?php 
        $currentYear = null;
        $currentMonth = null;
        
        foreach ($updates as $update):
            $year = date('Y', strtotime($update['created_at']));
            $month = date('n', strtotime($update['created_at']));
            $monthName = date('F', strtotime($update['created_at']));
            
            // 显示年份
            if ($currentYear !== $year):
                $currentYear = $year;
                $currentMonth = null;
        ?>
        <div class="timeline-year">
            <span><?php echo $year; ?></span>
        </div>
        <?php endif; ?>
        
        <?php 
            // 显示月份
            if ($currentMonth !== $month):
                $currentMonth = $month;
        ?>
        <div class="timeline-month">
            <span><?php echo $monthName; ?></span>
        </div>
        <?php endif; ?>
        
        <div class="timeline-item">
            <div class="timeline-date">
                <span><?php echo date('m-d', strtotime($update['created_at'])); ?></span>
            </div>
            <div class="timeline-content">
                <div class="update-card">
                    <?php if (!empty($update['thumbnail'])): ?>
                    <div class="update-image">
                        <img src="<?php echo $update['thumbnail']; ?>" alt="<?php echo $update['title']; ?>">
                    </div>
                    <?php endif; ?>
                    <div class="update-details">
                        <h3 class="update-title">
                            <a href="article.php?id=<?php echo $update['id']; ?>"><?php echo $update['title']; ?></a>
                        </h3>
                        <div class="update-excerpt">
                            <?php 
                            // 提取纯文本摘要
                            $excerpt = strip_tags($update['content']);
                            echo mb_substr($excerpt, 0, 120) . (mb_strlen($excerpt) > 120 ? '...' : '');
                            ?>
                        </div>
                        <div class="update-meta">
                            <span class="update-author"><i class="far fa-user"></i> <?php echo $update['author_name']; ?></span>
                            <a href="article.php?id=<?php echo $update['id']; ?>" class="read-more">查看详情 <i class="fas fa-angle-right"></i></a>
                        </div>
                    </div>
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
            <li><a href="updates.php?page=<?php echo ($page - 1); ?>"><i class="fas fa-chevron-left"></i> 上一页</a></li>
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
                echo '<li><a href="updates.php?page=1">1</a></li>';
                if ($startPage > 2) {
                    echo '<li class="ellipsis">...</li>';
                }
            }
            
            // 输出页码
            for ($i = $startPage; $i <= $endPage; $i++) {
                if ($i == $page) {
                    echo '<li class="active"><span>' . $i . '</span></li>';
                } else {
                    echo '<li><a href="updates.php?page=' . $i . '">' . $i . '</a></li>';
                }
            }
            
            // 输出最后页码和省略号
            if ($endPage < $totalPages) {
                if ($endPage < $totalPages - 1) {
                    echo '<li class="ellipsis">...</li>';
                }
                echo '<li><a href="updates.php?page=' . $totalPages . '">' . $totalPages . '</a></li>';
            }
            ?>
            
            <?php if ($page < $totalPages): ?>
            <li><a href="updates.php?page=<?php echo ($page + 1); ?>">下一页 <i class="fas fa-chevron-right"></i></a></li>
            <?php endif; ?>
        </ul>
    </div>
    <?php endif; ?>
    
    <?php endif; ?>
</main>

<style>
.updates-header {
    text-align: center;
    margin-bottom: 40px;
    padding-bottom: 20px;
    border-bottom: 1px solid #eee;
}

.updates-header h1 {
    font-size: 2.5rem;
    margin-bottom: 15px;
    color: var(--primary-color);
}

.updates-header p {
    font-size: 1.1rem;
    color: #666;
    max-width: 700px;
    margin: 0 auto;
}

.timeline {
    position: relative;
    margin: 0 auto 60px;
    padding-left: 40px;
}

.timeline:before {
    content: '';
    position: absolute;
    left: 10px;
    top: 0;
    bottom: 0;
    width: 2px;
    background-color: #ddd;
}

.timeline-year {
    position: relative;
    margin: 40px 0 20px;
}

.timeline-year:before {
    content: '';
    position: absolute;
    left: -40px;
    top: 50%;
    transform: translateY(-50%);
    width: 20px;
    height: 20px;
    background-color: var(--primary-color);
    border-radius: 50%;
    z-index: 1;
}

.timeline-year span {
    font-size: 1.8rem;
    font-weight: 700;
    color: var(--primary-color);
}

.timeline-month {
    position: relative;
    margin: 30px 0 15px;
}

.timeline-month:before {
    content: '';
    position: absolute;
    left: -36px;
    top: 50%;
    transform: translateY(-50%);
    width: 12px;
    height: 12px;
    background-color: var(--secondary-color);
    border-radius: 50%;
    z-index: 1;
}

.timeline-month span {
    font-size: 1.3rem;
    font-weight: 600;
    color: var(--secondary-color);
}

.timeline-item {
    position: relative;
    margin-bottom: 30px;
    padding-left: 20px;
}

.timeline-item:before {
    content: '';
    position: absolute;
    left: -32px;
    top: 15px;
    width: 6px;
    height: 6px;
    background-color: #888;
    border-radius: 50%;
    z-index: 1;
}

.timeline-date {
    margin-bottom: 10px;
}

.timeline-date span {
    display: inline-block;
    padding: 5px 10px;
    background-color: #f5f5f5;
    border-radius: 5px;
    font-size: 0.9rem;
    color: #777;
}

.timeline-content {
    position: relative;
}

.update-card {
    display: flex;
    flex-direction: column;
    background-color: #fff;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 3px 15px rgba(0, 0, 0, 0.08);
    transition: transform 0.3s ease;
}

.update-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.12);
}

.update-image {
    height: 200px;
    overflow: hidden;
}

.update-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.5s ease;
}

.update-card:hover .update-image img {
    transform: scale(1.05);
}

.update-details {
    padding: 20px;
}

.update-title {
    font-size: 1.3rem;
    margin-bottom: 15px;
    line-height: 1.4;
}

.update-title a {
    color: #333;
    text-decoration: none;
    transition: color 0.2s;
}

.update-title a:hover {
    color: var(--primary-color);
}

.update-excerpt {
    color: #666;
    margin-bottom: 15px;
    line-height: 1.6;
}

.update-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.9rem;
}

.update-author {
    color: #888;
    display: flex;
    align-items: center;
}

.update-author i {
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
    .updates-header h1 {
        font-size: 2rem;
    }
    
    .timeline {
        padding-left: 30px;
    }
    
    .timeline:before {
        left: 8px;
    }
    
    .timeline-year:before {
        left: -30px;
        width: 16px;
        height: 16px;
    }
    
    .timeline-month:before {
        left: -27px;
        width: 10px;
        height: 10px;
    }
    
    .timeline-item:before {
        left: -25px;
        width: 5px;
        height: 5px;
    }
    
    .timeline-item {
        padding-left: 10px;
    }
    
    .update-title {
        font-size: 1.2rem;
    }
    
    .pagination a, .pagination span {
        padding: 6px 12px;
    }
}
</style>

<?php require_once 'includes/footer.php'; ?> 