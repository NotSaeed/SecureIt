# SecureIt Browser Extension

A modern browser extension for SecureIt Password Manager with comprehensive vault, generator, and send functionality.

## Features

### 🔐 Vault Management
- Securely store and manage passwords, notes, cards, and identities
- Smart autofill for login forms
- Quick search and filter functionality
- Contextual password filling

### 🎲 Password Generator
- Generate strong passwords with customizable options
- Create memorable passphrases
- Copy generated passwords to clipboard
- Integration with vault for easy saving

### 📤 Send Feature
- Securely share text and files with expiration dates
- Password protection for sensitive sends
- View limits and access tracking
- Copy sharing links directly

### 🚀 Modern Features
- Manifest V3 compliance for latest browser support
- Clean, intuitive Material Design-inspired UI
- Real-time autofill suggestions
- Context menu integration
- Session management with auto-lock

## Installation

### Development Setup

1. **Load Extension in Chrome/Edge:**
   ```
   1. Open Chrome/Edge and go to chrome://extensions/
   2. Enable "Developer mode"
   3. Click "Load unpacked"
   4. Select the browser-extension folder
   ```

2. **Load Extension in Firefox:**
   ```
   1. Go to about:debugging
   2. Click "This Firefox"
   3. Click "Load Temporary Add-on"
   4. Select the manifest.json file
   ```

### Production Build

For production deployment, you'll need to:

1. Update the `host_permissions` in manifest.json to match your production domain
2. Create proper icon files (see Assets section below)
3. Package the extension for store submission

## Configuration

### API Endpoint
The extension is configured to work with your SecureIt backend at:
```
http://localhost/SecureIt/backend/api
```

To change this for production:
1. Edit `popup/popup.js` and update the `apiBase` variable
2. Edit `background/background.js` and update the `apiBase` variable

### Permissions
The extension requests minimal permissions:
- `activeTab` - For autofill functionality
- `storage` - For local extension settings
- `clipboardWrite` - For copying passwords
- `alarms` - For session timeout
- `contextMenus` - For right-click password generation

## Assets Required

You'll need to create the following icon files in the `assets/` folder:

- `icon16.png` (16x16px)
- `icon32.png` (32x32px) 
- `icon48.png` (48x48px)
- `icon128.png` (128x128px)

These should be your SecureIt logo in PNG format.

## Usage

### First Time Setup
1. Click the SecureIt extension icon
2. Enter your SecureIt account credentials
3. The extension will sync with your vault

### Password Generation
- Click the Generator tab in the extension popup
- Customize password/passphrase options
- Click Generate and copy the result
- Use the context menu to generate passwords directly in fields

### Autofill
- Visit any login page
- The extension will automatically detect login forms
- Click the SecureIt icon next to password fields
- Select the account you want to use

### Send Feature
- Click the Send tab in the extension popup
- Create new sends with expiration dates and passwords
- Share the generated links securely
- Track views and manage existing sends

## Security Features

- All data communication uses HTTPS/secure protocols
- Session timeout after 30 minutes of inactivity
- Vault locks automatically when browser is closed
- Passwords are never stored in browser local storage
- Content Security Policy prevents XSS attacks

## Browser Compatibility

- Chrome 88+
- Edge 88+
- Firefox 109+
- Safari 16+ (with manifest adjustments)

## Development

### File Structure
```
browser-extension/
├── manifest.json          # Extension manifest
├── popup/
│   ├── index.html         # Extension popup UI
│   ├── popup.js          # Popup logic and API calls
│   └── styles.css        # Modern UI styles
├── background/
│   └── background.js     # Service worker for autofill
├── content/
│   ├── autofill.js      # Content script for form detection
│   └── autofill.css     # Autofill UI styles
└── assets/
    └── (icon files)
```

### Key Components

1. **Popup Interface** (`popup/`)
   - Main extension interface with vault, generator, and send tabs
   - Handles user authentication and API communication
   - Material Design-inspired responsive UI

2. **Background Service Worker** (`background/background.js`)
   - Manages autofill functionality
   - Handles context menu actions
   - Session management and timeout handling

3. **Content Script** (`content/autofill.js`)
   - Detects login forms on web pages
   - Provides autofill buttons and menus
   - Handles credential filling and form submission

### API Integration

The extension integrates with your existing SecureIt backend APIs:

- **Authentication**: `/api/auth.php`
- **Vault Management**: `/api/vault.php`
- **Password Generation**: `/api/generator.php`
- **Send Feature**: `/api/send.php`

### Contributing

1. Ensure your SecureIt backend is running locally
2. Load the extension in developer mode
3. Make changes to the code
4. Reload the extension to test changes
5. Check the browser console for any errors

## Troubleshooting

### Common Issues

1. **Extension won't load**
   - Check that manifest.json is valid
   - Ensure all referenced files exist
   - Check browser console for errors

2. **API calls failing**
   - Verify SecureIt backend is running
   - Check CORS headers in PHP files
   - Ensure session cookies are working

3. **Autofill not working**
   - Check that content script is injected
   - Verify domain permissions
   - Test on simple login forms first

### Debug Mode

Enable debug logging by:
1. Opening browser developer tools
2. Going to the Console tab
3. Checking for SecureIt extension logs

## License

This extension is part of the SecureIt Password Manager project.
