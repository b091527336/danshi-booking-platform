# 丹媞創網 Booking Platform（DBP）

DBP 是丹媞創網自有的多據點預約管理平台。第一個導入客戶為 ANASA 安耐曬，TableSit 作為 Reserve with Google 的底層服務，DBP 透過 Public API v1 集中管理各據點預約。

## TableSit 正式 API 規格

- Base URL：`https://www.tablesit.co/api/v1`
- 認證：`Authorization: Bearer tsk_live_...`
- 預約列表：`GET /bookings`
- 日期參數：`date_from`、`date_to`（UTC ISO 8601）
- 分頁參數：`page`、`per_page`，每頁最多 50 筆
- 每把 API Key 只綁定一個 Organization

## 多據點金鑰

```dotenv
TABLESIT_API_KEYS_JSON={"anasa-kaohsiung":"tsk_live_xxx","anasa-taipei":"tsk_live_yyy"}
```

JSON 的鍵必須與 DBP 據點 slug 相同，API 金鑰不得提交至版本庫。

## 同步方式

- 後台「同步中心」可查看各據點 API 設定狀態。
- 可指定日期範圍，手動同步單一據點。
- 頁面顯示收到、新增、更新、失敗筆數及錯誤摘要。
- 系統排程每 15 分鐘同步最近 30 天至未來一年。

命令列亦可執行：

```bash
php artisan tablesit:sync-bookings
php artisan tablesit:sync-bookings anasa-kaohsiung
```

## 目前進度（DBP v0.7）

- [x] 管理者登入與 Dashboard
- [x] 預約、客戶與據點管理
- [x] TableSit 正式 API 串接與多據點獨立金鑰
- [x] 自動同步排程及同步紀錄
- [x] 後台同步中心與單據點手動同步
- [ ] 填入各據點正式 `tsk_live_` API Key
- [ ] 使用真實資料完成首次同步驗收
- [ ] 部署正式環境
