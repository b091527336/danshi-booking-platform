# DBP 正式部署與驗收

## 部署前需要準備

1. 一個支援 Docker 的主機或雲端服務。
2. MySQL 8 資料庫。
3. 正式網域與 HTTPS。
4. DBP 管理者 Email 與高強度密碼。
5. 每個 TableSit Organization 的專屬 `tsk_live_` API Key。

## 必要環境變數

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://booking.example.com
APP_KEY=base64:...
DB_CONNECTION=mysql
DB_HOST=...
DB_PORT=3306
DB_DATABASE=dbp
DB_USERNAME=...
DB_PASSWORD=...
DBP_ADMIN_NAME=DBP 管理者
DBP_ADMIN_EMAIL=...
DBP_ADMIN_PASSWORD=...
TABLESIT_API_KEYS_JSON={"anasa-kaohsiung":"tsk_live_xxx"}
```

不要把正式密碼或 API Key 寫入 GitHub。

## 容器啟動內容

啟動時會自動：

1. 建立 Laravel 必要目錄與權限。
2. 快取正式環境設定與 Blade 頁面。
3. 執行 `php artisan migrate --force`。
4. 在管理者環境變數齊全時建立或更新管理者。
5. 啟動 Apache 網站。
6. 啟動 Laravel 排程，讓 TableSit 每 15 分鐘同步。

## 正式驗收順序

1. 開啟 `/up`，確認服務回應正常。
2. 開啟 `/login`，使用管理者帳號登入。
3. 確認 Dashboard、預約、客戶與據點頁面正常。
4. 在據點管理確認每個 slug 與金鑰 JSON 鍵一致。
5. 到同步中心確認「API 已設定」。
6. 先用單一據點與短日期範圍執行同步。
7. 核對收到、新增、更新與失敗筆數。
8. 抽查 TableSit 與 DBP 的預約時間、客戶、服務及狀態。
9. 確認 15 分鐘排程產生後續同步紀錄。
10. 關閉 `APP_DEBUG` 並確認 HTTPS。

## 回復方式

程式版本由 GitHub 保存。若新版本發生問題，可將部署服務切回上一個成功映像。資料庫回復前必須先建立備份，禁止直接刪除正式資料表。
