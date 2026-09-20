# Mirage Projekt

Produktionsnahes, klassisches CMS auf Basis von **PHP 8.2+**, **MySQL/MariaDB**, **Bootstrap 5**, **Vanilla JavaScript**.

Kein Framework, kein Build-Schritt, kein Composer/NPM erforderlich.

## 1) Voraussetzungen

- PHP 8.2+
- MySQL 8+ oder MariaDB 10.6+
- Apache (oder kompatibler Webserver)
- HTTPS für produktiven Betrieb

## 2) PHP-Version

Empfohlen:

- `display_errors=Off` in Produktion
- `log_errors=On`
- aktivierte Extensions: `pdo`, `pdo_mysql`, `openssl`, `mbstring`

## 3) MySQL/MariaDB Einrichtung

1. Datenbank und User anlegen.
2. Rechte auf die Datenbank vergeben.

## 4) Datenbank importieren

```bash
mysql -u <user> -p <db_name> < database/schema.sql
```

Zusätzlich für das Ticket-System:

```bash
mysql -u <user> -p <db_name> < database/ticket_system.sql
```

Hinweis: `database/schema.sql` muss vorher importiert sein, da `ticket_system.sql` auf `users` und `admins` referenziert.

## 5) Konfiguration

1. `.env.example` nach `.env` kopieren.
2. Werte setzen:

- `DB_HOST`
- `DB_PORT`
- `DB_NAME`
- `DB_USER`
- `DB_PASSWORD`
- `APP_URL`
- `TELEGRAM_BOT_TOKEN`
- `TELEGRAM_BOT_USERNAME`
- `ENCRYPTION_KEY`
- `SESSION_COOKIE_SECURE`
- `SESSION_SAME_SITE`
- Rate-Limit Variablen (`ADMIN_LOGIN_*`)

**Wichtig:** `ENCRYPTION_KEY` und Secrets niemals committen.

## 6) Telegram Bot Einrichtung

1. Bot via `@BotFather` erstellen.
2. Bot Token in `TELEGRAM_BOT_TOKEN` hinterlegen.
3. Bot Username ohne `@` in `TELEGRAM_BOT_USERNAME` setzen.
4. Im Telegram Login Widget wird auf `/login/telegram` zurückgeleitet.
5. Die Signatur wird serverseitig nach Telegram-Spezifikation geprüft (`hash` + `auth_date`).

## 7) Webserver Einrichtung

- Dokumentenroot auf das Projektverzeichnis setzen.
- `.htaccess` aktivieren (`AllowOverride All`).
- Schöne URLs sind per Rewrite hinterlegt.

Wichtige Routen:

- `/login/telegram`
- `/dashboard`
- `/dashboard/vouchers`
- `/dashboard/tickets`
- `/logout`
- `/payment/voucher`
- `/admin/login`
- `/admin/register`
- `/admin/dashboard`
- `/admin/vouchers`
- `/admin/tickets`
- `/admin/users`
- `/admin/logs`
- `/admin/admins`
- `/admin/edit`

## 8) HTTPS

In Produktion zwingend HTTPS nutzen.

- Session-Cookies `HttpOnly`
- `SameSite=Lax` (konfigurierbar)
- `Secure` bei HTTPS

## 9) Dateiberechtigungen

- Schreibrechte nur dort vergeben, wo notwendig.
- Keine Schreibrechte auf Quellcodeverzeichnisse für den Webserver-User.
- `.env` nicht öffentlich ausliefern.

## 10) Ersten Head-Admin erstellen

Per CLI-Script:

```bash
php scripts/create_head_admin.php
```

Erstellt einen Admin mit:

- `rank = 3`
- `activate = 1`

## 11) Admin-Freischaltung

Neu registrierte Admins erhalten:

- `activate = 0`
- `rank = 1`

Nur Rank-3 (Head-Admin) kann in `/admin/admins` aktivieren/deaktivieren und Rollen ändern.

## 12) Sicherheitsmaßnahmen

- Telegram Login serverseitig verifiziert
- PDO + Prepared Statements
- CSRF-Schutz für alle kritischen POST-Aktionen
- XSS-Schutz via `htmlspecialchars`
- Session-Hardening (`session_regenerate_id(true)`, sicheres Logout)
- Admin Login Rate Limiting (IP/Username)
- Audit Logging (`admin_logs`, `admin_login_logs`)
- Voucher-Codes nur als Hash + verschlüsselt gespeichert
- Kein Klartext in URLs/GET/Logs

## 13) Backup

Empfehlung:

- Tägliche DB-Backups
- Regelmäßige Restore-Tests
- Sichere Schlüsselverwaltung für `ENCRYPTION_KEY`

## 14) Troubleshooting

- **Telegram Login schlägt fehl:** Token/Username/APP_URL prüfen; Zeit-Sync des Servers prüfen.
- **DB-Verbindung fehlgeschlagen:** `.env` DB-Werte prüfen.
- **403 bei Formularen:** CSRF Token und Session prüfen.
- **Admin kann sich nicht einloggen:** `activate=1` erforderlich.

## Datenschutz-Hinweis

Das System speichert für Sicherheits- und Audit-Zwecke u. a. IP-Adressen und User-Agent-Daten.
Betreiber müssen je nach Land/Branche Datenschutz- und Aufbewahrungspflichten selbst prüfen und umsetzen.

## Projektstruktur

```text
/
├── index.php
├── .htaccess
├── .env.example
├── config/
├── includes/
├── login/
├── dashboard/
├── payment/
├── admin/
├── assets/
├── database/
├── scripts/
└── errors/
```