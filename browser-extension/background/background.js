// Background Service Worker for SecureIt Extension
class SecureItBackground {
    constructor() {
        this.apiBase = 'http://localhost/SecureIt/backend/api';
        this.sessionTimeout = 30 * 60 * 1000; // 30 minutes
        this.setupEventListeners();
        this.setupContextMenus();
    }

    setupEventListeners() {
        // Extension installation
        chrome.runtime.onInstalled.addListener(() => {
            console.log('SecureIt Extension installed');
            this.setupContextMenus();
        });

        // Message handling
        chrome.runtime.onMessage.addListener((message, sender, sendResponse) => {
            this.handleMessage(message, sender, sendResponse);
            return true; // Keep the message channel open for async responses
        });

        // Tab updates for autofill
        chrome.tabs.onUpdated.addListener((tabId, changeInfo, tab) => {
            if (changeInfo.status === 'complete' && tab.url) {
                this.checkForAutofillOpportunities(tabId, tab.url);
            }
        });

        // Alarm for session timeout
        chrome.alarms.onAlarm.addListener((alarm) => {
            if (alarm.name === 'sessionTimeout') {
                this.clearSession();
            }
        });
    }

    setupContextMenus() {
        chrome.contextMenus.removeAll(() => {
            // Generate password context menu
            chrome.contextMenus.create({
                id: 'generate-password',
                title: 'Generate Password',
                contexts: ['editable']
            });

            // Fill password context menu (only show when logged in)
            chrome.contextMenus.create({
                id: 'fill-password',
                title: 'Fill from Vault',
                contexts: ['editable']
            });

            // Context menu click handler
            chrome.contextMenus.onClicked.addListener((info, tab) => {
                this.handleContextMenuClick(info, tab);
            });
        });
    }

    async handleMessage(message, sender, sendResponse) {
        try {
            switch (message.action) {
                case 'checkAuth':
                    const authStatus = await this.checkAuthStatus();
                    sendResponse({ success: true, authenticated: authStatus });
                    break;

                case 'generatePassword':
                    const password = await this.generatePassword(message.options);
                    sendResponse({ success: true, password });
                    break;

                case 'getVaultItems':
                    const items = await this.getVaultItemsForDomain(message.domain);
                    sendResponse({ success: true, items });
                    break;

                case 'fillCredentials':
                    await this.fillCredentials(sender.tab.id, message.credentials);
                    sendResponse({ success: true });
                    break;

                case 'saveCredentials':
                    const saved = await this.saveCredentials(message.credentials);
                    sendResponse({ success: true, saved });
                    break;

                case 'lockVault':
                    await this.clearSession();
                    sendResponse({ success: true });
                    break;

                case 'open_popup_with_password':
                    // Store the password data for the popup to access
                    await chrome.storage.local.set({
                        'analyzedPassword': {
                            password: message.password,
                            url: message.url,
                            timestamp: Date.now()
                        }
                    });
                    
                    // Open the popup
                    chrome.action.openPopup();
                    sendResponse({ success: true });
                    break;

                case 'checkPasswordInVault':
                    try {
                        const vaultCheck = await this.checkPasswordInVault(message.password, message.url);
                        sendResponse({ success: true, result: vaultCheck });
                    } catch (error) {
                        sendResponse({ success: false, error: error.message });
                    }
                    break;

                default:
                    sendResponse({ success: false, error: 'Unknown action' });
            }
        } catch (error) {
            console.error('Background message error:', error);
            sendResponse({ success: false, error: error.message });
        }
    }

    async handleContextMenuClick(info, tab) {
        try {
            switch (info.menuItemId) {
                case 'generate-password':
                    const password = await this.generatePassword({
                        length: 14,
                        uppercase: true,
                        lowercase: true,
                        numbers: true,
                        symbols: true
                    });
                    
                    if (password) {
                        await chrome.tabs.sendMessage(tab.id, {
                            action: 'insertPassword',
                            password: password
                        });
                    }
                    break;

                case 'fill-password':
                    const domain = new URL(tab.url).hostname;
                    const items = await this.getVaultItemsForDomain(domain);
                    
                    if (items.length > 0) {
                        // Use the first matching item
                        const item = items[0];
                        await chrome.tabs.sendMessage(tab.id, {
                            action: 'fillCredentials',
                            credentials: {
                                username: item.username,
                                password: item.password
                            }
                        });
                    }
                    break;
            }
        } catch (error) {
            console.error('Context menu action error:', error);
        }
    }

    async checkForAutofillOpportunities(tabId, url) {
        try {
            // Only check for login pages
            if (!url.startsWith('http')) return;

            const domain = new URL(url).hostname;
            const items = await this.getVaultItemsForDomain(domain);

            if (items.length > 0) {
                // Inject autofill content script
                chrome.tabs.sendMessage(tabId, {
                    action: 'enableAutofill',
                    items: items
                });
            }
        } catch (error) {
            console.error('Autofill check error:', error);
        }
    }

    async checkAuthStatus() {
        try {
            const response = await this.makeRequest('/auth.php', {
                method: 'POST',
                body: JSON.stringify({ action: 'check_session' })
            });

            if (response.success && response.user) {
                // Reset session timeout
                chrome.alarms.clear('sessionTimeout');
                chrome.alarms.create('sessionTimeout', { delayInMinutes: 30 });
                return true;
            }
        } catch (error) {
            console.error('Auth check failed:', error);
        }
        return false;
    }

    async generatePassword(options = {}) {
        try {
            const response = await this.makeRequest('/generator.php', {
                method: 'POST',
                body: JSON.stringify({
                    action: 'generate_password',
                    length: options.length || 14,
                    options: {
                        uppercase: options.uppercase !== false,
                        lowercase: options.lowercase !== false,
                        numbers: options.numbers !== false,
                        symbols: options.symbols !== false
                    }
                })
            });

            return response.success ? response.password : null;
        } catch (error) {
            console.error('Password generation error:', error);
            return null;
        }
    }

    async getVaultItemsForDomain(domain) {
        try {
            const response = await this.makeRequest('/vault.php?action=list');
            if (!response.success) return [];

            const items = response.items || [];
            
            // Filter items that match the domain
            return items.filter(item => {
                if (!item.url) return false;
                
                try {
                    const itemDomain = new URL(item.url).hostname;
                    return itemDomain === domain || itemDomain.endsWith('.' + domain);
                } catch {
                    // If URL parsing fails, check if domain is contained in the URL string
                    return item.url.includes(domain);
                }
            });
        } catch (error) {
            console.error('Failed to get vault items:', error);
            return [];
        }
    }

    async fillCredentials(tabId, credentials) {
        try {
            await chrome.tabs.sendMessage(tabId, {
                action: 'fillCredentials',
                credentials: credentials
            });
        } catch (error) {
            console.error('Fill credentials error:', error);
        }
    }

    async saveCredentials(credentials) {
        try {
            const response = await this.makeRequest('/vault.php', {
                method: 'POST',
                body: JSON.stringify({
                    action: 'create',
                    type: 'login',
                    name: credentials.name || credentials.domain,
                    username: credentials.username,
                    password: credentials.password,
                    url: credentials.url,
                    notes: 'Saved from browser extension'
                })
            });

            return response.success;
        } catch (error) {
            console.error('Save credentials error:', error);
            return false;
        }
    }

    async clearSession() {
        try {
            await this.makeRequest('/auth.php', {
                method: 'POST',
                body: JSON.stringify({ action: 'logout' })
            });
        } catch (error) {
            console.error('Logout error:', error);
        }

        // Clear alarms
        chrome.alarms.clear('sessionTimeout');

        // Notify all tabs about logout
        chrome.tabs.query({}, (tabs) => {
            tabs.forEach(tab => {
                chrome.tabs.sendMessage(tab.id, {
                    action: 'sessionExpired'
                }).catch(() => {
                    // Ignore errors for tabs that don't have content script
                });
            });
        });
    }

    async checkPasswordInVault(password, url) {
        try {
            const response = await this.makeRequest('/extension.php', {
                method: 'POST',
                body: JSON.stringify({
                    action: 'check_password_in_vault',
                    password: password,
                    url: url
                })
            });

            return response;
        } catch (error) {
            console.error('Password vault check error:', error);
            return { success: false, in_vault: false, matched_items: [] };
        }
    }

    async makeRequest(endpoint, options = {}) {
        const url = `${this.apiBase}${endpoint}`;
        const config = {
            credentials: 'include',
            headers: {
                'Content-Type': 'application/json',
                ...options.headers
            },
            ...options
        };

        const response = await fetch(url, config);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        return await response.json();
    }
}

// Initialize background service
new SecureItBackground();
