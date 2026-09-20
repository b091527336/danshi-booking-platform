# 丹媞創網 Booking Platform（DBP）

DBP 是丹媞創網自有的多據點預約管理平台。第一個導入客戶為 ANASA 安耐曬，TableSit 作為 Reserve with Google 的底層服務，DBP 透過 Public API v1 集中管理各據點預約。

## TableSit 正式 API

- Base URL：`https://www.tablesit.co/api/v1`
- 認證：`Authorization: Bearer tsk_live_...`
- 預約列表：`GET /bookings`
- 每把 API Key 只綁定一個 Organization
- 系統排程每 15 分鐘同步最近 30 天至未來一年

## Docker 啟動

```bash
docker compose up -d --build
```

Docker 容器包含：

- PHP 8.3＋Apache
- Laravel 正式環境快取
- MySQL 8.4
- 自動資料庫 migration
- 管理者帳號建立
- Laravel 排程常駐執行
- `/up` 健康檢查

完整環境變數及驗收流程請見 `docs/DEPLOYMENT.md`。

## GitHub 自動檢查

每次推送至 `main` 或建立 Pull Request 時，自動執行：

- Composer 設定驗證
- PHP 8.3 依賴安裝
- PHP 語法檢查
- TableSit 欄位映射單元測試

## 目前進度（DBP v0.8）

- [x] 管理者登入與 Dashboard
- [x] 預約、客戶與據點管理
- [x] TableSit 正式 API 串接與同步中心
- [x] Docker、Apache、MySQL 與排程
- [x] GitHub 自動測試流程
- [x] 正式部署與驗收文件
- [ ] 填入各據點正式 `tsk_live_` API Key
- [ ] 選定部署主機並建立正式環境
- [ ] 使用真實資料完成首次同步驗收
