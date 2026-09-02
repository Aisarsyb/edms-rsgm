/**
 * ==========================================================================
 * EDMS RSGM Universitas Airlangga - Frontend Controller (main.js)
 * Mengatur alur Single Page Workflow, Fetch API, Modal, Toast & PDF.js
 * ==========================================================================
 */

// State Global Aplikasi
let activeView = 'employees';
let selectedEmployeeId = null;
let selectedDocumentId = null;
let originalEmployeeData = null; // Menyimpan data asli biodata pegawai untuk perbandingan (batal/simpan)

// State Visibilitas Panel (ON/OFF toggle)
let detailPanelOn = false;
let filesPanelOn  = false;

// State Refresh Notifikasi Global
let refreshNotifications = null;


// State Rendering PDF.js
let pdfDoc = null;
let pageNum = 1;
let pageRendering = false;
let pageNumPending = null;
let pdfScale = 1.2;
const pdfCanvas = document.getElementById('pdf-canvas');
const pdfCtx = pdfCanvas ? pdfCanvas.getContext('2d') : null;

// Tunggu DOM selesai dimuat
document.addEventListener('DOMContentLoaded', () => {
    initViewNavigation();
    initEmployeeFilters();
    initEmployeeModal();
    initDocumentUpload();
    initPDFZoomControls();
    initGlobalSearch();
    initPanelCollapseToggles(); // Tombol sembunyikan/tampilkan panel
    initResizeHandles();        // Geser perbatasan untuk resize panel
    initNotifications();        // Notifikasi masa berlaku berkas

    // Muat daftar pegawai secara default di awal
    loadEmployeeList();
});

/**
 * ==========================================================================
 * 1. MANAJEMEN NAVIGASI & SWITCH VIEW (SINGLE PAGE WORKFLOW)
 * ==========================================================================
 */
function initViewNavigation() {
    const navItems = {
        'nav-dashboard': 'dashboard',
        'nav-employees': 'employees',
        'nav-trash': 'trash'
    };

    Object.entries(navItems).forEach(([navId, viewName]) => {
        const btn = document.getElementById(navId);
        if (btn) {
            btn.addEventListener('click', () => {
                switchView(viewName);
            });
        }
    });
}

function switchView(viewName) {
    if (activeView === viewName) return;

    // Hapus kelas aktif dari tombol navigasi sebelumnya
    document.querySelectorAll('.nav-item').forEach(item => {
        item.classList.remove('active');
        // Reset warna ikon
        const icon = item.querySelector('.material-symbols-outlined');
        if (icon) {
            icon.classList.replace('text-primary', 'text-on-surface-variant');
        }
    });

    // Set tombol aktif baru
    let activeNavBtn = null;
    if (viewName === 'dashboard') activeNavBtn = document.getElementById('nav-dashboard');
    if (viewName === 'employees') activeNavBtn = document.getElementById('nav-employees');
    if (viewName === 'trash') activeNavBtn = document.getElementById('nav-trash');

    if (activeNavBtn) {
        activeNavBtn.classList.add('active');
        const icon = activeNavBtn.querySelector('.material-symbols-outlined');
        if (icon) {
            icon.classList.replace('text-on-surface-variant', 'text-primary');
        }
    }

    // Sembunyikan semua tampilan
    document.querySelectorAll('.content-view').forEach(view => {
        view.classList.remove('active-view');
    });

    // Tampilkan tampilan terpilih
    const targetView = document.getElementById(`view-${viewName}`);
    if (targetView) {
        targetView.classList.add('active-view');
    }

    activeView = viewName;

    // Refresh data view terkait
    if (viewName === 'dashboard') {
        loadDashboardStats();
    } else if (viewName === 'employees') {
        loadEmployeeList();
    } else if (viewName === 'trash') {
        loadTrashData();
    }
}

/**
 * ==========================================================================
 * 1b. MANAJEMEN PANEL LAYOUT DINAMIS
 *     - Saat employee dipilih: list panel menyempit, detail & files muncul
 *     - Tombol collapse: sembunyikan/tampilkan panel individual
 *     - Drag handle: geser batas antar panel untuk mengubah ukuran
 * ==========================================================================
 */

/**
 * Aktifkan layout 3-panel saat employee pertama kali dipilih.
 */
function activatePanelLayout() {
    const workspace = document.getElementById('personnel-workspace');
    if (workspace) workspace.classList.add('has-selection');
}

/**
 * Sinkronisasi tampilan panel berdasarkan state global detailPanelOn / filesPanelOn.
 * - Jika keduanya OFF  → kembali ke tampilan daftar penuh (full-width list)
 * - Jika salah satu ON → tampilkan panel yang aktif, sembunyikan yang OFF
 * - Update tombol bulat di kartu aktif
 */
function syncPanelState() {
    const workspace    = document.getElementById('personnel-workspace');
    const panelDetails = document.getElementById('panel-details');
    const panelFiles   = document.getElementById('panel-files');
    if (!workspace || !panelDetails || !panelFiles) return;

    // Bersihkan sisa drag resizer (inline width/flexBasis) agar layout CSS via class bisa bekerja optimal
    panelDetails.style.width = '';
    panelDetails.style.flexBasis = '';
    panelFiles.style.width = '';
    panelFiles.style.flexBasis = '';

    if (!detailPanelOn && !filesPanelOn) {
        // ── Keduanya OFF: kembali ke tampilan daftar penuh ──
        workspace.classList.remove('has-selection', 'detail-hidden');
        // Hapus seleksi visual dari semua kartu
        document.querySelectorAll('.employee-card').forEach(c => {
            c.classList.remove('active-selection');
            c.querySelectorAll('.card-action-btn').forEach(b => b.classList.remove('btn-active'));
        });
        selectedEmployeeId = null;
        return;
    }

    // ── Ada panel yang ON: tampilkan workspace 3-kolom ──
    workspace.classList.add('has-selection');
    panelDetails.classList.toggle('panel-collapsed', !detailPanelOn);
    panelFiles.classList.toggle('panel-collapsed', !filesPanelOn);
    // Fix blank space saat detail disembunyikan → files melebar
    workspace.classList.toggle('detail-hidden', !detailPanelOn);

    // Update warna tombol bulat di kartu aktif
    if (selectedEmployeeId) {
        const activeCard = document.querySelector(`.employee-card[data-id="${selectedEmployeeId}"]`);
        if (activeCard) {
            const btnD = activeCard.querySelector('.btn-card-detail');
            const btnF = activeCard.querySelector('.btn-card-files');
            if (btnD) btnD.classList.toggle('btn-active', detailPanelOn);
            if (btnF) btnF.classList.toggle('btn-active', filesPanelOn);
        }
    }
}

/**
 * Inisialisasi tombol collapse/expand untuk panel Detail dan Files.
 */
function initPanelCollapseToggles() {
    const btnToggleDetails = document.getElementById('btn-toggle-details');
    const panelDetails     = document.getElementById('panel-details');
    const btnToggleFiles   = document.getElementById('btn-toggle-files');
    const panelFiles       = document.getElementById('panel-files');
    const workspace        = document.getElementById('personnel-workspace');

    if (btnToggleDetails && panelDetails) {
        btnToggleDetails.addEventListener('click', () => {
            const collapsed = panelDetails.classList.toggle('panel-collapsed');
            btnToggleDetails.classList.toggle('collapsed', collapsed);
            btnToggleDetails.title = collapsed ? 'Tampilkan Detail Biodata' : 'Sembunyikan Detail Biodata';
            // Fix blank space: saat detail collapse, files melebar
            if (workspace) workspace.classList.toggle('detail-hidden', collapsed);
        });
    }

    if (btnToggleFiles && panelFiles) {
        btnToggleFiles.addEventListener('click', () => {
            const collapsed = panelFiles.classList.toggle('panel-collapsed');
            btnToggleFiles.classList.toggle('collapsed', collapsed);
            btnToggleFiles.title = collapsed ? 'Tampilkan Berkas Dokumen' : 'Sembunyikan Berkas Dokumen';
        });
    }
}

/**
 * Inisialisasi drag handle untuk menggeser batas antar panel.
 */
function initResizeHandles() {
    const workspace    = document.getElementById('personnel-workspace');
    const panelList    = document.getElementById('panel-list');
    const panelDetails = document.getElementById('panel-details');
    const panelFiles   = document.getElementById('panel-files');
    const rh1 = document.getElementById('rh-1');
    const rh2 = document.getElementById('rh-2');

    if (!workspace || !panelList || !panelDetails || !panelFiles || !rh1 || !rh2) return;

    let isDragging    = false;
    let activeHandle  = null;
    let startX        = 0;
    let startDetailsW = 0;
    let startFilesW   = 0;

    function startDrag(e, handle) {
        if (!workspace.classList.contains('has-selection')) return;
        isDragging    = true;
        activeHandle  = handle;
        startX        = e.clientX;
        startDetailsW = panelDetails.offsetWidth;
        startFilesW   = panelFiles.offsetWidth;
        handle.classList.add('is-dragging');
        document.body.style.cursor     = 'col-resize';
        document.body.style.userSelect = 'none';
        e.preventDefault();
    }

    rh1.addEventListener('mousedown', (e) => startDrag(e, rh1));
    rh2.addEventListener('mousedown', (e) => startDrag(e, rh2));

    document.addEventListener('mousemove', (e) => {
        if (!isDragging) return;
        const dx = e.clientX - startX;
        const totalWidth  = workspace.offsetWidth;
        const MIN_LIST    = 320;
        const MIN_FILES   = 180;
        const MIN_DETAILS = 240;

        if (activeHandle === rh1) {
            // Drag handle 1: Geser batas detail (kiri)
            const maxDetailsW = totalWidth - MIN_LIST - (panelFiles.classList.contains('panel-collapsed') ? 0 : panelFiles.offsetWidth) - 16;
            const newDetailsW = Math.max(MIN_DETAILS, Math.min(startDetailsW - dx, maxDetailsW));
            panelDetails.style.width = newDetailsW + 'px';
            panelDetails.style.flexBasis = newDetailsW + 'px';
        } else if (activeHandle === rh2) {
            // Drag handle 2: Geser batas berkas (kanan)
            const maxFilesW = totalWidth - MIN_LIST - (panelDetails.classList.contains('panel-collapsed') ? 0 : panelDetails.offsetWidth) - 16;
            const newFilesW = Math.max(MIN_FILES, Math.min(startFilesW - dx, maxFilesW));
            panelFiles.style.width = newFilesW + 'px';
            panelFiles.style.flexBasis = newFilesW + 'px';
        }
    });

    document.addEventListener('mouseup', () => {
        if (!isDragging) return;
        isDragging = false;
        if (activeHandle) {
            activeHandle.classList.remove('is-dragging');
            activeHandle = null;
        }
        document.body.style.cursor     = '';
        document.body.style.userSelect = '';
    });
}

/**
 * Helper terpusat untuk melakukan request fetch dengan Error Handling lengkap.
 */
async function fetchAPI(url, options = {}) {
    // Sisipkan header untuk mengidentifikasi request AJAX (untuk auth.php 401 handling)
    if (!options.headers) {
        options.headers = {};
    }
    options.headers['X-Requested-With'] = 'XMLHttpRequest';

    // Sisipkan CSRF token otomatis untuk request POST
    if (options.method && options.method.toUpperCase() === 'POST') {
        const csrfMeta = document.querySelector('meta[name="csrf-token"]');
        const csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';
        
        if (options.body instanceof FormData) {
            // Tambahkan ke FormData jika belum ada
            if (!options.body.has('csrf_token')) {
                options.body.append('csrf_token', csrfToken);
            }
        } else {
            // Kirim via header untuk non-FormData body
            options.headers['X-CSRF-TOKEN'] = csrfToken;
        }
    }

    try {
        const response = await fetch(url, options);
        
        // 1. Tangani Sesi Habis (Session Timeout)
        if (response.status === 401) {
            showToast('Sesi Anda telah berakhir. Mengalihkan ke login...', 'error');
            setTimeout(() => {
                window.location.href = 'login.php';
            }, 2000);
            throw new Error('Sesi kedaluwarsa (401)');
        }

        // 2. Tangani response error lainnya (400, 404, 405, dll)
        if (!response.ok) {
            const errData = await response.json().catch(() => ({}));
            const errMsg = errData.message || `Terjadi kesalahan (${response.status})`;
            showToast(errMsg, 'error');
            throw new Error(errMsg);
        }

        return await response.json();
    } catch (error) {
        console.error('Fetch API Error:', error);
        throw error;
    }
}

/**
 * ==========================================================================
 * 2. MODUL DASHBOARD
 * ==========================================================================
 */
function initDashboard() {
    // Muat data awal saat dashboard terbuka
    loadDashboardStats();
}

async function loadDashboardStats() {
    if (!document.getElementById('stat-total-employees')) return;
    try {
        const res = await fetchAPI('modules/dashboard/stats.php');
        if (res.status === 'success') {
            const stats = res.data;
            
            // Set angka-angka statistik utama
            document.getElementById('stat-total-employees').innerText = stats.total_employees;
            document.getElementById('stat-total-documents').innerText = stats.total_documents;
            document.getElementById('stat-warning-total').innerText = `${stats.reminders.length} Dokumen`;

            // Progress Bar Kelayakan Dokumen
            const counts = stats.counts;
            const total = counts.active + counts.warning + counts.expired;
            
            let actPct = 0, warnPct = 0, expPct = 0;
            if (total > 0) {
                actPct = Math.round((counts.active / total) * 100);
                warnPct = Math.round((counts.warning / total) * 100);
                expPct = 100 - actPct - warnPct; // Pastikan total genap 100%
            }

            document.getElementById('pb-active').style.width = `${actPct}%`;
            document.getElementById('pb-warning').style.width = `${warnPct}%`;
            document.getElementById('pb-expired').style.width = `${expPct}%`;

            document.getElementById('lbl-active-count').innerText = counts.active;
            document.getElementById('lbl-active-pct').innerText = `${actPct}%`;
            document.getElementById('lbl-warning-count').innerText = counts.warning;
            document.getElementById('lbl-warning-pct').innerText = `${warnPct}%`;
            document.getElementById('lbl-expired-count').innerText = counts.expired;
            document.getElementById('lbl-expired-pct').innerText = `${expPct}%`;

            // Render Daftar Pengingat (Reminder List)
            const reminderList = document.getElementById('dashboard-reminder-list');
            reminderList.innerHTML = '';

            if (stats.reminders.length === 0) {
                reminderList.innerHTML = `
                    <div class="empty-state">
                        <span class="material-symbols-outlined">verified</span>
                        <p>Seluruh berkas dokumen aman dan berlaku.</p>
                    </div>
                `;
            } else {
                stats.reminders.forEach(rem => {
                    const isExpired = rem.status === 'Kedaluwarsa';
                    const iconBoxClass = isExpired ? 'bg-expired-soft text-expired' : 'bg-warning-soft text-warning';
                    const textClass = isExpired ? 'text-expired' : 'text-warning';
                    
                    const card = document.createElement('div');
                    card.className = 'reminder-card';
                    card.innerHTML = `
                        <div class="reminder-badge-icon ${iconBoxClass}">
                            <span class="material-symbols-outlined">${isExpired ? 'gpp_maybe' : 'report'}</span>
                        </div>
                        <div class="reminder-info">
                            <div class="reminder-title">${escapeHTML(rem.document_type)}</div>
                            <div class="reminder-owner">${escapeHTML(rem.employee_name)} (NIP: ${escapeHTML(rem.employee_nip)})</div>
                            <div class="reminder-meta">
                                <span>No: ${escapeHTML(rem.document_number || '-')}</span>
                                <span class="${textClass}">${escapeHTML(rem.days_label)}</span>
                            </div>
                        </div>
                    `;
                    // Click untuk beralih ke pegawai terkait di Personnel Hub
                    card.style.cursor = 'pointer';
                    card.addEventListener('click', () => {
                        viewEmployeeFromReminder(rem.employee_id || null, rem.employee_nip);
                    });
                    reminderList.appendChild(card);
                });
            }
        }
    } catch (e) {
        console.error('Gagal memuat statistik dashboard:', e);
    }
}

/**
 * Alihkan ke data pegawai spesifik saat mengklik list reminder dashboard.
 */
async function viewEmployeeFromReminder(empId, empNip) {
    switchView('employees');
    // Beri jeda sebentar agar list pegawai selesai dimuat sebelum memilih
    setTimeout(async () => {
        // Cari pegawai berdasarkan NIP
        const searchInput = document.getElementById('employee-search');
        if (searchInput) {
            searchInput.value = empNip;
            await loadEmployeeList(empNip, 'ALL', async () => {
                // Pilih kartu pegawai pertama
                const firstCard = document.querySelector('.employee-card');
                if (firstCard) {
                    firstCard.click();
                }
            });
        }
    }, 400);
}

/**
 * ==========================================================================
 * 3. MODUL MANAJEMEN PEGAWAI (PERSONNEL HUB)
 * ==========================================================================
 */
function initEmployeeFilters() {
    const searchInput = document.getElementById('employee-search');
    const pills = document.querySelectorAll('#employee-type-filters .pill');

    // Input Pencarian Pegawai
    let searchTimeout = null;
    searchInput.addEventListener('input', () => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            const q = searchInput.value.trim();
            const activePill = document.querySelector('#employee-type-filters .pill.active');
            const type = activePill ? activePill.getAttribute('data-type') : 'ALL';
            loadEmployeeList(q, type);
        }, 300); // Debounce pencarian 300ms
    });

    // Filter Kategori Pegawai (Pills)
    pills.forEach(pill => {
        pill.addEventListener('click', () => {
            pills.forEach(p => p.classList.remove('active'));
            pill.classList.add('active');
            
            const q = searchInput.value.trim();
            const type = pill.getAttribute('data-type');
            loadEmployeeList(q, type);
        });
    });
}

/**
 * Ambil daftar pegawai dan render ke panel kiri.
 */
async function loadEmployeeList(q = '', type = 'ALL', callback = null) {
    const container = document.getElementById('employee-cards-container');
    container.innerHTML = '<div class="empty-state"><div class="spinner"></div><p>Memuat daftar pegawai...</p></div>';

    try {
        const url = `modules/employees/get.php?q=${encodeURIComponent(q)}&type=${encodeURIComponent(type)}`;
        const res = await fetchAPI(url);
        
        if (res.status === 'success') {
            document.getElementById('employee-count-label').innerText = `${res.count} Pegawai terdaftar`;
            container.innerHTML = '';

            if (res.count === 0) {
                container.innerHTML = `
                    <div class="empty-state">
                        <span class="material-symbols-outlined">search_off</span>
                        <p>Pegawai tidak ditemukan.</p>
                    </div>
                `;
                resetDetailsPanel();
                return;
            }

            res.data.forEach(emp => {
                const card = document.createElement('div');
                card.className = `employee-card ${selectedEmployeeId === emp.id ? 'active-selection' : ''}`;
                card.setAttribute('data-id', emp.id);

                // Buat inisial nama (maks 2 huruf)
                const initials = emp.name.split(' ').map(w => w[0]).slice(0,2).join('').toUpperCase();

                card.innerHTML = `
                    <div class="employee-avatar-placeholder">${initials}</div>
                    <div class="employee-info-main">
                        <div class="employee-name-row">
                            <span class="employee-card-name" title="${escapeHTML(emp.name)}">${escapeHTML(emp.name)}${emp.gelar ? ', ' + escapeHTML(emp.gelar) : ''}</span>
                            <span class="employee-card-badge">${escapeHTML(emp.employee_type)}</span>
                        </div>
                        <p class="employee-card-nip">NIP: ${escapeHTML(emp.nip)}</p>

                        <!-- Tombol Aksi Cepat (Bulat) - tampil saat hover/aktif -->
                        <div class="employee-card-actions">
                            <button class="card-action-btn btn-card-detail" title="Buka Detail Biodata saja">
                                <span class="material-symbols-outlined">badge</span>
                            </button>
                            <button class="card-action-btn btn-card-files" title="Buka Berkas Dokumen saja">
                                <span class="material-symbols-outlined">folder_open</span>
                            </button>
                        </div>
                    </div>
                    <span class="material-symbols-outlined card-arrow">arrow_forward_ios</span>
                `;

                // =========================================================
                // EVENT HANDLERS KARTU PEGAWAI
                // Sistem ON/OFF toggle: tombol menyala/mati, keduanya OFF
                // → kembali ke tampilan daftar penuh
                // =========================================================

                // ── Klik NAMA/KARTU → kedua panel ON (detail + berkas nyala) ──
                card.addEventListener('click', (e) => {
                    if (e.target.closest('.card-action-btn')) return;

                    const isNew = selectedEmployeeId !== emp.id;

                    // Tandai kartu ini aktif
                    document.querySelectorAll('.employee-card').forEach(c => c.classList.remove('active-selection'));
                    card.classList.add('active-selection');

                    detailPanelOn = true;
                    filesPanelOn  = true;
                    selectedEmployeeId = emp.id;
                    syncPanelState();

                    // Muat data jika pegawai berbeda (atau belum pernah dimuat)
                    if (isNew) loadEmployeePanelData(emp.id);
                });

                // ── Klik tombol bulat DETAIL → toggle panel Detail ON/OFF ──
                card.querySelector('.btn-card-detail').addEventListener('click', (e) => {
                    e.stopPropagation();

                    const isNew = selectedEmployeeId !== emp.id;

                    document.querySelectorAll('.employee-card').forEach(c => c.classList.remove('active-selection'));
                    card.classList.add('active-selection');

                    if (isNew) {
                        // Pegawai baru: nyalakan hanya Detail
                        selectedEmployeeId = emp.id;
                        detailPanelOn = true;
                        filesPanelOn  = false;
                        syncPanelState();
                        loadEmployeePanelData(emp.id);
                    } else {
                        // Pegawai sama: toggle Detail ON/OFF
                        detailPanelOn = !detailPanelOn;
                        syncPanelState(); // jika keduanya OFF, otomatis balik ke full-list
                    }
                });

                // ── Klik tombol bulat BERKAS → toggle panel Berkas ON/OFF ──
                card.querySelector('.btn-card-files').addEventListener('click', (e) => {
                    e.stopPropagation();

                    const isNew = selectedEmployeeId !== emp.id;

                    document.querySelectorAll('.employee-card').forEach(c => c.classList.remove('active-selection'));
                    card.classList.add('active-selection');

                    if (isNew) {
                        // Pegawai baru: nyalakan hanya Berkas
                        selectedEmployeeId = emp.id;
                        detailPanelOn = false;
                        filesPanelOn  = true;
                        syncPanelState();
                        loadEmployeePanelData(emp.id);
                    } else {
                        // Pegawai sama: toggle Berkas ON/OFF
                        filesPanelOn = !filesPanelOn;
                        syncPanelState(); // jika keduanya OFF, otomatis balik ke full-list
                    }
                });

                container.appendChild(card);
            });

            if (callback) callback();
        }
    } catch (e) {
        container.innerHTML = '<div class="empty-state text-error"><span class="material-symbols-outlined">error</span><p>Gagal memuat data pegawai.</p></div>';
    }
}

/**
 * Mengambil data pegawai secara asinkronus dan merendernya ke panel detail dan dokumen.
 * @param {number} empId - ID pegawai
 */
async function loadEmployeePanelData(empId) {
    selectedDocumentId = null;

    // Reset tombol pratinjau dokumen di sidebar kanan
    document.getElementById('employee-files-footer').style.display = 'none';

    // 1. Muat Detail Biodata Pegawai
    const detailsBody = document.getElementById('employee-details-body');
    detailsBody.innerHTML = '<div class="empty-state"><div class="spinner"></div><p>Memuat biodata...</p></div>';

    try {
        const empRes = await fetchAPI(`modules/employees/get.php?id=${empId}`);
        if (empRes.status === 'success') {
            const emp = empRes.data;
            originalEmployeeData = emp; // Simpan data asli

            detailsBody.innerHTML = `
                <form id="details-form-data" class="details-form">
                    <div class="form-group-details">
                        <label for="detail-name">Nama Lengkap (Sesuai Ijazah)</label>
                        <input type="text" id="detail-name" name="name" value="${escapeHTML(emp.name)}" required>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group-details">
                            <label for="detail-nip">NIP / NUPTK</label>
                            <input type="text" id="detail-nip" name="nip" value="${escapeHTML(emp.nip)}" required>
                        </div>
                        <div class="form-group-details">
                            <label for="detail-type">Jenis Staf</label>
                            <select id="detail-type" name="employee_type" required>
                                <option value="Dokter Gigi" ${emp.employee_type === 'Dokter Gigi' ? 'selected' : ''}>Dokter Gigi</option>
                                <option value="Dokter Gigi Spesialis" ${emp.employee_type === 'Dokter Gigi Spesialis' ? 'selected' : ''}>Dokter Gigi Spesialis</option>
                                <option value="Perawat" ${emp.employee_type === 'Perawat' ? 'selected' : ''}>Perawat</option>
                                <option value="Staf Administrasi" ${emp.employee_type === 'Staf Administrasi' ? 'selected' : ''}>Staf Administrasi</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group-details">
                            <label for="detail-gelar">Gelar Kepegawaian</label>
                            <input type="text" id="detail-gelar" name="gelar" value="${escapeHTML(emp.gelar || '')}" placeholder="Contoh: drg., A.Md.Kep, S.Kom">
                        </div>
                        <div class="form-group-details">
                            <label for="detail-status">Status Kepegawaian</label>
                            <select id="detail-status" name="status_kepegawaian" required>
                                <option value="PNS" ${emp.status_kepegawaian === 'PNS' ? 'selected' : ''}>PNS</option>
                                <option value="P3K" ${emp.status_kepegawaian === 'P3K' ? 'selected' : ''}>P3K</option>
                                <option value="Pegawai Tetap (PT)" ${emp.status_kepegawaian === 'Pegawai Tetap (PT)' ? 'selected' : ''}>Pegawai Tetap (PT)</option>
                                <option value="Kontrak / Honorer" ${emp.status_kepegawaian === 'Kontrak / Honorer' ? 'selected' : ''}>Kontrak / Honorer</option>
                            </select>
                        </div>
                    </div>
                    
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 16px 20px; border-radius: 8px; background-color: var(--surface-low); margin-top: 32px; border: 1px solid var(--outline-variant);">
                        <div>
                            <p style="font-size: 11px; font-weight: 700; color: var(--on-surface-variant); text-transform: uppercase; margin-bottom: 4px;">Status Akun</p>
                            <select name="is_active" style="padding: 4px 8px; font-size: 13px; font-weight: 700; border-radius: 4px; border: 1px solid var(--outline-variant); outline: none; background: #fff; cursor: pointer; color: ${emp.is_active == 1 ? 'var(--success)' : 'var(--error)'};">
                                <option value="1" ${emp.is_active == 1 ? 'selected' : ''}>AKTIF</option>
                                <option value="0" ${emp.is_active == 0 ? 'selected' : ''}>NONAKTIF</option>
                            </select>
                        </div>
                        <div style="text-align: center;">
                            <p style="font-size: 11px; font-weight: 700; color: var(--on-surface-variant); text-transform: uppercase; margin-bottom: 4px;">Terdaftar Pada</p>
                            <p style="font-size: 13px; font-weight: 600; color: var(--on-surface);">${formatDateOnly(emp.created_at)}</p>
                        </div>
                        <div>
                            <p style="font-size: 11px; font-weight: 700; color: var(--on-surface-variant); text-transform: uppercase; margin-bottom: 4px;">Penghapusan</p>
                            <button type="button" class="btn btn-error btn-sm" id="btn-soft-delete-emp" style="font-size: 12px; font-weight: 600; padding: 6px 12px; border-radius: 6px; display: flex; align-items: center; gap: 4px; cursor: pointer; border: none; background-color: var(--error); color: white;">
                                <span class="material-symbols-outlined" style="font-size: 16px;">delete</span> Hapus
                            </button>
                        </div>
                    </div>
                </form>
            `;

            // Daftarkan listener input untuk mendeteksi perubahan formulir
            const form = document.getElementById('details-form-data');
            const origSaveBtn = document.getElementById('btn-save-biodata');
            const origDiscardBtn = document.getElementById('btn-discard-biodata');
            
            // Fix memory leak: clone & replace tombol agar event lama tidak menumpuk
            const saveBtn = origSaveBtn.cloneNode(true);
            origSaveBtn.parentNode.replaceChild(saveBtn, origSaveBtn);
            const discardBtn = origDiscardBtn.cloneNode(true);
            origDiscardBtn.parentNode.replaceChild(discardBtn, origDiscardBtn);
            
            saveBtn.disabled = true;
            discardBtn.disabled = true;

            form.addEventListener('input', () => {
                saveBtn.disabled = false;
                discardBtn.disabled = false;
            });

            // Action: Batal Perubahan
            discardBtn.addEventListener('click', () => {
                form.reset();
                saveBtn.disabled = true;
                discardBtn.disabled = true;
            });

            // Action: Simpan Perubahan Biodata
            saveBtn.addEventListener('click', async () => {
                if (!form.reportValidity()) return;
                
                const formData = new FormData(form);
                formData.append('id', empId);

                try {
                    const saveRes = await fetchAPI('modules/employees/save.php', {
                        method: 'POST',
                        body: formData
                    });

                    if (saveRes.status === 'success') {
                        showToast(saveRes.message, 'success');
                        saveBtn.disabled = true;
                        discardBtn.disabled = true;
                        // Refresh list pegawai agar nama berubah di sebelah kiri
                        loadEmployeeList(document.getElementById('employee-search').value.trim(), document.querySelector('#employee-type-filters .pill.active').getAttribute('data-type'));
                    } else {
                        showToast(saveRes.message || 'Gagal menyimpan perubahan.', 'error');
                    }
                } catch (e) {
                    // Penanganan error terpusat fetchAPI
                }
            });

            // Action: Soft Delete Pegawai
            document.getElementById('btn-soft-delete-emp').addEventListener('click', async () => {
                if (confirm(`Apakah Anda yakin ingin memindahkan pegawai "${emp.name}" ke Tempat Sampah?\nSeluruh dokumen pegawai ini juga akan dinonaktifkan sementara.`)) {
                    const fd = new FormData();
                    fd.append('id', empId);
                    
                    try {
                        const delRes = await fetchAPI('modules/employees/delete.php', {
                            method: 'POST',
                            body: fd
                        });
                        
                        if (delRes.status === 'success') {
                            showToast(delRes.message, 'success');
                            selectedEmployeeId = null;
                            resetDetailsPanel();
                            loadEmployeeList(document.getElementById('employee-search').value.trim(), document.querySelector('#employee-type-filters .pill.active').getAttribute('data-type'));
                        }
                    } catch (e) {}
                }
            });
        }
    } catch (e) {
        detailsBody.innerHTML = '<div class="empty-state text-error"><span class="material-symbols-outlined">error</span><p>Gagal memuat biodata pegawai.</p></div>';
    }

    // 2. Muat Berkas Dokumen Milik Pegawai
    loadEmployeeDocuments(empId);
}

/**
 * Ambil daftar dokumen milik pegawai dan render di panel kanan.
 */
async function loadEmployeeDocuments(empId) {
    const container = document.getElementById('employee-files-container');
    container.innerHTML = '<div class="empty-state"><div class="spinner"></div><p>Memuat berkas...</p></div>';
    
    // Tampilkan tombol Tambah Dokumen Baru
    const addDocBtn = document.getElementById('btn-add-document');
    addDocBtn.style.display = 'block';

    try {
        const res = await fetchAPI(`modules/documents/get.php?employee_id=${empId}`);
        if (res.status === 'success') {
            container.innerHTML = '';

            if (res.data.length === 0) {
                container.innerHTML = `
                    <div class="empty-state">
                        <span class="material-symbols-outlined">folder_open</span>
                        <p>Belum ada dokumen yang diunggah.</p>
                    </div>
                `;
                return;
            }

            res.data.forEach(doc => {
                const card = document.createElement('div');
                card.className = 'doc-card';
                card.setAttribute('data-doc-id', doc.document_id);
                card.setAttribute('data-version-id', doc.version_id);
                card.setAttribute('data-file-path', doc.file_path);
                card.setAttribute('data-title', `${doc.document_type} - v${doc.version_number}`);
                card.setAttribute('data-meta', `No: ${doc.document_number || '-'} | ${doc.start_date ? `Terbit: ${formatDateOnly(doc.start_date)} | ` : ''}Berlaku s/d: ${doc.expired_date ? formatDateOnly(doc.expired_date) : 'Seumur Hidup'}`);

                const badgeClass = doc.status === 'Aktif' ? 'badge-active' : (doc.status === 'Akan Berakhir' ? 'badge-warning' : 'badge-expired');
                
                card.innerHTML = `
                    <div class="doc-card-body">
                        <div class="doc-icon-box">
                            <span class="material-symbols-outlined">picture_as_pdf</span>
                        </div>
                        <div class="doc-info">
                            <p class="doc-title" title="${escapeHTML(doc.document_type)}">${escapeHTML(doc.document_type)}</p>
                            <p class="doc-meta">v${doc.version_number} | No: ${escapeHTML(doc.document_number || '-')}</p>
                            <p class="doc-meta" style="font-size: 11px; margin-top: 2px; color: var(--on-surface-variant);">
                                ${doc.start_date ? `Terbit: ${formatDateOnly(doc.start_date)}` : ''}
                                ${doc.start_date && doc.expired_date ? ' | ' : ''}
                                ${doc.expired_date ? `Berlaku s/d: ${formatDateOnly(doc.expired_date)}` : 'Seumur Hidup'}
                            </p>
                            
                            <div class="doc-actions">
                                <div style="display: flex; align-items: center; gap: 4px;">
                                    <span class="doc-badge ${badgeClass}">${escapeHTML(doc.status)}</span>
                                    <span style="font-size: 10px; font-weight: 700; color: var(--on-surface-variant);">
                                        ${doc.expired_date ? formatDateOnly(doc.expired_date) : 'Seumur Hidup'}
                                    </span>
                                </div>
                                <div class="doc-buttons">
                                    <!-- Visibility Preview -->
                                    <button type="button" class="btn-doc-action btn-preview-pdf" title="Pratinjau PDF">
                                        <span class="material-symbols-outlined" style="font-size: 16px;">visibility</span>
                                    </button>
                                    <!-- Riwayat Versi / Versioning -->
                                    <button type="button" class="btn-doc-action btn-history-version" title="Riwayat Versi">
                                        <span class="material-symbols-outlined" style="font-size: 16px;">history</span>
                                    </button>
                                    <!-- Soft Delete Dokumen -->
                                    <button type="button" class="btn-doc-action btn-delete-doc" title="Hapus Dokumen">
                                        <span class="material-symbols-outlined" style="font-size: 16px;">delete_outline</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;

                // Event listener untuk klik dokumen
                card.addEventListener('click', (e) => {
                    // Abaikan jika menekan tombol aksi internal
                    if (e.target.closest('.btn-doc-action')) return;
                    
                    document.querySelectorAll('.doc-card').forEach(c => c.classList.remove('selected-doc'));
                    card.classList.add('selected-doc');
                    
                    selectedDocumentId = doc.document_id;
                    
                    // Aktifkan footer preview
                    const footer = document.getElementById('employee-files-footer');
                    footer.style.display = 'block';
                });

                // Handler: Preview PDF
                card.querySelector('.btn-preview-pdf').addEventListener('click', () => {
                    openPDFPreview(doc.file_path, `${doc.document_type} (Versi ${doc.version_number})`, `Pemilik: ${originalEmployeeData.name} | NIP: ${originalEmployeeData.nip}`);
                });

                // Handler: Riwayat Versi
                card.querySelector('.btn-history-version').addEventListener('click', () => {
                    openVersionHistory(doc.document_id, doc.document_type);
                });

                // Handler: Delete Dokumen (Soft Delete)
                card.querySelector('.btn-delete-doc').addEventListener('click', async () => {
                    if (confirm(`Apakah Anda yakin ingin memindahkan dokumen "${doc.document_type}" milik pegawai ini ke Tempat Sampah?`)) {
                        const fd = new FormData();
                        fd.append('id', doc.document_id);
                        
                        try {
                            const delRes = await fetchAPI('modules/documents/delete.php', {
                                method: 'POST',
                                body: fd
                            });
                            
                            if (delRes.status === 'success') {
                                showToast(delRes.message, 'success');
                                loadEmployeeDocuments(empId);
                                if (refreshNotifications) refreshNotifications();
                            }
                        } catch (e) {}
                    }
                });

                container.appendChild(card);
            });
        }
    } catch (e) {
        container.innerHTML = '<div class="empty-state text-error"><span class="material-symbols-outlined">error</span><p>Gagal memuat berkas.</p></div>';
    }
}

/**
 * Mengatur aksi tombol pratinjau dokumen di sidebar kanan.
 */
document.getElementById('btn-full-preview').addEventListener('click', () => {
    const selectedDocCard = document.querySelector('.doc-card.selected-doc');
    if (selectedDocCard) {
        const filePath = selectedDocCard.getAttribute('data-file-path');
        const title = selectedDocCard.getAttribute('data-title');
        const meta = selectedDocCard.getAttribute('data-meta');
        openPDFPreview(filePath, title, meta);
    } else {
        showToast('Pilih salah satu dokumen terlebih dahulu.', 'warning');
    }
});

function resetDetailsPanel() {
    selectedEmployeeId = null;
    originalEmployeeData = null;
    document.getElementById('btn-save-biodata').disabled = true;
    document.getElementById('btn-discard-biodata').disabled = true;
    document.getElementById('btn-add-document').style.display = 'none';
    document.getElementById('employee-files-footer').style.display = 'none';
    
    document.getElementById('employee-details-body').innerHTML = `
        <div class="empty-state">
            <span class="material-symbols-outlined text-4xl text-on-surface-variant">person_search</span>
            <p class="mt-2">Pilih salah satu pegawai dari daftar sebelah kiri untuk mengelola biodata dan dokumen.</p>
        </div>
    `;
    document.getElementById('employee-files-container').innerHTML = `<div class="empty-state">Pilih pegawai terlebih dahulu.</div>`;
    
    // Matikan kedua panel dan sinkronkan agar kembali ke full-width list
    detailPanelOn = false;
    filesPanelOn  = false;
    syncPanelState();
}

/**
 * ==========================================================================
 * 4. KELOLA MODAL (PEGAWAI & DOKUMEN)
 * ==========================================================================
 */
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('hidden');
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('hidden');
    }
}

// Inisialisasi Modal Tambah Pegawai Baru
function initEmployeeModal() {
    const addBtn = document.getElementById('btn-add-employee');
    const form = document.getElementById('employee-form');
    
    addBtn.addEventListener('click', () => {
        form.reset();
        document.getElementById('employee-modal-title').innerText = 'Tambah Pegawai Baru';
        openModal('employee-modal');
    });

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(form);

        try {
            const res = await fetchAPI('modules/employees/save.php', {
                method: 'POST',
                body: formData
            });

            if (res.status === 'success') {
                showToast(res.message, 'success');
                closeModal('employee-modal');
                loadEmployeeList(); // Refresh list pegawai
            } else {
                showToast(res.message || 'Gagal menyimpan pegawai.', 'error');
            }
        } catch (err) {}
    });
}

// Inisialisasi Modal Unggah Dokumen Baru
function initDocumentUpload() {
    const btnAdd = document.getElementById('btn-add-document');
    const form = document.getElementById('upload-document-form');
    const selectDocType = document.getElementById('upload-doc-type');
    const customDocGroup = document.getElementById('custom-doc-type-group');
    const customDocInput = document.getElementById('custom-doc-type');

    btnAdd.addEventListener('click', () => {
        form.reset();
        document.getElementById('upload-emp-id').value = selectedEmployeeId;
        customDocGroup.classList.add('hidden');
        customDocInput.removeAttribute('required');
        openModal('upload-document-modal');
    });

    const startDateInput = document.getElementById('upload-start-date');
    const expiredDateInput = document.getElementById('upload-expired-date');
    const startLabel = document.getElementById('upload-start-date-label');
    const expiredLabel = document.getElementById('upload-expired-date-label');

    // Helper untuk menghitung tanggal kedaluwarsa secara otomatis
    function calculateExpiration() {
        const docType = selectDocType.value;
        const startDateVal = startDateInput.value;
        
        if (!startDateVal) return;
        if (!['SIP', 'KGB', 'Kenaikan Pangkat'].includes(docType)) return;

        try {
            const date = new Date(startDateVal);
            if (isNaN(date.getTime())) return;

            if (docType === 'SIP') {
                date.setFullYear(date.getFullYear() + 5);
            } else if (docType === 'KGB') {
                date.setFullYear(date.getFullYear() + 2);
            } else if (docType === 'Kenaikan Pangkat') {
                date.setFullYear(date.getFullYear() + 4);
            }

            const yyyy = date.getFullYear();
            const mm = String(date.getMonth() + 1).padStart(2, '0');
            const dd = String(date.getDate()).padStart(2, '0');
            
            expiredDateInput.value = `${yyyy}-${mm}-${dd}`;
        } catch (e) {}
    }

    if (startDateInput) {
        startDateInput.addEventListener('change', calculateExpiration);
        startDateInput.addEventListener('input', calculateExpiration);
    }

    // Toggle input kustom jika memilih "Tambah Jenis Dokumen Kustom"
    selectDocType.addEventListener('change', () => {
        if (selectDocType.value === 'KUSTOM_LAIN') {
            customDocGroup.classList.remove('hidden');
            customDocInput.setAttribute('required', 'required');
            customDocInput.focus();
        } else {
            customDocGroup.classList.add('hidden');
            customDocInput.removeAttribute('required');
        }

        // Penyesuaian keterangan input tanggal
        if (['SIP', 'KGB', 'Kenaikan Pangkat'].includes(selectDocType.value)) {
            if (startLabel) startLabel.innerText = 'Tanggal Terbit (Wajib)';
            if (startDateInput) startDateInput.setAttribute('required', 'required');
            if (expiredLabel) expiredLabel.innerText = 'Tanggal Kedaluwarsa (Otomatis)';
            if (expiredDateInput) expiredDateInput.setAttribute('required', 'required');
            calculateExpiration();
        } else if (selectDocType.value === 'STR') {
            if (startLabel) startLabel.innerText = 'Tanggal Terbit (Opsional)';
            if (startDateInput) startDateInput.removeAttribute('required');
            if (expiredLabel) expiredLabel.innerText = 'Tanggal Kedaluwarsa (Kosongkan jika Seumur Hidup)';
            if (expiredDateInput) expiredDateInput.removeAttribute('required');
        } else {
            if (startLabel) startLabel.innerText = 'Tanggal Terbit (Opsional)';
            if (startDateInput) startDateInput.removeAttribute('required');
            if (expiredLabel) expiredLabel.innerText = 'Tanggal Kedaluwarsa (Opsional)';
            if (expiredDateInput) expiredDateInput.removeAttribute('required');
        }
    });

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const fileInput = document.getElementById('upload-file');
        if (fileInput.files.length === 0) {
            showToast('Silakan pilih berkas PDF terlebih dahulu.', 'warning');
            return;
        }

        const file = fileInput.files[0];
        
        // Validasi Ekstensi & Ukuran di sisi klien
        if (!file.name.toLowerCase().endsWith('.pdf')) {
            showToast('Tipe berkas tidak didukung. Harus berformat PDF.', 'error');
            return;
        }
        
        if (file.size > 10 * 1024 * 1024) {
            showToast('Ukuran berkas melebihi batasan 10 MB.', 'error');
            return;
        }

        const formData = new FormData(form);
        showToast('Sedang mengunggah berkas...', 'warning');

        try {
            const res = await fetchAPI('modules/documents/upload.php', {
                method: 'POST',
                body: formData
            });

            if (res.status === 'success') {
                showToast(res.message, 'success');
                closeModal('upload-document-modal');
                // Refresh list dokumen pegawai
                loadEmployeeDocuments(selectedEmployeeId);
                // Refresh notifikasi lonceng
                if (refreshNotifications) refreshNotifications();
            } else {
                showToast(res.message || 'Gagal mengunggah berkas.', 'error');
            }
        } catch (e) {}
    });
}

/**
 * ==========================================================================
 * 5. MANAJEMEN RIWAYAT VERSI DOKUMEN (VERSIONING HISTORY)
 * ==========================================================================
 */
async function openVersionHistory(docId, docType) {
    const subtitle = document.getElementById('version-history-subtitle');
    const container = document.getElementById('version-timeline-container');
    
    subtitle.innerText = `Kategori: ${docType}`;
    container.innerHTML = '<div class="empty-state"><div class="spinner"></div><p>Memuat riwayat versi...</p></div>';
    
    openModal('version-history-modal');

    try {
        const res = await fetchAPI(`modules/documents/get.php?document_id=${docId}`);
        if (res.status === 'success') {
            container.innerHTML = '';
            const versions = res.data;

            versions.forEach((ver, index) => {
                const isLatest = index === 0; // Versi teratas di sorted array
                
                const item = document.createElement('div');
                item.className = `timeline-item ${isLatest ? 'active-version' : ''}`;
                
                item.innerHTML = `
                    <div class="timeline-marker"></div>
                    <div class="timeline-content">
                        <div>
                            <div class="timeline-title">Versi ${ver.version_number} ${isLatest ? '<span class="text-xs text-success" style="font-weight:700; margin-left:8px;">[AKTIF]</span>' : ''}</div>
                            <p class="timeline-meta">No. Dokumen: ${escapeHTML(ver.document_number || '-')}</p>
                            <p class="timeline-meta">Tanggal Terbit: ${ver.start_date ? formatDateOnly(ver.start_date) : '-'}</p>
                            <p class="timeline-meta">Tanggal Kedaluwarsa: ${ver.expired_date ? formatDateOnly(ver.expired_date) : 'Seumur Hidup'}</p>
                            <p class="timeline-meta">Diunggah: ${formatDateFull(ver.created_at)} oleh ${escapeHTML(ver.uploader_name || 'System')}</p>
                        </div>
                        <div class="flex gap-2">
                            <!-- Preview PDF Versi Ini -->
                            <button type="button" class="btn btn-primary-container btn-sm btn-prev-ver" title="Lihat Berkas Versi Ini">
                                <span class="material-symbols-outlined text-sm">visibility</span>
                            </button>
                            <!-- Tombol Restore (Sembunyikan jika sudah aktif/terbaru) -->
                            ${!isLatest ? `
                                <button type="button" class="btn btn-secondary btn-sm btn-restore-ver" data-version-id="${ver.id}">
                                    <span class="material-symbols-outlined text-sm">settings_backup_restore</span>
                                    <span>Restore</span>
                                </button>
                            ` : ''}
                        </div>
                    </div>
                `;

                // Handler: Preview PDF Versi lama
                item.querySelector('.btn-prev-ver').addEventListener('click', () => {
                    openPDFPreview(ver.file_path, `${docType} (Versi ${ver.version_number})`, `Diunggah: ${formatDateOnly(ver.created_at)} oleh ${ver.uploader_name}`);
                });

                // Handler: Restore Versi Lama
                const restoreBtn = item.querySelector('.btn-restore-ver');
                if (restoreBtn) {
                    restoreBtn.addEventListener('click', async () => {
                        const versionId = restoreBtn.getAttribute('data-version-id');
                        if (confirm(`Apakah Anda yakin ingin memulihkan Versi ${ver.version_number} menjadi versi aktif paling baru?`)) {
                            const fd = new FormData();
                            fd.append('version_id', versionId);
                            
                            try {
                                const restRes = await fetchAPI('modules/documents/restore.php', {
                                    method: 'POST',
                                    body: fd
                                });
                                
                                if (restRes.status === 'success') {
                                    showToast(restRes.message, 'success');
                                    closeModal('version-history-modal');
                                    loadEmployeeDocuments(selectedEmployeeId);
                                    if (refreshNotifications) refreshNotifications();
                                }
                            } catch (e) {}
                        }
                    });
                }

                container.appendChild(item);
            });
        }
    } catch (e) {
        container.innerHTML = '<div class="empty-state text-error"><span class="material-symbols-outlined">error</span><p>Gagal memuat riwayat versi.</p></div>';
    }
}

/**
 * ==========================================================================
 * 6. INTEGRASI PDF.JS VIEW MODAL (RENDERING PDF LOKAL)
 * ==========================================================================
 */
function openPDFPreview(filePath, title, meta) {
    document.getElementById('preview-doc-title').innerText = title;
    document.getElementById('preview-doc-meta').innerText = meta;
    
    // Set link download berkas fisik via proxy
    const dlBtn = document.getElementById('btn-download-pdf');
    dlBtn.href = getFileURL(filePath, true);

    // Reset zoom ke default
    pdfScale = 1.2;
    document.getElementById('zoom-percentage').innerText = '100%';

    openModal('preview-modal');
    loadPDFDocument(getFileURL(filePath));
}

function loadPDFDocument(url) {
    const loadingDiv = document.getElementById('pdf-loading');
    loadingDiv.classList.remove('hidden');
    loadingDiv.innerHTML = '<div class="spinner"></div><p>Memuat Dokumen PDF...</p>';
    
    if (pdfCanvas) {
        pdfCtx.clearRect(0, 0, pdfCanvas.width, pdfCanvas.height);
    }

    pageNum = 1;
    pdfDoc = null;

    // Dapatkan data PDF via CDN Worker yang diatur di footer
    pdfjsLib.getDocument(url).promise.then(pdfDoc_ => {
        pdfDoc = pdfDoc_;
        loadingDiv.classList.add('hidden');
        
        // Update page indicator
        updatePageIndicator();
        
        renderPDFPage(pageNum);
    }).catch(err => {
        console.error('Gagal me-render PDF.js:', err);
        loadingDiv.innerHTML = `
            <span class="material-symbols-outlined text-error" style="font-size:32px;">error</span>
            <p>Gagal memuat berkas PDF. Silakan unduh file secara langsung.</p>
        `;
    });
}

function renderPDFPage(num) {
    pageRendering = true;
    
    pdfDoc.getPage(num).then(page => {
        const viewport = page.getViewport({ scale: pdfScale });
        pdfCanvas.height = viewport.height;
        pdfCanvas.width = viewport.width;

        const renderContext = {
            canvasContext: pdfCtx,
            viewport: viewport
        };
        
        const renderTask = page.render(renderContext);

        renderTask.promise.then(() => {
            pageRendering = false;
            if (pageNumPending !== null) {
                renderPDFPage(pageNumPending);
                pageNumPending = null;
            }
        });
    });
}

/**
 * Update indikator halaman PDF ("Halaman 1 / 5")
 */
function updatePageIndicator() {
    const indicator = document.getElementById('pdf-page-indicator');
    if (indicator && pdfDoc) {
        indicator.innerText = `Halaman ${pageNum} / ${pdfDoc.numPages}`;
    }
    
    // Toggle tombol prev/next
    const prevBtn = document.getElementById('btn-pdf-prev');
    const nextBtn = document.getElementById('btn-pdf-next');
    if (prevBtn) prevBtn.disabled = (pageNum <= 1);
    if (nextBtn) nextBtn.disabled = (!pdfDoc || pageNum >= pdfDoc.numPages);
}

/**
 * Navigasi ke halaman PDF berikutnya atau sebelumnya
 */
function goToPDFPage(direction) {
    if (!pdfDoc) return;
    
    if (direction === 'prev' && pageNum > 1) {
        pageNum--;
    } else if (direction === 'next' && pageNum < pdfDoc.numPages) {
        pageNum++;
    } else {
        return;
    }
    
    updatePageIndicator();
    
    if (pageRendering) {
        pageNumPending = pageNum;
    } else {
        renderPDFPage(pageNum);
    }
}

function initPDFZoomControls() {
    const zoomIn = document.getElementById('btn-zoom-in');
    const zoomOut = document.getElementById('btn-zoom-out');
    const pctLabel = document.getElementById('zoom-percentage');

    zoomIn.addEventListener('click', () => {
        if (!pdfDoc || pdfScale >= 3.0) return;
        pdfScale += 0.2;
        pctLabel.innerText = `${Math.round(pdfScale * 100 / 1.2)}%`;
        renderPDFPage(pageNum);
    });

    zoomOut.addEventListener('click', () => {
        if (!pdfDoc || pdfScale <= 0.6) return;
        pdfScale -= 0.2;
        pctLabel.innerText = `${Math.round(pdfScale * 100 / 1.2)}%`;
        renderPDFPage(pageNum);
    });
    
    // PDF Page Navigation
    const prevBtn = document.getElementById('btn-pdf-prev');
    const nextBtn = document.getElementById('btn-pdf-next');
    
    if (prevBtn) {
        prevBtn.addEventListener('click', () => goToPDFPage('prev'));
    }
    if (nextBtn) {
        nextBtn.addEventListener('click', () => goToPDFPage('next'));
    }
}

/**
 * ==========================================================================
 * 7. MODUL TEMPAT SAMPAH (TRASH BIN)
 * ==========================================================================
 */
async function loadTrashData() {
    const empTbody = document.getElementById('trash-employees-tbody');
    const docTbody = document.getElementById('trash-documents-tbody');

    empTbody.innerHTML = '<tr><td colspan="5" class="text-center py-4"><div class="spinner" style="margin: 0 auto;"></div></td></tr>';
    docTbody.innerHTML = '<tr><td colspan="4" class="text-center py-4"><div class="spinner" style="margin: 0 auto;"></div></td></tr>';

    try {
        const res = await fetchAPI('modules/trash/get.php');
        if (res.status === 'success') {
            const data = res.data;

            // Render Pegawai Terhapus
            empTbody.innerHTML = '';
            if (data.employees.length === 0) {
                empTbody.innerHTML = '<tr><td colspan="5" class="text-center py-4 text-on-surface-variant">Tempat sampah pegawai kosong.</td></tr>';
            } else {
                data.employees.forEach(emp => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td><strong>${escapeHTML(emp.name)}</strong></td>
                        <td>${escapeHTML(emp.nip)}</td>
                        <td><span class="employee-card-badge">${escapeHTML(emp.employee_type)}</span></td>
                        <td>${formatDateFull(emp.deleted_at)}</td>
                        <td class="trash-actions-cell">
                            <button type="button" class="btn btn-primary-container btn-sm btn-restore" title="Pulihkan Pegawai">
                                <span class="material-symbols-outlined text-sm">restore</span>
                                <span>Pulihkan</span>
                            </button>
                            <button type="button" class="btn btn-text text-error btn-sm btn-delete-permanent" title="Hapus Permanen">
                                <span class="material-symbols-outlined text-sm">delete_forever</span>
                                <span>Hapus Permanen</span>
                            </button>
                        </td>
                    `;

                    // Action Pulihkan Pegawai
                    tr.querySelector('.btn-restore').addEventListener('click', () => {
                        restoreTrashItem('employee', emp.id);
                    });

                    // Action Hapus Permanen Pegawai
                    tr.querySelector('.btn-delete-permanent').addEventListener('click', () => {
                        forceDeleteTrashItem('employee', emp.id, emp.name);
                    });

                    empTbody.appendChild(tr);
                });
            }

            // Render Dokumen Terhapus
            docTbody.innerHTML = '';
            if (data.documents.length === 0) {
                docTbody.innerHTML = '<tr><td colspan="4" class="text-center py-4 text-on-surface-variant">Tempat sampah kategori dokumen kosong.</td></tr>';
            } else {
                data.documents.forEach(doc => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td>${escapeHTML(doc.employee_name)} (NIP: ${escapeHTML(doc.employee_nip)})</td>
                        <td><strong>${escapeHTML(doc.document_type)}</strong></td>
                        <td>${formatDateFull(doc.deleted_at)}</td>
                        <td class="trash-actions-cell">
                            <button type="button" class="btn btn-primary-container btn-sm btn-restore" title="Pulihkan Kategori Dokumen">
                                <span class="material-symbols-outlined text-sm">restore</span>
                                <span>Pulihkan</span>
                            </button>
                            <button type="button" class="btn btn-text text-error btn-sm btn-delete-permanent" title="Hapus Permanen">
                                <span class="material-symbols-outlined text-sm">delete_forever</span>
                                <span>Hapus Permanen</span>
                            </button>
                        </td>
                    `;

                    // Action Pulihkan Dokumen
                    tr.querySelector('.btn-restore').addEventListener('click', () => {
                        restoreTrashItem('document', doc.document_id);
                    });

                    // Action Hapus Permanen Dokumen
                    tr.querySelector('.btn-delete-permanent').addEventListener('click', () => {
                        forceDeleteTrashItem('document', doc.document_id, `${doc.document_type} (Milik ${doc.employee_name})`);
                    });

                    docTbody.appendChild(tr);
                });
            }
        }
    } catch (e) {
        empTbody.innerHTML = '<tr><td colspan="5" class="text-center py-4 text-error">Gagal memuat data pegawai.</td></tr>';
        docTbody.innerHTML = '<tr><td colspan="4" class="text-center py-4 text-error">Gagal memuat data dokumen.</td></tr>';
    }
}

async function restoreTrashItem(type, id) {
    const fd = new FormData();
    fd.append('type', type);
    fd.append('id', id);

    try {
        const res = await fetchAPI('modules/trash/restore.php', {
            method: 'POST',
            body: fd
        });

        if (res.status === 'success') {
            showToast(res.message, 'success');
            loadTrashData(); // Reload table
        }
    } catch (e) {}
}

async function forceDeleteTrashItem(type, id, name) {
    const promptMsg = type === 'employee' 
        ? `PERINGATAN KRITIS: Anda akan menghapus pegawai "${name}" secara PERMANEN.\nSeluruh data biodata, kategori dokumen, riwayat versi, serta file fisik PDF di server akan DIHAPUS dan TIDAK DAPAT DIKEMBALIKAN.\n\nApakah Anda yakin?`
        : `PERINGATAN KRITIS: Anda akan menghapus dokumen "${name}" secara PERMANEN.\nSeluruh data versi dokumen serta file fisik PDF di server akan DIHAPUS dan TIDAK DAPAT DIKEMBALIKAN.\n\nApakah Anda yakin?`;

    if (confirm(promptMsg)) {
        const fd = new FormData();
        fd.append('type', type);
        fd.append('id', id);

        try {
            const res = await fetchAPI('modules/trash/force_delete.php', {
                method: 'POST',
                body: fd
            });

            if (res.status === 'success') {
                showToast(res.message, 'success');
                loadTrashData(); // Reload table
            }
        } catch (e) {}
    }
}

/**
 * ==========================================================================
 * 8. PENCARIAN GLOBAL INTEGRASI & HELPER
 * ==========================================================================
 */
function initGlobalSearch() {
    const globalInput = document.getElementById('global-search');
    
    globalInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            const q = globalInput.value.trim();
            if (q !== '') {
                // Alihkan ke Personnel Hub
                switchView('employees');
                // Set nilai di filter list pegawai dan trigger filter
                const empSearchInput = document.getElementById('employee-search');
                if (empSearchInput) {
                    empSearchInput.value = q;
                    // Reset filter pil "Semua"
                    const pills = document.querySelectorAll('#employee-type-filters .pill');
                    pills.forEach(p => p.classList.remove('active'));
                    const allPill = document.querySelector('#employee-type-filters .pill[data-type="ALL"]');
                    if (allPill) allPill.classList.add('active');
                    
                    loadEmployeeList(q, 'ALL');
                }
            }
        }
    });
}

/**
 * Tampilkan Toast alert dinamis.
 */
function showToast(message, type = 'success') {
    const toast = document.getElementById('toast');
    const toastMessage = document.getElementById('toast-message');
    const toastIcon = document.getElementById('toast-icon');

    // Reset kelas
    toast.className = 'toast';
    
    // Tentukan warna kelas dan ikon berdasarkan tipe status
    if (type === 'success') {
        toast.classList.add('success');
        toastIcon.innerText = 'check_circle';
    } else if (type === 'error') {
        toast.classList.add('error');
        toastIcon.innerText = 'cancel';
    } else if (type === 'warning') {
        toast.classList.add('warning');
        toastIcon.innerText = 'info_outline';
    }

    toastMessage.innerText = message;
    
    // Tampilkan toast
    toast.classList.add('show-toast');

    // Sembunyikan setelah 3 detik
    setTimeout(() => {
        toast.classList.remove('show-toast');
    }, 3000);
}

/**
 * escapeHTML untuk mencegah serangan XSS di sisi klien.
 */
function escapeHTML(str) {
    if (!str) return '';
    return str.replace(/[&<>'"]/g, 
        tag => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            "'": '&#39;',
            '"': '&quot;'
        }[tag] || tag)
    );
}

/**
 * Format tanggal ringkas (Y-m-d) -> (12 Jan 2026)
 */
function formatDateOnly(dateStr) {
    if (!dateStr) return '';
    const date = new Date(dateStr);
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    return `${date.getDate()} ${months[date.getMonth()]} ${date.getFullYear()}`;
}

/**
 * Format tanggal lengkap dengan waktu (Y-m-d H:i:s) -> (12 Jan 2026 14:30)
 */
function formatDateFull(dateTimeStr) {
    if (!dateTimeStr) return '';
    const date = new Date(dateTimeStr.replace(/-/g, "/")); // format safari friendly
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    const pad = (n) => n < 10 ? '0' + n : n;
    return `${date.getDate()} ${months[date.getMonth()]} ${date.getFullYear()} ${pad(date.getHours())}:${pad(date.getMinutes())}`;
}

/**
 * Konversi path file ke URL proxy yang terautentikasi.
 * Mencegah akses langsung ke uploads/ yang sudah diblokir .htaccess.
 */
function getFileURL(filePath, download = false) {
    let url = `modules/documents/serve_file.php?path=${encodeURIComponent(filePath)}`;
    if (download) url += '&download=1';
    return url;
}

/**
 * Inisialisasi Fitur Notifikasi Masa Berlaku Dokumen Pegawai
 */
function initNotifications() {
    const btnBell = document.getElementById('btn-notifications');
    const dropdownBox = document.getElementById('notification-dropdown-box');
    const badge = document.getElementById('notification-badge-count');
    const listContainer = document.getElementById('notification-items-list');
    const btnRefresh = document.getElementById('btn-refresh-notif');

    if (!btnBell || !dropdownBox) return;

    // Toggle Dropdown
    btnBell.addEventListener('click', (e) => {
        e.stopPropagation();
        dropdownBox.classList.toggle('hidden');
    });

    // Close Dropdown when clicking outside
    document.addEventListener('click', (e) => {
        if (!e.target.closest('#bell-container')) {
            dropdownBox.classList.add('hidden');
        }
    });

    // Refresh button
    if (btnRefresh) {
        btnRefresh.addEventListener('click', (e) => {
            e.stopPropagation();
            fetchNotifications();
        });
    }

    // Load notifications on init
    fetchNotifications();

    async function fetchNotifications() {
        if (listContainer) {
            listContainer.innerHTML = '<div class="empty-state-notif"><div class="spinner" style="margin: 0 auto; width: 20px; height: 20px;"></div></div>';
        }

        try {
            const res = await fetchAPI('modules/notifications/get.php');
            if (res.status === 'success') {
                const count = res.count;
                
                // Set Badge Count
                if (count > 0) {
                    badge.innerText = count;
                    badge.classList.remove('hidden');
                } else {
                    badge.classList.add('hidden');
                }

                if (count === 0) {
                    listContainer.innerHTML = '<div class="empty-state-notif">Tidak ada notifikasi aktif. Semua berkas aman.</div>';
                    return;
                }

                listContainer.innerHTML = '';
                res.data.forEach(item => {
                    const div = document.createElement('div');
                    div.className = 'notif-item';
                    
                    const iconType = item.status === 'Expired' ? 'expired' : 'warning';
                    const iconName = item.status === 'Expired' ? 'cancel' : 'warning';

                    div.innerHTML = `
                        <div class="notif-icon-box ${iconType}">
                            <span class="material-symbols-outlined">${iconName}</span>
                        </div>
                        <div class="notif-content">
                            <div class="notif-title">${escapeHTML(item.employee_name)}</div>
                            <div class="notif-desc">${escapeHTML(item.message)}</div>
                            <div class="notif-date">Kadaluwarsa: ${formatDateOnly(item.expired_date)}</div>
                        </div>
                    `;

                    // Klik item notifikasi -> arahkan ke halaman pegawai dan aktifkan panel berkas
                    div.addEventListener('click', () => {
                        dropdownBox.classList.add('hidden');
                        
                        // 1. Pindah view ke Pegawai
                        switchView('employees');
                        
                        // 2. Pilih pegawai & aktifkan panel files secara otomatis
                        document.querySelectorAll('.employee-card').forEach(c => {
                            c.classList.remove('active-selection');
                            c.querySelectorAll('.card-action-btn').forEach(b => b.classList.remove('btn-active'));
                        });
                        
                        const targetCard = document.querySelector(`.employee-card[data-id="${item.employee_id}"]`);
                        if (targetCard) {
                            targetCard.classList.add('active-selection');
                            const btnF = targetCard.querySelector('.btn-card-files');
                            if (btnF) btnF.classList.add('btn-active');
                        }

                        // Nyalakan panel Berkas saja agar user fokus ke berkas bermasalah tersebut
                        detailPanelOn = false;
                        filesPanelOn = true;
                        selectedEmployeeId = item.employee_id;
                        syncPanelState();
                        loadEmployeePanelData(item.employee_id);
                        
                        // Scroll ke kartu pegawai agar keliatan di layar
                        if (targetCard) {
                            targetCard.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                        }
                    });

                    listContainer.appendChild(div);
                });
            }
        } catch (e) {
            listContainer.innerHTML = '<div class="empty-state-notif text-error">Gagal memuat notifikasi.</div>';
        }
    }

    // Simpan ke reference global agar bisa di-call dari success handler mana saja
    refreshNotifications = fetchNotifications;
}
