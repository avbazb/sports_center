# 体育中心官网项目

本项目是一个完整的体育中心官方网站系统，提供体育新闻、通知公告、最新动态、运动记录、荣誉墙等功能。

## 项目结构

```
.
├── index.php              // 网站首页
├── news.php               // 新闻消息页面
├── notices.php            // 通知公告页面
├── updates.php            // 最新动态页面
├── records.php            // 运动记录页面
├── honors.php             // 荣誉墙页面
├── events.php             // 体育赛事页面
├── login.php              // 登录页面
├── logout.php             // 退出登录页面
├── register.php           // 注册页面
├── profile.php            // 用户个人主页
├── article.php            // 文章详情页
├── admin/                 // 管理后台
│   ├── index.php          // 管理后台首页
│   ├── articles.php       // 文章管理
│   ├── records.php        // 运动记录管理
│   ├── honors.php         // 荣誉墙管理
│   └── users.php          // 用户管理
├── user/                  // 用户相关功能
│   └── ...                // 用户管理相关文件
├── assets/                // 静态资源
│   ├── css/               // 样式文件
│   ├── js/                // JavaScript文件
│   ├── images/            // 图片资源
│   └── uploads/           // 上传文件存储
├── includes/              // 公共组件
│   ├── header.php         // 页头
│   ├── footer.php         // 页脚
│   ├── sidebar.php        // 侧边栏
│   └── functions.php      // 公共函数
├── config/                // 配置文件
│   └── db.php             // 数据库配置
└── database/              // 数据库相关
    └── schema.sql         // 数据库结构
```

## 功能特点

- 用户系统：登录、注册、个人主页、认证标识
- 文章管理：添加、编辑、删除文章，支持在线编辑和图片上传
- 运动记录管理：记录各项体育项目的记录及保持者
- 荣誉墙：展示体育类荣誉和奖项
- 评论系统：用户可以对文章进行评论
- 响应式设计：适配电脑和手机等多种设备
- 丰富动画：优化用户体验

## 技术栈

- 前端：HTML, CSS, JavaScript
- 后端：PHP
- 数据库：MySQL

## 部署说明

本项目适用于宝塔面板部署，请按照以下步骤进行：

1. 在宝塔面板中创建网站
2. 导入数据库结构（database/schema.sql）
3. 配置数据库连接（config/db.php）
4. 设置目录权限（assets/uploads/需要写入权限）


## 贡献指南

1. Fork 本仓库
2. 创建你的特性分支 (`git checkout -b feature/amazing-feature`)
3. 提交你的更改 (`git commit -m 'Add some amazing feature'`)
4. 推送到分支 (`git push origin feature/amazing-feature`)
5. 打开一个 Pull Request

## 开源许可

该项目根据MIT许可证授权 - 有关详细信息，请查看 [LICENSE](LICENSE) 文件 