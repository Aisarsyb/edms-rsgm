<?php
// Kontainer Utama Aplikasi (Single Page Workflow)
require_once 'config/database.php';
require_once 'includes/auth.php';

// Proteksi Sesi Login
check_auth();

// Muat Header HTML
require_once 'includes/header.php';
?>

<!-- Header Bagian Atas (Top Header) -->
<header class="top-header">
    <div class="header-left">
        <div class="logo-circle">
            <span class="material-symbols-outlined text-white">folder_managed</span>
        </div>
        <span class="app-title">EDMS RSGM <span class="app-title-bold">Unair</span></span>
    </div>
    
    <div class="header-right">
        <!-- Pencarian Global Ringkas -->
        <div class="global-search-container">
            <span class="material-symbols-outlined search-icon">search</span>
            <input type="text" id="global-search" placeholder="Cari NIP, Nama, Dokumen..." class="global-search-input">
        </div>
        
        <!-- Tombol Lonceng Notifikasi -->
        <div class="notification-bell-container" id="bell-container">
            <button class="notification-bell-btn" id="btn-notifications" title="Notifikasi Masa Berlaku Berkas">
                <span class="material-symbols-outlined">notifications</span>
                <span class="notification-badge hidden" id="notification-badge-count">0</span>
            </button>
            
            <!-- Dropdown Notifikasi -->
            <div class="notification-dropdown hidden" id="notification-dropdown-box">
                <div class="notif-header">
                    <span>Notifikasi Berkas Masa Berlaku</span>
                    <button class="btn btn-text btn-sm" id="btn-refresh-notif" style="padding:0; min-height:auto; font-weight:700;">Segarkan</button>
                </div>
                <div class="notif-body" id="notification-items-list">
                    <div class="empty-state-notif">Tidak ada notifikasi aktif.</div>
                </div>
            </div>
        </div>
        
        <!-- Info Admin & Avatar -->
        <div class="admin-profile">
            <div class="admin-info">
                <span class="admin-name">Administrator</span>
            </div>
            <div class="admin-avatar">
                AD
            </div>
        </div>
    </div>
</header>

<!-- Muat Menu Navigasi Samping -->
<?php require_once 'includes/sidebar.php'; ?>

<!-- Kontainer Utama Konten (Beralih Secara Dinamis via JS) -->
<div class="main-content-wrapper">

    <!-- ================= VIEW 1: PERSONNEL HUB (LAYOUT DINAMIS) ================= -->
    <section id="view-employees" class="content-view active-view">
        <div class="personnel-workspace" id="personnel-workspace">

            <!-- ======= PANEL KIRI: Daftar Pegawai (mulai full-width) ======= -->
            <aside class="personnel-list-panel" id="panel-list">
                <div class="panel-header">
                    <div class="flex justify-between items-start w-full">
                        <div>
                            <h2 class="panel-title">Manajemen Pegawai</h2>
                            <p class="panel-subtitle" id="employee-count-label">Memuat daftar pegawai...</p>
                        </div>
                        <button class="btn btn-secondary btn-sm" id="btn-add-employee">
                            <span class="material-symbols-outlined text-sm">person_add</span>
                            <span>Tambah Pegawai</span>
                        </button>
                    </div>

                    <!-- Search Input -->
                    <div class="search-input-wrapper">
                        <span class="material-symbols-outlined search-icon">search</span>
                        <input type="text" id="employee-search" placeholder="Cari berdasarkan NIP, Nama..." class="search-input">
                    </div>

                    <!-- Filter Tipe Pegawai -->
                    <div class="filter-pills-row" id="employee-type-filters">
                        <span class="pill active" data-type="ALL">Semua</span>
                        <span class="pill" data-type="Dokter Gigi">Dokter Gigi</span>
                        <span class="pill" data-type="Dokter Gigi Spesialis">Dokter Gigi Spesialis</span>
                        <span class="pill" data-type="Perawat">Perawat</span>
                        <span class="pill" data-type="Staf Administrasi">Staf Administrasi</span>
                    </div>
                </div>

                <!-- List Container -->
                <div class="panel-list-container" id="employee-cards-container">
                    <div class="empty-state">Memuat data pegawai...</div>
                </div>
            </aside>

            <!-- ======= RESIZE HANDLE 1 (antara List & Detail) ======= -->
            <div class="resize-handle" id="rh-1" title="Geser untuk mengubah ukuran">
                <div class="rh-dots"><span></span><span></span><span></span></div>
            </div>

            <!-- ======= PANEL TENGAH: Detail Biodata & Edit Form ======= -->
            <section class="personnel-details-panel" id="panel-details">
                <div class="details-header">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-secondary">badge</span>
                        <h2 class="panel-title">Detail Biodata</h2>
                    </div>
                    <div class="flex items-center gap-2">
                        <button class="btn btn-text" id="btn-discard-biodata" disabled>Batal</button>
                        <button class="btn btn-secondary" id="btn-save-biodata" disabled>Simpan</button>
                    </div>
                </div>

                <div class="details-body" id="employee-details-body">
                    <!-- Form detail pegawai terpilih -->
                    <div class="empty-state">
                        <span class="material-symbols-outlined text-4xl text-on-surface-variant">person_search</span>
                        <p class="mt-2">Pilih salah satu pegawai dari daftar sebelah kiri untuk mengelola biodata dan dokumen.</p>
                    </div>
                </div>
            </section>

            <!-- ======= RESIZE HANDLE 2 (antara Detail & Files) ======= -->
            <div class="resize-handle" id="rh-2" title="Geser untuk mengubah ukuran">
                <div class="rh-dots"><span></span><span></span><span></span></div>
            </div>

            <!-- ======= PANEL KANAN: Berkas Dokumen ======= -->
            <aside class="personnel-files-panel" id="panel-files">
                <div class="files-header border-b">
                    <div class="flex items-center gap-2">
                        <span class="panel-title-sm">Berkas Dokumen</span>
                    </div>
                    <button class="btn-icon" id="btn-add-document" style="display: none;" title="Unggah Dokumen Baru">
                        <span class="material-symbols-outlined" style="color: var(--secondary);">add_circle</span>
                    </button>
                </div>

                <!-- File List Container -->
                <div class="files-list-container" id="employee-files-container">
                    <div class="empty-state">Pilih pegawai terlebih dahulu.</div>
                </div>

                <div class="files-footer" id="employee-files-footer" style="display: none;">
                    <button class="btn btn-primary-container w-full" id="btn-full-preview">
                        <span class="material-symbols-outlined">zoom_in</span>
                        <span>Pratinjau Dokumen Aktif</span>
                    </button>
                </div>
            </aside>

        </div>
    </section>


    <!-- ================= VIEW 3: TEMPAT SAMPAH (TRASH BIN) ================= -->
    <section id="view-trash" class="content-view">
        <div class="trash-workspace">
            <h1 class="page-title">Tempat Sampah</h1>
            <p class="page-subtitle mb-6">Pulihkan data atau hapus data secara permanen beserta file fisik dokumennya.</p>
            
            <div class="trash-grid">
                <!-- Tabel Pegawai Terhapus -->
                <div class="trash-card bg-surface-lowest">
                    <h3 class="section-title mb-4">Pegawai Terhapus</h3>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Nama Pegawai</th>
                                    <th>NIP</th>
                                    <th>Tipe</th>
                                    <th>Tanggal Dihapus</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="trash-employees-tbody">
                                <tr>
                                    <td colspan="5" class="text-center py-4">Memuat data pegawai di tempat sampah...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Tabel Dokumen Terhapus -->
                <div class="trash-card bg-surface-lowest">
                    <h3 class="section-title mb-4">Kategori Dokumen Terhapus</h3>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Pemilik Dokumen</th>
                                    <th>Jenis Dokumen</th>
                                    <th>Tanggal Dihapus</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="trash-documents-tbody">
                                <tr>
                                    <td colspan="4" class="text-center py-4">Memuat data dokumen di tempat sampah...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>

</div>

<!-- ================================= MODALS ================================= -->

<!-- 1. Modal Tambah/Edit Pegawai -->
<div class="modal-overlay hidden" id="employee-modal">
    <div class="modal-card">
        <div class="modal-header">
            <h3 class="modal-title" id="employee-modal-title">Tambah Pegawai Baru</h3>
            <button class="btn-icon" onclick="closeModal('employee-modal')">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <form id="employee-form">
            <div class="modal-body">
                <div class="form-group">
                    <label for="modal-employee-nip">NIP / NUPTK</label>
                    <input type="text" id="modal-employee-nip" name="nip" required placeholder="Contoh: 198504122010011003">
                </div>
                <div class="form-group">
                    <label for="modal-employee-name">Nama Lengkap (Tanpa Gelar)</label>
                    <input type="text" id="modal-employee-name" name="name" required placeholder="Nama lengkap sesuai Ijazah">
                </div>
                <div class="form-group">
                    <label for="modal-employee-gelar">Gelar Kepegawaian (Opsional)</label>
                    <input type="text" id="modal-employee-gelar" name="gelar" placeholder="Contoh: drg., A.Md.Kep, S.Kom">
                </div>
                <div class="form-group">
                    <label for="modal-employee-type">Jenis / Tipe Staf</label>
                    <select id="modal-employee-type" name="employee_type" required>
                        <option value="">Pilih Jenis Staf</option>
                        <option value="Dokter Gigi">Dokter Gigi</option>
                        <option value="Dokter Gigi Spesialis">Dokter Gigi Spesialis</option>
                        <option value="Perawat">Perawat</option>
                        <option value="Staf Administrasi">Staf Administrasi</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="modal-employee-status">Status Kepegawaian</label>
                    <select id="modal-employee-status" name="status_kepegawaian" required>
                        <option value="">Pilih Status Kepegawaian</option>
                        <option value="PNS">PNS</option>
                        <option value="P3K">P3K</option>
                        <option value="Pegawai Tetap (PT)">Pegawai Tetap (PT)</option>
                        <option value="Kontrak / Honorer">Kontrak / Honorer</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-text" onclick="closeModal('employee-modal')">Batal</button>
                <button type="submit" class="btn btn-secondary">Simpan Pegawai</button>
            </div>
        </form>
    </div>
</div>

<!-- 2. Modal Unggah Dokumen Baru (Dengan Versioning) -->
<div class="modal-overlay hidden" id="upload-document-modal">
    <div class="modal-card">
        <div class="modal-header">
            <h3 class="modal-title">Unggah Berkas Dokumen</h3>
            <button class="btn-icon" onclick="closeModal('upload-document-modal')">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <form id="upload-document-form" enctype="multipart/form-data">
            <input type="hidden" name="employee_id" id="upload-emp-id">
            <div class="modal-body">
                <!-- Dropdown Kategori Dokumen (STR, SIP, Ijazah, dll) -->
                <div class="form-group">
                    <label for="upload-doc-type">Jenis Kategori Dokumen</label>
                    <select id="upload-doc-type" name="document_type" required>
                        <option value="">Pilih Jenis Dokumen</option>
                        <option value="Ijazah">Ijazah</option>
                        <option value="SK Calon Pegawai">SK Calon Pegawai</option>
                        <option value="SK Pertama Pegawai">SK Pertama Pegawai</option>
                        <option value="SK Terakhir Pegawai">SK Terakhir Pegawai</option>
                        <option value="STR">STR</option>
                        <option value="SIP">SIP</option>
                        <option value="KGB">KGB (Kenaikan Gaji Berkala)</option>
                        <option value="Kenaikan Pangkat">Kenaikan Pangkat</option>
                        <option value="Sertifikat">Sertifikat</option>
                        <!-- Pilihan Tambah Kustom -->
                        <option value="KUSTOM_LAIN">+ Tambah Jenis Dokumen Kustom</option>
                    </select>
                </div>
                
                <!-- Input Kustom jika memilih Kustom -->
                <div class="form-group hidden" id="custom-doc-type-group">
                    <label for="custom-doc-type">Nama Jenis Dokumen Baru</label>
                    <input type="text" id="custom-doc-type" name="custom_document_type" placeholder="Masukkan nama kategori dokumen baru">
                </div>

                <div class="form-group">
                    <label for="upload-doc-number">Nomor Dokumen (Opsional)</label>
                    <input type="text" id="upload-doc-number" name="document_number" placeholder="Contoh: No. STR / Nomor Keputusan">
                </div>

                <div class="form-row" style="display: flex; gap: 16px;">
                    <div class="form-group" style="flex: 1;">
                        <label for="upload-start-date" id="upload-start-date-label">Tanggal Terbit (Opsional)</label>
                        <input type="date" id="upload-start-date" name="start_date">
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label for="upload-expired-date" id="upload-expired-date-label">Tanggal Kedaluwarsa (Opsional)</label>
                        <input type="date" id="upload-expired-date" name="expired_date">
                    </div>
                </div>

                <div class="form-group">
                    <label for="upload-file">Berkas PDF (Maksimal 10 MB)</label>
                    <input type="file" id="upload-file" name="file" accept="application/pdf" required>
                    <p class="input-info">Hanya file dengan ekstensi .pdf yang diperbolehkan.</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-text" onclick="closeModal('upload-document-modal')">Batal</button>
                <button type="submit" class="btn btn-secondary">Unggah Dokumen</button>
            </div>
        </form>
    </div>
</div>

<!-- 3. Modal Riwayat Versi Dokumen -->
<div class="modal-overlay hidden" id="version-history-modal">
    <div class="modal-card modal-lg">
        <div class="modal-header">
            <div>
                <h3 class="modal-title">Riwayat Versi Dokumen</h3>
                <p class="modal-subtitle" id="version-history-subtitle">-</p>
            </div>
            <button class="btn-icon" onclick="closeModal('version-history-modal')">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <div class="modal-body overflow-y-auto" style="max-height: 450px;">
            <div class="timeline" id="version-timeline-container">
                <!-- Timeline Versi Diisi via JS -->
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal('version-history-modal')">Selesai</button>
        </div>
    </div>
</div>

<!-- 4. Modal PDF Preview Terintegrasi (PDF.js Canvas Renderer) -->
<div class="modal-overlay hidden" id="preview-modal" style="z-index: 1000;">
    <div class="preview-modal-content">
        <div class="preview-header">
            <div class="flex items-center gap-3">
                <span class="material-symbols-outlined text-error">picture_as_pdf</span>
                <div>
                    <h3 class="preview-doc-title" id="preview-doc-title">-</h3>
                    <p class="preview-doc-meta" id="preview-doc-meta">-</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <!-- Zoom Controls -->
                <button class="btn-icon hover:bg-surface-container-high rounded" id="btn-zoom-out" title="Perkecil"><span class="material-symbols-outlined">zoom_out</span></button>
                <span id="zoom-percentage" class="text-xs font-semibold px-2">100%</span>
                <button class="btn-icon hover:bg-surface-container-high rounded" id="btn-zoom-in" title="Perbesar"><span class="material-symbols-outlined">zoom_in</span></button>
                
                <span class="divider"></span>
                
                <!-- Page Navigation -->
                <button class="btn-icon hover:bg-surface-container-high rounded" id="btn-pdf-prev" title="Halaman Sebelumnya" disabled><span class="material-symbols-outlined">navigate_before</span></button>
                <span id="pdf-page-indicator" class="text-xs font-semibold px-2" style="white-space: nowrap;">Halaman 1 / 1</span>
                <button class="btn-icon hover:bg-surface-container-high rounded" id="btn-pdf-next" title="Halaman Berikutnya" disabled><span class="material-symbols-outlined">navigate_next</span></button>
                
                <span class="divider"></span>
                
                <!-- Download direct file -->
                <a href="" id="btn-download-pdf" class="btn-icon hover:bg-surface-container-high rounded" download title="Unduh File PDF">
                    <span class="material-symbols-outlined">download</span>
                </a>
                <button class="btn-icon close-preview bg-error-container text-on-error-container hover:bg-error hover:text-white transition-colors" onclick="closeModal('preview-modal')">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
        </div>
        
        <div class="preview-body" id="pdf-viewer-container">
            <!-- Loading Indicator -->
            <div id="pdf-loading" class="pdf-loading">
                <div class="spinner"></div>
                <p>Memuat Dokumen PDF...</p>
            </div>
            
            <!-- Canvas PDF.js -->
            <canvas id="pdf-canvas" class="pdf-canvas"></canvas>
        </div>
    </div>
</div>

<!-- Toast Notifikasi Merah/Hijau/Kuning -->
<div id="toast" class="toast hidden">
    <span class="material-symbols-outlined toast-icon" id="toast-icon">check_circle</span>
    <span class="toast-message" id="toast-message">Pesan Berhasil</span>
</div>

<!-- Memuat Footer dan Script JS -->
<?php require_once 'includes/footer.php'; ?>
