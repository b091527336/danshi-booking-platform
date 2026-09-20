# TableSit API Mapping（草案）

此文件定義 TableSit Partner API 與 DBP 核心資料表的預定對應。正式欄位名稱須在取得實際 API 回應樣本後鎖定。

## Organization

| TableSit | DBP |
| --- | --- |
| organization/location id | organizations.external_id |
| name | organizations.name |
| timezone | organizations.timezone |
| active/status | organizations.is_active |

## Booking

| TableSit | DBP |
| --- | --- |
| booking id | bookings.external_id |
| organization/location id | bookings.organization_id |
| status | bookings.status |
| start time | bookings.starts_at |
| end time | bookings.ends_at |
| party size | bookings.party_size |
| service | bookings.service_name |
| notes | bookings.notes |
| full response | bookings.provider_payload |

## Customer

| TableSit | DBP |
| --- | --- |
| customer name | customers.name |
| phone | customers.phone |
| email | customers.email |

## 同步原則

- 以 `external_provider + external_id` 作為冪等鍵，避免重複匯入。
- 所有 API 原始回應保留於 `provider_payload`，方便追查欄位差異。
- DBP 內部時間以 UTC 儲存，介面以 Organization 的時區顯示。
- API 金鑰只存放於環境變數，不提交至版本庫。
