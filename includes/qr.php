<?php
// includes/qr.php

/**
 * Generates a URL for the QR code image using a public API.
 * 
 * @param string $code_unique The unique document code.
 * @return string The URL to the generated QR code image.
 */
function generateQRCodeUrl($code_unique) {
    // Generate the verification URL that the QR code will point to.
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    
    // We assume public.php is at the root of the project
    // This attempts to build a reliable base URL.
    $scriptDir = dirname($_SERVER['SCRIPT_NAME']);
    // If we are in /admin/ajouter.php, dirname is /admin, so we need the parent.
    // A more robust way is getting the relative path to root:
    $baseUrl = $protocol . '://' . $host . str_replace('\\', '/', dirname(dirname($_SERVER['SCRIPT_NAME'])));
    
    // Ensure the URL is correctly formed
    $verificationUrl = rtrim($baseUrl, '/') . "/public.php?code=" . urlencode($code_unique);
    
    // Use QRServer API for simple generation
    return "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=" . urlencode($verificationUrl);
}
?>
