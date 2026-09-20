# 丹媞創網 Booking Platform（DBP）

DBP 是丹媞創網自有的多據點預約管理平台。第一個導入客戶為 ANASA 安耐曬，TableSit 作為 Reserve with Google 的底層服務，DBP 透過 Public API v1 集中管理各據點預約。

## TableSit 正式 API 規格

已依 TableSit OpenAPI 確認：

- Base URL：`https://www.tablesit.co/api/v1`
- 認證：`Authorization: Bearer tsk_live_...`
- 預約列表：`GET /bookings`
- 日期參數：`date_from`、`date_to`（UTC ISO 8601）
- 分頁參數：`page`、`per_page`，每頁最多 50 筆
- 分頁資訊：`meta.page`、`meta.total_pages`
- 每把 API Key 只綁定一個 Organization，不傳 organization ID
- 預約主鍵：`uid`
- 客戶欄位：`client`
- 人數欄位：`client_count`
- 來源欄位：`source`

## 多據點金鑰

在 `.env` 以 DBP 據點 slug 對應每個 TableSit Organization 的專屬金鑰：

```dotenv
TABLESIT_API_KEYS_JSON={"anasa-kaohsiung":"tsk_live_xxx","anasa-taipei":"tsk_live_yyy"}
```

API 金鑰不得提交至版本庫。

## 同步指令

```bash
php artisan tablesit:sync-bookings
php artisan tablesit:sync-bookings anasa-kaohsiung
php artisan tablesit:sync-bookings --date-from=2026-09-01T00:00:00Z --date-to=2027-09-01T00:00:00Z
```

排程預設每 15 分鐘同步最近 30 天至未來一年內的預約。

## 目前進度（DBP v0.6）

- [x] 管理者登入與 Dashboard
- [x] 預約、客戶與據點管理
- [x] TableSit 正式 Bearer 認證與 v1 端點
- [x] 官方預約欄位及分頁映射
- [x] 每據點獨立 API Key
- [x] 跨據點同步、錯誤紀錄與排程
- [ ] 填入各據點的正式 `tsk_live_` API Key
- [ ] 使用真實帳號完成首次同步驗收
- [ ] 部署正式環境
