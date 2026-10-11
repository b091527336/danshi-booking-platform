@extends('layouts.admin')
@section('title', '預約通知設定｜DBP')
@section('content')
<div class="mb-4"><div class="text-secondary small mb-1">BOOKING NOTIFICATIONS</div><h1 class="h3">預約通知設定</h1><p class="text-secondary">自行編輯通知文字，讓每個據點都傳達一致的服務資訊。</p></div>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
@if(!$selected)<div class="card stat-card p-4">尚未分配可管理的據點。</div>@else
<div class="card stat-card mb-4"><div class="card-body p-4"><form method="GET" class="d-flex flex-wrap align-items-end gap-3"><div><label for="organization_id" class="form-label">目前編輯據點</label><select id="organization_id" name="organization_id" class="form-select">@foreach($organizations as $org)<option value="{{ $org->id }}" @selected($org->id===$selected->id)>{{ $org->name }}</option>@endforeach</select></div><button class="btn btn-outline-secondary">載入設定</button></form></div></div>
<div class="alert alert-light border">LINE 通知需完成帳號連線與收件對象綁定。內容可以先儲存；啟用後只處理新進或有變更的未來預約，不會補發歷史紀錄。</div>
<form method="POST" action="{{ route('notifications.update') }}">@csrf @method('PUT')
<div class="card stat-card mb-4"><div class="card-body p-4"><h2 class="h5 mb-3">套用據點</h2><p class="small text-secondary">勾選多個據點，即可共用這份通知內容與開關。各據點的群組綁定仍會保留。</p><div class="d-flex flex-wrap gap-3">@foreach($organizations as $org)<label class="form-check"><input class="form-check-input" type="checkbox" name="organization_ids[]" value="{{ $org->id }}" @checked(in_array($org->id,old('organization_ids',[$selected->id])))>{{ $org->name }}</label>@endforeach</div>
@if(auth()->user()->isAdmin() && count($channels))<label for="channel" class="form-label mt-3">LINE 發送帳號</label><select id="channel" name="channel" class="form-select"><option value="">尚未指定</option>@foreach($channels as $channel)<option value="{{ $channel }}" @selected(old('channel',$settings['channel']??'')===$channel)>{{ $channel }}</option>@endforeach</select>@endif
</div></div>
<div class="row g-4 mb-4">@foreach(['staff'=>'工作人員通知','customer'=>'客人通知'] as $audience=>$label)
<div class="col-12 col-xl-6"><div class="card stat-card h-100"><div class="card-body p-4">
<div class="d-flex justify-content-between gap-3 mb-3"><h2 class="h5">{{ $label }}</h2><label class="form-check form-switch"><input type="hidden" name="{{ $audience }}_enabled" value="0"><input class="form-check-input" type="checkbox" name="{{ $audience }}_enabled" value="1" @checked(old($audience.'_enabled',$settings[$audience.'_enabled']))>啟用</label></div>
<p class="small text-secondary">{{ $audience==='staff' ? '發送到已綁定的內部群組，提醒處理新預約、異動與取消。' : '發送給已完成該據點身分綁定的預約客人。' }}</p>
<label for="{{ $audience }}_template" class="form-label">通知內容</label><textarea class="form-control notification-template" rows="9" maxlength="1500" id="{{ $audience }}_template" name="{{ $audience }}_template" data-audience="{{ $audience }}">{{ old($audience.'_template',$settings[$audience.'_template']) }}</textarea>
<div class="d-flex flex-wrap gap-2 mt-3">@foreach($tokens as $token)<button type="button" class="btn btn-sm btn-outline-secondary insert-token" data-target="{{ $audience }}_template" data-token="{{ $token }}">＋{{ $token }}</button>@endforeach</div>
<div class="mt-4 small text-secondary">訊息預覽 · 示範資料</div><pre class="mt-2 p-3 bg-light rounded-3 mb-0" id="{{ $audience }}_preview" style="white-space:pre-wrap;font:inherit;overflow-wrap:anywhere"></pre>
</div></div></div>@endforeach</div>
<button class="btn btn-primary mb-4" type="submit">儲存通知設定</button>
</form>
<div class="card stat-card mb-4"><div class="card-body p-4"><h2 class="h5 mb-3">LINE 收件對象</h2>
@php $channel=$settings['channel']??null; $connected=$channel && config('services.line.channels.'.$channel.'.secret') && config('services.line.channels.'.$channel.'.token'); @endphp
@if(!$connected)<span class="badge text-bg-warning">帳號連線待完成</span><p class="text-secondary mt-3 mb-0">通知文字已可編輯。管理員完成 LINE 串接後，就能建立群組與客人綁定碼。</p>@else
<p class="text-secondary small">綁定碼有效 30 分鐘，只能使用一次。群組更換成功後，新通知會改送新群組。</p>
@if(session('pairing_code'))<div class="alert alert-info">{{ session('pairing_audience')==='staff' ? '請在指定內部群組傳送下列文字，群組需包含官方帳號。' : '請將下列文字交給該位客人，請客人私訊官方帳號完成綁定。' }}<div class="fw-bold mt-2" style="overflow-wrap:anywhere">{{ session('pairing_code') }}</div></div>@endif
<div class="d-flex gap-4 flex-wrap">
<form method="POST" action="{{ route('notifications.pair') }}">@csrf<input type="hidden" name="audience" value="staff"><h3 class="h6">工作人員群組</h3>@foreach($organizations as $org)<label class="form-check"><input class="form-check-input" type="checkbox" name="organization_ids[]" value="{{ $org->id }}" @checked($org->id===$selected->id)>{{ $org->name }} <span class="small text-secondary">{{ $recipients->where('organization_id',$org->id)->where('audience','staff')->count() ? '已綁定' : '未綁定' }}</span></label>@endforeach<button class="btn btn-outline-primary mt-3">建立／更換群組綁定碼</button></form>
<form method="POST" action="{{ route('notifications.pair') }}">@csrf<input type="hidden" name="audience" value="customer"><input type="hidden" name="organization_ids[]" value="{{ $selected->id }}"><h3 class="h6">預約客人</h3><label for="customer_id" class="form-label">選擇此據點的預約客人</label><select class="form-select" id="customer_id" name="customer_id" required><option value="">請選擇</option>@foreach($customers as $customer)<option value="{{ $customer->id }}">{{ $customer->name ?: '未提供姓名' }}（#{{ $customer->id }}）</option>@endforeach</select><button class="btn btn-outline-primary mt-3" @disabled($customers->isEmpty())>建立客人綁定碼</button></form>
</div>@endif
</div></div>
<div class="card stat-card"><div class="card-body p-4"><h2 class="h5">最近通知紀錄</h2><div class="table-responsive"><table class="table mb-0"><thead><tr><th>建立時間</th><th>據點</th><th>對象</th><th>狀態</th></tr></thead><tbody>@forelse($logs as $log)<tr><td>{{ $log->created_at->timezone('Asia/Taipei')->format('m/d H:i') }}</td><td>{{ $log->booking?->organization?->name }}</td><td>{{ $log->audience==='staff' ? '工作人員':'客人' }}</td><td>{{ $log->status==='pending' && $log->error_code==='LINE_NOT_CONNECTED' ? '等待 LINE 綁定' : (['pending'=>'等待發送','sent'=>'已送交 LINE','failed'=>'發送失敗','disabled'=>'已停用','expired'=>'已逾期','superseded'=>'已被新異動取代'][$log->status]??$log->status) }}</td></tr>@empty<tr><td colspan="4" class="text-secondary text-center py-4">尚無通知紀錄</td></tr>@endforelse</tbody></table></div></div></div>
@verbatim
<script>
const examples={事件:'新預約已收到',客戶姓名:'王小姐',預約時間:'2026/10/20 10:00',結束時間:'2026/10/20 11:00',據點:'示範據點',服務項目:'安裝預約',預約編號:'DEMO-001',預約狀態:'已確認'};
function preview(input){document.getElementById(input.dataset.audience+'_preview').textContent=input.value.replace(/\{\{(.*?)\}\}/g,(_,key)=>examples[key]??'【未支援欄位】');}
document.querySelectorAll('.notification-template').forEach(input=>{input.addEventListener('input',()=>preview(input));preview(input);});
document.querySelectorAll('.insert-token').forEach(button=>button.addEventListener('click',()=>{const input=document.getElementById(button.dataset.target),token='{'+ '{'+button.dataset.token+'}'+ '}';const start=input.selectionStart,end=input.selectionEnd;if(input.value.length-(end-start)+token.length>1500)return;input.setRangeText(token,start,end,'end');input.focus();preview(input);}));
</script>
@endverbatim
@endif
@endsection
