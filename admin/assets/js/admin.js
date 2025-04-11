/**
 * 管理后台JavaScript文件
 */

document.addEventListener('DOMContentLoaded', function() {
    // 初始化表格排序
    initTableSort();
    
    // 初始化表格过滤
    initTableFilter();
    
    // 初始化表格批量操作
    initBulkActions();
    
    // 初始化确认对话框
    initConfirmDialog();
    
    // 初始化标签输入
    initTagsInput();
});

/**
 * 初始化表格排序
 */
function initTableSort() {
    const tables = document.querySelectorAll('.sortable-table');
    
    tables.forEach(table => {
        const headers = table.querySelectorAll('th.sortable');
        
        headers.forEach(header => {
            header.addEventListener('click', function() {
                const column = this.cellIndex;
                const tbody = table.querySelector('tbody');
                const rows = Array.from(tbody.querySelectorAll('tr'));
                const isAsc = this.classList.contains('asc');
                
                // 移除所有表头的排序类
                headers.forEach(h => {
                    h.classList.remove('asc', 'desc');
                });
                
                // 设置当前表头的排序类
                this.classList.add(isAsc ? 'desc' : 'asc');
                
                // 排序行
                rows.sort((a, b) => {
                    const aValue = a.cells[column].textContent.trim();
                    const bValue = b.cells[column].textContent.trim();
                    
                    // 判断是否为数字
                    if (!isNaN(aValue) && !isNaN(bValue)) {
                        return isAsc ? 
                            parseFloat(aValue) - parseFloat(bValue) : 
                            parseFloat(bValue) - parseFloat(aValue);
                    } else {
                        return isAsc ? 
                            aValue.localeCompare(bValue) : 
                            bValue.localeCompare(aValue);
                    }
                });
                
                // 重新添加行
                rows.forEach(row => {
                    tbody.appendChild(row);
                });
            });
        });
    });
}

/**
 * 初始化表格过滤
 */
function initTableFilter() {
    const filterInputs = document.querySelectorAll('.table-filter');
    
    filterInputs.forEach(input => {
        const targetId = input.dataset.target;
        const target = document.getElementById(targetId);
        
        if (target) {
            input.addEventListener('input', function() {
                const value = this.value.toLowerCase();
                const rows = target.querySelectorAll('tbody tr');
                
                rows.forEach(row => {
                    const text = row.textContent.toLowerCase();
                    row.style.display = text.indexOf(value) > -1 ? '' : 'none';
                });
            });
        }
    });
}

/**
 * 初始化表格批量操作
 */
function initBulkActions() {
    const bulkActionForms = document.querySelectorAll('.bulk-action-form');
    
    bulkActionForms.forEach(form => {
        const selectAll = form.querySelector('.select-all');
        const selectItems = form.querySelectorAll('.select-item');
        const bulkActionSelect = form.querySelector('.bulk-action-select');
        const bulkActionBtn = form.querySelector('.bulk-action-btn');
        
        if (selectAll && selectItems.length > 0) {
            // 全选/取消全选
            selectAll.addEventListener('change', function() {
                selectItems.forEach(item => {
                    item.checked = this.checked;
                });
            });
            
            // 更新全选状态
            selectItems.forEach(item => {
                item.addEventListener('change', function() {
                    let allChecked = true;
                    let anyChecked = false;
                    
                    selectItems.forEach(i => {
                        if (!i.checked) {
                            allChecked = false;
                        } else {
                            anyChecked = true;
                        }
                    });
                    
                    selectAll.checked = allChecked;
                    selectAll.indeterminate = !allChecked && anyChecked;
                });
            });
        }
        
        if (bulkActionBtn && bulkActionSelect) {
            // 提交前验证
            bulkActionBtn.addEventListener('click', function(e) {
                e.preventDefault();
                
                // 检查是否选择了操作
                if (bulkActionSelect.value === '') {
                    alert('请选择要执行的操作');
                    return;
                }
                
                // 检查是否选择了项目
                let hasChecked = false;
                selectItems.forEach(item => {
                    if (item.checked) {
                        hasChecked = true;
                    }
                });
                
                if (!hasChecked) {
                    alert('请选择要操作的项目');
                    return;
                }
                
                // 确认操作
                if (bulkActionSelect.value === 'delete') {
                    if (confirm('确定要删除所选项目吗？此操作不可撤销。')) {
                        form.submit();
                    }
                } else {
                    form.submit();
                }
            });
        }
    });
}

/**
 * 初始化确认对话框
 */
function initConfirmDialog() {
    const confirmLinks = document.querySelectorAll('[data-confirm]');
    
    confirmLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            const message = this.dataset.confirm || '确定要执行此操作吗？';
            
            if (!confirm(message)) {
                e.preventDefault();
            }
        });
    });
}

/**
 * 初始化标签输入
 */
function initTagsInput() {
    const tagsContainers = document.querySelectorAll('.tags-input');
    
    tagsContainers.forEach(container => {
        const input = container.querySelector('input[type="text"]');
        const hiddenInput = container.querySelector('input[type="hidden"]');
        const tagsList = container.querySelector('.tags-list');
        
        if (input && hiddenInput && tagsList) {
            // 初始化标签
            let tags = [];
            if (hiddenInput.value) {
                tags = hiddenInput.value.split(',');
                renderTags();
            }
            
            // 添加标签
            input.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' || e.key === ',') {
                    e.preventDefault();
                    
                    const tag = this.value.trim();
                    if (tag && !tags.includes(tag)) {
                        tags.push(tag);
                        this.value = '';
                        renderTags();
                    }
                }
            });
            
            // 渲染标签
            function renderTags() {
                tagsList.innerHTML = '';
                hiddenInput.value = tags.join(',');
                
                tags.forEach(tag => {
                    const tagItem = document.createElement('div');
                    tagItem.className = 'tag-item';
                    tagItem.innerHTML = `
                        <span>${tag}</span>
                        <button type="button" class="tag-remove" data-tag="${tag}">×</button>
                    `;
                    tagsList.appendChild(tagItem);
                });
                
                // 移除标签
                const removeButtons = tagsList.querySelectorAll('.tag-remove');
                removeButtons.forEach(button => {
                    button.addEventListener('click', function() {
                        const tagToRemove = this.dataset.tag;
                        tags = tags.filter(t => t !== tagToRemove);
                        renderTags();
                    });
                });
            }
        }
    });
}

/**
 * 初始化日期选择器
 */
function initDatePicker() {
    const dateInputs = document.querySelectorAll('.date-picker');
    
    dateInputs.forEach(input => {
        if (typeof flatpickr !== 'undefined') {
            flatpickr(input, {
                dateFormat: 'Y-m-d',
                locale: 'zh'
            });
        }
    });
}

/**
 * 显示通知
 * @param {string} message - 通知消息
 * @param {string} type - 通知类型 (success, error, warning, info)
 */
function showNotification(message, type = 'info') {
    const notificationContainer = document.getElementById('notifications');
    
    if (!notificationContainer) {
        return;
    }
    
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <div class="notification-icon">
                ${getNotificationIcon(type)}
            </div>
            <div class="notification-message">${message}</div>
        </div>
        <button type="button" class="notification-close">&times;</button>
    `;
    
    notificationContainer.appendChild(notification);
    
    // 显示通知
    setTimeout(() => {
        notification.classList.add('show');
    }, 10);
    
    // 添加关闭事件
    const closeButton = notification.querySelector('.notification-close');
    closeButton.addEventListener('click', () => {
        closeNotification(notification);
    });
    
    // 自动关闭
    setTimeout(() => {
        closeNotification(notification);
    }, 5000);
}

/**
 * 关闭通知
 * @param {Element} notification - 通知元素
 */
function closeNotification(notification) {
    notification.classList.remove('show');
    
    // 动画结束后移除元素
    setTimeout(() => {
        if (notification.parentNode) {
            notification.parentNode.removeChild(notification);
        }
    }, 300);
}

/**
 * 获取通知图标
 * @param {string} type - 通知类型
 * @return {string} SVG图标
 */
function getNotificationIcon(type) {
    switch(type) {
        case 'success':
            return '<i class="fas fa-check-circle"></i>';
        case 'error':
            return '<i class="fas fa-times-circle"></i>';
        case 'warning':
            return '<i class="fas fa-exclamation-circle"></i>';
        default:
            return '<i class="fas fa-info-circle"></i>';
    }
} 