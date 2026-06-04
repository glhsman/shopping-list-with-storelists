# 🛒 Gemeinsame Einkaufs-App (PWA)

Eine moderne, collaborative Einkaufsliste, optimiert als Progressive Web App (PWA) für die mobile Nutzung. Teile deine Einkaufsliste mit der ganzen Familie in Echtzeit.

<p align="center">
  <img src="preview.png" alt="App Vorschau" width="300" style="border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.5);">
</p>

## ✨ Features
- 🚀 **Realtime Sync**: Sofortige Aktualisierung auf allen Geräten dank Supabase.
- 📱 **Mobile Optimized**: Premium-Design mit Glassmorphism-Effekten, optimiert für iPhone/Android (375px+).
- 🏷️ **Store Management**: Organisiere deine Liste nach verschiedenen Filialen (z. B. Aldi, Lidl, Baumarkt).
- 🔐 **Group Access**: Sicherer Zugang über einen einfachen 6-stelligen Gruppen-PIN.
- 💰 **Budget Tracking**: Automatische Schätzung der Gesamtsumme basierend auf hinterlegten Preisen.
- 📦 **PWA Ready**: Kann direkt auf dem Home-Bildschirm installiert werden und funktioniert offline.

## 🛠 Tech Stack
-   **Frontend**: HTML5, Vanilla JavaScript (ES Module), CSS3 (Custom Properties).
-   **Backend**: [Supabase](https://supabase.com/) (Database & Realtime Channels).
-   **Icons**: FontAwesome 6.
-   **Typo**: Outfit (Google Fonts).

## 🚀 Schnellstart
1.  **Repository klonen**:
    ```bash
    git clone https://github.com/glhsman/einkaufsliste.git
    cd einkaufsliste
    ```
2.  **Supabase Konfiguration**:
    Erstelle eine `supabase_config.js` im Hauptverzeichnis (basierend auf der Vorlage) und trage deine `SUPABASE_URL` und `SUPABASE_ANON_KEY` ein.
3.  **Projekt starten**:
    Öffne das Projekt in einem Webserver (z. B. über VS Code Live Server oder PHP-Server).

## 📂 Struktur
-   `index.php`: Hauptanwendung (Struktur & PWA-Logik).
-   `assets/css/style.css`: Modernes UI-Design System (Neon-Glassmorphism).
-   `assets/js/app.js`: Kernlogik, Supabase-Integration & Summen-Berechnung.
-   `sw.js`: Service Worker für Offline-Funktionalität & Auto-Updates.
-   `manifest.webmanifest`: PWA-Konfiguration.

## 📝 Letzte Änderungen
### v1.4.2
- ✨ **Alphabetische Sortierung**: Die Einkaufsliste bleibt nun stabil sortiert (kein Springen nach Preisänderungen).
- 🛠️ **BIGINT Fix**: Robuste ID-Handhabung als Strings zur Vermeidung von Präzisionsfehlern.

### v1.4.1
- 💰 **Preiseingabe-Robustheit**: Automatisches Ersetzen von Kommas durch Punkte und Filterung ungültiger Zeichen.
- 🎨 **UI-Feinschliff**: Anpassung der Schriftstärke für eine dezentere Preis-Darstellung.

### v1.4.0
- 🚀 **PWA Auto-Update**: Banner-Benachrichtigung bei neuen Versionen ("Update verfügbar").
- 🏷️ **Versioning**: Sichtbare Versionsnummer oben links im Frontend.

## 📄 Lizenz
Dieses Projekt ist unter der MIT Lizenz lizenziert. Weitere Details findest du in der [LICENSE](LICENSE) Datei.
