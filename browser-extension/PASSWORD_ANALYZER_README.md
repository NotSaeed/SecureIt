# SecureIt Password Analyzer

Real-time password strength analysis with vault checking for the SecureIt browser extension.

## Features

The SecureIt Password Analyzer provides real-time feedback on password fields throughout the web with these features:

### 🔴 Leak Detection
- **Red pulsing circle**: Password found in known data breaches
- Uses the HaveIBeenPwned API to check against millions of compromised passwords
- Instant warning when typing a compromised password

### 💪 Strength Analysis
- **Orange circle**: Weak password (short, simple, common patterns)
- **Yellow circle**: Moderate password (decent but could be stronger)
- **Green circle**: Strong password (long, complex, secure)

### 🔐 Vault Integration
- **Purple pulsing circle**: Password already exists in your SecureIt vault
- Shows which vault item uses the same password
- Helps prevent password reuse across sites

### 📱 Interactive Features
- Click any analysis circle to open the SecureIt extension popup
- Hover effects and smooth animations
- Auto-positioning that follows the password field
- Messages that appear and fade automatically

## How It Works

1. **Real-time Analysis**: As you type in any password field, the analyzer immediately starts working
2. **Visual Feedback**: A colored circle appears next to the password field showing the analysis result
3. **Detailed Messages**: Short messages explain the analysis (appears for 3 seconds)
4. **Vault Checking**: If logged into SecureIt, checks if the password is already in your vault
5. **Breach Detection**: Securely checks password against known breaches using k-anonymity

## Installation & Setup

1. **Install SecureIt Extension**: Load the extension in Chrome/Edge developer mode
2. **Log Into SecureIt**: Use the extension popup to log into your SecureIt account
3. **Browse & Type**: The analyzer automatically works on any website with password fields

## Testing

Use the test page: `test-password-analyzer.html`

### Test Passwords:
- `123456` - Shows as leaked (red)
- `password` - Shows as leaked (red)  
- `Password123` - Shows as moderate (yellow)
- `MyStr0ng!P@ssw0rd` - Shows as strong (green)
- Any password in your vault - Shows as vault match (purple)

## Technical Implementation

### Content Script (`password-analyzer.js`)
- Automatically detects password fields on any webpage
- Implements debounced analysis to avoid excessive API calls
- Handles dynamic content and single-page applications
- Provides smooth animations and user feedback

### Extension API (`extension.php`)
- Checks passwords against the user's SecureIt vault
- Secure session handling
- Returns vault matches with item details

### Background Script Integration
- Handles communication between content script and popup
- Manages analyzed password data sharing
- Provides secure API communication

## Security Features

- **k-Anonymity**: Only the first 5 characters of the password hash are sent to check breaches
- **Secure Vault API**: Uses session authentication for vault checking
- **No Password Storage**: Passwords are never stored, only analyzed in real-time
- **HTTPS Communication**: All API calls use secure connections

## Visual Indicators

| Color | Meaning | Animation |
|-------|---------|-----------|
| 🔴 Red | Leaked/Very Weak | Pulsing |
| 🟠 Orange | Weak | Pulsing |
| 🟡 Yellow | Moderate | Static |
| 🟢 Green | Strong | Static |
| 🟣 Purple | In Vault | Pulsing |

## Browser Compatibility

- ✅ Chrome/Chromium (Manifest V3)
- ✅ Microsoft Edge
- ✅ Firefox (with minor modifications)
- ✅ Works on all websites
- ✅ Supports dynamic content/SPAs

## Privacy

- Passwords are analyzed locally when possible
- Breach checking uses secure hashing (SHA-1)
- Only hash prefixes are sent to external APIs
- Vault checking requires user authentication
- No tracking or data collection

## Configuration

The analyzer works automatically with these defaults:
- **Debounce delay**: 500ms (prevents excessive API calls)
- **Message display**: 3 seconds
- **Circle size**: 18px with 2px border
- **Position offset**: 5px to the right of password fields

## Troubleshooting

### Circle Not Appearing
- Check if SecureIt extension is installed and enabled
- Verify the password field is properly detected (type="password")
- Check browser console for any JavaScript errors

### Vault Checking Not Working
- Ensure you're logged into SecureIt extension
- Check network connectivity to localhost API
- Verify SecureIt backend is running

### False Positives/Negatives
- Some password fields may not be detected (unusual HTML structure)
- Breach checking requires internet connection
- Very new breaches may not be detected immediately

## Development

### Files Structure:
```
browser-extension/
├── content/
│   └── password-analyzer.js    # Main content script
├── background/
│   └── background.js          # Background service worker
├── popup/
│   └── popup.js              # Extension popup
└── test-password-analyzer.html # Test page

backend/
└── api/
    └── extension.php         # Vault checking API
```

### Adding New Features:
1. Modify `password-analyzer.js` for new analysis types
2. Update `extension.php` for new vault features  
3. Extend `popup.js` for new UI interactions

## Support

For issues or feature requests:
1. Check the browser console for errors
2. Test with the provided test page
3. Verify SecureIt backend connectivity
4. Review browser extension permissions

---

**Security Note**: This analyzer helps improve password security but should be used alongside other security best practices like two-factor authentication and regular password updates.
