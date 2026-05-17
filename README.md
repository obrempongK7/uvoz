# Create README, deployment guide, and remaining needed files

cat > /home/claude/uvoz/README.md << 'PHPEOF'
# UVOZ – Speak. Connect. Earn.

**Voice-first social networking web application** where users share content, grow audiences, connect with businesses, and earn real money from genuine engagement.

---

## 🚀 Quick Installation (cPanel/Shared Hosting)

1. Upload the entire `uvoz/` folder to your server (e.g. `public_html/`)
2. Point your domain's document root to `public_html/public/`
3. Navigate to `https://yourdomain.com/install/install.php`
4. Follow the 5-step wizard
5. **Delete** `install/install.php` after installation

---

## ⚙️ Requirements

| Requirement | Version |
|---|---|
| PHP | 8.1+ |
| MySQL | 8.0+ |
| Extensions | PDO MySQL, OpenSSL, Mbstring, cURL, GD |

---

## 🔑 Default Admin Credentials

Set during installation. Default test credentials:
- **Email:** admin@uvoz.app  
- **Password:** Admin@123456

> ⚠️ Change these immediately after installation!

---

## 📁 Folder Structure

```
uvoz/
├── app/                # Application code
│   ├── Http/           # Controllers, Middleware
│   ├── Models/         # Eloquent models
│   ├── Services/       # Business logic
│   └── Notifications/
├── config/             # Laravel config files
├── database/           # Migrations & seeders
├── install/            # Installer wizard
│   ├── install.php     # Web installer
│   └── schema.sql      # Database schema
├── public/             # Web root (point domain here)
│   ├── index.php
│   ├── .htaccess
│   └── storage/        # Symlink to storage/app/public
├── resources/views/    # Blade templates
├── routes/             # web.php, api.php
└── storage/            # Logs, cache, uploads
```

---

## 💡 Key Features

- 🎙️ **Voice Hub** – Record & share audio posts
- 📻 **Podcast Studio** – Full podcast management with RSS
- 🔴 **Live Audio Rooms** – Real-time voice discussions
- 📊 **Status Hub** – Text/image/video statuses with contact buttons
- 💰 **Rewards Engine** – 1000 pts = $1 USD, multiple redemption options
- 📣 **Campaign Marketplace** – Complete advertiser tasks for extra points
- 🔗 **Referral System** – 10% lifetime commission
- 💳 **Multi-Gateway Payments** – Stripe, Paystack, Flutterwave, PayPal
- 🏆 **Gamification** – Badges, streaks, leaderboards
- 🛡️ **KYC Verification** – Document verification for withdrawals
- 🔍 **Fraud Detection** – IP/device analysis
- ⚡ **Admin Panel** – Full platform management

---

## 🌐 Tech Stack

- **Backend:** PHP 8.3 + Laravel 12
- **Frontend:** Blade + Tailwind CSS + Alpine.js
- **Database:** MySQL 8
- **Cache/Queue:** Redis (or file/sync for shared hosting)
- **Real-time:** Laravel Reverb / Pusher

---

## 📧 Support

- Email: support@uvoz.app
- Docs: https://docs.uvoz.app
PHPEOF

# Create artisan file (required for Laravel)
cat > /home/claude/uvoz/artisan << 'PHPEOF'
#!/usr/bin/env php
<?php
define('LARAVEL_START', microtime(true));
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$status = $kernel->handle(
    $input = new Symfony\Component\Console\Input\ArgvInput,
    new Symfony\Component\Console\Output\ConsoleOutput
);
$kernel->terminate($input, $status);
exit($status);
PHPEOF

chmod +x /home/claude/uvoz/artisan

# bootstrap/providers.php
cat > /home/claude/uvoz/bootstrap/providers.php << 'PHPEOF'
<?php
return [
    App\Providers\AppServiceProvider::class,
];
PHPEOF

# Create missing model for UserStreak referenced in controller
cat > /home/claude/uvoz/app/Models/UserStreak.php << 'PHPEOF'
<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserStreak extends Model {
    protected $fillable = ['user_id','current_streak','longest_streak','last_active_date'];
    protected $casts = ['last_active_date' => 'date'];
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
PHPEOF

# Missing Admin content views
cat > /home/claude/uvoz/resources/views/admin/content/reports.blade.php << 'PHPEOF'
@extends('layouts.admin')
@section('page_title','Content Reports')
@section('content')
<div class="flex gap-2 mb-4"><form class="flex gap-2"><select name="status" class="input-field py-2 text-sm"><option value="">All</option>@foreach(['pending','reviewed','actioned','dismissed'] as $s)<option value="{{ $s }}" {{ request('status')===$s?'selected':'' }}>{{ ucfirst($s) }}</option>@endforeach</select><button class="btn-primary py-2">Filter</button></form></div>
<div class="card overflow-hidden">
<table class="w-full"><thead class="border-b border-dark-border"><tr class="text-xs text-slate-400"><th class="text-left px-5 py-3">Reporter</th><th class="text-left px-5 py-3">Type</th><th class="text-left px-5 py-3">Reason</th><th class="text-left px-5 py-3">Date</th><th class="text-left px-5 py-3">Status</th><th class="text-left px-5 py-3">Actions</th></tr></thead>
<tbody>
@forelse($reports as $r)
<tr class="border-b border-dark-border table-row">
<td class="px-5 py-3"><div class="flex items-center gap-2"><img src="{{ $r->reporter->avatar_url }}" class="w-7 h-7 rounded-full object-cover"><span class="text-sm text-white">{{ $r->reporter->display_name }}</span></div></td>
<td class="px-5 py-3 text-xs text-slate-400">{{ class_basename($r->reportable_type) }}</td>
<td class="px-5 py-3 text-sm text-slate-300">{{ $r->reason }}</td>
<td class="px-5 py-3 text-xs text-slate-500">{{ $r->created_at->format('M d') }}</td>
<td class="px-5 py-3">@if($r->status==='actioned')<span class="badge-active">Actioned</span>@elseif($r->status==='pending')<span class="badge-pending">Pending</span>@else<span class="text-xs text-slate-500">{{ ucfirst($r->status) }}</span>@endif</td>
<td class="px-5 py-3">
<form action="{{ route('admin.content.reports.action',$r) }}" method="POST" class="flex gap-2">@csrf
<select name="action" class="input-field py-1 text-xs w-28"><option value="reviewed">Reviewed</option><option value="actioned">Actioned</option><option value="dismissed">Dismissed</option></select>
<button type="submit" class="text-xs text-purple-400 hover:text-purple-300">Update</button>
</form>
</td>
</tr>
@empty<tr><td colspan="6" class="px-5 py-8 text-center text-slate-400">No reports</td></tr>@endforelse
</tbody></table>
<div class="px-5 py-4 border-t border-dark-border">{{ $reports->links() }}</div>
</div>
@endsection
PHPEOF

cat > /home/claude/uvoz/resources/views/admin/content/voice.blade.php << 'PHPEOF'
@extends('layouts.admin')
@section('page_title','Voice Posts')
@section('content')
<div class="card overflow-hidden">
<table class="w-full"><thead class="border-b border-dark-border"><tr class="text-xs text-slate-400"><th class="text-left px-5 py-3">Post</th><th class="text-left px-5 py-3">User</th><th class="text-left px-5 py-3">Plays</th><th class="text-left px-5 py-3">Status</th><th class="text-left px-5 py-3">Date</th><th class="text-left px-5 py-3">Actions</th></tr></thead>
<tbody>
@forelse($posts as $post)
<tr class="border-b border-dark-border table-row">
<td class="px-5 py-3"><p class="text-sm font-medium text-white truncate max-w-48">{{ $post->title }}</p></td>
<td class="px-5 py-3"><div class="flex items-center gap-2"><img src="{{ $post->user->avatar_url }}" class="w-7 h-7 rounded-full object-cover"><span class="text-sm text-white">{{ $post->user->display_name }}</span></div></td>
<td class="px-5 py-3 text-sm text-slate-400">{{ number_format($post->plays_count) }}</td>
<td class="px-5 py-3">@if($post->status==='published')<span class="badge-active">Published</span>@else<span class="badge-pending">{{ ucfirst($post->status) }}</span>@endif</td>
<td class="px-5 py-3 text-xs text-slate-500">{{ $post->created_at->format('M d, Y') }}</td>
<td class="px-5 py-3"><form action="{{ route('admin.content.voice.delete',$post) }}" method="POST" class="inline" onsubmit="return confirm('Delete?')">@csrf @method('DELETE')<button class="text-xs text-red-400 hover:text-red-300">Delete</button></form></td>
</tr>
@empty<tr><td colspan="6" class="px-5 py-8 text-center text-slate-400">No posts</td></tr>@endforelse
</tbody></table>
<div class="px-5 py-4 border-t border-dark-border">{{ $posts->links() }}</div>
</div>
@endsection
PHPEOF

cat > /home/claude/uvoz/resources/views/admin/campaigns.blade.php << 'PHPEOF'
@extends('layouts.admin')
@section('page_title','Campaigns')
@section('content')
<div class="card overflow-hidden">
<table class="w-full"><thead class="border-b border-dark-border"><tr class="text-xs text-slate-400"><th class="text-left px-5 py-3">Campaign</th><th class="text-left px-5 py-3">Advertiser</th><th class="text-left px-5 py-3">Budget</th><th class="text-left px-5 py-3">Actions Completed</th><th class="text-left px-5 py-3">Status</th><th class="text-left px-5 py-3">Actions</th></tr></thead>
<tbody>
@forelse($campaigns as $c)
<tr class="border-b border-dark-border table-row">
<td class="px-5 py-3"><p class="text-sm font-medium text-white">{{ $c->title }}</p><span class="text-xs text-slate-500">{{ ucfirst(str_replace('_',' ',$c->type)) }}</span></td>
<td class="px-5 py-3 text-sm text-slate-400">{{ $c->advertiser->display_name }}</td>
<td class="px-5 py-3 text-sm font-semibold text-green-400">${{ number_format($c->budget,2) }}</td>
<td class="px-5 py-3 text-sm text-slate-400">{{ number_format($c->completed_actions) }}</td>
<td class="px-5 py-3">@if($c->status==='active')<span class="badge-active">Active</span>@elseif($c->status==='pending')<span class="badge-pending">Pending</span>@elseif($c->status==='rejected')<span class="badge-rejected">Rejected</span>@else<span class="text-xs text-slate-500">{{ ucfirst($c->status) }}</span>@endif</td>
<td class="px-5 py-3">
<div class="flex gap-2">
@if($c->status==='pending')
<form action="{{ route('admin.campaigns.approve',$c) }}" method="POST" class="inline">@csrf<button class="text-xs text-green-400 hover:text-green-300">Approve</button></form>
<form action="{{ route('admin.campaigns.reject',$c) }}" method="POST" class="inline" x-data x-on:submit.prevent="const r=prompt('Rejection reason:');if(r){$el.querySelector('[name=reason]').value=r;$el.submit()}">@csrf<input type="hidden" name="reason" value=""><button class="text-xs text-red-400 hover:text-red-300">Reject</button></form>
@endif
</div>
</td>
</tr>
@empty<tr><td colspan="6" class="px-5 py-8 text-center text-slate-400">No campaigns</td></tr>@endforelse
</tbody></table>
<div class="px-5 py-4 border-t border-dark-border">{{ $campaigns->links() }}</div>
</div>
@endsection
PHPEOF

cat > /home/claude/uvoz/resources/views/admin/subscriptions.blade.php << 'PHPEOF'
@extends('layouts.admin')
@section('page_title','Subscription Plans')
@section('content')
<div class="grid md:grid-cols-3 gap-6">
@foreach($plans as $plan)
<div class="card p-6">
<h3 class="text-lg font-bold text-white mb-4">{{ $plan->name }}</h3>
<form action="{{ route('admin.subscriptions.update',$plan) }}" method="POST" class="space-y-3">
@csrf @method('PUT')
<div><label class="block text-xs text-slate-400 mb-1">Monthly Price ($)</label><input type="number" name="monthly_price" step="0.01" value="{{ $plan->monthly_price }}" class="input-field py-2 text-sm"></div>
<div><label class="block text-xs text-slate-400 mb-1">Yearly Price ($)</label><input type="number" name="yearly_price" step="0.01" value="{{ $plan->yearly_price }}" class="input-field py-2 text-sm"></div>
<div><label class="block text-xs text-slate-400 mb-1">Daily Points Cap</label><input type="number" name="daily_points_cap" value="{{ $plan->daily_points_cap }}" class="input-field py-2 text-sm"></div>
<div><label class="block text-xs text-slate-400 mb-1">Earning Boost (%)</label><input type="number" name="earning_boost_percent" value="{{ $plan->earning_boost_percent }}" class="input-field py-2 text-sm"></div>
<div class="flex items-center gap-2"><input type="checkbox" name="is_active" value="1" {{ $plan->is_active ? 'checked' : '' }} class="rounded"><label class="text-xs text-slate-300">Active</label></div>
<button type="submit" class="btn-primary w-full justify-center text-sm py-2">Save Plan</button>
</form>
</div>
@endforeach
</div>
@endsection
PHPEOF

# Admin wallet redemptions and catalog stubs
cat > /home/claude/uvoz/resources/views/admin/wallet/redemptions.blade.php << 'PHPEOF'
@extends('layouts.admin')
@section('page_title','Reward Redemptions')
@section('content')
<div class="card overflow-hidden">
<table class="w-full"><thead class="border-b border-dark-border"><tr class="text-xs text-slate-400"><th class="text-left px-5 py-3">User</th><th class="text-left px-5 py-3">Reward</th><th class="text-left px-5 py-3">Points</th><th class="text-left px-5 py-3">Status</th><th class="text-left px-5 py-3">Date</th><th class="text-left px-5 py-3">Actions</th></tr></thead>
<tbody>
@forelse($redemptions as $r)
<tr class="border-b border-dark-border table-row">
<td class="px-5 py-3"><div class="flex items-center gap-2"><img src="{{ $r->user->avatar_url }}" class="w-7 h-7 rounded-full object-cover"><span class="text-sm text-white">{{ $r->user->display_name }}</span></div></td>
<td class="px-5 py-3 text-sm text-white">{{ $r->reward->title }}</td>
<td class="px-5 py-3 text-sm font-semibold text-amber-400">{{ number_format($r->points_spent) }}</td>
<td class="px-5 py-3">@if($r->status==='fulfilled')<span class="badge-active">Fulfilled</span>@elseif($r->status==='pending')<span class="badge-pending">Pending</span>@else<span class="text-xs text-slate-500">{{ ucfirst($r->status) }}</span>@endif</td>
<td class="px-5 py-3 text-xs text-slate-500">{{ $r->created_at->format('M d, Y') }}</td>
<td class="px-5 py-3">@if($r->status==='pending')<form action="{{ route('admin.wallet.redemptions.fulfill',$r) }}" method="POST" class="inline">@csrf<button class="text-xs text-green-400 hover:text-green-300">Fulfill</button></form>@endif</td>
</tr>
@empty<tr><td colspan="6" class="px-5 py-8 text-center text-slate-400">No redemptions</td></tr>@endforelse
</tbody></table>
<div class="px-5 py-4 border-t border-dark-border">{{ $redemptions->links() }}</div>
</div>
@endsection
PHPEOF

cat > /home/claude/uvoz/resources/views/admin/wallet/catalog.blade.php << 'PHPEOF'
@extends('layouts.admin')
@section('page_title','Reward Catalog')
@section('content')
<div class="flex items-center justify-between mb-4">
<p class="text-slate-400 text-sm">{{ $rewards->total() }} rewards</p>
<button onclick="document.getElementById('addModal').classList.remove('hidden')" class="btn-primary text-sm py-2 px-4">+ Add Reward</button>
</div>
<div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
@forelse($rewards as $r)
<div class="card p-4">
@if($r->image)<img src="{{ $r->image_url }}" class="w-full h-32 object-cover rounded-2xl mb-3">@endif
<h3 class="font-semibold text-white mb-1">{{ $r->title }}</h3>
<div class="flex items-center justify-between mb-2">
<span class="badge badge-amber text-xs">{{ ucfirst($r->category) }}</span>
<span class="text-sm font-bold text-amber-400">{{ number_format($r->points_required) }} pts</span>
</div>
<p class="text-xs text-slate-400">Stock: {{ $r->stock_quantity ?? '∞' }} · {{ ucfirst($r->delivery_method) }}</p>
<div class="flex gap-2 mt-3">
@if($r->is_active)<span class="badge-active text-xs">Active</span>@else<span class="badge-rejected text-xs">Inactive</span>@endif
<form action="{{ route('admin.wallet.catalog.delete',$r) }}" method="POST" class="inline ml-auto" onsubmit="return confirm('Delete?')">@csrf @method('DELETE')<button class="text-xs text-red-400 hover:text-red-300">Delete</button></form>
</div>
</div>
@empty<div class="md:col-span-3 card p-8 text-center"><p class="text-slate-400">No rewards yet. Add your first reward!</p></div>@endforelse
</div>
{{ $rewards->links() }}
<!-- Add Modal -->
<div id="addModal" class="hidden fixed inset-0 bg-black/70 z-50 flex items-center justify-center p-4">
<div class="card p-6 w-full max-w-lg">
<div class="flex items-center justify-between mb-4"><h3 class="font-bold text-white">Add Reward</h3><button onclick="document.getElementById('addModal').classList.add('hidden')" class="text-slate-400 hover:text-white">✕</button></div>
<form action="{{ route('admin.wallet.catalog.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
@csrf
<div><label class="block text-xs text-slate-400 mb-1.5">Title *</label><input type="text" name="title" class="input-field" required></div>
<div class="grid sm:grid-cols-2 gap-3">
<div><label class="block text-xs text-slate-400 mb-1.5">Category</label><select name="category" class="input-field">@foreach(['cash','airtime','data','electronics','travel','gift_cards','vouchers','tickets','merchandise'] as $c)<option value="{{ $c }}">{{ ucfirst(str_replace('_',' ',$c)) }}</option>@endforeach</select></div>
<div><label class="block text-xs text-slate-400 mb-1.5">Points Required</label><input type="number" name="points_required" min="1" class="input-field" required></div>
</div>
<div class="grid sm:grid-cols-2 gap-3">
<div><label class="block text-xs text-slate-400 mb-1.5">USD Value</label><input type="number" name="usd_value" step="0.01" class="input-field"></div>
<div><label class="block text-xs text-slate-400 mb-1.5">Delivery</label><select name="delivery_method" class="input-field"><option value="digital">Digital</option><option value="physical">Physical</option><option value="automatic">Automatic</option></select></div>
</div>
<div><label class="block text-xs text-slate-400 mb-1.5">Image</label><input type="file" name="image" accept="image/*" class="input-field"></div>
<div class="flex gap-3"><button type="submit" class="btn-primary flex-1 justify-center">Add Reward</button><button type="button" onclick="document.getElementById('addModal').classList.add('hidden')" class="btn-secondary flex-1 justify-center">Cancel</button></div>
</form>
</div>
</div>
@endsection
PHPEOF

# Admin settings fraud and audit
cat > /home/claude/uvoz/resources/views/admin/settings/fraud.blade.php << 'PHPEOF'
@extends('layouts.admin')
@section('page_title','Fraud Flags')
@section('content')
<div class="card overflow-hidden">
<table class="w-full"><thead class="border-b border-dark-border"><tr class="text-xs text-slate-400"><th class="text-left px-5 py-3">User</th><th class="text-left px-5 py-3">Reason</th><th class="text-left px-5 py-3">Severity</th><th class="text-left px-5 py-3">IP</th><th class="text-left px-5 py-3">Date</th><th class="text-left px-5 py-3">Actions</th></tr></thead>
<tbody>
@forelse($flags as $f)
<tr class="border-b border-dark-border table-row">
<td class="px-5 py-3"><div class="flex items-center gap-2"><img src="{{ $f->user->avatar_url }}" class="w-7 h-7 rounded-full object-cover"><a href="{{ route('admin.users.show',$f->user) }}" class="text-sm text-white hover:text-purple-400">{{ $f->user->display_name }}</a></div></td>
<td class="px-5 py-3 text-xs text-slate-300 max-w-48 truncate">{{ $f->reason }}</td>
<td class="px-5 py-3">@if($f->severity==='high')<span class="badge-rejected">High</span>@elseif($f->severity==='medium')<span class="badge-pending">Medium</span>@else<span class="text-xs text-slate-500">Low</span>@endif</td>
<td class="px-5 py-3 text-xs text-slate-500 font-mono">{{ $f->ip_address }}</td>
<td class="px-5 py-3 text-xs text-slate-500">{{ $f->created_at->format('M d') }}</td>
<td class="px-5 py-3">
<form action="{{ route('admin.settings.fraud.action',$f) }}" method="POST" class="flex gap-2">@csrf
<select name="action" class="input-field py-1 text-xs w-28"><option value="reviewed">Reviewed</option><option value="dismissed">Dismiss</option><option value="actioned">Action</option></select>
<button type="submit" class="text-xs text-purple-400 hover:text-purple-300">Update</button>
</form>
</td>
</tr>
@empty<tr><td colspan="6" class="px-5 py-8 text-center text-slate-400">No fraud flags 🎉</td></tr>@endforelse
</tbody></table>
<div class="px-5 py-4 border-t border-dark-border">{{ $flags->links() }}</div>
</div>
@endsection
PHPEOF

cat > /home/claude/uvoz/resources/views/admin/settings/audit.blade.php << 'PHPEOF'
@extends('layouts.admin')
@section('page_title','Audit Logs')
@section('content')
<div class="card overflow-hidden">
<table class="w-full"><thead class="border-b border-dark-border"><tr class="text-xs text-slate-400"><th class="text-left px-5 py-3">User</th><th class="text-left px-5 py-3">Action</th><th class="text-left px-5 py-3">Target</th><th class="text-left px-5 py-3">IP</th><th class="text-left px-5 py-3">Date</th></tr></thead>
<tbody>
@forelse($logs as $log)
<tr class="border-b border-dark-border table-row">
<td class="px-5 py-3"><span class="text-sm text-white">{{ $log->user?->display_name ?? 'System' }}</span></td>
<td class="px-5 py-3"><span class="badge badge-purple text-xs">{{ $log->action }}</span></td>
<td class="px-5 py-3 text-xs text-slate-400">{{ $log->model_type ? class_basename($log->model_type).'#'.$log->model_id : '-' }}</td>
<td class="px-5 py-3 text-xs text-slate-500 font-mono">{{ $log->ip_address }}</td>
<td class="px-5 py-3 text-xs text-slate-500">{{ $log->created_at->format('M d H:i') }}</td>
</tr>
@empty<tr><td colspan="5" class="px-5 py-8 text-center text-slate-400">No audit logs</td></tr>@endforelse
</tbody></table>
<div class="px-5 py-4 border-t border-dark-border">{{ $logs->links() }}</div>
</div>
@endsection
PHPEOF

echo "All remaining views and files created"
