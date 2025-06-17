#!/usr/bin/env node

// Setup script for SecureIt Browser Extension
const fs = require('fs');
const path = require('path');

console.log('🔐 SecureIt Browser Extension Setup');
console.log('=====================================\\n');

// Check if backend is configured
function checkBackendConfig() {
    const manifestPath = 'manifest.json';
    if (fs.existsSync(manifestPath)) {
        const manifest = JSON.parse(fs.readFileSync(manifestPath, 'utf8'));
        const hostPermissions = manifest.host_permissions || [];
        
        console.log('✅ Manifest file found');
        console.log(`📋 Host permissions: ${hostPermissions.join(', ')}`);
        
        if (hostPermissions.includes('http://localhost/*')) {
            console.log('✅ Local development backend configured');
        } else {
            console.log('⚠️  Consider adding localhost permissions for development');
        }
    } else {
        console.log('❌ Manifest file not found');
        return false;
    }
    return true;
}

// Check required files
function checkRequiredFiles() {
    const requiredFiles = [
        'popup/index.html',
        'popup/popup.js',
        'popup/styles.css',
        'background/background.js',
        'content/autofill.js',
        'content/autofill.css'
    ];
    
    let allPresent = true;
    
    console.log('\\n📁 Checking required files:');
    requiredFiles.forEach(file => {
        if (fs.existsSync(file)) {
            console.log(`✅ ${file}`);
        } else {
            console.log(`❌ ${file} - MISSING`);
            allPresent = false;
        }
    });
    
    return allPresent;
}

// Check for icons
function checkIcons() {
    const iconSizes = [16, 32, 48, 128];
    let hasIcons = true;
    
    console.log('\\n🎨 Checking icons:');
    iconSizes.forEach(size => {
        const iconPath = `assets/icon${size}.png`;
        if (fs.existsSync(iconPath)) {
            console.log(`✅ icon${size}.png`);
        } else {
            console.log(`⚠️  icon${size}.png - Consider adding for better appearance`);
            hasIcons = false;
        }
    });
    
    if (!hasIcons) {
        console.log('\\n💡 To create icons:');
        console.log('   1. Design your logo in PNG format');
        console.log('   2. Create sizes: 16x16, 32x32, 48x48, 128x128 pixels');
        console.log('   3. Save as icon16.png, icon32.png, etc. in assets/ folder');
    }
    
    return hasIcons;
}

// Provide installation instructions
function showInstallInstructions() {
    console.log('\\n🚀 Installation Instructions:');
    console.log('===============================');
    
    console.log('\\n📱 Chrome/Edge:');
    console.log('   1. Open chrome://extensions/');
    console.log('   2. Enable "Developer mode" (top right)');
    console.log('   3. Click "Load unpacked"');
    console.log('   4. Select this folder (browser-extension)');
    
    console.log('\\n🦊 Firefox:');
    console.log('   1. Go to about:debugging');
    console.log('   2. Click "This Firefox"');
    console.log('   3. Click "Load Temporary Add-on"');
    console.log('   4. Select the manifest.json file');
    
    console.log('\\n⚙️  Configuration:');
    console.log('   • Ensure your SecureIt backend is running');
    console.log('   • Default API URL: http://localhost/SecureIt/backend/api');
    console.log('   • Update API URLs in popup.js and background.js for production');
}

// Show usage tips
function showUsageTips() {
    console.log('\\n💡 Usage Tips:');
    console.log('===============');
    console.log('• Click the extension icon to open the main interface');
    console.log('• Right-click on password fields for quick password generation');
    console.log('• The extension will auto-detect login forms for autofill');
    console.log('• Use the Generator tab to create strong passwords');
    console.log('• Use the Send tab to securely share sensitive information');
    console.log('• The vault automatically locks after 30 minutes of inactivity');
}

// Main setup function
function runSetup() {
    const hasManifest = checkBackendConfig();
    const hasFiles = checkRequiredFiles();
    const hasIcons = checkIcons();
    
    console.log('\\n📊 Setup Summary:');
    console.log('==================');
    console.log(`Manifest: ${hasManifest ? '✅' : '❌'}`);
    console.log(`Required Files: ${hasFiles ? '✅' : '❌'}`);
    console.log(`Icons: ${hasIcons ? '✅' : '⚠️'}`);
    
    if (hasManifest && hasFiles) {
        console.log('\\n🎉 Extension is ready to install!');
        showInstallInstructions();
        showUsageTips();
    } else {
        console.log('\\n❌ Setup incomplete. Please fix the issues above.');
    }
    
    console.log('\\n🔧 Development:');
    console.log('   • npm run build - Create production build');
    console.log('   • Check browser console for debugging');
    console.log('   • Reload extension after making changes');
    
    console.log('\\n📚 Documentation:');
    console.log('   • See README.md for detailed instructions');
    console.log('   • Check the SecureIt backend documentation');
}

// Run the setup
runSetup();
