<?php
/**
 * Voxu Admin — User Ad Campaigns Review
 * @author  Jcode | ObrempongK
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../core/Security.php';
requireAdmin();

$admin = DB::first('SELECT * FROM admins WHERE id=?', [$_SESSION['admin_id']]);
$activeMenu = 'user-ads';
$settings   = getPlatformSettings();
$msg = '';

// Handle approve/reject actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $adId  = (int)($_POST['ad_id'] ?? 0);
    $act   = sanitize($_POST['action'] ?? '');
    $note  = sanitize($_POST['admin_note'] ?? '');
    if ($adId && in_array($act, ['approve','reject','pause'])) {
        $newStatus = $act === 'approve' ? 'active' : ($act === 'pause' ? 'paused' : 'rejected');
        DB::exec('UPDATE user_ads SET status=?, admin_note=?, updated_at=NOW() WHERE id=?', [$newStatus, $note, $adId]);
        // Notify user
        $ad = DB::first('SELECT user_id, title FROM user_ads WHERE id=?', [$adId]);
        if ($ad) {
            $notifMsg = $act==='approve' ? "✅ Your ad \"{$ad['title']}\' is now live!" : "❌ Your ad \"{$ad['title']}\' was {$newStatus}.".($note?" Reason: {$note}":'');
            createNotification((int)$ad['user_id'], 'info', $notifMsg);
        }
        logAdminAction('user_ad_'.$act, "Ad ID #{$adId}: {$act}");
        $msg = "Ad #{$adId} {$act}d successfully.";
    }
}

$status = sanitize($_GET['status'] ?? 'pending');
try {
    $ads = DB::query(
        "SELECT ua.*, u.username FROM user_ads ua LEFT JOIN users u ON u.id=ua.user_id WHERE ua.status=? ORDER BY ua.created_at DESC LIMIT 50",
        [$status]
    );
    $counts = DB::query("SELECT status, COUNT(*) AS cnt FROM user_ads GROUP BY status");
    $statusCounts = array_column($counts, 'cnt', 'status');
} catch (Throwable $e) { $ads = []; $statusCounts = []; }
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1"/>
<meta name="csrf-token" content="<?= csrfToken() ?>"/>
<title>User Ads — Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="/assets/css/admin.css"/>
</head><body>
<?php include __DIR__ . '/../includes/admin_sidebar.php'; ?>
<div class="admin-main">
  <div class="admin-topbar"><h1 class="admin-title">📣 User Ad Campaigns</h1></div>
  <div class="admin-body">
    <?php if ($msg): ?><div class="alert alert-success mb-4"><?= clean($msg) ?></div><?php endif; ?>
    <!-- Status filter -->
    <div style="display:flex;gap:8px;margin-bottom:20px;flex-wrap:wrap">
      <?php foreach(['pending','active','paused','rejected','completed'] as $s): ?>
      <a href="?status=<?= $s ?>" class="btn <?= $status===$s?'btn-primary':'btn-secondary' ?> btn-sm" style="border-radius:999px">
        <?= ucfirst($s) ?> (<?= $statusCounts[$s] ?? 0 ?>)
      </a>
      <?php endforeach; ?>
    </div>
    <?php if (empty($ads)): ?>
      <div class="card" style="text-align:center;padding:40px;color:var(--text3)">No <?= $status ?> campaigns</div>
    <?php else: ?>
    <div style="display:flex;flex-direction:column;gap:14px">
    <?php foreach ($ads as $ad): ?>
    <div class="card">
      <div style="display:flex;gap:14px;align-items:flex-start">
        <?php if ($ad['image_url']): ?>
          <img src="<?= clean($ad['image_url']) ?>" style="width:90px;height:90px;object-fit:cover;border-radius:8px;flex-shrink:0"/>
        <?php else: ?>
          <div style="width:90px;height:90px;background:var(--bg3);border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:32px;flex-shrink:0">📣</div>
        <?php endif; ?>
        <div style="flex:1;min-width:0">
          <div style="font-size:16px;font-weight:700;margin-bottom:4px"><?= clean($ad['title']) ?></div>
          <div style="font-size:13px;color:var(--text3);margin-bottom:8px">
            By @<?= clean($ad['username']??'unknown') ?> ·
            <?= clean($ad['placement']) ?> ·
            <?= number_format($ad['budget_amount']) ?> <?= $ad['budget_type'] ?> budget ·
            <?= date('d M Y', strtotime($ad['created_at'])) ?>
          </div>
          <?php if ($ad['description']): ?><p style="font-size:13px;color:var(--text2);margin-bottom:8px"><?= clean($ad['description']) ?></p><?php endif; ?>
          <a href="<?= clean($ad['target_url']) ?>" target="_blank" style="font-size:13px;color:var(--purple)"><?= clean(substr($ad['target_url'],0,60)) ?></a>
          <div style="display:flex;gap:16px;margin-top:8px;font-size:12px;color:var(--text3)">
            <span>👁 <?= number_format($ad['impressions']) ?> views</span>
            <span>🖱 <?= number_format($ad['clicks']) ?> clicks</span>
            <span>💸 <?= number_format($ad['spent']) ?> spent</span>
          </div>
        </div>
        <?php if ($ad['status'] === 'pending'): ?>
        <div style="display:flex;flex-direction:column;gap:8px;flex-shrink:0">
          <form method="POST" style="display:flex;flex-direction:column;gap:6px">
            <input type="hidden" name="_csrf" value="<?= csrfToken() ?>"/>
            <input type="hidden" name="ad_id" value="<?= $ad['id'] ?>"/>
            <input class="input" type="text" name="admin_note" placeholder="Admin note (optional)" style="font-size:12px;padding:6px 10px"/>
            <button name="action" value="approve" class="btn btn-success btn-sm" style="border-radius:999px">✅ Approve</button>
            <button name="action" value="reject"  class="btn btn-danger btn-sm"  style="border-radius:999px">❌ Reject</button>
          </form>
        </div>
        <?php elseif ($ad['status'] === 'active'): ?>
        <form method="POST">
          <input type="hidden" name="_csrf" value="<?= csrfToken() ?>"/>
          <input type="hidden" name="ad_id" value="<?= $ad['id'] ?>"/>
          <button name="action" value="pause" class="btn btn-secondary btn-sm" style="border-radius:999px">⏸ Pause</button>
        </form>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</div>
<script src="/assets/js/voxu.js"></script>
</body></html>
