# 丹媞創網 Booking Platform（DBP）

DBP 是丹媞創網自有的多據點預約管理平台。第一個導入客戶為 ANASA 安耐曬，TableSit 作為 Reserve with Google 的底層服務，DBP 透過 Partner API 集中管理各據點預約。

## 技術架構

- Laravel 12
- PHP 8.2+
- MySQL 8
- Bootstrap 5

## 啟動

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

## TableSit 同步

DBP 已提供可配置的 TableSit REST/JSON 拉取同步引擎：

```bash
php artisan tablesit:sync-bookings
php artisan tablesit:sync-bookings anasa-kaohsiung
php artisan tablesit:sync-bookings --since=2026-09-20T00:00:00Z
```

排程預設每 15 分鐘執行一次。正式上線前，須依 TableSit 實際 OpenAPI 設定 `TABLESIT_BASE_URL`、認證方式、預約端點與查詢參數。API 密鑰不得提交至版本庫。

同步具備：

- 多據點逐一拉取
- 依外部預約 ID 冪等新增或更新
- 客戶 Email／電話比對
- 狀態與時區正規化
- 原始 JSON 保存
- 分頁上限保護
- 同步結果及錯誤紀錄
- 單筆資料失敗隔離

## 目前進度（DBP v0.6）

- [x] 管理者登入與 Dashboard
- [x] 預約、客戶與據點管理
- [x] TableSit API Client 與容錯映射器
- [x] 跨據點預約同步引擎
- [x] 同步紀錄、錯誤追蹤與每 15 分鐘排程
- [ ] 取得 TableSit 正式 OpenAPI 細節並鎖定設定
- [ ] 使用真實帳號完成首次同步驗收
- [ ] 部署正式環境
