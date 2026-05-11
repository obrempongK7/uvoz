<?php
/**
 * Voxu — User Ad Campaigns · Self-serve advertising
 * @author  Jcode | ObrempongK
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../core/Security.php';
require_once __DIR__ . '/../core/i18n.php';
requireAuth();

$user     = auth();
$userId   = (int)$user['id'];
$wallet   = getUserWallet($userId);
$settings = getPlatformSettings();
$appName  = clean($settings['app_name'] ?? 'Voxu');
$symbol   = clean($settings['currency_symbol'] ?? '$');
$rate     = max(1, (int)($settings['points_to_cash_rate'] ?? 100));
$theme    = getTheme();

// Cost per placement (configurable)
$PLACEMENT_COSTS = [
    'feed_top'    => ['pts' => 500,  'label' => 'Feed Top',    'reach' => '~2,000 views/day', 'icon' => '🔝'],
    'feed_middle' => ['pts' => 300,  'label' => 'Feed Middle', 'reach' => '~1,200 views/day', 'icon' => '📍'],
    'feed_right'  => ['pts' => 200,  'label' => 'Right Sidebar','reach' => '~800 views/day',  'icon' => '➡️'],
    'status'      => ['pts' => 400,  'label' => 'Status Bar',  'reach' => '~1,500 views/day', 'icon' => '✨'],
];

try {
    $myAds = DB::query(
        'SELECT * FROM user_ads WHERE user_id=? ORDER BY created_at DESC',
        [$userId]
    );
} catch (Throwable) { $myAds = []; }

$tab = sanitize($_GET['tab'] ?? 'my-ads');
?>
<!DOCTYPE html>
<html lang="<?= getCurrentLang() ?>" <?= isRtl() ? 'dir="rtl"' : '' ?>>
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1"/>
  <meta name="csrf-token" content="<?= csrfToken() ?>"/>
  <title>Ad Campaigns — <?= $appName ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Poppins:wght@700;800&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="/assets/css/voxu.css"/>
</head>
<body class="theme-<?= clean($theme) ?>">

<nav class="sk-topnav">
  <a href="/dashboard/feed.php" class="sk-logo"><?= $appName ?><span class="dot">.</span></a>
  <div style="position:absolute;left:50%;transform:translateX(-50%);font-size:16px;font-weight:700;color:var(--text)">📣 Ad Campaigns</div>
  <div class="sk-nav-actions">
    <a href="/dashboard/notifications.php" class="sk-nav-btn">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
    </a>
    <a href="/dashboard/profile.php" style="flex-shrink:0">
      <div class="avatar avatar-sm"><?php if(!empty($user['avatar'])): ?><img src="<?= clean($user['avatar']) ?>" alt="me"/><?php else: ?><?= avatarInitials($user['username']) ?><?php endif; ?></div>
    </a>
  </div>
</nav>

<div style="padding-top:var(--nav-h);min-height:100vh">

  <!-- Hero banner -->
  <div style="background:var(--grad-main);padding:28px 20px;text-align:center">
    <div style="font-size:28px;font-weight:800;color:#fff;margin-bottom:6px">Grow Your Audience</div>
    <p style="font-size:14px;color:rgba(255,255,255,.85);max-width:480px;margin:0 auto 16px">
      Reach thousands of <?= $appName ?> users with targeted voice-first advertising. Pay with your earned points.
    </p>
    <div style="display:flex;gap:20px;justify-content:center">
      <div style="text-align:center;color:#fff">
        <div style="font-size:22px;font-weight:800"><?= number_format((int)($wallet['points_balance']??0)) ?></div>
        <div style="font-size:11px;opacity:.8">Points Available</div>
      </div>
      <div style="width:1px;background:rgba(255,255,255,.3)"></div>
      <div style="text-align:center;color:#fff">
        <div style="font-size:22px;font-weight:800"><?= $symbol ?><?= number_format((float)($wallet['balance']??0),2) ?></div>
        <div style="font-size:11px;opacity:.8">Cash Available</div>
      </div>
    </div>
  </div>

  <div style="max-width:900px;margin:0 auto;padding:20px 16px 80px">

    <!-- Tabs -->
    <div class="sk-tabs" style="margin-bottom:20px;border:1px solid var(--border);border-radius:var(--radius);overflow:hidden">
      <div class="sk-tab <?= $tab==='my-ads'?'active':'' ?>" onclick="location.href='?tab=my-ads'">📋 My Campaigns</div>
      <div class="sk-tab <?= $tab==='create'?'active':'' ?>" onclick="location.href='?tab=create'">➕ Create Campaign</div>
      <div class="sk-tab <?= $tab==='guide'?'active':'' ?>" onclick="location.href='?tab=guide'">📖 How It Works</div>
    </div>

    <?php if ($tab === 'my-ads'): ?>
    <!-- MY CAMPAIGNS -->
    <?php if (empty($myAds)): ?>
    <div class="sk-empty">
      <div class="sk-empty-icon">📣</div>
      <div class="sk-empty-title">No campaigns yet</div>
      <p class="sk-empty-desc">Create your first ad campaign and start reaching a wider audience today.</p>
      <button onclick="location.href='?tab=create'" class="btn btn-primary" style="border-radius:999px;margin-top:8px">➕ Create First Campaign</button>
    </div>
    <?php else: ?>
      <?php foreach ($myAds as $ad):
        $statusColor = match($ad['status']) {
          'active'    => 'var(--green)',
          'pending'   => 'var(--warning)',
          'paused'    => 'var(--text3)',
          'rejected'  => 'var(--danger)',
          'completed' => 'var(--blue)',
          default     => 'var(--text3)'
        };
        $spent    = (int)$ad['spent'];
        $budget   = (int)$ad['budget_amount'];
        $progress = $budget > 0 ? min(100, round($spent / $budget * 100)) : 0;
        $ctr      = $ad['impressions'] > 0 ? round($ad['clicks'] / $ad['impressions'] * 100, 1) : 0;
      ?>
      <div class="card" style="margin-bottom:14px">
        <div style="display:flex;align-items:flex-start;gap:14px">
          <?php if ($ad['image_url']): ?>
            <img src="<?= clean($ad['image_url']) ?>" style="width:80px;height:80px;object-fit:cover;border-radius:var(--radius-sm);flex-shrink:0"/>
          <?php else: ?>
            <div style="width:80px;height:80px;border-radius:var(--radius-sm);background:var(--purple-l);display:flex;align-items:center;justify-content:center;font-size:32px;flex-shrink:0">
              <?= $PLACEMENT_COSTS[$ad['placement']]['icon'] ?? '📣' ?>
            </div>
          <?php endif; ?>
          <div style="flex:1;min-width:0">
            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:4px">
              <div style="font-size:16px;font-weight:700;color:var(--text)"><?= clean($ad['title']) ?></div>
              <span class="badge" style="background:<?= $statusColor ?>22;color:<?= $statusColor ?>;border:1px solid <?= $statusColor ?>44">
                <?= ucfirst($ad['status']) ?>
              </span>
            </div>
            <div style="font-size:12px;color:var(--text3);margin-bottom:10px">
              <?= $PLACEMENT_COSTS[$ad['placement']]['label'] ?? clean($ad['placement']) ?> ·
              <?= clean($ad['budget_type']) === 'points' ? number_format($budget).' pts budget' : $symbol.number_format($budget,2).' budget' ?>
            </div>
            <!-- Progress bar -->
            <div style="height:6px;background:var(--bg3);border-radius:3px;margin-bottom:8px;overflow:hidden">
              <div style="height:100%;width:<?= $progress ?>%;background:var(--grad-main);border-radius:3px;transition:.3s"></div>
            </div>
            <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:8px">
              <?php foreach([['👁','Impressions',number_format($ad['impressions'])],['🖱','Clicks',number_format($ad['clicks'])],['📊','CTR',$ctr.'%'],['💸','Spent',number_format($spent)]] as [$ic,$lbl,$val]): ?>
              <div style="background:var(--bg3);border-radius:var(--radius-sm);padding:8px;text-align:center">
                <div style="font-size:13px;font-weight:700;color:var(--text)"><?= $val ?></div>
                <div style="font-size:10px;color:var(--text3)"><?= $lbl ?></div>
              </div>
              <?php endforeach; ?>
            </div>
            <?php if ($ad['admin_note']): ?>
              <div class="alert alert-info" style="margin-top:10px;font-size:13px">💬 Admin note: <?= clean($ad['admin_note']) ?></div>
            <?php endif; ?>
          </div>
          <?php if (in_array($ad['status'], ['active','paused'])): ?>
          <button onclick="toggleAd(<?= $ad['id'] ?>, this)" class="btn btn-secondary btn-sm" style="border-radius:999px;flex-shrink:0">
            <?= $ad['status'] === 'active' ? '⏸ Pause' : '▶ Resume' ?>
          </button>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    <?php endif; ?>

    <?php elseif ($tab === 'create'): ?>
    <!-- CREATE CAMPAIGN -->
    <div style="max-width:620px">
      <h2 style="font-size:20px;font-weight:800;color:var(--text);margin-bottom:4px">Create Ad Campaign</h2>
      <p style="font-size:14px;color:var(--text2);margin-bottom:20px">Your ad will be reviewed by our team before going live (usually within 24h).</p>

      <!-- Placement selector -->
      <div class="settings-section" style="margin-bottom:16px">
        <div class="settings-section-title">Choose Placement</div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px" id="placementGrid">
          <?php foreach ($PLACEMENT_COSTS as $key => $info): ?>
          <div class="create-option" id="placement-<?= $key ?>" onclick="selectPlacement('<?= $key ?>')"
            style="position:relative;text-align:left;padding:14px">
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px">
              <span style="font-size:20px"><?= $info['icon'] ?></span>
              <span style="font-size:14px;font-weight:700;color:var(--text)"><?= $info['label'] ?></span>
            </div>
            <div style="font-size:12px;color:var(--text3);margin-bottom:6px"><?= $info['reach'] ?></div>
            <div style="font-size:15px;font-weight:800;color:var(--purple)"><?= number_format($info['pts']) ?> pts/day</div>
          </div>
          <?php endforeach; ?>
        </div>
        <input type="hidden" id="selectedPlacement" value="feed_top"/>
      </div>

      <!-- Budget selector -->
      <div class="settings-section" style="margin-bottom:16px">
        <div class="settings-section-title">Budget & Duration</div>
        <div style="display:flex;gap:8px;margin-bottom:12px" id="budgetTypeToggle">
          <button onclick="setBudgetType('points')" id="btPoints" class="btn btn-primary btn-sm" style="border-radius:999px">⚡ Points</button>
          <button onclick="setBudgetType('cash')" id="btCash" class="btn btn-secondary btn-sm" style="border-radius:999px">💵 Cash</button>
        </div>
        <input type="hidden" id="selectedBudgetType" value="points"/>
        <div class="input-group">
          <label class="input-label" id="budgetLabel">Points Budget (min 100)</label>
          <input class="input" type="number" id="budgetInput" placeholder="Enter budget" min="100" oninput="updateBudgetCalc()"/>
          <div class="input-hint" id="budgetCalc"></div>
        </div>
      </div>

      <!-- Ad content -->
      <div class="settings-section" style="margin-bottom:16px">
        <div class="settings-section-title">Ad Content</div>
        <div class="input-group">
          <label class="input-label">Ad Title *</label>
          <input class="input" type="text" id="adTitle" placeholder="e.g. My Brand — Visit Now!" maxlength="80"/>
        </div>
        <div class="input-group">
          <label class="input-label">Description</label>
          <textarea class="input" id="adDesc" rows="2" placeholder="Short description of your offer..." maxlength="200" style="resize:none"></textarea>
        </div>
        <div class="input-group">
          <label class="input-label">Target URL * <span class="input-hint" style="display:inline">(where clicks go)</span></label>
          <input class="input" type="url" id="adUrl" placeholder="https://yourwebsite.com or wa.me/..."/>
        </div>
        <div class="input-group">
          <label class="input-label">Ad Image <span class="input-hint" style="display:inline">(recommended: 1200×628px)</span></label>
          <label style="display:flex;align-items:center;gap:10px;background:var(--bg3);border:1px dashed var(--border2);border-radius:var(--radius-sm);padding:12px 14px;cursor:pointer">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--text3)" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
            <span id="adImgLabel" style="font-size:13px;color:var(--text2)">Upload ad image (JPG, PNG, WebP, max 10MB)</span>
            <input type="file" id="adImageInput" accept="image/jpeg,image/png,image/webp,image/gif" style="display:none" onchange="previewAdImage()"/>
          </label>
          <div id="adImgPreview" class="hidden" style="margin-top:8px;position:relative">
            <img id="adImgEl" style="width:100%;max-height:200px;object-fit:cover;border-radius:var(--radius-sm)"/>
            <button type="button" onclick="clearAdImage()" style="position:absolute;top:6px;right:6px;width:24px;height:24px;background:rgba(0,0,0,.65);border:none;border-radius:50%;color:#fff;cursor:pointer;font-size:12px">✕</button>
          </div>
        </div>
      </div>

      <!-- Summary card -->
      <div id="adSummary" class="card" style="background:var(--purple-l);border-color:var(--purple);margin-bottom:16px">
        <div style="font-size:14px;font-weight:700;color:var(--purple);margin-bottom:8px">Campaign Summary</div>
        <div style="font-size:13px;color:var(--text2);line-height:1.8" id="summaryText">
          Select a placement and enter your budget above.
        </div>
      </div>

      <button onclick="submitAd()" class="btn btn-primary w-full" style="border-radius:999px;padding:14px;font-size:15px">🚀 Submit Campaign for Review</button>
    </div>

    <?php else: ?>
    <!-- HOW IT WORKS -->
    <div class="settings-section" style="max-width:620px">
      <div class="settings-section-title">How Ad Campaigns Work</div>
      <div style="display:flex;flex-direction:column;gap:16px">
        <?php foreach ([
          ['1️⃣','Choose Your Placement','Pick where your ad appears — top of feed, middle of feed, right sidebar, or the status bar. Each placement reaches a different audience size.'],
          ['2️⃣','Set Your Budget','Pay with points you\'ve earned on '.$appName.' or with your cash wallet balance. Set how much you want to spend total.'],
          ['3️⃣','Create Your Ad','Upload an image, write a catchy title, add your target URL (website, WhatsApp, Instagram, etc.).'],
          ['4️⃣','Admin Review','Our team reviews your ad (usually within 24 hours) to ensure it meets community standards. You\'ll get a notification when it goes live.'],
          ['5️⃣','Track Performance','Monitor impressions, clicks, and click-through rate in real time from your My Campaigns dashboard.'],
        ] as [$num,$title,$desc]): ?>
        <div style="display:flex;gap:14px;padding:14px 0;border-bottom:1px solid var(--border)">
          <span style="font-size:24px;flex-shrink:0"><?= $num ?></span>
          <div>
            <div style="font-size:15px;font-weight:700;color:var(--text);margin-bottom:4px"><?= $title ?></div>
            <div style="font-size:13px;color:var(--text2);line-height:1.6"><?= $desc ?></div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <div class="alert alert-info" style="margin-top:16px">
        <span style="font-size:18px">💡</span>
        <span>Ads are <strong>charged per impression</strong>. Your campaign pauses automatically when the budget is exhausted. Unused budget is <em>not</em> refunded — spend wisely!</span>
      </div>
    </div>
    <?php endif; ?>

  </div>
</div>

<!-- MOBILE BOTTOM NAV -->
<nav class="bottom-nav">
  <a href="/dashboard/feed.php" class="bottom-nav-item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>Home</a>
  <a href="/dashboard/notifications.php" class="bottom-nav-item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>Alerts</a>
  <a href="/dashboard/wallet.php" class="bottom-nav-item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>Wallet</a>
  <a href="/dashboard/ads.php" class="bottom-nav-item active"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>Ads</a>
  <a href="/dashboard/profile.php" class="bottom-nav-item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>Me</a>
</nav>

<div id="toast-container"></div>
<script src="/assets/js/voxu.js"></script>
<script>
const PLACEMENT_COSTS = <?= json_encode(array_map(fn($v) => $v['pts'], $PLACEMENT_COSTS)) ?>;
const PLACEMENTS      = <?= json_encode($PLACEMENT_COSTS) ?>;
let selectedPlacement = 'feed_top';
let selectedBudgetType = 'points';

function selectPlacement(key) {
  selectedPlacement = key;
  document.getElementById('selectedPlacement').value = key;
  document.querySelectorAll('#placementGrid .create-option').forEach(el => {
    el.style.borderColor = el.id === 'placement-'+key ? 'var(--purple)' : 'var(--border)';
    el.style.background  = el.id === 'placement-'+key ? 'var(--purple-l)' : 'var(--bg3)';
  });
  updateBudgetCalc();
}

function setBudgetType(type) {
  selectedBudgetType = type;
  document.getElementById('selectedBudgetType').value = type;
  document.getElementById('btPoints').className = 'btn '+(type==='points'?'btn-primary':'btn-secondary')+' btn-sm';
  document.getElementById('btCash').className   = 'btn '+(type==='cash'?'btn-primary':'btn-secondary')+' btn-sm';
  document.getElementById('btPoints').style.borderRadius = document.getElementById('btCash').style.borderRadius = '999px';
  document.getElementById('budgetLabel').textContent = type==='points' ? 'Points Budget (min 100)' : 'Cash Budget (<?= $symbol ?>, min 1)';
  updateBudgetCalc();
}

function updateBudgetCalc() {
  const budget = parseFloat(document.getElementById('budgetInput').value)||0;
  const info   = PLACEMENTS[selectedPlacement];
  const hint   = document.getElementById('budgetCalc');
  if (!budget) { hint.textContent = ''; updateSummary(); return; }
  if (selectedBudgetType === 'points') {
    const days = Math.round(budget / (info?.pts||500) * 10)/10;
    hint.textContent = `≈ ${days} days of "${info?.label||''}" placement`;
  } else {
    hint.textContent = `<?= $symbol ?>${budget.toFixed(2)} cash budget`;
  }
  updateSummary();
}

function updateSummary() {
  const budget    = parseFloat(document.getElementById('budgetInput').value)||0;
  const title     = document.getElementById('adTitle').value.trim();
  const info      = PLACEMENTS[selectedPlacement];
  const summEl    = document.getElementById('summaryText');
  if (!budget || !title) { summEl.innerHTML = 'Fill in the details above to see your campaign summary.'; return; }
  const budgetStr = selectedBudgetType==='points' ? budget.toLocaleString()+' pts' : '<?= $symbol ?>'+budget.toFixed(2);
  summEl.innerHTML = `<strong>${title}</strong><br>
    📍 Placement: ${info.label} (${info.reach})<br>
    💰 Budget: ${budgetStr} (${selectedBudgetType})<br>
    🎯 Status: Will go live after admin review`;
}

function previewAdImage() {
  const f = document.getElementById('adImageInput').files[0];
  if (!f) return;
  document.getElementById('adImgLabel').textContent = f.name;
  const r = new FileReader(); r.onload = e => {
    document.getElementById('adImgEl').src = e.target.result;
    document.getElementById('adImgPreview').classList.remove('hidden');
  }; r.readAsDataURL(f);
}

function clearAdImage() {
  document.getElementById('adImageInput').value = '';
  document.getElementById('adImgPreview').classList.add('hidden');
  document.getElementById('adImgLabel').textContent = 'Upload ad image (JPG, PNG, WebP, max 10MB)';
}

async function submitAd() {
  const title  = document.getElementById('adTitle').value.trim();
  const url    = document.getElementById('adUrl').value.trim();
  const budget = parseInt(document.getElementById('budgetInput').value)||0;
  if (!title)  { Toast.error('Ad title is required'); return; }
  if (!url)    { Toast.error('Target URL is required'); return; }
  if (budget<1){ Toast.error('Enter a valid budget amount'); return; }

  const fd = new FormData();
  fd.append('title',         title);
  fd.append('description',   document.getElementById('adDesc').value.trim());
  fd.append('target_url',    url);
  fd.append('placement',     selectedPlacement);
  fd.append('budget_type',   selectedBudgetType);
  fd.append('budget',        String(budget));
  fd.append('cost_per_view', '1');
  const imgFile = document.getElementById('adImageInput').files[0];
  if (imgFile) fd.append('image', imgFile, imgFile.name);
  const csrf = getCsrfToken();
  if (csrf) fd.append('_csrf', csrf);

  const btnEl = document.querySelector('[onclick="submitAd()"]');
  setLoading(btnEl, true);
  let data;
  try {
    const res = await fetch('/api/v1/ads/create', {
      method:'POST', credentials:'same-origin',
      headers: { ...(csrf?{'X-CSRF-Token':csrf}:{}), 'X-Requested-With':'XMLHttpRequest' },
      body: fd
    });
    data = await res.json();
  } catch { data = { success:false, message:'Network error' }; }
  setLoading(btnEl, false);
  if (data.success) {
    Toast.success('Campaign submitted! Redirecting…');
    setTimeout(() => location.href='?tab=my-ads', 1200);
  } else {
    Toast.error(data.message||'Submission failed');
  }
}

async function toggleAd(id, btn) {
  const res = await API.post('/ads/'+id+'/toggle', {});
  if (res?.success) {
    Toast.success('Campaign updated');
    setTimeout(()=>location.reload(), 600);
  }
}

// Init first placement selected
selectPlacement('feed_top');
VoxuI18n.init();
</script>
</body>
</html>
