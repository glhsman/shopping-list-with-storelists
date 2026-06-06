# Projekt-Status & Handover-Notizen

**Stand:** 2026-06-05
**Aktuelle Version (live):** v1.5.10
**Sprache:** Deutsch (Antworten + UI-Texte lt. Projekt-Skill)
**Plattform:** Windows / PowerShell 5.1

---

## 1. In dieser Session umgesetzt

### v1.5.8 — Quick-Wins (UI-Polish & Accessibility)
- Kontrast `--text-muted` von `#94a3b8` auf `#cbd5e1` (besserer WCAG-AA-Kontrast auf dunklem Grund)
- Touch-Targets vergrößert: Checkbox 28×28 → 36×36 px, Delete-Button 32×32 → 44×44 px
- iOS Safe-Area (`env(safe-area-inset-*)`) für Header, Bottom-Nav und Action-Bar
- `:focus-visible` Fokusring (Cyan, 2 px Outline + 4 px Glow) auf allen interaktiven Elementen
- `prefers-reduced-motion: reduce` Media Query deaktiviert Animationen
- Checked-Item zeigt zusätzlich `line-through` (nicht nur Opacity)
- `.nav-item` Mindestbreite 64 px, `min-height: 44px` global auf Buttons

### v1.5.9 — Katalog blendet aktive Artikel aus
- Items, die auf der Einkaufsliste stehen, werden im Katalog nicht mehr angezeigt
- Rote Minus-Markierung und Icon-Wechsel (`fa-minus-circle`) entfernt
- Empty-State, wenn alle Items auf der Liste: Glas-Karte „Alle Katalogartikel sind auf der Liste. 📋"
- Katalog-Tap ruft `toggleInCatalog(id, false)` — Status-Variable entfällt
- `.active-list` CSS-Klasse bleibt im Stylesheet (ungenutzt, kann später entfernt werden)

### v1.5.10 — Action-Button verkleinert, rechts angedockt
- `.action-bar`: `left: auto; right: 1.5rem; width: 50%;` (halbe Breite, rechts)
- `.action-btn`: Padding 1.25rem → `0.7rem 1rem`, Font 1rem → 0.78rem, Icon 1.3rem → 1.05rem
- Letter-Spacing 0.1rem → 0.05rem
- `white-space: nowrap` verhindert Umbruch von „Neuer Artikel"
- `min-height: 44px` aus v1.5.8 sorgt weiterhin für Touch-Target-Konformität

---

## 2. Backups vorhanden

Jede `.bak`-Datei enthält den Stand **vor** der jeweiligen letzten Änderung (Stand v1.5.9 / v1.5.10):

| Datei | Größe | Inhalt |
|---|---|---|
| `index.php.bak` | 18011 B | Stand vor v1.5.10 |
| `assets/css/style.css.bak` | 17199 B | Stand vor v1.5.10 |
| `assets/js/app.js.bak` | 23943 B | Stand vor v1.5.9 |

**Revert-Beispiel:** `Copy-Item index.php.bak index.php -Force`

---

## 3. Geplant, aber NICHT umgesetzt

### v1.5.11 — Store-Gate UX (frontend-only, klein)

Kann **isoliert vor** v1.6.0 umgesetzt werden.

**Anforderung:** Vor „Artikel zum Warenkorb hinzufügen" muss zwingend eine Filiale ausgewählt sein. Existiert keine Filiale, muss der User zum Anlegen einer aufgefordert werden.

**Realisierung:**
- Modal `#storeGateModal` (Bottom-Sheet, analog `#addModal`)
- Helper `ensureStoreForAction()` öffnet Modal, liefert Promise
- Guard in `toggleInCatalog` (`app.js:404`) und `submitQuickAdd` (`app.js:122`)
- Auto-Trigger nach Login, wenn `stores.length === 0` (`app.js:checkAccess`)
- Optional: Empty-State-Hinweis im Katalog „Bitte Filiale auswählen"

**Aufwand:** ~1 h, Risiko: gering (kein Schema, kein Backend).

### v1.6.0 — Items pro (Gruppe, Filiale) [DSGVO-Migration]

**Auslöser:** Datenschutz-Befund. Der aktuelle `get_catalog` (`api/index.php:239`) hat **keinen `group_id`-Filter** — bei mehreren Gruppen sieht jede Familie den Katalog der anderen. Verletzt Art. 5 + 25 DSGVO (Datenminimierung, Privacy by Design).

**Design-Festlegung (mit User bestätigt):**
- `items.group_id` → `NOT NULL`
- `items.store_id` → `NOT NULL` (kein NULL = „global" erlaubt)
- `UNIQUE (group_id, store_id, name)` gegen Doppelanlage
- Items pro (Gruppe, Filiale) eineindeutig
- Konsequenz: „Bio-Milch" in Aldi und „Bio-Milch" in Lidl sind zwei verschiedene Einträge

**Aufwandsschätzung v1.6.0 (gesamt: ~4–6 h, Schwierigkeit: mittel):**

| Schritt | Datei | Zeit |
|---|---|---|
| Schema-Änderung + neues `database.sql` | `database.sql` | 15 min |
| Migrations-SQL + Verifizierung per PHP-CLI | `migrations/v1.6.0_items_per_store.sql` + `verify_db.php` | 30 min |
| Backend: `get_catalog` um Filter erweitern | `api/index.php:233` | 20 min |
| Backend: `add_item` um `group_id`+`store_id` erweitern | `api/index.php:249` | 15 min |
| Frontend: `submitQuickAdd` sendet jetzt `group_id`+`store_id` | `app.js:122` | 15 min |
| Frontend: `refreshData` ruft `get_catalog` mit Filiale auf | `app.js:302` | 15 min |
| Frontend: Store-Gate-Modal (siehe v1.5.11) | `index.php` + `app.js` | 60 min |
| Edge-Cases & Tests (PHP-CLI, Browser) | – | 60 min |
| README + Versions-Bump v1.5.10 → v1.6.0 | – | 10 min |

---

## 4. Offene Fragen an den User (blockierend für v1.6.0)

> **Diese drei Antworten werden vor jeder Schema-Änderung benötigt.**

### Frage 1 — Wie viele Gruppen existieren in der DB?
```sql
SELECT COUNT(*) FROM groups;
```
- **Bei 1 Gruppe:** Migration trivial — alle Bestands-Items bekommen deren `group_id`.
- **Bei > 1 Gruppen:** Bestand kann **nicht eindeutig** zugeordnet werden. Strategie klären (siehe Frage 2).

### Frage 2 — Was passiert mit den bestehenden Items?
```sql
SELECT COUNT(*) FROM items;
```
- **Verwerfen:** Saubere Trennung. Bestehende Artikel gehen verloren, müssen neu angelegt werden. Einfachste Variante.
- **Erhalten + erster Gruppe zuordnen:** Andere Gruppen verlieren Katalog. Nur sinnvoll, wenn nur eine Gruppe aktiv ist.
- **Export → Reset → Reimport:** CSV-Export der Bestandsdaten, Schema-Migration, manueller Reimport pro Gruppe. Aufwändig und fehleranfällig.

### Frage 3 — `last_known_price` Migration
Mit strikter Isolation ist `last_known_price` automatisch pro (Gruppe, Filiale) — keine Schema-Änderung am Preis nötig. **Aber:** Bestehende Preise haben keinen Filialbezug. Beim Anlegen der ersten Filiale einmalig nach Zuordnung fragen, oder Preise verwerfen.

---

## 5. Zurückgestellte UI-Punkte (aus erster Analyse, niedrige Priorität)

- **Swipe-to-delete / Bestätigungsdialog in Shopping-List-Zeile:** Schutz vor versehentlichem „Erledigt"-Tippen bei Realtime-Sync (Daten gehen sofort bei allen Mitbenutzern weg).
- **Sync-Status mit drei Zuständen:** Aktuell nur 2 Icon-Varianten; `setSyncStatus` (`app.js:628`) sollte `synced / syncing / error` klarer trennen.
- **Push-Benachrichtigungen** bei Änderungen durch andere Mitbenutzer.
- **Logout-Flow mit Bestätigung:** Aktuell `localStorage.clear()` + Reload (`index.php:121`) — harter Reset ohne Rückfrage.
- **Filter-Chips im Katalog:** Erst sinnvoll, wenn Kategorie-Erfassung im Add-Modal ergänzt wird (vom User explizit zurückgestellt).
- **Menge-Eingabefeld im Add-Modal** (`index.php:160-179`): aktuell nur Einheit, keine Menge.

---

## 6. Empfohlene Reihenfolge beim Wiederaufnehmen

1. **Optional:** v1.5.11 (Store-Gate) zuerst umsetzen → kleines Risiko, sofortiger UX-Gewinn, isoliert testbar.
2. **Fragen 1–3 oben beantworten** (SQL-Counts laufen lassen).
3. **Backup** von `database.sql`, `api/index.php`, ggf. DB-Dump.
4. **Schema-Migration** (Migrations-SQL mit `IF NOT EXISTS` / `ADD COLUMN IF NOT EXISTS` — MariaDB-Syntax prüfen).
5. **Verifizierung per PHP-CLI** (lt. Projekt-Skill Pflicht bei Logik-Änderungen).
6. **Backend** anpassen, mit `verify_db.php` oder Test-Skript prüfen.
7. **Frontend** (Store-Gate + `submitQuickAdd` + `refreshData`).
8. **Browser-Test:** Multi-Gruppen-Szenario (falls Frage 1 > 1), Single-Gruppe, „keine Filiale"-Flow, „Filiale wechseln"-Flow.
9. **README** aktualisieren mit Breaking-Change-Hinweis (Schema-Änderung = Minor-Bump).
10. **Versionierung:** v1.5.10 → v1.6.0 in `index.php` (4 Stellen: Cache-Buster, sichtbarer Tag, Settings-Footer, `CACHE_NAME`) + `app.js` (Header-Kommentar).

---

## 7. Aktuelle Datei-Lage

| Datei | Größe | Zustand |
|---|---|---|
| `index.php` | 18006 B | live, v1.5.10 |
| `assets/css/style.css` | 17226 B | live, v1.5.10 |
| `assets/js/app.js` | 23943 B | live, v1.5.9 (kein JS-Change in v1.5.10) |
| `index.php.bak` | 18011 B | Stand vor v1.5.10 |
| `style.css.bak` | 17199 B | Stand vor v1.5.10 |
| `app.js.bak` | 23943 B | Stand vor v1.5.9 |
| `database.sql` | unverändert | ausstehend: v1.6.0-Migration |
| `PROJEKT_STATUS.md` | diese Datei | neu, Handover-Notizen |

**Versions-Stempel aktuell in `index.php`:**
- Zeile 21: `style.css?v=1.5.10`
- Zeile 25: sichtbarer Tag `v1.5.10`
- Zeile 122: Settings-Footer „Version 1.5.10 (Action-Button verkleinert)"
- Zeile 280: `CACHE_NAME = 'einkaufs-v1.5.10'`
- Zeile 264: `app.js?v=1.5.9` (nicht in v1.5.10 geändert, da kein JS-Change)
- `assets/js/app.js:2`: Header-Kommentar `Version 1.5.9`

---

## 8. Wichtige Code-Anker (zum schnellen Wiederfinden)

| Was | Datei:Zeile |
|---|---|
| `renderCatalog` (Filter-Logik für aktive Items) | `assets/js/app.js:577` |
| `renderShoppingList` (Items oben, keine Löschen-Button) | `assets/js/app.js:548` |
| `toggleInCatalog` (Katalog → Einkaufsliste) | `assets/js/app.js:404` |
| `buyItem` (Häkchen → Liste verlassen) | `assets/js/app.js:425` |
| `submitQuickAdd` (Add-Modal Submit) | `assets/js/app.js:122` |
| `get_catalog` API (Backend) | `api/index.php:233` |
| `add_item` API (Backend) | `api/index.php:249` |
| `get_shopping_list` API (Backend, hat `store_id`-Filter) | `api/index.php:189` |
| `toggle_in_catalog` API (Backend) | `api/index.php:306` |
| Action-Button CSS (verkleinert, halbe Breite) | `assets/css/style.css:351-395` |
| Quick-Win CSS-Block (Safe-Area, Touch-Targets, Focus, Reduced-Motion) | `assets/css/style.css:738-789` |
| DSGVO-Checkliste | `security_datenschutz-check.md` |
| Projekt-Skill (Antwort-Sprache, .bak-Konvention, Verifizierungs-Pflicht) | `.agents/skills/general/SKILL.md` |
