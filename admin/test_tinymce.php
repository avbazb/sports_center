<?php
/**
 * 测试TinyMCE编辑器页面
 */

// 页面信息
$pageTitle = 'TinyMCE测试';

// 引入管理后台头部
require_once 'includes/admin_header.php';
?>

<div class="admin-content-header">
    <h2>TinyMCE编辑器测试</h2>
</div>

<div class="admin-content-body">
    <div class="panel">
        <div class="panel-header">
            <h3>编辑器测试区域</h3>
        </div>
        <div class="panel-body">
            <form action="" method="post">
                <div class="form-group">
                    <label for="content">测试内容</label>
                    <div class="editor-container">
                        <textarea id="content" name="content" class="form-control tinymce-editor" style="min-height:300px;"></textarea>
                    </div>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> 测试提交
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
// 引入管理后台页脚
require_once 'includes/admin_footer.php';
?> 