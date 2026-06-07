<p align="center">
  <img src="Sentinel logo.png" alt="Sentinel Logo" width="180"/>
</p>

<h1 align="center">🛡️ Sentinel — Encrypted Chat System</h1>

<p align="center">
  <b>End-to-End Encrypted Communication Platform for Law Enforcement</b><br>
  <i>Zero-Knowledge Architecture · Self-Destruct Messages · Compliance Audit Vault</i>
</p>

<p align="center">
  <img src="https://img.shields.io/badge/Version-1.4-blue?style=for-the-badge" alt="Version"/>
  <img src="https://img.shields.io/badge/PHP-7.4+-purple?style=for-the-badge&logo=php&logoColor=white" alt="PHP"/>
  <img src="https://img.shields.io/badge/MySQL-Database-orange?style=for-the-badge&logo=mysql&logoColor=white" alt="MySQL"/>
  <img src="https://img.shields.io/badge/Encryption-RSA%20%2B%20AES-green?style=for-the-badge&logo=letsencrypt&logoColor=white" alt="Encryption"/>
  <img src="https://img.shields.io/badge/License-Academic-red?style=for-the-badge" alt="License"/>
</p>

---

## 📖 About

**Sentinel** is a zero-knowledge encrypted chat system designed as a Final Year Project (FYP). Built for law enforcement agencies, it ensures all messages are encrypted inside the user's browser before being sent — the server **never** sees plaintext data.

The system balances **secure private communication** between officers with **authorized compliance oversight** by the Head of Security (HOS) through an encrypted audit vault.

---

## ✨ Key Features

### 🔐 Security & Encryption
- **End-to-End Encryption** — RSA-OAEP (2048-bit) + AES-GCM (256-bit) hybrid encryption
- **Zero-Knowledge Architecture** — Server stores only ciphertext; cannot read messages
- **Security Key (.json)** — Auto-generated on registration, required every login (2FA)
- **Web Crypto API** — No third-party crypto libraries; uses browser-native cryptography
- **Brute-Force Protection** — Account locks after 5 failed login attempts
- **Blocked File Types** — Prevents upload of executables (`.exe`, `.bat`, `.php`, etc.)

### 💬 Messaging
- **Real-Time Chat** — Instant messaging with live message polling
- **Read Receipts** — Single tick (sent) → Double tick (delivered) → Blue tick (read)
- **Reply to Messages** — Quote and reply to specific messages with clickable references
- **Edit Messages** — Edit your own messages within 1 hour of sending
- **Forward Messages** — Forward messages to other contacts
- **Delete Messages** — Delete for yourself or delete for everyone
- **Auto-Scroll** — Chat always scrolls to the latest message

### 🔥 Self-Destruct Messages
- **Burn After Read** — Message vanishes once the receiver opens it
- **Timed Deletion** — Auto-delete after 1 hour, 8 hours, or 5 days
- **Live Countdown** — Real-time timer displayed on both sender and receiver sides
- **Permanent Destruction** — Original content is irreversibly destroyed from the database

### 📎 File Attachments
- **All File Types Supported** — Photos, videos, documents, and more
- **Up to 1 GB Upload** — Large file transfer with server-side limit configuration
- **Upload Progress Bar** — Real-time percentage with animated progress indicator
- **Send Lock** — Send button disabled during upload to prevent duplicates
- **Smart Thumbnails** — Image previews, video icons, and document badges

### 📌 Message Organization
- **Pin Messages** — Pin up to 3 messages per chat (1hr / 8hr / 24hr durations)
- **Bookmark Messages** — Save messages with priority levels (High / Medium / Low)
- **Right-Click Context Menu** — Copy, reply, forward, edit, pin, bookmark, delete
- **Saved Messages Panel** — Browse all bookmarked messages in the sidebar

### 🎨 User Interface
- **Dark & Light Mode** — Toggle between themes with remembered preference
- **Glassmorphism Design** — Modern frosted-glass UI with gradient accents
- **Responsive Layout** — Sidebar + chat area + input bar layout
- **Micro-Animations** — Smooth transitions, hover effects, and loading states

### 👮 Admin / HOS Dashboard
- **User Management** — Approve, lock, unlock, and manage officer accounts
- **Compliance Audit Vault** — Encrypted cold storage of all communications
- **Master Key Decryption** — HOS can decrypt audit logs using physical `.json` key
- **Media Decryption** — View decrypted photos, videos, PDFs, and documents
- **Panic Mode** — Emergency data wipe triggered on compromised accounts
- **Account Suspension** — Instantly revoke an officer's system access

---

## 🏗️ Tech Stack

| Layer | Technology |
|-------|-----------|
| **Frontend** | HTML5, CSS3, JavaScript (ES6+), Bootstrap 5 |
| **Backend** | PHP 7.4+ |
| **Database** | MySQL / MariaDB |
| **Encryption** | Web Crypto API (RSA-OAEP, AES-GCM) |
| **Server** | Apache (XAMPP) |
| **Icons** | Bootstrap Icons |

---

## 📁 Project Structure

```
Sentinel (FYP)/
├── index.php                 # Main chat interface
├── login.php                 # Login page with brute-force protection
├── register.php              # User registration
├── secure_gate.php           # Security key generation & upload
├── admin_dashboard.php       # HOS compliance & user management
├── crypto.js                 # Client-side encryption engine (RSA + AES)
├── send_message.php          # Message sending & audit log storage
├── load_messages.php         # Message retrieval API
├── message_actions.php       # Pin, bookmark, star actions
├── delete_message.php        # Delete for me / everyone
├── contact_controller.php    # Add/remove/block contacts
├── contacts_ui.php           # Contact list UI component
├── profile_ui.php            # User profile viewer
├── settings_ui.php           # Settings & key export
├── saved_messages.php        # Bookmarked messages panel
├── forgot_password.php       # Password recovery
├── burn_on_read.php          # Self-destruct message handler
├── db_connect.php            # Database connection
├── .htaccess                 # PHP upload limits (1GB support)
├── uploads/                  # User-uploaded attachments
│   └── attachments/          # Photos, videos, documents
└── Sentinel logo.png         # Application logo
```

---

## 🚀 Installation & Setup

### Prerequisites
- [XAMPP](https://www.apachefriends.org/) (Apache + MySQL + PHP 7.4+)
- Modern web browser (Chrome, Edge, or Firefox)

### Steps

**1. Clone the repository**
```bash
git clone https://github.com/Xavier1105/FYP-Project-Encrypted-Chat-System-with-self-destruct.git
```

**2. Move to XAMPP directory**
```bash
cp -r FYP-Project-Encrypted-Chat-System-with-self-destruct "C:/xampp/htdocs/Sentinel (FYP)"
```

**3. Start XAMPP**
- Open XAMPP Control Panel
- Start **Apache** and **MySQL**

**4. Import the database**
- Open `http://localhost/phpmyadmin`
- Create a new database (e.g., `sentinel_db`)
- Import the SQL file into the database

**5. Configure database connection**
- Edit `db_connect.php` with your database credentials:
```php
$conn = new mysqli("localhost", "root", "", "sentinel_db");
```

**6. Access the system**
```
http://localhost/Sentinel (FYP)/login.php
```

---

## 🔒 Security Architecture

```
┌──────────────────────────────────────────────────────────────────┐
│                        USER'S BROWSER                            │
│                                                                  │
│  ┌──────────────┐    ┌──────────────┐    ┌──────────────────┐   │
│  │  Plaintext    │───▶│  Web Crypto  │───▶│  Ciphertext      │   │
│  │  Message      │    │  API (RSA +  │    │  (Encrypted)     │   │
│  │              │    │  AES-GCM)    │    │                  │   │
│  └──────────────┘    └──────────────┘    └───────┬──────────┘   │
│                                                   │              │
└───────────────────────────────────────────────────┼──────────────┘
                                                    │
                                          ┌─────────▼──────────┐
                                          │   SERVER (PHP)      │
                                          │                     │
                                          │  Stores ONLY        │
                                          │  ciphertext.        │
                                          │  Cannot decrypt.    │
                                          │                     │
                                          │  ┌───────────────┐  │
                                          │  │  MySQL DB      │  │
                                          │  │  (Encrypted    │  │
                                          │  │   data only)   │  │
                                          │  └───────────────┘  │
                                          └─────────────────────┘
```

### Encryption Flow
1. **Sender** encrypts message with **Receiver's public key** (RSA-OAEP)
2. **Sender** encrypts a copy with **their own public key** (for sent messages view)
3. **Sender** encrypts a copy with **HOS's master public key** (for audit vault)
4. Server receives and stores **3 encrypted blobs** — zero plaintext

### Security Key (.json) Flow
1. **First login** → Keys auto-generated → `.json` file auto-downloaded
2. **Every login after** → User must upload `.json` to prove identity
3. **Logout** → Key wiped from browser memory (localStorage cleared)

---

## 📊 System Specifications

| Specification | Value |
|--------------|-------|
| RSA Key Size | 2048-bit (RSA-OAEP, SHA-256) |
| AES Key Size | 256-bit (AES-GCM, 12-byte IV) |
| Max File Upload | 1 GB |
| Lockout Threshold | 5 failed login attempts |
| Edit Time Window | 1 hour after sending |
| Max Pins Per Chat | 3 pinned messages |
| Self-Destruct Options | Off, Burn on Read, 1hr, 8hr, 5 days |
| Admin Encryption Limit | Files ≤ 15 MB (larger files use direct access) |
| Blocked Extensions | exe, bat, cmd, msi, com, scr, pif, vbs, js, wsf, ps1, sh, cgi, php, htaccess |

---

## 👥 User Roles

| Role | Capabilities |
|------|-------------|
| **User** | Chat, send files, self-destruct, bookmark, block/unblock |
| **Admin / HOS** | All user capabilities + user management, audit vault decryption, panic mode, account lock/unlock |

---

## 📜 License

This project was developed as a **Final Year Project (FYP)** for academic purposes. All rights reserved.

---

## 👨‍💻 Author

**Xavier** — Final Year Project  
🔗 [GitHub Repository](https://github.com/Xavier1105/FYP-Project-Encrypted-Chat-System-with-self-destruct)

---

<p align="center">
  <b>🛡️ Sentinel v1.4</b><br>
  <i>Secure. Private. Compliant.</i>
</p>
