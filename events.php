<?php
/**
 * 体育赛事页面
 */

// 页面信息
$pageTitle = '体育赛事';
$pageBanner = 'assets/images/banner-events.jpg';
$pageBreadcrumb = [
    'events.php' => '体育赛事'
];

// 引入头部
require_once 'includes/header.php';
?>

<div class="container">
    <div class="section events-section">
        <h2 class="section-title">即将举行的赛事</h2>
        
        <div class="events-grid">
            <div class="event-card">
                <div class="event-date">
                    <span class="day">15</span>
                    <span class="month">六月</span>
                </div>
                <div class="event-content">
                    <h3 class="event-title">夏季篮球联赛</h3>
                    <div class="event-meta">
                        <span><i class="fas fa-map-marker-alt"></i> 体育中心篮球馆</span>
                        <span><i class="fas fa-clock"></i> 09:00 - 17:00</span>
                    </div>
                    <p class="event-description">
                        夏季篮球联赛将在6月15日正式开幕，欢迎各队伍报名参加。
                    </p>
                    <a href="#" class="btn btn-outline">查看详情</a>
                </div>
            </div>
            
            <div class="event-card">
                <div class="event-date">
                    <span class="day">22</span>
                    <span class="month">七月</span>
                </div>
                <div class="event-content">
                    <h3 class="event-title">游泳锦标赛</h3>
                    <div class="event-meta">
                        <span><i class="fas fa-map-marker-alt"></i> 奥林匹克游泳馆</span>
                        <span><i class="fas fa-clock"></i> 13:30 - 18:00</span>
                    </div>
                    <p class="event-description">
                        年度游泳锦标赛，设多个项目组别，欢迎各年龄段选手报名。
                    </p>
                    <a href="#" class="btn btn-outline">查看详情</a>
                </div>
            </div>
            
            <div class="event-card">
                <div class="event-date">
                    <span class="day">05</span>
                    <span class="month">八月</span>
                </div>
                <div class="event-content">
                    <h3 class="event-title">田径运动会</h3>
                    <div class="event-meta">
                        <span><i class="fas fa-map-marker-alt"></i> 体育中心田径场</span>
                        <span><i class="fas fa-clock"></i> 08:00 - 17:00</span>
                    </div>
                    <p class="event-description">
                        一年一度的田径盛会，包括短跑、长跑、跳远、跳高等多个项目。
                    </p>
                    <a href="#" class="btn btn-outline">查看详情</a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="section past-events-section">
        <h2 class="section-title">往期赛事回顾</h2>
        
        <div class="past-events-grid">
            <div class="past-event-card">
                <div class="past-event-image">
                    <img src="assets/images/event-1.jpg" alt="冬季越野跑">
                </div>
                <div class="past-event-content">
                    <h3 class="past-event-title">冬季越野跑</h3>
                    <div class="past-event-date">2023年1月10日</div>
                    <p class="past-event-description">
                        2023年冬季越野跑比赛圆满结束，超过200名选手参与了此次活动。
                    </p>
                    <a href="#" class="btn btn-sm">查看照片</a>
                </div>
            </div>
            
            <div class="past-event-card">
                <div class="past-event-image">
                    <img src="assets/images/event-2.jpg" alt="春季羽毛球赛">
                </div>
                <div class="past-event-content">
                    <h3 class="past-event-title">春季羽毛球赛</h3>
                    <div class="past-event-date">2023年4月15日</div>
                    <p class="past-event-description">
                        春季羽毛球比赛已圆满落幕，感谢所有参赛选手的积极参与和精彩表现。
                    </p>
                    <a href="#" class="btn btn-sm">查看照片</a>
                </div>
            </div>
            
            <div class="past-event-card">
                <div class="past-event-image">
                    <img src="assets/images/event-3.jpg" alt="足球友谊赛">
                </div>
                <div class="past-event-content">
                    <h3 class="past-event-title">足球友谊赛</h3>
                    <div class="past-event-date">2023年5月20日</div>
                    <p class="past-event-description">
                        足球友谊赛在欢快的氛围中结束，促进了各队之间的交流与合作。
                    </p>
                    <a href="#" class="btn btn-sm">查看照片</a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* 赛事列表样式 */
.events-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 30px;
    margin-bottom: 40px;
}

.event-card {
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
    display: flex;
    overflow: hidden;
    transition: transform 0.3s ease;
}

.event-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
}

.event-date {
    background-color: var(--primary-color);
    color: #fff;
    padding: 20px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    min-width: 100px;
    text-align: center;
}

.event-date .day {
    font-size: 32px;
    font-weight: 700;
    line-height: 1;
}

.event-date .month {
    font-size: 16px;
    margin-top: 5px;
}

.event-content {
    padding: 25px;
    flex: 1;
}

.event-title {
    font-size: 22px;
    margin-bottom: 10px;
}

.event-meta {
    display: flex;
    gap: 20px;
    margin-bottom: 15px;
    color: #666;
    font-size: 14px;
}

.event-description {
    margin-bottom: 20px;
    color: #666;
}

/* 往期赛事样式 */
.past-events-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 30px;
}

.past-event-card {
    background: #fff;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
    transition: transform 0.3s ease;
}

.past-event-card:hover {
    transform: translateY(-5px);
}

.past-event-image {
    height: 200px;
    overflow: hidden;
}

.past-event-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.5s ease;
}

.past-event-card:hover .past-event-image img {
    transform: scale(1.1);
}

.past-event-content {
    padding: 20px;
}

.past-event-title {
    font-size: 18px;
    margin-bottom: 5px;
}

.past-event-date {
    color: #666;
    font-size: 14px;
    margin-bottom: 10px;
}

.past-event-description {
    color: #666;
    font-size: 14px;
    margin-bottom: 15px;
}

/* 响应式调整 */
@media (max-width: 768px) {
    .event-card {
        flex-direction: column;
    }
    
    .event-date {
        padding: 10px;
        flex-direction: row;
        gap: 10px;
        justify-content: center;
    }
    
    .event-date .day {
        font-size: 24px;
    }
    
    .event-meta {
        flex-direction: column;
        gap: 5px;
    }
}
</style>

<?php
// 引入页脚
require_once 'includes/footer.php';
?> 