<?php
/**
 * 运动记录页面
 */

// 页面信息
$pageTitle = '运动记录';
$pageBanner = 'assets/images/banner-records.jpg';
$pageBreadcrumb = [
    'records.php' => '运动记录'
];

// 引入头部
require_once 'includes/header.php';

// 获取所有运动记录
$records = getSchoolRecords();

// 按项目类型分组
$recordGroups = [];
foreach ($records as $record) {
    // 根据项目名称提取类型
    $type = explode('-', $record['event_name'])[0];
    $type = trim($type);
    
    if (!isset($recordGroups[$type])) {
        $recordGroups[$type] = [];
    }
    
    $recordGroups[$type][] = $record;
}
?>

<div class="container">
    <div class="section">
        <h1 class="page-title text-center animated fadeInDown">体育运动记录</h1>
        <p class="text-center subtitle animated fadeInUp" style="animation-delay: 0.2s;">记录在这里被刷新，传奇从这里开始</p>
        
        <!-- 项目分类标签 -->
        <div class="records-tabs animated fadeInUp" style="animation-delay: 0.3s;">
            <div class="tabs-container">
                <button class="tab-btn active" data-target="all">全部</button>
                <?php foreach (array_keys($recordGroups) as $type): ?>
                <button class="tab-btn" data-target="<?php echo strtolower(str_replace(' ', '-', $type)); ?>"><?php echo $type; ?></button>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- 记录数据表格 -->
        <div class="records-content animated fadeInUp" style="animation-delay: 0.4s;">
            <!-- 全部记录 -->
            <div class="records-group active" id="all">
                <?php if (empty($records)): ?>
                <p class="text-center">暂无记录</p>
                <?php else: ?>
                <div class="records-table-container">
                    <table class="records-table">
                        <thead>
                            <tr>
                                <th>项目</th>
                                <th>记录</th>
                                <th>记录保持者</th>
                                <th>创建日期</th>
                                <th>备注</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($records as $record): ?>
                            <tr>
                                <td><?php echo $record['event_name']; ?></td>
                                <td class="record-value"><?php echo $record['record']; ?></td>
                                <td>
                                    <?php if (!empty($record['record_holder_id'])): ?>
                                    <a href="profile.php?id=<?php echo $record['record_holder_id']; ?>" class="record-holder">
                                        <?php if (!empty($record['avatar'])): ?>
                                        <img src="<?php echo $record['avatar']; ?>" alt="<?php echo $record['record_holder_name']; ?>" class="record-holder-avatar">
                                        <?php endif; ?>
                                        <span><?php echo $record['record_holder_name']; ?></span>
                                    </a>
                                    <?php else: ?>
                                    <?php echo $record['record_holder_name']; ?>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('Y-m-d', strtotime($record['record_date'])); ?></td>
                                <td><?php echo !empty($record['description']) ? $record['description'] : '-'; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- 分组记录 -->
            <?php foreach ($recordGroups as $type => $typeRecords): ?>
            <div class="records-group" id="<?php echo strtolower(str_replace(' ', '-', $type)); ?>">
                <h2 class="group-title"><?php echo $type; ?>项目记录</h2>
                <div class="records-table-container">
                    <table class="records-table">
                        <thead>
                            <tr>
                                <th>项目</th>
                                <th>记录</th>
                                <th>记录保持者</th>
                                <th>创建日期</th>
                                <th>备注</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($typeRecords as $record): ?>
                            <tr>
                                <td><?php echo $record['event_name']; ?></td>
                                <td class="record-value"><?php echo $record['record']; ?></td>
                                <td>
                                    <?php if (!empty($record['record_holder_id'])): ?>
                                    <a href="profile.php?id=<?php echo $record['record_holder_id']; ?>" class="record-holder">
                                        <?php if (!empty($record['avatar'])): ?>
                                        <img src="<?php echo $record['avatar']; ?>" alt="<?php echo $record['record_holder_name']; ?>" class="record-holder-avatar">
                                        <?php endif; ?>
                                        <span><?php echo $record['record_holder_name']; ?></span>
                                    </a>
                                    <?php else: ?>
                                    <?php echo $record['record_holder_name']; ?>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('Y-m-d', strtotime($record['record_date'])); ?></td>
                                <td><?php echo !empty($record['description']) ? $record['description'] : '-'; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<style>
/* 运动记录页面样式 */
.page-title {
    font-size: 36px;
    margin-bottom: 10px;
}

.subtitle {
    font-size: 18px;
    color: var(--lighter-text);
    margin-bottom: 40px;
}

.records-tabs {
    margin-bottom: 30px;
}

.tabs-container {
    display: flex;
    overflow-x: auto;
    gap: 10px;
    padding-bottom: 15px;
    border-bottom: 1px solid var(--border-color);
}

.tab-btn {
    padding: 10px 20px;
    background-color: var(--light-bg);
    border: none;
    border-radius: 30px;
    cursor: pointer;
    font-size: 16px;
    font-weight: 500;
    color: var(--text-color);
    transition: var(--transition);
    white-space: nowrap;
}

.tab-btn.active {
    background-color: var(--primary-color);
    color: var(--white);
}

.tab-btn:hover:not(.active) {
    background-color: var(--gray-bg);
}

.records-group {
    display: none;
}

.records-group.active {
    display: block;
    animation: fadeIn 0.5s ease;
}

.group-title {
    font-size: 24px;
    margin-bottom: 20px;
    color: var(--primary-color);
}

.record-value {
    font-weight: 700;
    color: var(--primary-color);
}

.record-holder {
    display: flex;
    align-items: center;
    gap: 10px;
}

.record-holder-avatar {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    object-fit: cover;
}

/* 响应式调整 */
@media (max-width: 768px) {
    .page-title {
        font-size: 28px;
    }
    
    .subtitle {
        font-size: 16px;
    }
    
    .tab-btn {
        padding: 8px 15px;
        font-size: 14px;
    }
    
    .group-title {
        font-size: 20px;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 标签切换功能
    const tabButtons = document.querySelectorAll('.tab-btn');
    const recordGroups = document.querySelectorAll('.records-group');
    
    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            const target = this.dataset.target;
            
            // 更新活动按钮
            tabButtons.forEach(btn => {
                btn.classList.remove('active');
            });
            this.classList.add('active');
            
            // 更新显示的记录组
            recordGroups.forEach(group => {
                group.classList.remove('active');
            });
            
            document.getElementById(target).classList.add('active');
        });
    });
});
</script>

<?php
// 引入页脚
require_once 'includes/footer.php';
?> 