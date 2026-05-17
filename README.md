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
