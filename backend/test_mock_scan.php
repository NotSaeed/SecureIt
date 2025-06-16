<?php
// Test the enhanced UI with mock detailed scan results
session_start();
$_SESSION['user_id'] = 1;

// Simulate detailed scan results
$mockResult = [
    'success' => true,
    'positives' => 2,
    'total' => 70,
    'threat_level' => 'medium',
    'scan_date' => '2025-06-16 12:00:00',
    'url' => 'https://test-malware-site.com',
    'verbose_msg' => 'Scan completed successfully',
    'permalink' => '#',
    'scan_details' => [
        'malicious' => [
            [
                'engine' => 'Kaspersky',
                'result' => 'Trojan.Generic.Malware',
                'version' => '15.0.1.13',
                'update' => '20250616'
            ],
            [
                'engine' => 'Symantec',
                'result' => 'Suspicious.Cloud.7',
                'version' => '12.5.0.1',
                'update' => '20250616'
            ]
        ],
        'suspicious' => [],
        'clean' => [
            [
                'engine' => 'Microsoft',
                'result' => 'Clean',
                'version' => '1.1.24060.3',
                'update' => '20250616'
            ],
            [
                'engine' => 'Avast',
                'result' => 'Clean',
                'version' => '23.11.8700.0',
                'update' => '20250616'
            ],
            [
                'engine' => 'BitDefender',
                'result' => 'Clean',
                'version' => '7.2',
                'update' => '20250616'
            ],
            [
                'engine' => 'ESET-NOD32',
                'result' => 'Clean',
                'version' => '25432',
                'update' => '20250616'
            ],
            [
                'engine' => 'F-Secure',
                'result' => 'Clean',
                'version' => '18.10.1547.307',
                'update' => '20250616'
            ]
        ]
    ]
];

// Output as JSON for AJAX testing
header('Content-Type: application/json');
echo json_encode($mockResult, JSON_PRETTY_PRINT);
?>
