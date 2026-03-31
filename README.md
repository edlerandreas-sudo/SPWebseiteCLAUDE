# Steirer Pellets – Homepage v4

Moderne One-Page-Website + Blog/Magazin für den steirischen Pellets-Hersteller.

---

## ✅ Implementierte Features

### Startseite (`index.html`)
- **Bestellformular ganz oben** (3-Schritt-Prozess prominent vor allen Inhalten)
  - Schritt 1: Mengen-Schieberegler (3–50 t) + Kalenderwoche-Picker
  - Schritt 2: Lieferadresse (PLZ mit Dropdown + PLZ-Routing, Ort, Straße, Tankgröße, Zufahrtshinweise)
  - Schritt 3: Kundendaten + Bestellübersicht mit Live-Gesamtpreis
  - Erfolgs-Screen mit Bestellzusammenfassung
- **Logo** dominiert: Prominent im Hero-Bereich und Navbar
- **Nur lose Pellets** – kein Sackware-Verweis mehr
- **Preisliste** (eigener Bereich `#preise`):
  - 398 €/t ab 4 Tonnen
  - 418 €/t unter 4 Tonnen
  - 58 € Abschlauchgebühr (einmalig)
  - Schnellrechner mit Schieberegler
- **Live-Preisberechnung** im Formular (synchron mit Schieberegler)
- **Standardwert 5 Tonnen** (= Jahresbedarf Einfamilienhaus)
- **Kalenderwoche-Picker**: Navigation vor/zurück, zeigt KW-Nummer + Datum-Range + Hinweis-Text
- **PLZ-Autocomplete** mit Dropdown, Direkterkennung bei 4 Stellen, Regionseiten-Link
- Navigation: Bestellen | Preise | Produkt | Vorteile | Nachhaltigkeit | Lieferung | **Magazin** | Kontakt
- Produkt-Sektion (Blasbefüllung, Tankwagen, ENplus A1-Badges)
- Vorteile-Grid (6 USPs)
- Nachhaltigkeits-Visualisierung (CO₂-Kreislauf)
- Lieferprozess (4 Schritte)
- Kundenstimmen
- Kontaktbereich
- Footer mit Navigation, Kontakt, Social

### Blog / Magazin (`blog/`)
- **Übersichtsseite** (`blog/index.html`):
  - Hero mit Suchfunktion
  - Kategorie-Filter (Ratgeber, Tipps & Tricks, Nachhaltigkeit, Aktuell, Produkt)
  - Featured Article (hervorgehobener Artikel groß dargestellt)
  - Artikel-Karten-Grid (2 Spalten)
  - „Weitere Artikel laden" Button
  - Sidebar: Bestell-CTA, Newsletter-Anmeldung, Tag-Cloud, Fakten
- **Artikel-Detailseite** (`blog/artikel.html?slug=...`):
  - Dynamisch geladen per URL-Parameter `?slug=...`
  - Inhaltsverzeichnis (TOC) aus H2-Überschriften
  - Share-Buttons (WhatsApp, Facebook, Link kopieren)
  - Verwandte Artikel
  - Sidebar-CTA
- **6 Start-Artikel** in der Datenbank

### Regionale Landingpages (`region/`)
- Dynamisch generierte Seiten für verschiedene PLZ-Bereiche der Steiermark

---

## 📁 Dateistruktur

```
index.html              Startseite (Bestellformular + alle Sektionen)
blog/
  index.html            Magazin-Übersicht
  artikel.html          Artikel-Detailseite (dynamisch per ?slug=)
region/
  graz.html             Beispiel Regionalseite
  [weitere].html        Weitere Regionen
css/
  style.css             Haupt-Stylesheet (Startseite + allgemein)
  blog.css              Blog/Magazin-Stylesheet
  region.css            Regionale Landingpage-Stylesheet
js/
  main.js               Hauptlogik (Formular, Slider, PLZ, KW-Picker)
  blog.js               Blog-Logik (Artikel laden, rendern, filtern)
  plz-data.js           PLZ-Datenbank für Steiermark
  region-data.js        Regionale Slug-Daten
images/
  logo.png              Steirer Pellets Logo
```

---

## 🔗 Seiten-URLs

| Seite | URL |
|-------|-----|
| Startseite | `index.html` |
| Magazin | `blog/index.html` |
| Artikel | `blog/artikel.html?slug=pellets-richtig-lagern` |
| Graz Region | `region/graz.html` |

---

## 🗄️ Datenmodelle (REST-API: `tables/`)

### `bestellungen`
| Feld | Typ | Beschreibung |
|------|-----|--------------|
| menge | number | Bestellmenge in Tonnen |
| lieferkw | text | Gewünschte Lieferwoche (z.B. "KW12-2025") |
| plz | text | Postleitzahl |
| ort | text | Ort |
| strasse | text | Straße + Hausnummer |
| tankgroesse | text | Tankgröße (Selektor) |
| zufahrt | text | Zufahrtshinweise |
| vorname | text | Vorname |
| nachname | text | Nachname |
| email | text | E-Mail |
| telefon | text | Telefon |
| gesamtpreis | number | Geschätzter Gesamtpreis |
| sent_at | datetime | Zeitstempel |

### `blog_articles`
| Feld | Typ | Beschreibung |
|------|-----|--------------|
| title | text | Artikel-Titel |
| teaser | text | Kurzbeschreibung |
| category | text | Kategorie |
| author | text | Autor |
| published_at | datetime | Veröffentlichungsdatum |
| reading_time | number | Lesezeit in Minuten |
| slug | text | URL-Slug |
| content | rich_text | Artikel-Inhalt (HTML) |
| featured | bool | Hervorgehobener Artikel |
| tags | array | Tags |

### `newsletter_subscribers`
| Feld | Typ | Beschreibung |
|------|-----|--------------|
| email | text | E-Mail-Adresse |
| subscribed_at | datetime | Anmeldezeitpunkt |

---

## 💰 Preisstruktur

| Kategorie | Preis | Bedingung |
|-----------|-------|-----------|
| Großlieferung | 398 €/t | Ab 4 Tonnen |
| Kleinlieferung | 418 €/t | Unter 4 Tonnen |
| Abschlauchgebühr | 58 € | Einmalig pro Lieferung |
| Mindestbestellung | 2 Tonnen | – |

---

## 📌 Noch nicht implementiert / Nächste Schritte

1. **Echte Fotos** des Unternehmens, Tankwagen, Produktionsanlage einbinden
2. **Impressum & Datenschutz** Unterseiten erstellen
3. **AGB** Seite erstellen
4. **Blog-Admin** zum komfortablen Erstellen neuer Artikel (z.B. ein einfaches Formular)
5. **Google Maps** Einbindung im Kontaktbereich
6. **Preisalert-Funktion** (Newsletter bei Preisänderungen)
7. **Weitere regionale Landingpages** für alle steirischen Regionen
8. **E-Mail-Versand** der Bestellbestätigung (erfordert Backend)

---

## 🚀 Deployment

Zum Veröffentlichen der Website: **Publish-Tab** verwenden.
