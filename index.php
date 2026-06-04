<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>Einkaufs-App</title>
    
    <!-- PWA Settings -->
    <link rel="manifest" href="manifest.webmanifest">
    <meta name="theme-color" content="#020617">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="icon" type="image/png" href="favicon.png">
    <link rel="apple-touch-icon" href="assets/img/icon-192.png">

    <!-- Fonts & Icons -->
    <link rel="stylesheet" href="assets/css/inter.css">
    <link rel="stylesheet" href="assets/css/fontawesome.min.css">

    <!-- Core Styles -->
    <link rel="stylesheet" href="assets/css/style.css?v=1.5.7">
</head>
<body>
    <div id="app">
        <div id="version-display" class="version-tag">v1.5.7</div>
        
        <!-- Update Notification -->
        <div id="update-banner" class="update-banner">
            <div class="update-content">
                <i class="fas fa-rocket"></i>
                <span>Update verfügbar!</span>
            </div>
            <button id="update-btn" class="update-btn">Installieren</button>
        </div>
        <!-- Header -->
        <header class="app-header">
            <div class="header-content">
                <div style="flex: 1;">
                    <h1>Einkauf</h1>
                    <div class="group-info" id="currentGroup">Lade Gruppe...</div>
                </div>
                <!-- Store Selector -->
                <div class="store-selector-wrapper">
                    <select id="activeStore" class="glass-select">
                        <option value="">🛒 Filiale wählen...</option>
                    </select>
                </div>
                <div class="sync-status" id="syncStatus">
                    <i class="fas fa-check-double"></i>
                </div>
            </div>
        </header>

        <!-- Main Content (Consolidated View) -->
        <main id="mainContent">
            <!-- Active Shopping Section -->
            <section id="listView" class="view active">
                <div class="section-title">ZU KAUFEN <span id="listCount" class="badge">0</span></div>
                <div id="shoppingList" class="list-wrapper">
                    <!-- Loaded dynamically -->
                </div>

                <!-- Geschätzte Gesamtsumme -->
                <div id="totalEstimateContainer" class="estimate-box glass">
                    <span class="estimate-label">GESCHÄTZTE SUMME</span>
                    <span id="totalEstimate" class="estimate-value">0,00 €</span>
                </div>

                <div class="section-divider"></div>

                <!-- Catalog Section -->
                <div class="section-title">KATALOG <i class="fas fa-book-open" style="margin-left: 5px; opacity: 0.3;"></i></div>
                <div class="search-bar-container glass">
                    <i class="fas fa-search"></i>
                    <input type="text" id="catalogSearch" placeholder="Artikel suchen...">
                </div>
                <div id="catalogList" class="catalog-grid">
                    <!-- Items populated via JS -->
                </div>
            </section>

            <!-- Settings View -->
            <section id="settingsView" class="view">
                <div class="view-card glass">
                    <h2 style="margin-bottom: 2rem; font-weight: 800;">Einstellungen</h2>

                    <!-- Gruppen-PIN Info -->
                    <div id="groupInfoBox" class="glass" style="margin-bottom: 2.5rem; padding: 1.25rem; border: 1px solid var(--primary); border-radius: 12px; background: rgba(6, 182, 212, 0.05); position: relative; overflow: hidden;">
                        <div style="font-size: 0.75rem; font-weight: 700; color: var(--primary); letter-spacing: 0.1rem; text-transform: uppercase; margin-bottom: 0.8rem;">GRUPPEN-ZUGANG</div>
                        <div style="display: flex; justify-content: space-between; align-items: flex-end;">
                            <div>
                                <span style="display: block; font-size: 0.8rem; color: var(--text-muted); font-weight: 600;">GEMEINSAME PIN</span>
                                <span id="displayGroupPin" style="font-size: 2rem; font-weight: 900; color: var(--text-main); letter-spacing: 0.3rem;">000000</span>
                            </div>
                            <div style="text-align: right;">
                                <span style="display: block; font-size: 0.8rem; color: var(--text-muted); font-weight: 600;">GRUPPEN-ID</span>
                                <span id="displayGroupId" style="font-size: 0.85rem; font-family: monospace; color: var(--primary);">lade...</span>
                            </div>
                        </div>
                        <p style="margin-top: 1rem; font-size: 0.7rem; color: var(--text-muted); border-top: 1px solid rgba(255,255,255,0.05); padding-top: 0.5rem;">Teile diese PIN mit deiner Familie, um gemeinsam einzukaufen.</p>
                        <i class="fas fa-key" style="position: absolute; top: -10px; right: -10px; font-size: 4rem; opacity: 0.05; color: var(--primary); transform: rotate(-15deg);"></i>
                    </div>
                    
                    <!-- Store Management -->
                    <div style="margin-bottom: 2rem;">
                        <label style="display: block; font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.5rem; font-weight: 600;">FILIALEN VERWALTEN</label>
                        <div class="input-group">
                            <input type="text" id="newStoreName" placeholder="Neue Filiale (z.B. Aldi)">
                            <button id="addStoreBtn" class="btn-primary" style="width: auto; padding: 0.8rem 1.25rem; display: flex; align-items: center; gap: 8px;">
                                <i class="fas fa-plus"></i>
                                <span>Filiale hinzufügen</span>
                            </button>
                        </div>
                        <div id="storeList" style="margin-top: 1rem; display: flex; flex-wrap: wrap; gap: 8px;">
                            <!-- Stores listed here -->
                        </div>
                    </div>

                    <!-- App Account Info -->
                    <div style="margin-top: 3rem; border-top: 1px solid var(--glass-border); padding-top: 2rem;">
                        <button onclick="localStorage.clear(); location.reload();" class="btn-secondary" style="width: 100%;">Abmelden / Gruppe wechseln</button>
                        <p style="text-align: center; font-size: 0.7rem; color: var(--text-muted); margin-top: 1rem; opacity: 0.5;">Version 1.5.7 (Auto-Logout & MariaDB)</p>
                    </div>

                    <!-- Legal Links -->
                    <div style="margin-top: 2rem; display: flex; justify-content: center; gap: 20px; font-size: 0.75rem;">
                        <a href="#" id="openPrivacyBtn" style="color: var(--text-muted); text-decoration: none; font-weight: 500;">Datenschutzerklärung</a>
                        <a href="#" id="openImpressumBtn" style="color: var(--text-muted); text-decoration: none; font-weight: 500;">Impressum</a>
                    </div>
                </div>
            </section>
        </main>

        <!-- Action Bar (Add Button) -->
        <div class="action-bar">
            <button class="action-btn glass" id="openAddModal" onclick="console.log('Button clicked'); const m = document.getElementById('addModal'); if(m) { m.classList.add('active'); document.getElementById('quickAddInput')?.focus(); }">
                <i class="fas fa-plus-circle"></i>
                <span>Neuer Artikel</span>
            </button>
        </div>

        <!-- Navigation (Island) -->
        <nav class="bottom-nav glass">
            <button class="nav-item active" data-view="listView">
                <i class="fas fa-cart-shopping"></i>
                <span>Einkauf</span>
            </button>
            <button class="nav-item" data-view="settingsView">
                <i class="fas fa-user-gear"></i>
                <span>Profil</span>
            </button>
        </nav>


        <!-- Modal (New Item) -->
        <div id="addModal" class="modal">
            <div class="modal-content glass">
                <h3 style="margin-bottom: 1.5rem; font-weight: 800; font-size: 1.5rem;">Neuer Artikel</h3>
                
                <label style="display: block; font-size: 0.75rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.5rem;">ARTIKELNAME</label>
                <input type="text" id="quickAddInput" placeholder="Z. B. Bio-Milch">

                <label style="display: block; font-size: 0.75rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.5rem;">INITIALER PREIS (€)</label>
                <input type="text" id="itemPrice" placeholder="0.00" inputmode="decimal">
                
                <label style="display: block; font-size: 0.75rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.5rem;">EINHEIT</label>
                <select id="itemUnit" class="glass-select" style="width: 100%; margin-bottom: 2rem; padding: 1rem;">
                    <option value="Stück">Stück (Stk)</option>
                    <option value="kg">Kilogramm (kg)</option>
                    <option value="g">Gramm (g)</option>
                    <option value="l">Liter (l)</option>
                    <option value="Pkg">Packung (Pkg)</option>
                    <option value="Bd">Bund (Bd)</option>
                </select>

                <div style="display: flex; gap: 1rem;">
                    <button class="btn-secondary" id="closeModal" style="flex: 1;">Abbrechen</button>
                    <button class="btn-primary" id="submitQuickAdd" style="flex: 2;">Speichern</button>
                </div>
            </div>
        </div>

        <!-- Gatekeeper Overlay (PIN) -->
        <div id="gatekeeper" class="modal" style="display: none; align-items: center; justify-content: center; backdrop-filter: blur(45px); z-index: 3000;">
            <div class="view-card glass" style="width: 90%; max-width: 400px; padding: 3rem 2rem;">
                <div id="joinGroupSection">
                    <h2 style="font-weight: 800; margin-bottom: 1rem;">PIN eingeben</h2>
                    <div class="pin-input-container" style="display: flex; gap: 8px; justify-content: center; margin-bottom: 2rem;">
                        <input type="text" class="pin-digit" maxlength="1" pattern="\d*" inputmode="numeric">
                        <input type="text" class="pin-digit" maxlength="1" pattern="\d*" inputmode="numeric">
                        <input type="text" class="pin-digit" maxlength="1" pattern="\d*" inputmode="numeric">
                        <input type="text" class="pin-digit" maxlength="1" pattern="\d*" inputmode="numeric">
                        <input type="text" class="pin-digit" maxlength="1" pattern="\d*" inputmode="numeric">
                        <input type="text" class="pin-digit" maxlength="1" pattern="\d*" inputmode="numeric">
                    </div>
                    <button id="joinGroupBtn" class="btn-primary">Beitreten</button>
                    <p style="text-align: center; font-size: 0.85rem; color: var(--text-muted); margin-top: 2rem;">
                        Noch keine Gruppe? <a href="#" id="showCreateGroup" style="color: var(--primary); font-weight: 600; text-decoration: none;">Neu erstellen</a>
                    </p>
                </div>

                <div id="createGroupSection" style="display: none;">
                    <h2 style="font-weight: 800; margin-bottom: 1rem;">Neue Gruppe</h2>
                    <input type="text" id="newGroupName" placeholder="Name deiner Gruppe (privat)">
                    <input type="number" id="newGroupPin" placeholder="6-stelliger Wunsch-PIN" maxlength="6">
                    <button id="createGroupBtn" class="btn-primary">Gruppe erstellen</button>
                    <p style="text-align: center; font-size: 0.85rem; color: var(--text-muted); margin-top: 2rem;">
                        Bereits eine Gruppe? <a href="#" id="showJoinGroup" style="color: var(--primary); font-weight: 600; text-decoration: none;">Zum Login</a>
                    </p>
                </div>
            </div>
        </div>

        <div id="toast" class="toast">Hinzugefügt!</div>

        <!-- Modal: Datenschutzerklärung -->
        <div id="privacyModal" class="modal">
            <div class="modal-content glass" style="max-height: 80vh; overflow-y: auto; text-align: left;">
                <h3 style="margin-bottom: 1.5rem; font-weight: 800; font-size: 1.5rem;">Datenschutzerklärung</h3>
                
                <h4 style="margin-top: 1.5rem; font-weight: 700;">1. Datenschutz auf einen Blick</h4>
                <p style="font-size: 0.85rem; color: var(--text-muted); line-height: 1.5; margin-bottom: 1rem;">
                    Diese Anwendung verarbeitet keine personenbezogenen Daten (wie Name, E-Mail-Adresse oder IP-Adresse) dauerhaft. Für die Synchronisation wird lediglich ein Gruppen-PIN und eine Gruppen-ID verwendet. Es findet keine Weitergabe von Daten an Dritte statt. Alle Schriftarten und Icons werden lokal von unserem Webserver geladen.
                </p>

                <h4 style="margin-top: 1.5rem; font-weight: 700;">2. Bereitstellung der Website (Logfiles)</h4>
                <p style="font-size: 0.85rem; color: var(--text-muted); line-height: 1.5; margin-bottom: 1rem;">
                    Der Webserver erhebt standardmäßig technische Logfiles (u.a. IP-Adresse des anfragenden Geräts, Datum, Uhrzeit, abgerufene Datei, User-Agent), die für den sicheren Betrieb erforderlich sind. Diese Daten werden nicht mit anderen Datenquellen zusammengeführt.
                </p>

                <h4 style="margin-top: 1.5rem; font-weight: 700;">3. Hosting</h4>
                <p style="font-size: 0.85rem; color: var(--text-muted); line-height: 1.5; margin-bottom: 1rem;">
                    Das Hosting dieser Anwendung erfolgt auf einem privaten Server. Zur Absicherung des Datenverkehrs wird eine verschlüsselte Verbindung (HTTPS) genutzt.
                </p>

                <button class="btn-secondary" id="closePrivacyModal" style="width: 100%; margin-top: 2rem;">Schließen</button>
            </div>
        </div>

        <!-- Modal: Impressum -->
        <div id="impressumModal" class="modal">
            <div class="modal-content glass" style="text-align: left;">
                <h3 style="margin-bottom: 1.5rem; font-weight: 800; font-size: 1.5rem;">Impressum</h3>
                
                <p style="font-size: 0.85rem; color: var(--text-muted); line-height: 1.6; margin-bottom: 1rem;">
                    <strong>Betreiber dieser Anwendung:</strong><br>
                    Frank [Nachname]<br>
                    [Straße Hausnummer]<br>
                    [PLZ Ort]<br>
                    Deutschland
                </p>

                <p style="font-size: 0.85rem; color: var(--text-muted); line-height: 1.6; margin-bottom: 1rem;">
                    <strong>Kontakt:</strong><br>
                    E-Mail: [E-Mail-Adresse]
                </p>

                <button class="btn-secondary" id="closeImpressumModal" style="width: 100%; margin-top: 2rem;">Schließen</button>
            </div>
        </div>
    </div>

    <!-- App Logic -->
    <script src="assets/js/app.js?v=1.5.7" type="module"></script>
    <script>
        // PWA Service Worker Registration with Update Logic
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('./sw.js')
                    .then(reg => {
                        console.log('PWA SW registered!', reg);
                        
                        // Check for updates
                        if (reg.waiting) {
                             document.getElementById('update-banner').classList.add('show');
                        }
                        
                        reg.addEventListener('updatefound', () => {
                             const newWorker = reg.installing;
                             const CACHE_NAME = 'einkaufs-v1.5.7';
                            newWorker.addEventListener('statechange', () => {
                                if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                                    document.getElementById('update-banner').classList.add('show');
                                }
                            });
                        });
                    })
                    .catch(err => console.log('PWA SW registration failed!', err));
            });

            let refreshing;
            navigator.serviceWorker.addEventListener('controllerchange', () => {
                if (refreshing) return;
                window.location.reload();
                refreshing = true;
            });

            document.getElementById('update-btn')?.addEventListener('click', () => {
                navigator.serviceWorker.getRegistration().then(reg => {
                    reg.waiting.postMessage({ type: 'SKIP_WAITING' });
                });
            });
        }
    </script>
</body>
</html>
