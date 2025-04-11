<?php
/**
 * 首页
 */

// 页面信息
$pageTitle = '首页';

// 引入头部
require_once 'includes/header.php';

// 获取最新文章
$latestArticles = getArticles(null, 6);

// 获取最新运动记录
$schoolRecords = getSchoolRecords();
$latestRecords = array_slice($schoolRecords, 0, 4);

// 获取最新荣誉
$latestHonors = getHonors(4);
?>

<!-- 首页轮播图 -->
<div class="hero-slider">
    <div class="hero-slide active" style="background-image: url('assets/images/slider-1.jpg');">
        <div class="container">
            <div class="slide-content animated fadeInUp">
                <h2>欢迎来到体育中心官网</h2>
                <p>致力于培养体育精神和健康意识，提供优质的体育教育资源</p>
                <a href="#" class="btn btn-primary">了解更多</a>
            </div>
        </div>
    </div>
    <div class="hero-slide" style="background-image: url('assets/images/slider-2.jpg');">
        <div class="container">
            <div class="slide-content">
                <h2>探索体育的魅力</h2>
                <p>参与各类体育活动，发掘自己的潜力，享受运动的乐趣</p>
                <a href="#" class="btn btn-primary">查看项目</a>
            </div>
        </div>
    </div>
    <div class="hero-slide" style="background-image: url('assets/images/slider-3.jpg');">
        <div class="container">
            <div class="slide-content">
                <h2>挑战自我，突破极限</h2>
                <p>通过体育锻炼，培养坚韧不拔的意志和团队合作精神</p>
                <a href="#" class="btn btn-primary">查看记录</a>
            </div>
        </div>
    </div>
    
    <!-- 轮播导航 -->
    <div class="slider-nav">
        <button class="prev"><i class="fas fa-chevron-left"></i></button>
        <div class="dots">
            <span class="dot active"></span>
            <span class="dot"></span>
            <span class="dot"></span>
        </div>
        <button class="next"><i class="fas fa-chevron-right"></i></button>
    </div>
</div>

<!-- 特色模块 -->
<section class="features">
    <div class="container">
        <div class="features-grid">
            <div class="feature-card animated fadeInUp" style="animation-delay: 0.1s;">
                <div class="feature-icon">
                    <i class="fas fa-trophy"></i>
                </div>
                <h3>体育竞赛</h3>
                <p>举办丰富多彩的体育竞赛，为运动爱好者提供展示才能的平台</p>
            </div>
            <div class="feature-card animated fadeInUp" style="animation-delay: 0.3s;">
                <div class="feature-icon">
                    <i class="fas fa-running"></i>
                </div>
                <h3>体能训练</h3>
                <p>提供专业的体能训练课程，帮助提高身体素质和运动能力</p>
            </div>
            <div class="feature-card animated fadeInUp" style="animation-delay: 0.5s;">
                <div class="feature-icon">
                    <i class="fas fa-users"></i>
                </div>
                <h3>团队培训</h3>
                <p>组建多支运动队伍，进行专业的培训，参与各级比赛</p>
            </div>
            <div class="feature-card animated fadeInUp" style="animation-delay: 0.7s;">
                <div class="feature-icon">
                    <i class="fas fa-heartbeat"></i>
                </div>
                <h3>健康教育</h3>
                <p>开展健康教育，培养良好的生活习惯和健康意识</p>
            </div>
        </div>
    </div>
</section>

<!-- 最新动态 -->
<section class="latest-news section">
    <div class="container">
        <h2 class="section-title">最新动态</h2>
        
        <div class="article-grid">
            <?php if (empty($latestArticles)): ?>
            <p class="text-center">暂无文章</p>
            <?php else: ?>
            <?php foreach ($latestArticles as $article): ?>
            <div class="article-card animated fadeInUp">
                <div class="article-thumbnail">
                    <?php if (!empty($article['thumbnail'])): ?>
                    <img src="<?php echo $article['thumbnail']; ?>" alt="<?php echo $article['title']; ?>">
                    <?php else: ?>
                    <img src="assets/images/placeholder.jpg" alt="<?php echo $article['title']; ?>">
                    <?php endif; ?>
                </div>
                <div class="article-content">
                    <span class="article-category"><?php echo $article['category_name']; ?></span>
                    <h3 class="article-title">
                        <a href="article.php?id=<?php echo $article['id']; ?>"><?php echo $article['title']; ?></a>
                    </h3>
                    <div class="article-meta">
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
                    <p class="article-excerpt">
                        <?php echo mb_substr(strip_tags($article['content']), 0, 100, 'utf-8') . '...'; ?>
                    </p>
                    <a href="article.php?id=<?php echo $article['id']; ?>" class="read-more">
                        阅读更多 <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <div class="text-center mt-4">
            <a href="news.php" class="btn btn-outline">查看所有文章</a>
        </div>
    </div>
</section>

<!-- 运动记录展示 -->
<section class="school-records section bg-light">
    <div class="container">
        <h2 class="section-title">运动记录</h2>
        
        <div class="records-table-container">
            <table class="records-table">
                <thead>
                    <tr>
                        <th>项目</th>
                        <th>记录</th>
                        <th>记录保持者</th>
                        <th>创建日期</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($latestRecords)): ?>
                    <tr>
                        <td colspan="4" class="text-center">暂无记录</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($latestRecords as $record): ?>
                    <tr>
                        <td><?php echo $record['event_name']; ?></td>
                        <td><?php echo $record['record']; ?></td>
                        <td>
                            <?php if (!empty($record['record_holder_id'])): ?>
                            <a href="profile.php?id=<?php echo $record['record_holder_id']; ?>"><?php echo $record['record_holder_name']; ?></a>
                            <?php else: ?>
                            <?php echo $record['record_holder_name']; ?>
                            <?php endif; ?>
                        </td>
                        <td><?php echo date('Y-m-d', strtotime($record['record_date'])); ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <div class="text-center mt-4">
            <a href="records.php" class="btn btn-outline">查看所有记录</a>
        </div>
    </div>
</section>

<!-- 荣誉墙 -->
<section class="honors-wall section">
    <div class="container">
        <h2 class="section-title">荣誉墙</h2>
        
        <div class="honors-grid">
            <?php if (empty($latestHonors)): ?>
            <p class="text-center">暂无荣誉</p>
            <?php else: ?>
            <?php foreach ($latestHonors as $honor): ?>
            <div class="honor-card animated fadeInUp">
                <div class="honor-image">
                    <img src="<?php echo $honor['image']; ?>" alt="<?php echo $honor['title']; ?>">
                </div>
                <div class="honor-content">
                    <h3 class="honor-title"><?php echo $honor['title']; ?></h3>
                    <div class="honor-date"><?php echo date('Y-m-d', strtotime($honor['honor_date'])); ?></div>
                    <p class="honor-description"><?php echo mb_substr($honor['description'], 0, 50, 'utf-8') . '...'; ?></p>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <div class="text-center mt-4">
            <a href="honors.php" class="btn btn-outline">查看所有荣誉</a>
        </div>
    </div>
</section>

<!-- 首页轮播图脚本 -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 轮播图初始化
    let currentSlide = 0;
    const slides = document.querySelectorAll('.hero-slide');
    const dots = document.querySelectorAll('.dot');
    const total = slides.length;
    
    // 切换到指定幻灯片
    function goToSlide(index) {
        // 移除当前活动的幻灯片和点
        slides[currentSlide].classList.remove('active');
        dots[currentSlide].classList.remove('active');
        
        // 更新当前幻灯片索引
        currentSlide = (index + total) % total;
        
        // 激活新的幻灯片和点
        slides[currentSlide].classList.add('active');
        dots[currentSlide].classList.add('active');
        
        // 添加动画效果
        const content = slides[currentSlide].querySelector('.slide-content');
        content.classList.remove('animated', 'fadeInUp');
        
        // 触发重排以应用新动画
        void content.offsetWidth;
        
        content.classList.add('animated', 'fadeInUp');
    }
    
    // 下一张幻灯片
    function nextSlide() {
        goToSlide(currentSlide + 1);
    }
    
    // 上一张幻灯片
    function prevSlide() {
        goToSlide(currentSlide - 1);
    }
    
    // 添加点击事件监听器
    document.querySelector('.slider-nav .next').addEventListener('click', nextSlide);
    document.querySelector('.slider-nav .prev').addEventListener('click', prevSlide);
    
    // 为每个点添加点击事件
    dots.forEach((dot, index) => {
        dot.addEventListener('click', () => goToSlide(index));
    });
    
    // 自动轮播
    let slideInterval = setInterval(nextSlide, 5000);
    
    // 鼠标悬停时暂停轮播
    const slider = document.querySelector('.hero-slider');
    slider.addEventListener('mouseenter', () => {
        clearInterval(slideInterval);
    });
    
    // 鼠标离开时恢复轮播
    slider.addEventListener('mouseleave', () => {
        slideInterval = setInterval(nextSlide, 5000);
    });
});
</script>

<style>
/* 首页轮播图样式 */
.hero-slider {
    position: relative;
    height: 600px;
    overflow: hidden;
}

.hero-slide {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-size: cover;
    background-position: center;
    opacity: 0;
    visibility: hidden;
    transition: opacity 1s ease, visibility 1s ease;
}

.hero-slide::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
}

.hero-slide.active {
    opacity: 1;
    visibility: visible;
}

.slide-content {
    position: relative;
    color: #fff;
    max-width: 600px;
    padding: 150px 0;
}

.slide-content h2 {
    font-size: 48px;
    margin-bottom: 20px;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
}

.slide-content p {
    font-size: 18px;
    margin-bottom: 30px;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
}

.slider-nav {
    position: absolute;
    bottom: 30px;
    left: 0;
    width: 100%;
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 15px;
    z-index: 10;
}

.slider-nav button {
    background: transparent;
    border: none;
    color: #fff;
    font-size: 20px;
    cursor: pointer;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: transform 0.3s ease;
}

.slider-nav button:hover {
    transform: scale(1.2);
}

.dots {
    display: flex;
    gap: 8px;
}

.dot {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background-color: rgba(255, 255, 255, 0.5);
    cursor: pointer;
    transition: background-color 0.3s ease, transform 0.3s ease;
}

.dot.active {
    background-color: #fff;
    transform: scale(1.2);
}

/* 特色模块样式 */
.features {
    padding: 60px 0;
    background-color: #f8f9fa;
}

.features-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 30px;
}

.feature-card {
    background: #fff;
    border-radius: 8px;
    padding: 30px;
    text-align: center;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.feature-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
}

.feature-icon {
    width: 70px;
    height: 70px;
    margin: 0 auto 20px;
    background-color: var(--primary-color);
    color: #fff;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 30px;
}

.feature-card h3 {
    font-size: 20px;
    margin-bottom: 15px;
}

.feature-card p {
    color: #666;
}

/* 校记录表格样式 */
.records-table-container {
    overflow-x: auto;
}

.records-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 20px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
    background: #fff;
    border-radius: 8px;
    overflow: hidden;
}

.records-table th, 
.records-table td {
    padding: 15px;
    text-align: left;
    border-bottom: 1px solid #e1e1e1;
}

.records-table th {
    background-color: var(--primary-color);
    color: #fff;
    font-weight: 500;
}

.records-table tr:hover {
    background-color: #f5f5f5;
}

.records-table tr:last-child td {
    border-bottom: none;
}

/* 荣誉墙样式 */
.honors-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 30px;
}

.honor-card {
    background: #fff;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
    transition: transform 0.3s ease;
}

.honor-card:hover {
    transform: translateY(-10px);
}

.honor-image {
    height: 200px;
    overflow: hidden;
}

.honor-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.5s ease;
}

.honor-card:hover .honor-image img {
    transform: scale(1.1);
}

.honor-content {
    padding: 20px;
}

.honor-title {
    font-size: 18px;
    margin-bottom: 10px;
    font-weight: 500;
}

.honor-date {
    color: #666;
    font-size: 14px;
    margin-bottom: 10px;
}

.honor-description {
    color: #666;
    font-size: 14px;
}

/* 响应式调整 */
@media (max-width: 992px) {
    .hero-slider {
        height: 500px;
    }
    
    .slide-content {
        padding: 100px 0;
    }
    
    .slide-content h2 {
        font-size: 36px;
    }
}

@media (max-width: 768px) {
    .hero-slider {
        height: 400px;
    }
    
    .slide-content {
        padding: 80px 0;
        max-width: 100%;
    }
    
    .slide-content h2 {
        font-size: 28px;
    }
    
    .slide-content p {
        font-size: 16px;
    }
}

@media (max-width: 480px) {
    .hero-slider {
        height: 350px;
    }
    
    .slide-content {
        padding: 60px 0;
    }
    
    .slide-content h2 {
        font-size: 24px;
    }
    
    .slide-content p {
        font-size: 14px;
    }
}
</style>

<?php
// 引入页脚
require_once 'includes/footer.php';
?> 