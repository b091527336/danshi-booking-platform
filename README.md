# 丹媞創網 Booking Platform（DBP）

DBP 是丹媞創網自有的多據點預約管理平台。第一個導入客戶為 ANASA 安耐曬，TableSit 作為 Reserve with Google 的底層服務，DBP 透過 Partner API 集中管理各據點預約。

## 第一階段範圍

- 管理者登入
- Dashboard
- 預約列表與明細
- 客戶資料
- 多據點（Organization）管理
- TableSit API 串接

## 技術架構

- Laravel 12
- PHP 8.2+
- MySQL 8
- Bootstrap 5

## 本機啟動

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
```

## 目前進度

- [x] SRS V1.0
- [x] 資料庫核心模型
- [x] TableSit API 基礎設定
- [ ] 管理者登入
- [ ] Dashboard
- [ ] 預約同步服務
- [ ] 預約列表與明細
