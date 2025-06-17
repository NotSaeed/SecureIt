// Build script for SecureIt Extension
const fs = require('fs');
const path = require('path');

const buildDir = 'dist';
const sourceDir = '.';

// Files and directories to include in build
const filesToCopy = [
    'manifest.json',
    'popup/',
    'background/',
    'content/',
    'assets/',
    'README.md'
];

// Create build directory
if (!fs.existsSync(buildDir)) {
    fs.mkdirSync(buildDir);
}

// Copy files
function copyRecursive(src, dest) {
    if (fs.statSync(src).isDirectory()) {
        if (!fs.existsSync(dest)) {
            fs.mkdirSync(dest);
        }
        
        fs.readdirSync(src).forEach(file => {
            copyRecursive(path.join(src, file), path.join(dest, file));
        });
    } else {
        fs.copyFileSync(src, dest);
    }
}

console.log('Building SecureIt Extension...');

filesToCopy.forEach(file => {
    const srcPath = path.join(sourceDir, file);
    const destPath = path.join(buildDir, file);
    
    if (fs.existsSync(srcPath)) {
        console.log(`Copying ${file}...`);
        copyRecursive(srcPath, destPath);
    } else {
        console.warn(`Warning: ${file} not found`);
    }
});

console.log('Build complete! Output in ./dist folder');
console.log('To load in browser:');
console.log('1. Go to chrome://extensions/');
console.log('2. Enable Developer mode');
console.log('3. Click "Load unpacked" and select the ./dist folder');
