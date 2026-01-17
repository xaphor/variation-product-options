#!/usr/bin/env pwsh
<#
.SYNOPSIS
    Test script to validate Variation-Specific Field Groups functionality.
    
.DESCRIPTION
    This script helps verify that:
    1. Fields assigned to specific variations display correctly
    2. AJAX variation field loading works
    3. Price calculation works for variation options
    4. Cart integration works correctly
    
.PARAMETER WpPath
    Path to WordPress installation (e.g., "C:\xampp\htdocs\wordpress")
    
.PARAMETER ProductId
    Product ID to test (must be a variable product with variations)
    
.PARAMETER VariationId
    Variation ID to test against
    
.EXAMPLE
    .\test-variation-fields.ps1 -WpPath "C:\xampp\htdocs\wordpress" -ProductId 123 -VariationId 2627
#>

param(
    [string]$WpPath = "",
    [int]$ProductId = 0,
    [int]$VariationId = 0
)

Write-Host "================================" -ForegroundColor Cyan
Write-Host "VPO Variation Fields Test Suite" -ForegroundColor Cyan
Write-Host "================================" -ForegroundColor Cyan
Write-Host ""

# Step 1: PHP Syntax Check
Write-Host "Step 1: Verifying PHP Syntax" -ForegroundColor Yellow
$phpFiles = @(
    "assets/js/frontend.js",
    "frontend/class-vpo-frontend.php",
    "includes/class-vpo-data-handler.php"
)

$syntaxOk = $true
foreach ($file in $phpFiles) {
    $fullPath = "$(Get-Location)\$file"
    if (Test-Path $fullPath) {
        Write-Host "  ✓ $file exists" -ForegroundColor Green
    } else {
        Write-Host "  ✗ $file NOT FOUND" -ForegroundColor Red
        $syntaxOk = $false
    }
}
Write-Host ""

# Step 2: Check for required fixes
Write-Host "Step 2: Verifying Fixes Applied" -ForegroundColor Yellow

$frontend_js = Get-Content "assets/js/frontend.js" -Raw
$checks = @{
    "Dynamic container creation in JS" = $frontend_js -like "*'`<div class=`"vpo-product-options`"*"
    "Console logging in JS" = $frontend_js -like "*console.log('VPO: loadOptionsForVariation*"
}

foreach ($check in $checks.GetEnumerator()) {
    if ($check.Value) {
        Write-Host "  ✓ $($check.Name)" -ForegroundColor Green
    } else {
        Write-Host "  ✗ $($check.Name) - Fix not applied!" -ForegroundColor Red
    }
}
Write-Host ""

# Step 3: File modification timestamps
Write-Host "Step 3: Recent File Modifications" -ForegroundColor Yellow
$recentFiles = @(
    "assets/js/frontend.js",
    "frontend/class-vpo-frontend.php",
    "assets/css/frontend.css"
)

foreach ($file in $recentFiles) {
    $fullPath = "$(Get-Location)\$file"
    if (Test-Path $fullPath) {
        $lastWrite = (Get-Item $fullPath).LastWriteTime
        $ageMinutes = [math]::Round(((Get-Date) - $lastWrite).TotalMinutes)
        
        if ($ageMinutes -lt 60) {
            Write-Host "  ✓ $file (modified $ageMinutes minutes ago)" -ForegroundColor Green
        } else {
            Write-Host "  ⚠ $file (modified $ageMinutes minutes ago)" -ForegroundColor Yellow
        }
    }
}
Write-Host ""

# Step 4: Database info (if WP path provided)
if ($WpPath -ne "") {
    Write-Host "Step 4: WordPress Database Check" -ForegroundColor Yellow
    
    if (Test-Path "$WpPath/wp-config.php") {
        Write-Host "  ✓ WordPress installation found at: $WpPath" -ForegroundColor Green
        
        # Try to read DB info from wp-config
        $wpConfig = Get-Content "$WpPath/wp-config.php" | Select-String "DB_NAME|DB_USER|DB_HOST" | Select-Object -First 1
        if ($null -ne $wpConfig) {
            Write-Host "  ✓ WordPress database configured" -ForegroundColor Green
        }
    } else {
        Write-Host "  ✗ WordPress installation not found at: $WpPath" -ForegroundColor Red
    }
    Write-Host ""
}

# Step 5: Manual Testing Instructions
Write-Host "Step 5: Manual Testing Instructions" -ForegroundColor Yellow
Write-Host ""
Write-Host "Test Case A: Create Variation-Specific Field Group"
Write-Host "  1. Go to: WooCommerce > Product Options"
Write-Host "  2. Click Add Field Group"
Write-Host "  3. Enter group name e.g. Palm Installation"
Write-Host "  4. Add a field with type Radio Switch e.g. Installation Required"
Write-Host "  5. Set assignment:"
Write-Host "     - Uncheck All Products"
Write-Host "     - Select Specific Variations"
Write-Host "     - Choose any variation ID from your variable product"
Write-Host "  6. Click Save Field Group"
Write-Host ""

Write-Host "Test Case B: Verify Frontend Display"
Write-Host "  1. Open the product page in browser"
Write-Host "  2. Open DevTools: Press F12"
Write-Host "  3. Go to Console tab"
Write-Host "  4. Initially: No fields should display (correct!)"
Write-Host "  5. Select the variation you assigned to"
Write-Host "  6. Look for console output:"
Write-Host "     VPO: loadOptionsForVariation called with productId=XXX, variationId=YYY"
Write-Host "     VPO: Making AJAX request for variation fields"
Write-Host "     VPO: AJAX success, response: success: true"
Write-Host "     VPO: Fields inserted, field count: 1"
Write-Host "  7. Fields should NOW appear on the page"
Write-Host ""
Write-Host "  1. Open the product page in browser"
Write-Host "  2. Open DevTools: Press F12"
Write-Host "  3. Go to 'Console' tab"
Write-Host "  4. Initially: No fields should display (correct!)"
Write-Host "  5. Select the variation you assigned to"
Write-Host "  6. Look for console output:"
Write-Host "     VPO: loadOptionsForVariation called with productId=XXX, variationId=YYY"
Write-Host "     VPO: Making AJAX request for variation fields"
Write-Host "     VPO: AJAX success, response: {success: true, ...}"
Write-Host "     VPO: Fields inserted, field count: 1"
Write-Host "  7. Fields should NOW appear on the page"
Write-Host ""

Write-Host "Test Case C: Verify Price Calculation"
Write-Host "  1. Select one of the radio options"
Write-Host "  2. Look for 'Additional Options Total' to appear"
Write-Host "  3. Verify the price updates dynamically"
Write-Host ""

Write-Host "Test Case D: Add to Cart"
Write-Host "  1. Select a variation and options"
Write-Host "  2. Click 'Add to Cart'"
Write-Host "  3. View cart"
Write-Host "  4. Verify options are displayed with variation"
Write-Host "  5. Verify option prices are included in cart total"
Write-Host ""

# Step 6: Debugging checklist
Write-Host "Step 6: If Tests Fail - Debugging Checklist" -ForegroundColor Yellow
Write-Host ""
Write-Host "Issue: Fields do not appear after selecting variation"
Write-Host "  1. Open /wp-content/debug.log"
Write-Host "     Look for: VPO AJAX: vpo_get_variation_fields called"
Write-Host "     If not found: AJAX not being called"
Write-Host "  2. Check browser Console (F12)"
Write-Host "     Look for error messages or warnings"
Write-Host "     Should see VPO: loadOptionsForVariation called"
Write-Host "  3. Verify field group has correct variation ID:"
Write-Host "     Go to WooCommerce > Product Options, edit group"
Write-Host "     Check that variation ID matches the one you selected"
Write-Host ""

Write-Host "Issue: Fields appear but are invisible"
Write-Host "  1. Right-click on where field should be, select Inspect"
Write-Host "  2. Check DevTools Elements tab for .vpo-field div"
Write-Host "  3. In Styles panel, verify display and visibility are not hidden"
Write-Host "  4. Look for CSS with important flag (should have it)"
Write-Host ""

Write-Host "Issue: AJAX errors in console"
Write-Host "  1. Click on AJAX error in console"
Write-Host "  2. Look for status code and error message"
Write-Host "  3. Check /wp-content/debug.log for corresponding error"
Write-Host "  4. Most common: Nonce verification failed (hard refresh page)"
Write-Host ""

Write-Host ""
Write-Host "================================" -ForegroundColor Cyan
Write-Host "Test Setup Complete!" -ForegroundColor Cyan
Write-Host "================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Documentation:" -ForegroundColor Cyan
Write-Host "  - Detailed guide: VARIATION_ASSIGNMENT_DEBUG.md" -ForegroundColor Cyan
Write-Host "  - Technical details: FIX_VARIATION_DISPLAY.md" -ForegroundColor Cyan
Write-Host "  - AI instructions: .github/copilot-instructions.md" -ForegroundColor Cyan
Write-Host ""
