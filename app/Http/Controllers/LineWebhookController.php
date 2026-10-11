<?php

namespace App\Http\Controllers;

use App\Models\LinePairingCode;
use App\Models\LineRecipient;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LineWebhookController extends Controller
{
    public function __invoke(Request $request, string $channel)
    {
        $secret = config('services.line.channels.'.$channel.'.secret');
        abort_unless($secret, 404);
        $expected = base64_encode(hash_hmac('sha256', $request->getContent(), $secret, true));
        abort_unless(hash_equals($expected, (string) $request->header('X-Line-Signature')), 403);
        $data = json_decode($request->getContent(), true);
        abort_unless(is_array($data) && isset($data['events']) && is_array($data['events']), 400);
        foreach ($data['events'] as $event) {
            if (($event['type'] ?? '') !== 'message' || ($event['message']['type'] ?? '') !== 'text' ||
                ! preg_match('/^DBP-([A-Za-z0-9]{32})$/', trim($event['message']['text'] ?? ''), $match)) {
                continue;
            }
            DB::transaction(function () use ($event, $match, $channel) {
                $code = LinePairingCode::where('code_hash',hash('sha256',$match[1]))->lockForUpdate()->first();
                if (! $code || $code->channel !== $channel || $code->used_at || $code->expires_at->isPast()) {
                    return;
                }
                $source = $event['source'] ?? [];
                $isStaff = $code->audience === 'staff';
                if (($source['type'] ?? '') !== ($isStaff ? 'group' : 'user')) {
                    return;
                }
                $lineId = $source[$isStaff ? 'groupId' : 'userId'] ?? '';
                if (! preg_match('/^'.($isStaff ? 'C' : 'U').'[a-f0-9]{32}$/', $lineId)) {
                    return;
                }
                $issuer = User::find($code->issued_by);
                $authorized = $issuer?->organizationIds() ?? [];
                foreach ($code->organization_ids as $id) {
                    $org = Organization::find($id);
                    if (! in_array($id, $authorized) || ! $org || ($org->settings['notifications']['channel'] ?? null) !== $channel) {
                        return;
                    }
                }
                foreach ($code->organization_ids as $id) {
                    LineRecipient::updateOrCreate(['recipient_key'=>$id.':'.$code->audience.':'.($code->customer_id ?? 'group')], [
                        'organization_id'=>$id, 'customer_id'=>$code->customer_id, 'audience'=>$code->audience,
                        'channel'=>$channel, 'line_id'=>$lineId,
                    ]);
                }
                $code->update(['used_at'=>now()]);
            });
        }
        return response()->json(['ok'=>true]);
    }
}
