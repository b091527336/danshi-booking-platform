<?php

namespace App\Services;

use App\Models\Booking;
use Illuminate\Validation\ValidationException;

class BookingNotificationTemplate
{
    public const TOKENS = ['事件', '客戶姓名', '預約時間', '結束時間', '據點', '服務項目', '預約編號', '預約狀態'];

    public static function defaults(): array
    {
        return [
            'staff_enabled' => false,
            'customer_enabled' => false,
            'staff_template' => "【{{事件}}】\n據點：{{據點}}\n客戶：{{客戶姓名}}\n時間：{{預約時間}}\n服務：{{服務項目}}\n編號：{{預約編號}}\n請登入 DBP 查看詳情。",
            'customer_template' => "{{客戶姓名}}您好，您的{{事件}}。\n服務：{{服務項目}}\n時間：{{預約時間}}\n據點：{{據點}}\n預約編號：{{預約編號}}\n若需調整，請聯繫 ANASA 安耐曬客服。",
        ];
    }

    public function validate(string $template, string $field): void
    {
        preg_match_all('/\{\{(.*?)\}\}/u', $template, $matches);
        foreach ($matches[1] as $token) {
            if (! in_array($token, self::TOKENS, true)) {
                throw ValidationException::withMessages([$field => '不支援的欄位：'.$token]);
            }
        }
        $stripped = preg_replace('/\{\{.*?\}\}/u', '', $template);
        if (str_contains($stripped, '{{') || str_contains($stripped, '}}')) {
            throw ValidationException::withMessages([$field => '欄位標記格式不完整，請使用下方插入按鈕。']);
        }
    }

    public function render(string $template, Booking $booking, string $event): string
    {
        $timezone = $booking->organization->timezone;
        $values = [
            '事件' => $event,
            '客戶姓名' => $booking->customer?->name ?? '貴賓',
            '預約時間' => $booking->starts_at->copy()->timezone($timezone)->format('Y/m/d H:i'),
            '結束時間' => $booking->ends_at?->copy()->timezone($timezone)->format('Y/m/d H:i') ?? '未提供',
            '據點' => $booking->organization->name,
            '服務項目' => $booking->service_name ?? '預約服務',
            '預約編號' => $booking->external_id,
            '預約狀態' => ['confirmed'=>'已確認','pending'=>'待確認','cancelled'=>'已取消','completed'=>'已完成','no_show'=>'未出席'][$booking->status] ?? $booking->status,
        ];
        $replace = [];
        foreach ($values as $key => $value) {
            $replace['{{'.$key.'}}'] = mb_substr((string) $value, 0, 200);
        }
        return mb_substr(strtr($template, $replace), 0, 5000);
    }
}
