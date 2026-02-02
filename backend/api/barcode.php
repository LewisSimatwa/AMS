<?php
// Disable HTML error display and log errors instead
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Log all incoming request details
error_log("=== BARCODE REQUEST START ===");
error_log("Request Method: " . $_SERVER['REQUEST_METHOD']);
error_log("Request URI: " . $_SERVER['REQUEST_URI']);
error_log("Query String: " . ($_SERVER['QUERY_STRING'] ?? 'none'));
error_log("GET params: " . json_encode($_GET));
error_log("Authorization header: " . ($_SERVER['HTTP_AUTHORIZATION'] ?? 'none'));

// Start output buffering to catch any stray output
ob_start();

// CORS headers - must be set before any output
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Load dependencies
$configPath = __DIR__ . '/config.php';
$helpersPath = __DIR__ . '/helpers.php';

// Debug: check if files exist
if (!file_exists($configPath)) {
    http_response_code(500);
    header("Content-Type: application/json");
    echo json_encode(["error" => "config.php not found at: " . $configPath]);
    exit;
}

if (!file_exists($helpersPath)) {
    http_response_code(500);
    header("Content-Type: application/json");
    echo json_encode(["error" => "helpers.php not found at: " . $helpersPath]);
    exit;
}

require_once $configPath;
require_once $helpersPath;

// Verify JWT token authentication
// For img tags, token might be in query parameter instead of header
try {
    // Check if token is in query parameter (for img tags)
    if (isset($_GET['token'])) {
        // Temporarily set it as Authorization header for verifyAuth to work
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $_GET['token'];
    }
    
    verifyAuth(); // This sets $_GET['user_id'], $_GET['role'], $_GET['institution_id']
} catch (Exception $e) {
    http_response_code(401);
    header("Content-Type: application/json");
    echo json_encode(["error" => "Authentication failed: " . $e->getMessage()]);
    exit;
}

// Get parameters
$assetId = $_GET['asset_id'] ?? null;
$institutionId = $_GET['institution_id'] ?? null;

// Log for debugging
error_log("Barcode request - asset_id: " . ($assetId ?? 'null') . ", institution_id: " . ($institutionId ?? 'null'));

if (!$assetId) {
    http_response_code(400);
    header("Content-Type: application/json");
    echo json_encode(["error" => "Asset ID is required"]);
    exit;
}

try {
    // Get asset details
    $stmt = $db->prepare("
        SELECT id, asset_code, serial_number, name 
        FROM assets 
        WHERE id = ? AND institution_id = ?
    ");
    $stmt->execute([$assetId, $institutionId]);
    $asset = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$asset) {
        http_response_code(404);
        header("Content-Type: application/json");
        echo json_encode(["error" => "Asset not found"]);
        exit;
    }
    
    // Use serial_number if available, otherwise use asset_code
    $barcodeData = !empty($asset['serial_number']) ? $asset['serial_number'] : $asset['asset_code'];
    
    error_log("Generating barcode for: " . $barcodeData);
    
    // Generate barcode SVG
    $barcodeSvg = generateCode128Barcode($barcodeData);
    
    error_log("Barcode generated successfully, length: " . strlen($barcodeSvg));
    
    // Clear any buffered output before sending SVG
    ob_end_clean();
    
    // Set headers for SVG response
    header('Content-Type: image/svg+xml');
    header('Content-Disposition: inline; filename="barcode_' . $asset['asset_code'] . '.svg"');
    header('Cache-Control: public, max-age=3600');
    
    error_log("Sending SVG response");
    
    echo $barcodeSvg;
    exit;
    
} catch (PDOException $e) {
    error_log('Barcode generation error: ' . $e->getMessage());
    http_response_code(500);
    header("Content-Type: application/json");
    echo json_encode(["error" => "Failed to generate barcode: " . $e->getMessage()]);
    exit;
} catch (Exception $e) {
    error_log('Barcode generation error: ' . $e->getMessage());
    http_response_code(500);
    header("Content-Type: application/json");
    echo json_encode(["error" => "Error: " . $e->getMessage()]);
    exit;
}

/**
 * Generate Code 128 barcode as SVG
 * @param string $data The data to encode in the barcode
 * @return string SVG markup for the barcode
 */
function generateCode128Barcode($data) {
    // Code 128 character set B (most common for alphanumeric)
    $code128 = [
        ' ' => '11011001100', '!' => '11001101100', '"' => '11001100110',
        '#' => '10010011000', '$' => '10010001100', '%' => '10001001100',
        '&' => '10011001000', "'" => '10011000100', '(' => '10001100100',
        ')' => '11001001000', '*' => '11001000100', '+' => '11000100100',
        ',' => '10110011100', '-' => '10011011100', '.' => '10011001110',
        '/' => '10111001100', '0' => '10011101100', '1' => '10011100110',
        '2' => '11001110010', '3' => '11001011100', '4' => '11001001110',
        '5' => '11011100100', '6' => '11001110100', '7' => '11101101110',
        '8' => '11101001100', '9' => '11100101100', ':' => '11100100110',
        ';' => '11101100100', '<' => '11100110100', '=' => '11100110010',
        '>' => '11011011000', '?' => '11011000110', '@' => '11000110110',
        'A' => '10100011000', 'B' => '10001011000', 'C' => '10001000110',
        'D' => '10110001000', 'E' => '10001101000', 'F' => '10001100010',
        'G' => '11010001000', 'H' => '11000101000', 'I' => '11000100010',
        'J' => '10110111000', 'K' => '10110001110', 'L' => '10001101110',
        'M' => '10111011000', 'N' => '10111000110', 'O' => '10001110110',
        'P' => '11101110110', 'Q' => '11010001110', 'R' => '11000101110',
        'S' => '11011101000', 'T' => '11011100010', 'U' => '11011101110',
        'V' => '11101011000', 'W' => '11101000110', 'X' => '11100010110',
        'Y' => '11101101000', 'Z' => '11101100010', '[' => '11100011010',
        '\\' => '11101111010', ']' => '11001000010', '^' => '11110001010',
        '_' => '10100110000', '`' => '10100001100', 'a' => '10010110000',
        'b' => '10010000110', 'c' => '10000101100', 'd' => '10000100110',
        'e' => '10110010000', 'f' => '10110000100', 'g' => '10011010000',
        'h' => '10011000010', 'i' => '10000110100', 'j' => '10000110010',
        'k' => '11000010010', 'l' => '11001010000', 'm' => '11110111010',
        'n' => '11000010100', 'o' => '10001111010', 'p' => '10100111100',
        'q' => '10010111100', 'r' => '10010011110', 's' => '10111100100',
        't' => '10011110100', 'u' => '10011110010', 'v' => '11110100100',
        'w' => '11110010100', 'x' => '11110010010', 'y' => '11011011110',
        'z' => '11011110110', '{' => '11110110110', '|' => '10101111000',
        '}' => '10100011110', '~' => '10001011110'
    ];
    
    // Start code for Code 128B
    $startB = '11010010000';
    $stop = '1100011101011';
    
    // Build the barcode pattern
    $pattern = $startB;
    $checksum = 104; // Start B value
    $position = 1;
    
    // Encode each character
    for ($i = 0; $i < strlen($data); $i++) {
        $char = $data[$i];
        if (isset($code128[$char])) {
            $pattern .= $code128[$char];
            // Calculate checksum (ASCII value - 32)
            $checksum += (ord($char) - 32) * $position;
            $position++;
        }
    }
    
    // Add checksum character
    $checksum = $checksum % 103;
    $checksumChar = chr($checksum + 32);
    if (isset($code128[$checksumChar])) {
        $pattern .= $code128[$checksumChar];
    }
    
    // Add stop code
    $pattern .= $stop;
    
    // Generate SVG
    $barWidth = 2;
    $width = strlen($pattern) * $barWidth;
    $height = 80;
    $textHeight = 100;
    
    $svg = '<?xml version="1.0" encoding="UTF-8"?>';
    $svg .= '<svg width="' . $width . '" height="' . $textHeight . '" xmlns="http://www.w3.org/2000/svg">';
    $svg .= '<rect width="' . $width . '" height="' . $textHeight . '" fill="white"/>';
    
    // Draw bars
    $x = 0;
    for ($i = 0; $i < strlen($pattern); $i++) {
        if ($pattern[$i] == '1') {
            $svg .= '<rect x="' . $x . '" y="0" width="' . $barWidth . '" height="' . $height . '" fill="black"/>';
        }
        $x += $barWidth;
    }
    
    // Add text below barcode
    $svg .= '<text x="' . ($width / 2) . '" y="' . ($height + 15) . '" font-family="Arial, sans-serif" font-size="14" text-anchor="middle" fill="black">' . htmlspecialchars($data) . '</text>';
    $svg .= '</svg>';
    
    return $svg;
}
?>