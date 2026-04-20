# DNA WordPress Theme

一个以 WooCommerce 为核心的定制主题，面向 **Design n’ Aesthetics (DNA)** 站点。仓库内已包含可直接部署的 PHP 模板、CSS、JS 与 WooCommerce 覆盖文件，无需前端构建流程。

## 环境要求

- WordPress 6.x
- PHP 8.0+
- WooCommerce 7.x+

## 安装方式

1. 将本仓库放入 `wp-content/themes/dna`（目录名建议与主题 slug 保持一致）。
2. 在 WordPress 后台 **外观 → 主题** 启用 DNA。
3. 在 **外观 → 菜单** 将主导航分配到 `Primary Menu`。
4. 在 **WooCommerce → 设置 → 产品 → 固定链接** 中，将 **产品分类基础** 设置为 `line`，以匹配主题的分类路由与模板。

## 主题核心能力（基于当前代码）

- **WooCommerce 深度接管**  
  主题关闭了 WooCommerce 默认样式，并使用自定义模板与样式接管商店、分类与相关页面展示。
- **/line/ 分类路由兼容**  
  针对 `product_cat` 实现了 line 风格路由与模板匹配（含 Montessori 专用分类模板）。
- **全局 Contact Drawer**  
  通过 `template-parts/contact-popup.php` + `assets/js/contact-popup.js` 实现全站弹出联系层。
- **后台可视化图片槽位管理**  
  在 `外观 → DNA Images` 可管理首页/B2B/Case/Shop/Line 等主视觉，以及 Case Study 轮播图。
- **后台 SEO/文案批量导入工具**  
  在 `工具 → DNA Rank Math Import` 支持上传 CSV，更新产品 Rank Math 字段和商品描述。
- **B2B 报价管理工具**  
  通过主题后台页维护阶梯价、表格与相关展示配置。

## 页面与模板结构

### 根目录模板

- `front-page.php`：首页。
- `page.php`、`index.php`：通用页面/兜底模板。
- `page-line.php`：Line 聚合页。
- `page-line-landing.php`：Line 落地页模板。
- `page-montessori.php`、`page-montessori-line.php`：Montessori 相关页面。
- `page-b2b.php`：B2B 页面。
- `page-case-study.php`：案例页（含 Hero 轮播）。
- `page-contact.php`：联系页。
- `page-philosophy.php`：品牌理念页。
- `page-privacy.php`、`page-refund-returns.php`：政策类页面。
- `woocommerce.php`：WooCommerce 内容包装模板。
- `taxonomy-product_cat.php`、`taxonomy-product_cat-montessori.php`：产品分类模板。

### WooCommerce 覆盖

- `woocommerce/archive-product.php`
- `woocommerce/taxonomy-product_cat-montessori.php`

> 说明：`woocommerce/taxonomy-product_cat-montessori.php.bak` 为备份文件，不参与标准模板加载。

### 可复用组件

- `template-parts/site-footer.php`
- `template-parts/contact-popup.php`

## 静态资源

- CSS：
  - `style.css`
  - `assets/css/styles.css`
  - `assets/css/shop.css`
  - `assets/css/woocommerce.css`
  - `assets/css/line.css`
  - `assets/css/b2b-wizard.css`
  - `assets/css/contact-popup.css`
- JS：
  - `assets/js/main.js`
  - `assets/js/account.js`
  - `assets/js/variations.js`
  - `assets/js/b2b-wizard.js`
  - `assets/js/contact-popup.js`

主题通过 `functions.php` 中的 `filemtime` 方式进行资源版本控制（缓存自动失效），仓库中不包含打包构建脚本。

## 后台配置入口速览

- **外观 → DNA Images**：主题关键图片槽位。
- **外观 → B2B Pricing**：B2B 报价与案例图片相关配置。
- **工具 → DNA Rank Math Import**：CSV 导入 SEO/描述字段。

## 开发说明

- 本仓库是“直接修改即生效”的主题结构：编辑 PHP/CSS/JS 后即可在 WordPress 中验证。
- 建议在修改 WooCommerce 相关模板后同步检查：Shop、分类页、购物车、结账、我的账户等关键流程。
