/**
 * Einkaufs-App Frontend Logic (Version 1.5.7 - Auto-Logout & MariaDB)
 */

// --- Security: HTML Escaping helper ---
function escapeHTML(str) {
    if (!str) return '';
    return String(str).replace(/[&<>'"]/g, tag => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        "'": '&#39;',
        '"': '&quot;'
    }[tag] || tag));
}

// --- State ---
let currentGroupId = localStorage.getItem('groupId');
let shoppingList = [];
let catalog = [];
let stores = [];
let activeStoreId = localStorage.getItem('activeStoreId') || '';

// --- DOM elements ---
const views = document.querySelectorAll('.view');
const navItems = document.querySelectorAll('.nav-item');
const shoppingListContainer = document.getElementById('shoppingList');
const catalogContainer = document.getElementById('catalogList');
const storeListContainer = document.getElementById('storeList');
const activeStoreSelect = document.getElementById('activeStore');
const currentGroupText = document.getElementById('currentGroup');
const syncStatus = document.getElementById('syncStatus');
const toastElement = document.getElementById('toast');
const listCountBadge = document.getElementById('listCount');
const totalEstimateText = document.getElementById('totalEstimate');
const addModal = document.getElementById('addModal');
const gatekeeper = document.getElementById('gatekeeper');
const pinDigits = document.querySelectorAll('.pin-digit');
const privacyModal = document.getElementById('privacyModal');
const impressumModal = document.getElementById('impressumModal');

// -------------------------------------------------------------------------
// 1. EVENT LISTENERS
// -------------------------------------------------------------------------

navItems.forEach(item => {
    item.addEventListener('click', () => switchView(item.dataset.view));
});

// Legal Modals Listeners
document.getElementById('openPrivacyBtn')?.addEventListener('click', (e) => {
    e.preventDefault();
    privacyModal?.classList.add('active');
});

document.getElementById('closePrivacyModal')?.addEventListener('click', () => {
    privacyModal?.classList.remove('active');
});

document.getElementById('openImpressumBtn')?.addEventListener('click', (e) => {
    e.preventDefault();
    impressumModal?.classList.add('active');
});

document.getElementById('closeImpressumModal')?.addEventListener('click', () => {
    impressumModal?.classList.remove('active');
});

activeStoreSelect.addEventListener('change', (e) => {
    activeStoreId = e.target.value;
    localStorage.setItem('activeStoreId', activeStoreId);
    refreshData();
});

document.getElementById('addStoreBtn')?.addEventListener('click', async () => {
    const nameInput = document.getElementById('newStoreName');
    const name = nameInput.value.trim();
    if (!name) return;
    try {
        const res = await fetch('api/?action=add_store', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ name, group_id: currentGroupId })
        });
        const data = await res.json();
        if (!data.success) throw new Error(data.error);

        nameInput.value = '';
        refreshData();
    } catch (e) { 
        showToast('Fehler beim Filiale-Speichern'); 
    }
});

document.getElementById('openAddModal')?.addEventListener('click', () => {
    console.log('Standard listener trigger: openAddModal');
    const modal = document.getElementById('addModal');
    if (modal) {
        modal.classList.add('active');
        console.log('Modal element found, class active added');
    } else {
        console.error('Modal element NOT found in standard listener!');
    }
    
    const nameInput = document.getElementById('quickAddInput');
    if (nameInput) {
        setTimeout(() => {
            nameInput.focus();
            console.log('Focus called on quickAddInput');
        }, 100);
    }
    
    const priceInput = document.getElementById('itemPrice');
    if (priceInput) priceInput.value = '';
});

document.getElementById('closeModal')?.addEventListener('click', () => {
    const modal = document.getElementById('addModal');
    if (modal) modal.classList.remove('active');
});

document.getElementById('submitQuickAdd')?.addEventListener('click', async () => {
    const nameInput = document.getElementById('quickAddInput');
    const priceInput = document.getElementById('itemPrice');
    const unitInput = document.getElementById('itemUnit');
    
    if (!nameInput || !priceInput || !unitInput) {
        console.error('Eingabefelder nicht gefunden');
        return;
    }

    const name = nameInput.value.trim();
    const priceVal = priceInput.value.replace(',', '.');
    const price = parseFloat(priceVal) || 0;
    const unit = unitInput.value;

    if (!name) return showToast('Bitte Name eingeben');

    try {
        const res = await fetch('api/?action=add_item', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ 
                name, 
                unit, 
                category: 'Allgemein',
                last_known_price: price 
            })
        });
        const data = await res.json();
        if (!data.success) throw new Error(data.error);
        
        nameInput.value = '';
        priceInput.value = '';
        
        const modal = document.getElementById('addModal');
        if (modal) modal.classList.remove('active');
        
        refreshData();
        showToast('Gespeichert!');
    } catch (err) { 
        console.error('Fehler beim Speichern:', err);
        showToast('Speicherfehler'); 
    }
});

pinDigits.forEach((digit, idx) => {
    // Nur Ziffern zulassen und automatisch zum nächsten Feld springen
    digit.addEventListener('input', (e) => {
        let val = digit.value.replace(/\D/g, '');
        if (val.length > 0) {
            digit.value = val.charAt(0);
            if (idx < 5) {
                pinDigits[idx + 1].focus();
            }
        }
    });

    // Backspace-Logik zum Zurückspringen und Löschen
    digit.addEventListener('keydown', (e) => {
        if (e.key === 'Backspace') {
            if (digit.value === '') {
                if (idx > 0) {
                    pinDigits[idx - 1].focus();
                    pinDigits[idx - 1].value = '';
                }
            } else {
                digit.value = '';
            }
            e.preventDefault();
        }
    });

    // Einfügen einer kompletten PIN unterstützen
    digit.addEventListener('paste', (e) => {
        e.preventDefault();
        const pasteData = (e.clipboardData || window.clipboardData).getData('text');
        const numbersOnly = pasteData.replace(/\D/g, '').substring(0, 6);
        
        for (let i = 0; i < numbersOnly.length; i++) {
            if (pinDigits[i]) {
                pinDigits[i].value = numbersOnly.charAt(i);
            }
        }
        
        const focusIndex = Math.min(numbersOnly.length, 5);
        if (pinDigits[focusIndex]) {
            pinDigits[focusIndex].focus();
        }
    });
});

// Init Gatekeeper Event Listeners (Direct binding to avoid DOMContentLoaded races)
document.getElementById('showCreateGroup')?.addEventListener('click', (e) => {
    e.preventDefault();
    console.log("Neu erstellen clicked");
    const joinSection = document.getElementById('joinGroupSection');
    const createSection = document.getElementById('createGroupSection');
    if (joinSection) joinSection.style.display = 'none';
    if (createSection) createSection.style.display = 'block';
});

document.getElementById('showJoinGroup')?.addEventListener('click', (e) => {
    e.preventDefault();
    console.log("Zum Login clicked");
    const joinSection = document.getElementById('joinGroupSection');
    const createSection = document.getElementById('createGroupSection');
    if (createSection) createSection.style.display = 'none';
    if (joinSection) joinSection.style.display = 'block';
});

document.getElementById('joinGroupBtn')?.addEventListener('click', async () => {
    const pin = Array.from(pinDigits).map(d => d.value).join('');
    try {
        const res = await fetch('api/?action=join_group', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ pin })
        });
        const result = await res.json();
        if (result.success && result.data) {
            loginAs(result.data.id, result.data.name);
        } else {
            showToast('PIN ungültig');
        }
    } catch (e) { 
        showToast('Login-Fehler'); 
    }
});

document.getElementById('createGroupBtn')?.addEventListener('click', async () => {
    const name = document.getElementById('newGroupName').value.trim();
    const pin = document.getElementById('newGroupPin').value.trim();
    try {
        const res = await fetch('api/?action=create_group', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ name, pin })
        });
        const result = await res.json();
        if (result.success && result.data) {
            loginAs(result.data.id, result.data.name);
        } else {
            throw new Error(result.error || 'Erstellungsfehler');
        }
    } catch (e) { 
        showToast(e.message); 
    }
});

document.getElementById('catalogSearch')?.addEventListener('input', (e) => {
    refreshData(e.target.value);
});

// -------------------------------------------------------------------------
// 2. CORE LOGIC
// -------------------------------------------------------------------------

async function refreshData(search = '') {
    if (!currentGroupId) return;
    setSyncStatus('syncing');

    try {
        // Filialen holen
        const resStores = await fetch(`api/?action=get_stores&group_id=${encodeURIComponent(currentGroupId)}`);
        const dataStores = await resStores.json();
        if (!dataStores.success) throw new Error(dataStores.error);
        stores = dataStores.data || [];
        renderStoreSelector();
        renderStoreList();

        // Einkaufsliste holen
        const resShopping = await fetch(`api/?action=get_shopping_list&group_id=${encodeURIComponent(currentGroupId)}&store_id=${encodeURIComponent(activeStoreId)}`);
        const dataShopping = await resShopping.json();
        if (!dataShopping.success) throw new Error(dataShopping.error);
        shoppingList = dataShopping.data || [];

        // Artikelliste alphabetisch sortieren, damit sie nicht springt (BigInt Fix)
        shoppingList.sort((a, b) => a.name.localeCompare(b.name));

        // Katalog holen
        const resCatalog = await fetch(`api/?action=get_catalog&search=${encodeURIComponent(search)}`);
        const dataCatalog = await resCatalog.json();
        if (!dataCatalog.success) throw new Error(dataCatalog.error);
        catalog = dataCatalog.data || [];

        renderAll();
        calculateEstimate();
        setSyncStatus('done');
    } catch (err) { 
        console.error('Daten-Aktualisierungsfehler:', err);
        setSyncStatus('error'); 
    }
}

function calculateEstimate() {
    if (!totalEstimateText) return;
    const total = shoppingList.reduce((sum, item) => {
        const p = parseFloat(item.price);
        const price = (p > 0) ? p : (parseFloat(item.lastPrice) || 0);
        return sum + (price * (parseInt(item.quantity) || 1));
    }, 0);
    totalEstimateText.textContent = total.toLocaleString('de-DE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' €';
}

function renderAll() {
    renderShoppingList();
    renderCatalog();
    if (listCountBadge) listCountBadge.textContent = shoppingList.length;
}

// -------------------------------------------------------------------------
// 3. ACTIONS
// -------------------------------------------------------------------------

window.updateQuantity = (id, qty) => {
    const val = parseInt(qty) || 1;
    // Nur lokale Berechnung für sofortiges Feedback (verhindert Jump-Effekte beim Tippen)
    const item = shoppingList.find(s => String(s.id) === String(id));
    if (item) { 
        item.quantity = val; 
        calculateEstimate(); 
    }
};

window.saveQuantity = async (id, qty) => {
    const val = parseInt(qty) || 1;
    try {
        await fetch('api/?action=update_quantity', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id, quantity: val })
        });
    } catch (e) { 
        console.error('Mengen-Speicher-Fehler:', e); 
    }
};

window.updatePrice = (id, price) => {
    // Erlaube nur Ziffern und Punkt, ersetze Komma direkt durch Punkt
    let sanitized = price.replace(',', '.').replace(/[^0-9.]/g, '');
    
    // Verhindere mehr als einen Punkt
    const parts = sanitized.split('.');
    if (parts.length > 2) {
        sanitized = parts[0] + '.' + parts.slice(1).join('');
    }

    // Wir setzen den Wert nur zurück, wenn wir ungültige Zeichen gefiltert haben
    // Das verhindert Cursor-Sprünge beim normalen Tippen
    const input = document.getElementById(`price-${id}`);
    if (input && input.value !== sanitized && (price.includes(',') || price.match(/[^0-9.]/))) {
        input.value = sanitized;
    }

    const val = parseFloat(sanitized) || 0;
    // Lokale Berechnung für Summe (sofort)
    const item = shoppingList.find(s => String(s.id) === String(id));
    if (item) { 
        item.price = val; 
        calculateEstimate(); 
    }
};

window.savePrice = async (id, price) => {
    const val = parseFloat(String(price).replace(',', '.')) || 0;
    setSyncStatus('syncing');
    try {
        const res = await fetch('api/?action=update_price', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id, price: val })
        });
        const data = await res.json();
        if (!data.success) throw new Error(data.error);
        setSyncStatus('done');
    } catch (e) { 
        console.error('Preis-Speicher-Fehler:', e); 
        setSyncStatus('error');
        showToast('Fehler beim Speichern');
    }
};

window.toggleInCatalog = async (itemId, currentlyOnList) => {
    try {
        const res = await fetch('api/?action=toggle_in_catalog', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                item_id: itemId,
                group_id: currentGroupId,
                store_id: activeStoreId || '',
                currently_on_list: currentlyOnList
            })
        });
        const data = await res.json();
        if (!data.success) throw new Error(data.error);

        refreshData();
    } catch (e) { 
        showToast('Sync Fehler'); 
    }
};

window.buyItem = async (listId, itemId) => {
    const priceInput = document.getElementById(`price-${listId}`);
    const finalPrice = parseFloat(priceInput?.value) || 0;
    try {
        const res = await fetch('api/?action=buy_item', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                list_id: listId,
                item_id: itemId,
                price: finalPrice,
                store_id: activeStoreId || ''
            })
        });
        const data = await res.json();
        if (!data.success) throw new Error(data.error);

        showToast('Erledigt!');
        refreshData();
    } catch (e) { 
        showToast('Kauf-Fehler'); 
    }
};

window.deleteStore = async (id) => {
    try {
        const res = await fetch('api/?action=delete_store', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id })
        });
        const data = await res.json();
        if (!data.success) throw new Error(data.error);

        if (String(activeStoreId) === String(id)) { 
            activeStoreId = ''; 
            localStorage.removeItem('activeStoreId'); 
        }
        refreshData();
    } catch (e) { 
        showToast('Löschfehler'); 
    }
};

function loginAs(id, name) {
    currentGroupId = id;
    localStorage.setItem('groupId', id);
    localStorage.setItem('groupName', name);
    checkAccess();
}

function checkAccess() {
    if (!currentGroupId) { 
        gatekeeper.style.display = 'flex'; 
    } else {
        gatekeeper.style.display = 'none';
        if (currentGroupText) {
            currentGroupText.textContent = `Gruppe: ${localStorage.getItem('groupName') || currentGroupId}`;
        }
        
        // PIN & ID für Einstellungen laden
        fetchGroupDetails();

        refreshData();
        setupRealtime();
    }
}

async function fetchGroupDetails() {
    try {
        const res = await fetch(`api/?action=group_details&id=${encodeURIComponent(currentGroupId)}`);
        const result = await res.json();
        if (result.success && result.data) {
            const data = result.data;
            const pinDisplay = document.getElementById('displayGroupPin');
            const idDisplay = document.getElementById('displayGroupId');
            
            if (pinDisplay) pinDisplay.textContent = data.pin ? data.pin : 'KEINE PIN';
            if (idDisplay) idDisplay.textContent = data.id;
            
            localStorage.setItem('groupName', data.name);
        } else {
            // Falls die Gruppe in der DB nicht existiert (z.B. nach DB-Reset),
            // loggen wir den User aus und zeigen den Gatekeeper
            console.warn('Gruppe in DB nicht gefunden. Logge aus...');
            localStorage.clear();
            currentGroupId = null;
            checkAccess();
        }
    } catch (e) { 
        console.error('Fehler beim Laden der Gruppendetails:', e); 
    }
}

let pollInterval = null;
function setupRealtime() {
    if (pollInterval) clearInterval(pollInterval);
    pollInterval = setInterval(() => {
        // Polling unterbrechen, falls der User gerade Eingaben tätigt
        const activeEl = document.activeElement;
        if (activeEl && (activeEl.tagName === 'INPUT' || activeEl.tagName === 'SELECT' || activeEl.tagName === 'TEXTAREA')) {
            return;
        }
        refreshData();
    }, 10000); // 10 Sekunden Polling
}

function switchView(viewId) {
    if (!currentGroupId && viewId !== 'gatekeeper') return;
    views.forEach(v => { 
        v.classList.remove('active'); 
        if (v.id === viewId) v.classList.add('active'); 
    });
    navItems.forEach(n => { 
        n.classList.remove('active'); 
        if (n.dataset.view === viewId) n.classList.add('active'); 
    });
}

// -------------------------------------------------------------------------
// 4. RENDERING
// -------------------------------------------------------------------------

function renderShoppingList() {
    if (!shoppingListContainer) return;
    if (shoppingList.length === 0) {
        shoppingListContainer.innerHTML = `<div class="view-card glass" style="text-align: center; padding: 2rem;"><p style="color: var(--text-muted);">Dein Korb ist hier leer. 🧺</p></div>`;
        return;
    }
    shoppingListContainer.innerHTML = shoppingList.map((item, index) => `
        <div class="list-item" style="animation-delay: ${index * 0.05}s">
            <div class="checkbox-container" onclick="buyItem('${item.id}', '${item.item_id}')"><i class="fas fa-check"></i></div>
            <input type="number" class="compact-qty-input" value="${item.quantity || 1}" min="1" oninput="updateQuantity('${item.id}', this.value)" onchange="saveQuantity('${item.id}', this.value)">
            <div class="item-details" style="flex: 1; min-width: 0;">
                <div class="item-name">${escapeHTML(item.name)}</div>
                <div class="item-category">${escapeHTML(item.category)} • ${escapeHTML(item.unit)}</div>
            </div>
            <div class="price-input-wrapper">
                <input type="text" 
                    id="price-${item.id}" 
                    inputmode="decimal" 
                    placeholder="${parseFloat(item.lastPrice) > 0 ? parseFloat(item.lastPrice).toFixed(2) : '0.00'}" 
                    class="compact-price-input" 
                    value="${parseFloat(item.price) > 0 ? parseFloat(item.price) : ''}" 
                    oninput="updatePrice('${item.id}', this.value)" 
                    onchange="savePrice('${item.id}', this.value)"
                    onblur="savePrice('${item.id}', this.value)"><span style="font-size: 0.7rem; color: var(--text-muted); padding-right: 5px;">€</span>
            </div>
        </div>
    `).join('');
}

function renderCatalog() {
    if (!catalogContainer) return;
    const isOnList = (itemId) => shoppingList.some(s => String(s.item_id) === String(itemId));
    catalogContainer.innerHTML = catalog.map(item => {
        const active = isOnList(item.id);
        return `
            <div class="list-item glass ${active ? 'active-list' : ''}" onclick="toggleInCatalog(${item.id}, ${active})">
                <div class="item-details">
                    <div class="item-name" style="${active ? 'color: var(--primary);' : ''}">${escapeHTML(item.name)}</div>
                    <div class="item-category">${escapeHTML(item.category)} • ${escapeHTML(item.unit)}${item.last_known_price > 0 ? ` • ${parseFloat(item.last_known_price).toFixed(2)} €` : ''}</div>
                </div>
                <div class="item-actions">
                    <i class="fas fa-trash-can delete-item-icon" onclick="event.stopPropagation(); deleteItem(${item.id})"></i>
                    <i class="fas ${active ? 'fa-minus-circle' : 'fa-plus-circle'}" style="color: ${active ? 'var(--danger)' : 'var(--primary)'}; font-size: 1.25rem;"></i>
                </div>
            </div>
        `;
    }).join('');
}

window.deleteItem = async (id) => {
    const item = catalog.find(i => String(i.id) === String(id));
    const name = item ? item.name : 'Artikel';
    if (!confirm(`'${name}' wirklich löschen?`)) return;
    try {
        const res = await fetch('api/?action=delete_item', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id })
        });
        const data = await res.json();
        if (!data.success) throw new Error(data.error);

        showToast('Artikel gelöscht');
        refreshData();
    } catch (e) { 
        showToast('Löschfehler'); 
    }
};

function renderStoreSelector() {
    if (!activeStoreSelect) return;
    const options = stores.map(s => `<option value="${s.id}" ${String(s.id) === String(activeStoreId) ? 'selected' : ''}>${escapeHTML(s.name)}</option>`).join('');
    activeStoreSelect.innerHTML = `<option value="">🛒 Filiale wählen...</option>` + options;
}

function renderStoreList() {
    if (!storeListContainer) return;
    storeListContainer.innerHTML = stores.map(s => `<div class="store-badge glass">${escapeHTML(s.name)}<i class="fas fa-times" onclick="deleteStore(${s.id})"></i></div>`).join('');
}

function setSyncStatus(status) {
    if (!syncStatus) return;
    if (status === 'syncing') syncStatus.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i>';
    else if (status === 'done') syncStatus.innerHTML = '<i class="fas fa-check-double" style="color: var(--success);"></i>';
    else syncStatus.innerHTML = '<i class="fas fa-cloud-offline" style="color: var(--danger);"></i>';
}

function showToast(text) {
    if (!toastElement) return;
    toastElement.textContent = text;
    toastElement.classList.add('show');
    setTimeout(() => toastElement.classList.remove('show'), 2000);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', checkAccess);
} else {
    checkAccess();
}
