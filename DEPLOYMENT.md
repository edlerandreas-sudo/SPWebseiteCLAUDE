# 🚀 Deployment-Anleitung – Steirer Pellets Website

> **Für Webmaster / Hosting-Anbieter**  
> Diese Anleitung erklärt, wie die Website auf einem externen Server bereitgestellt wird.

---

## 📋 Voraussetzungen

| Anforderung | Details |
|-------------|---------|
| **Webserver** | Apache 2.4+ oder Nginx (mit PHP optional) |
| **PHP** | Version 7.4+ (nur für E-Mail-Versand via `mail.php`) |
| **SSL-Zertifikat** | Pflicht (HTTPS) – kostenlos via Let's Encrypt |
| **FTP-Zugang** | Zum Hochladen der Dateien |

---

## 📁 Vollständige Dateistruktur

```
/ (Webroot – z.B. public_html/ oder www/)
│
├── index.html                  ← Startseite
├── mail.php                    ← E-Mail-Handler (PHP nötig!)
│
├── css/
│   ├── style.css               ← Haupt-Stylesheet
│   ├── blog.css                ← Magazin-Styles
│   └── region.css              ← Regionsseiten-Styles
│
├── js/
│   ├── main.js                 ← Haupt-JavaScript
│   ├── blog.js                 ← Magazin-JavaScript
│   ├── plz-data.js             ← Postleitzahlen-Daten
│   ├── region-data.js          ← Regionsdaten
│   └── region.js               ← Regionsseiten-JavaScript
│
├── images/
│   ├── logo.png                ← Firmenlogo
│   ├── pellets-hero.jpg        ← Hero-Hintergrundbild
│   ├── lkw-freigestellt.png    ← LKW-Foto (freigestellt)
│   ├── lkw-original.jpg        ← LKW-Originalfoto
│   └── blog-fahrer-befüllung.jpg ← Blog-Titelbild
│
├── blog/
│   ├── index.html              ← Magazin-Übersicht
│   └── artikel.html            ← Artikel-Detailseite
│
├── admin/
│   └── index.html              ← Admin-Bereich (Passwortschutz!)
│
└── region/
    └── [regionsseiten]         ← Regionale Unterseiten
```

---

## 🔧 Schritt-für-Schritt Deployment

### Schritt 1: Dateien von Genspark exportieren

1. In Genspark auf den **„Publish"-Tab** klicken
2. Option „Als ZIP herunterladen" wählen (falls verfügbar)
3. ZIP entpacken

### Schritt 2: Per FTP auf den Server hochladen

**Empfohlenes FTP-Programm:** [FileZilla](https://filezilla-project.org/) (kostenlos)

```
FTP-Verbindung:
  Host:     ihre-domain.at (oder IP-Adresse)
  Benutzer: [vom Hosting-Anbieter]
  Passwort: [vom Hosting-Anbieter]
  Port:     21 (FTP) oder 22 (SFTP – sicherer!)
```

**Alle Dateien** in den Webroot hochladen (meist `public_html/`, `www/` oder `htdocs/`)

### Schritt 3: API-Endpunkt konfigurieren ⚠️ WICHTIG

Die Website nutzt eine **REST-API für dynamische Daten** (Preise, Blog-Artikel):

```
Genspark-API-URL: https://www.genspark.ai/api/...
```

> **Problem:** Die API läuft auf Genspark-Servern. Wenn Sie extern hosten, müssen die API-Aufrufe in den JavaScript-Dateien auf Ihre eigene API oder weiterhin auf die Genspark-API zeigen.

**Option A – Genspark-API weiternutzen (empfohlen):**
- Keine Änderung nötig, solange das Genspark-Projekt aktiv bleibt
- Preise und Blog-Artikel werden weiterhin über Genspark verwaltet

**Option B – Eigene Datenbank:**
- Erfordert Backend-Entwicklung (PHP/MySQL oder Node.js)
- Alle `fetch('tables/...')` Aufrufe in `js/main.js` und `js/blog.js` anpassen

### Schritt 4: E-Mail-Versand konfigurieren

Die Datei `mail.php` verarbeitet Bestellformular-Anfragen:

```php
// In mail.php anpassen:
$empfaenger = 'office@steirerpellets.at';  // ← Ihre E-Mail
```

**PHP-Mailer aktivieren** (empfohlen für Zuverlässigkeit):
```bash
composer require phpmailer/phpmailer
```

Oder: **Formspree.io** als Alternative (kein PHP nötig):
1. Account auf [formspree.io](https://formspree.io) erstellen
2. Formular-ID in `js/main.js` eintragen

### Schritt 5: Admin-Bereich absichern ⚠️ SICHERHEIT

Der Admin-Bereich läuft jetzt über `admin/index.php` mit serverseitiger Session-Anmeldung.

Vor dem Go-Live müssen Zugangsdaten als Server-Variablen gesetzt werden:

```bash
SP_ADMIN_USER=admin
SP_ADMIN_PASS_HASH='$2y$10$...'
```

Alternativ ist auch `SP_ADMIN_PASS` möglich, empfohlen ist aber ein Hash in `SP_ADMIN_PASS_HASH`.

Beispiel zum Erzeugen eines Passwort-Hashs auf einem Rechner mit PHP:

```bash
php -r "echo password_hash('IHR_SICHERES_PASSWORT', PASSWORD_DEFAULT), PHP_EOL;"
```

Zusätzlich bleibt eine echte Server-Absicherung sinnvoll:

**Option A: .htaccess HTTP-Auth (Apache)**
```apache
# /admin/.htaccess
AuthType Basic
AuthName "Admin-Bereich"
AuthUserFile /pfad/zur/.htpasswd
Require valid-user
```

```bash
# .htpasswd erstellen:
htpasswd -c /pfad/zur/.htpasswd admin
```

**Option B: IP-Beschränkung**
```apache
# /admin/.htaccess
Order Deny,Allow
Deny from all
Allow from 123.456.789.0  # ← Ihre Büro-IP
```

### Schritt 6: SSL / HTTPS einrichten

```apache
# In .htaccess (Webroot):
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

### Schritt 7: Domain konfigurieren

DNS-Einträge beim Domain-Anbieter:
```
Typ    Name    Wert
A      @       [Server-IP]
A      www     [Server-IP]
CNAME  www     ihre-domain.at.
```

---

## 🔄 Updates einspielen (nach Änderungen in Genspark)

1. Änderung in Genspark beauftragen und umsetzen lassen
2. Geänderte Dateien identifizieren (Genspark zeigt welche Dateien geändert wurden)
3. **Nur die geänderten Dateien** per FTP hochladen und überschreiben
4. Browser-Cache leeren und testen

**Typisch geänderte Dateien:**
- `css/style.css` – bei Layout/Design-Änderungen
- `index.html` – bei Inhaltsänderungen auf der Startseite
- `js/main.js` – bei Funktionsänderungen

---

## ✅ Checkliste vor Go-Live

- [ ] Alle Dateien hochgeladen
- [ ] SSL-Zertifikat aktiv (HTTPS grünes Schloss)
- [ ] Startseite lädt korrekt
- [ ] Bestellformular funktioniert (Test-Bestellung senden)
- [ ] E-Mail-Empfang geprüft
- [ ] Admin-Zugangsdaten als `SP_ADMIN_USER` + `SP_ADMIN_PASS_HASH` oder `SP_ADMIN_PASS` gesetzt
- [ ] Admin-Bereich erreichbar unter `/admin/`
- [ ] Magazin-Seite lädt (`/blog/`)
- [ ] Auf Mobilgerät getestet
- [ ] Google Search Console eingerichtet
- [ ] Cookie-Banner erscheint beim ersten Besuch

---

## 📞 Support

Bei technischen Fragen zur Website-Entwicklung:
- Änderungen am Code → über **Genspark** beauftragen
- Hosting/Server-Fragen → an Ihren Hosting-Anbieter wenden
- Preise & Artikel → selbst über **Admin-Bereich** (`/admin/`) verwalten

---

*Erstellt mit Genspark AI · Steirer Pellets GmbH · 8580 Köflach*
