        </main>
        
        <!-- 页脚区域 -->
        <footer class="site-footer">
            <div class="footer-top">
                <div class="container">
                    <div class="footer-widgets">
                        <div class="footer-widget">
                            <h3 class="widget-title">关于我们</h3>
                            <div class="widget-content">
                                <p>体育中心致力于培养学生的体育精神和健康意识，提供优质的体育教育和训练资源。</p>
                                <ul class="social-links">
                                    <li><a href="#" target="_blank"><i class="fab fa-weixin"></i></a></li>
                                    <li><a href="#" target="_blank"><i class="fab fa-weibo"></i></a></li>
                                    <li><a href="#" target="_blank"><i class="fab fa-qq"></i></a></li>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="footer-widget">
                            <h3 class="widget-title">快速链接</h3>
                            <div class="widget-content">
                                <ul class="footer-menu">
                                    <li><a href="index.php">主页</a></li>
                                    <li><a href="news.php">新闻消息</a></li>
                                    <li><a href="notices.php">通知公告</a></li>
                                    <li><a href="updates.php">最新动态</a></li>
                                    <li><a href="records.php">校记录</a></li>
                                    <li><a href="honors.php">荣誉墙</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="footer-bottom">
                <div class="container">
                    <div class="copyright">
                        <p>© <?php echo date('Y'); ?> 体育中心 版权所有</p>
                    </div>
                </div>
            </div>
        </footer>
    </div>
    
    <!-- 返回顶部按钮 -->
    <a href="#" class="back-to-top" id="backToTop">
        <i class="fas fa-chevron-up"></i>
    </a>
    
    <!-- JavaScript 文件 -->
    <script src="assets/js/main.js"></script>
</body>
</html><?php
// 关闭数据库连接
if (isset($GLOBALS['conn']) && $GLOBALS['conn'] instanceof mysqli && !$GLOBALS['conn']->connect_error) {
    try {
        $GLOBALS['conn']->close();
    } catch (Exception $e) {
        // 忽略已关闭连接的错误
    }
} 