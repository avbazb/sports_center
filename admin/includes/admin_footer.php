            </div> <!-- .admin-content -->
        </main> <!-- .admin-main -->
    </div> <!-- .admin-wrapper -->
    
    <!-- JavaScript 文件 -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // 侧边栏切换
        const sidebarToggles = document.querySelectorAll('.sidebar-toggle');
        const adminWrapper = document.querySelector('.admin-wrapper');
        
        sidebarToggles.forEach(toggle => {
            toggle.addEventListener('click', function() {
                adminWrapper.classList.toggle('sidebar-collapsed');
            });
        });
        
        // 用户下拉菜单
        const userToggle = document.querySelector('.user-toggle');
        const userDropdown = document.querySelector('.user-dropdown');
        
        if (userToggle) {
            userToggle.addEventListener('click', function(e) {
                e.stopPropagation();
                userDropdown.classList.toggle('active');
            });
            
            // 点击其他区域关闭下拉菜单
            document.addEventListener('click', function() {
                userDropdown.classList.remove('active');
            });
        }
        
        // 初始化编辑器（如果存在）
        const editor = document.getElementById('editor');
        if (editor) {
            ClassicEditor
                .create(editor, {
                    toolbar: [
                        'heading', '|', 
                        'bold', 'italic', 'link', 'bulletedList', 'numberedList', '|', 
                        'outdent', 'indent', '|', 
                        'imageUpload', 'blockQuote', 'insertTable', 'mediaEmbed', 'undo', 'redo'
                    ]
                })
                .then(editor => {
                    console.log('编辑器已初始化');
                    
                    // 表单提交前同步编辑器内容
                    const form = editor.sourceElement.form;
                    form.addEventListener('submit', () => {
                        const editorContent = document.querySelector('[name="content"]');
                        editorContent.value = editor.getData();
                    });
                })
                .catch(error => {
                    console.error('编辑器初始化失败:', error);
                });
        }
        
        // 文件上传预览
        const fileInputs = document.querySelectorAll('input[type="file"][data-preview]');
        fileInputs.forEach(input => {
            const previewId = input.dataset.preview;
            const preview = document.getElementById(previewId);
            
            if (preview) {
                input.addEventListener('change', function() {
                    if (this.files && this.files[0]) {
                        const reader = new FileReader();
                        
                        reader.onload = function(e) {
                            preview.src = e.target.result;
                            preview.style.display = 'block';
                        };
                        
                        reader.readAsDataURL(this.files[0]);
                    }
                });
            }
        });
    });
    </script>
    
    <script src="assets/js/admin.js"></script>
</body>
</html>

<?php
// 关闭数据库连接
if (isset($GLOBALS['conn'])) {
    $GLOBALS['conn']->close();
}

// 结束并刷新输出缓冲
ob_end_flush();
?> 