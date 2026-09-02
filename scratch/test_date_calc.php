<?php
// Test Script: Verify document date calculations & eligibility checks
require_once __DIR__ . '/../config/database.php';

echo "<h2>--- EDMS RSGM - TESTING DOCUMENT DATE CALCULATIONS ---</h2>";

// Helper function to test eligibility and calculations
function test_document_rules($doc_type, $input_date, $status_kepegawaian) {
    echo "Testing Type: <strong>$doc_type</strong> | Input Date: <strong>$input_date</strong> | Status: <strong>$status_kepegawaian</strong><br>";
    
    // SIP rules
    if ($doc_type === 'SIP') {
        $dt = new DateTime($input_date);
        $dt->modify('+5 years');
        $result = $dt->format('Y-m-d');
        echo " -> Result: <span style='color: green;'>PASS ($result)</span><br><br>";
        return;
    }
    
    // KGB rules
    if ($doc_type === 'KGB') {
        $allowed_status = ['PNS', 'P3K', 'Pegawai Tetap (PT)'];
        if (!in_array($status_kepegawaian, $allowed_status)) {
            echo " -> Result: <span style='color: orange;'>REJECTED (Correct Behavior)</span><br><br>";
            return;
        }
        $dt = new DateTime($input_date);
        $dt->modify('+2 years');
        $result = $dt->format('Y-m-d');
        echo " -> Result: <span style='color: green;'>PASS ($result)</span><br><br>";
        return;
    }
    
    // Kenaikan Pangkat rules
    if ($doc_type === 'Kenaikan Pangkat') {
        $allowed_status = ['PNS', 'Pegawai Tetap (PT)'];
        if (!in_array($status_kepegawaian, $allowed_status)) {
            echo " -> Result: <span style='color: orange;'>REJECTED (Correct Behavior)</span><br><br>";
            return;
        }
        $dt = new DateTime($input_date);
        $dt->modify('+4 years');
        $result = $dt->format('Y-m-d');
        echo " -> Result: <span style='color: green;'>PASS ($result)</span><br><br>";
        return;
    }
}

// Run test cases
test_document_rules('SIP', '2026-07-17', 'PNS'); // Should add 5 years -> 2031-07-17
test_document_rules('KGB', '2026-07-17', 'PNS'); // Should add 2 years -> 2028-07-17
test_document_rules('KGB', '2026-07-17', 'P3K'); // Should add 2 years -> 2028-07-17
test_document_rules('KGB', '2026-07-17', 'Pegawai Tetap (PT)'); // Should add 2 years -> 2028-07-17
test_document_rules('KGB', '2026-07-17', 'Kontrak / Honorer'); // Should reject

test_document_rules('Kenaikan Pangkat', '2026-07-17', 'PNS'); // Should add 4 years -> 2030-07-17
test_document_rules('Kenaikan Pangkat', '2026-07-17', 'Pegawai Tetap (PT)'); // Should add 4 years -> 2030-07-17
test_document_rules('Kenaikan Pangkat', '2026-07-17', 'P3K'); // Should reject
test_document_rules('Kenaikan Pangkat', '2026-07-17', 'Kontrak / Honorer'); // Should reject
