<?php
// Script seeding data pegawai medis sesuai screenshot referensi
require_once __DIR__ . '/../config/database.php';

try {
    // Kosongkan tabel employees terlebih dahulu agar bersih
    // (Akan otomatis men-cascade hapus documents jika ada relasi)
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
    $pdo->exec("TRUNCATE TABLE employees;");
    $pdo->exec("TRUNCATE TABLE documents;");
    $pdo->exec("TRUNCATE TABLE document_versions;");
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

    $employees = [
        [
            'nip' => '198001012010121001',
            'name' => 'Fajarin Nova',
            'gelar' => 'drg., Sp.KG.',
            'employee_type' => 'Dokter Gigi Spesialis',
            'status_kepegawaian' => 'PNS'
        ],
        [
            'nip' => '198001012010121002',
            'name' => 'Diah Savitri E.',
            'gelar' => 'Prof. Dr., drg., M.Si., Sp.PM(K)',
            'employee_type' => 'Dokter Gigi Spesialis',
            'status_kepegawaian' => 'PNS'
        ],
        [
            'nip' => '198001012010121003',
            'name' => 'Adiastuti Endah P',
            'gelar' => 'drg., M.Kes., Sp.PM',
            'employee_type' => 'Dokter Gigi Spesialis',
            'status_kepegawaian' => 'P3K'
        ],
        [
            'nip' => '198001012010121004',
            'name' => 'Desiana Radithia',
            'gelar' => 'Dr., drg., Sp.PM(K)',
            'employee_type' => 'Dokter Gigi Spesialis',
            'status_kepegawaian' => 'Pegawai Tetap (PT)'
        ],
        [
            'nip' => '198001012010121005',
            'name' => 'Nurina Febriyanti A',
            'gelar' => 'drg., M.Kes., Ph.D., Sp.PM',
            'employee_type' => 'Dokter Gigi',
            'status_kepegawaian' => 'Kontrak / Honorer'
        ],
        [
            'nip' => '198001012010121006',
            'name' => 'Reiska Kumala Bakti',
            'gelar' => 'drg., M.Ked.Trop',
            'employee_type' => 'Dokter Gigi',
            'status_kepegawaian' => 'Kontrak / Honorer'
        ],
        [
            'nip' => '198001012010121007',
            'name' => 'Fatma Yasmin Mahdani',
            'gelar' => 'drg., M.Kes.',
            'employee_type' => 'Dokter Gigi',
            'status_kepegawaian' => 'P3K'
        ],
        [
            'nip' => '198001012010121008',
            'name' => 'Meircurius Dwi Condro Surboyo',
            'gelar' => 'drg., Sp.PM',
            'employee_type' => 'Dokter Gigi Spesialis',
            'status_kepegawaian' => 'Pegawai Tetap (PT)'
        ],
        [
            'nip' => '198001012010121009',
            'name' => 'Adioro Soetojo',
            'gelar' => 'Prof. Dr., drg., MS., Sp.KG(K)',
            'employee_type' => 'Dokter Gigi Spesialis',
            'status_kepegawaian' => 'PNS'
        ],
        [
            'nip' => '198001012010121010',
            'name' => 'Ira Widjiastuti',
            'gelar' => 'Dr., drg., M.Kes., SpKG(K)',
            'employee_type' => 'Dokter Gigi Spesialis',
            'status_kepegawaian' => 'PNS'
        ],
        [
            'nip' => '198001012010121011',
            'name' => 'Tamara Yuanita',
            'gelar' => 'Prof. Dr., drg., MS., Sp.KG(K)',
            'employee_type' => 'Dokter Gigi Spesialis',
            'status_kepegawaian' => 'PNS'
        ],
        [
            'nip' => '198001012010121012',
            'name' => 'Ari Subiyanto',
            'gelar' => 'drg., M.Kes., Sp.KG(K)',
            'employee_type' => 'Dokter Gigi Spesialis',
            'status_kepegawaian' => 'P3K'
        ],
        [
            'nip' => '198001012010121013',
            'name' => 'Kun Ismiyatin',
            'gelar' => 'Prof. Dr., drg., M.Kes., Sp.KG(K)',
            'employee_type' => 'Dokter Gigi Spesialis',
            'status_kepegawaian' => 'Pegawai Tetap (PT)'
        ]
    ];

    $stmt = $pdo->prepare("INSERT INTO employees (nip, name, gelar, employee_type, status_kepegawaian) VALUES (?, ?, ?, ?, ?)");
    foreach ($employees as $emp) {
        $stmt->execute([
            $emp['nip'],
            $emp['name'],
            $emp['gelar'],
            $emp['employee_type'],
            $emp['status_kepegawaian']
        ]);
    }

    echo "Berhasil menginputkan 13 data pegawai medis dari screenshot ke database!\n";

} catch (\PDOException $e) {
    echo "Gagal melakukan seeding data: " . $e->getMessage() . "\n";
}
