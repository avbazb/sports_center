/**
 * 主JavaScript文件
 */

// 页面加载完成后执行
document.addEventListener('DOMContentLoaded', function() {
    // 初始化页面加载动画
    initPageLoader();
    
    // 初始化移动端菜单
    initMobileMenu();
    
    // 初始化返回顶部按钮
    initBackToTop();
    
    // 初始化文字动画
    initTextAnimation();
    
    // 初始化点击波纹效果
    initRippleEffect();
    
    // 初始化图片懒加载
    initLazyLoading();
    
    // 初始化表单验证
    initFormValidation();
});

/**
 * 初始化页面加载动画
 */
function initPageLoader() {
    const loader = document.querySelector('.page-loader');
    
    if (loader) {
        // 页面加载完成后淡出加载动画
        window.addEventListener('load', function() {
            setTimeout(function() {
                loader.classList.add('fade-out');
                
                // 动画结束后移除加载器
                setTimeout(function() {
                    loader.style.display = 'none';
                }, 500);
            }, 500);
        });
    }
}

/**
 * 初始化移动端菜单
 */
function initMobileMenu() {
    const mobileToggle = document.querySelector('.mobile-nav-toggle');
    const mobileMenuContainer = document.querySelector('.mobile-menu-container');
    const mainNav = document.querySelector('.main-nav');
    
    if (mobileToggle) {
        mobileToggle.addEventListener('click', function() {
            this.classList.toggle('active');
            if (mainNav) {
                // 创建移动端菜单
                if (!document.querySelector('.mobile-main-nav')) {
                    const mobileNav = document.createElement('div');
                    mobileNav.className = 'mobile-main-nav';
                    mobileNav.innerHTML = mainNav.innerHTML;
                    mobileMenuContainer.querySelector('.container').appendChild(mobileNav);
                }
                
                // 显示/隐藏移动端菜单
                const mobileMainNav = document.querySelector('.mobile-main-nav');
                if (mobileMainNav) {
                    mobileMainNav.style.display = mobileMainNav.style.display === 'block' ? 'none' : 'block';
                }
            }
        });
    }
    
    // 点击菜单项后关闭菜单
    const menuItems = mainNav.querySelectorAll('a');
    
    menuItems.forEach(function(item) {
        item.addEventListener('click', function() {
            if (window.innerWidth <= 768) {
                mainNav.classList.remove('active');
                mobileToggle.classList.remove('active');
                
                // 恢复横线的样式
                mobileToggle.querySelector('span:nth-child(1)').style.transform = 'none';
                mobileToggle.querySelector('span:nth-child(2)').style.opacity = '1';
                mobileToggle.querySelector('span:nth-child(3)').style.transform = 'none';
            }
        });
    });
    
    // 点击页面其他区域关闭菜单
    document.addEventListener('click', function(event) {
        const isClickInside = mainNav.contains(event.target) || mobileToggle.contains(event.target);
        
        if (!isClickInside && mainNav.classList.contains('active') && window.innerWidth <= 768) {
            mainNav.classList.remove('active');
            mobileToggle.classList.remove('active');
        }
    });
    
    // 处理用户下拉菜单
    const userToggle = document.querySelector('.user-toggle');
    const dropdownMenu = document.querySelector('.dropdown-menu');
    
    if (userToggle && dropdownMenu) {
        userToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            dropdownMenu.classList.toggle('active');
        });
        
        document.addEventListener('click', function() {
            dropdownMenu.classList.remove('active');
        });
    }
}

/**
 * 初始化返回顶部按钮
 */
function initBackToTop() {
    const backToTop = document.getElementById('backToTop');
    
    if (backToTop) {
        // 监听滚动事件
        window.addEventListener('scroll', function() {
            if (window.pageYOffset > 300) {
                backToTop.classList.add('visible');
            } else {
                backToTop.classList.remove('visible');
            }
        });
        
        // 点击返回顶部
        backToTop.addEventListener('click', function(e) {
            e.preventDefault();
            
            // 平滑滚动到顶部
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    }
}

/**
 * 初始化文字动画
 */
function initTextAnimation() {
    const elements = document.querySelectorAll('.text-animation');
    
    elements.forEach(function(element) {
        const text = element.textContent;
        element.textContent = '';
        
        // 为每个字符创建一个span元素
        for (let i = 0; i < text.length; i++) {
            const span = document.createElement('span');
            span.textContent = text[i];
            span.style.animationDelay = (0.05 * i) + 's';
            element.appendChild(span);
        }
    });
}

/**
 * 初始化点击波纹效果
 */
function initRippleEffect() {
    const buttons = document.querySelectorAll('.btn, .ripple-effect');
    
    buttons.forEach(function(button) {
        button.addEventListener('click', function(e) {
            const rect = button.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            
            // 创建波纹元素
            const ripple = document.createElement('span');
            ripple.className = 'ripple';
            ripple.style.left = x + 'px';
            ripple.style.top = y + 'px';
            
            // 计算波纹大小
            const size = Math.max(rect.width, rect.height);
            ripple.style.width = ripple.style.height = size + 'px';
            
            // 添加到按钮
            button.appendChild(ripple);
            
            // 动画结束后移除波纹
            setTimeout(function() {
                ripple.remove();
            }, 600);
        });
    });
}

/**
 * 初始化图片懒加载
 */
function initLazyLoading() {
    // 检查浏览器是否支持IntersectionObserver
    if ('IntersectionObserver' in window) {
        const lazyImages = document.querySelectorAll('img[data-src]');
        
        const imageObserver = new IntersectionObserver(function(entries, observer) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    
                    // 图片加载完成后添加动画类
                    img.onload = function() {
                        img.classList.add('fadeIn');
                    };
                    
                    // 停止监听已加载的图片
                    imageObserver.unobserve(img);
                }
            });
        });
        
        lazyImages.forEach(function(image) {
            imageObserver.observe(image);
        });
    } else {
        // 不支持IntersectionObserver的回退方案
        const lazyImages = document.querySelectorAll('img[data-src]');
        
        function lazyLoad() {
            lazyImages.forEach(function(img) {
                if (isInViewport(img)) {
                    img.src = img.dataset.src;
                    img.classList.add('fadeIn');
                }
            });
        }
        
        // 初始检查
        lazyLoad();
        
        // 添加滚动事件监听器
        window.addEventListener('scroll', lazyLoad);
        window.addEventListener('resize', lazyLoad);
    }
}

/**
 * 判断元素是否在视口内
 * @param {Element} element - 要检查的元素
 * @return {boolean} 元素是否在视口内
 */
function isInViewport(element) {
    const rect = element.getBoundingClientRect();
    
    return (
        rect.top >= 0 &&
        rect.left >= 0 &&
        rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
        rect.right <= (window.innerWidth || document.documentElement.clientWidth)
    );
}

/**
 * 初始化表单验证
 */
function initFormValidation() {
    const forms = document.querySelectorAll('form[data-validate]');
    
    forms.forEach(function(form) {
        form.addEventListener('submit', function(e) {
            let isValid = true;
            
            // 获取所有必填输入框
            const requiredInputs = form.querySelectorAll('input[required], textarea[required]');
            
            requiredInputs.forEach(function(input) {
                // 移除之前的错误信息
                const existingError = input.parentNode.querySelector('.error-message');
                if (existingError) {
                    existingError.remove();
                }
                
                // 重置输入框样式
                input.classList.remove('is-invalid');
                
                // 检查输入框是否为空
                if (!input.value.trim()) {
                    isValid = false;
                    input.classList.add('is-invalid');
                    
                    // 添加错误信息
                    const errorMessage = document.createElement('div');
                    errorMessage.className = 'error-message';
                    errorMessage.textContent = '此字段为必填项';
                    input.parentNode.appendChild(errorMessage);
                    
                    // 聚焦到第一个错误输入框
                    if (document.querySelector('.is-invalid') === input) {
                        input.focus();
                    }
                }
                
                // 验证邮箱格式
                if (input.type === 'email' && input.value.trim()) {
                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    
                    if (!emailRegex.test(input.value)) {
                        isValid = false;
                        input.classList.add('is-invalid');
                        
                        // 添加错误信息
                        const errorMessage = document.createElement('div');
                        errorMessage.className = 'error-message';
                        errorMessage.textContent = '请输入有效的邮箱地址';
                        input.parentNode.appendChild(errorMessage);
                    }
                }
                
                // 验证密码强度
                if (input.type === 'password' && input.dataset.validateStrength) {
                    const password = input.value;
                    const minLength = 8;
                    
                    if (password.length < minLength) {
                        isValid = false;
                        input.classList.add('is-invalid');
                        
                        // 添加错误信息
                        const errorMessage = document.createElement('div');
                        errorMessage.className = 'error-message';
                        errorMessage.textContent = `密码长度必须至少为 ${minLength} 个字符`;
                        input.parentNode.appendChild(errorMessage);
                    }
                }
                
                // 验证密码确认
                if (input.dataset.validateMatch) {
                    const targetId = input.dataset.validateMatch;
                    const targetInput = document.getElementById(targetId);
                    
                    if (targetInput && input.value !== targetInput.value) {
                        isValid = false;
                        input.classList.add('is-invalid');
                        
                        // 添加错误信息
                        const errorMessage = document.createElement('div');
                        errorMessage.className = 'error-message';
                        errorMessage.textContent = '两次输入的密码不匹配';
                        input.parentNode.appendChild(errorMessage);
                    }
                }
            });
            
            // 如果表单验证失败，阻止提交
            if (!isValid) {
                e.preventDefault();
            }
        });
    });
}

/**
 * 显示通知消息
 * @param {string} message - 通知消息内容
 * @param {string} type - 通知类型 (success/error/info/warning)
 * @param {number} duration - 显示时长(毫秒)
 */
function showNotification(message, type = 'info', duration = 3000) {
    // 创建通知元素
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <div class="notification-icon">
                ${getNotificationIcon(type)}
            </div>
            <div class="notification-message">${message}</div>
        </div>
        <button class="notification-close">&times;</button>
    `;
    
    // 添加到页面
    document.body.appendChild(notification);
    
    // 添加显示类
    setTimeout(() => {
        notification.classList.add('show');
    }, 10);
    
    // 添加关闭事件
    const closeButton = notification.querySelector('.notification-close');
    closeButton.addEventListener('click', () => {
        closeNotification(notification);
    });
    
    // 自动关闭
    if (duration > 0) {
        setTimeout(() => {
            closeNotification(notification);
        }, duration);
    }
}

/**
 * 关闭通知消息
 * @param {Element} notification - 通知元素
 */
function closeNotification(notification) {
    notification.classList.remove('show');
    
    // 动画结束后移除元素
    setTimeout(() => {
        notification.remove();
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
            return '<svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm4.59-12.42L10 14.17l-2.59-2.58L6 13l4 4 8-8z"/></svg>';
        case 'error':
            return '<svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm-1-13h2v6h-2zm0 8h2v2h-2z"/></svg>';
        case 'warning':
            return '<svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm-1-9h2V7h-2v4zm0 6h2v-2h-2v2z"/></svg>';
        default:
            return '<svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm-1-11h2v6h-2zm0 8h2v-2h-2z"/></svg>';
    }
} 