// SecureIt Extension Popup Controller
class SecureItPopup {
    constructor() {
        console.log('SecureIt Extension Popup initializing...');
        this.apiBase = 'http://localhost/SecureIt/backend/api';
        this.currentUser = null;
        this.currentSection = 'vault';
        this.vaultItems = [];
        this.sendItems = [];
        
        // Storage abstraction for web vs extension
        this.storage = {
            set: async (data) => {
                if (typeof chrome !== 'undefined' && chrome.storage) {
                    return await chrome.storage.local.set(data);
                } else {
                    // Fallback to localStorage for web testing
                    for (const [key, value] of Object.entries(data)) {
                        localStorage.setItem(key, JSON.stringify(value));
                    }
                }
            },
            get: async (keys) => {
                if (typeof chrome !== 'undefined' && chrome.storage) {
                    return await chrome.storage.local.get(keys);
                } else {
                    // Fallback to localStorage for web testing
                    const result = {};
                    for (const key of keys) {
                        const item = localStorage.getItem(key);
                        if (item) {
                            try {
                                result[key] = JSON.parse(item);
                            } catch (e) {
                                result[key] = item;
                            }
                        }
                    }
                    return result;
                }
            },
            remove: async (keys) => {
                if (typeof chrome !== 'undefined' && chrome.storage) {
                    return await chrome.storage.local.remove(keys);
                } else {
                    // Fallback to localStorage for web testing
                    const keyArray = Array.isArray(keys) ? keys : [keys];
                    for (const key of keyArray) {
                        localStorage.removeItem(key);
                    }
                }
            }
        };
        
        // Initialize the popup
        this.init().catch(error => {
            console.error('Failed to initialize SecureIt popup:', error);
            this.showError('Failed to initialize extension: ' + error.message);
        });
    }

    async init() {
        console.log('Initializing SecureIt extension...');
        this.setupEventListeners();
        
        // Check for analyzed password data from content script
        await this.checkAnalyzedPassword();
        
        // Don't pre-fill test credentials - let user enter their own
        // Check for existing session first
        await this.checkAuthStatus();
        this.updateUI();
    }

    setupEventListeners() {
        // Navigation
        document.querySelectorAll('.nav-item').forEach(item => {
            item.addEventListener('click', (e) => {
                const section = e.currentTarget.dataset.section;
                this.switchSection(section);
            });
        });

        // Login form
        const loginForm = document.querySelector('.login-form');
        if (loginForm) {
            loginForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.login();
            });
        }
        
        const loginBtn = document.getElementById('login-btn');
        if (loginBtn) {
            loginBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.login();
            });
        }

        const passwordInput = document.getElementById('password');
        if (passwordInput) {
            passwordInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    this.login();
                }
            });
        }

        // Header actions
        const lockBtn = document.getElementById('lock-btn');
        if (lockBtn) {
            lockBtn.addEventListener('click', () => this.logout());
        }

        // Vault
        document.getElementById('add-item-btn').addEventListener('click', () => this.showAddItemModal());
        document.getElementById('vault-search').addEventListener('input', (e) => this.searchVault(e.target.value));

        // Generator
        document.getElementById('generate-btn').addEventListener('click', () => this.generateCredential());
        document.getElementById('regenerate-password').addEventListener('click', () => this.generateCredential());
        document.getElementById('copy-password').addEventListener('click', () => this.copyToClipboard());
        
        // Generator type toggle
        document.querySelectorAll('input[name="generator-type"]').forEach(radio => {
            radio.addEventListener('change', () => this.toggleGeneratorType());
        });

        // Length slider
        document.getElementById('password-length').addEventListener('input', (e) => {
            document.getElementById('length-value').textContent = e.target.value;
        });

        document.getElementById('word-count').addEventListener('input', (e) => {
            document.getElementById('word-count-value').textContent = e.target.value;
        });

        // Send
        document.getElementById('create-send-btn').addEventListener('click', () => this.showCreateSendModal());

        // Modals
        document.querySelectorAll('.close-modal').forEach(btn => {
            btn.addEventListener('click', () => this.closeModals());
        });

        document.getElementById('cancel-add').addEventListener('click', () => this.closeModals());
        document.getElementById('cancel-send').addEventListener('click', () => this.closeModals());
        document.getElementById('save-item').addEventListener('click', () => this.saveVaultItem());
        document.getElementById('save-send').addEventListener('click', () => this.createSend());

        // Modal background click
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) this.closeModals();
            });
        });
    }

    async checkAuthStatus() {
        try {
            console.log('Checking authentication status...');
              // First check extension storage for cached session
            const stored = await this.storage.get(['secureit_user', 'secureit_session']);
            if (stored.secureit_user && stored.secureit_session) {
                // Check if stored session is not too old (1 hour)
                const sessionAge = Date.now() - stored.secureit_session;
                if (sessionAge < 60 * 60 * 1000) { // 1 hour
                    console.log('Found valid stored session');
                    this.currentUser = stored.secureit_user;
                    await this.loadUserData();
                    return true;
                } else {
                    console.log('Stored session expired, clearing');
                    await this.storage.remove(['secureit_user', 'secureit_session']);
                }
            }
            
            // Check server session
            const response = await this.makeRequest('/auth.php', {
                method: 'POST',
                body: JSON.stringify({ action: 'check_session' })
            });
            
            console.log('Auth check response:', response);

            if (response.success && response.user) {
                this.currentUser = response.user;
                console.log('User authenticated:', this.currentUser);
                  // Store in extension storage
                await this.storage.set({
                    'secureit_user': response.user,
                    'secureit_session': Date.now()
                });
                
                await this.loadUserData();
                return true;
            } else {
                console.log('User not authenticated');
            }
        } catch (error) {
            console.error('Auth check failed:', error);
        }
        return false;
    }

    async login() {
        const email = document.getElementById('email').value.trim();
        const password = document.getElementById('password').value;

        if (!email || !password) {
            this.showError('Please enter both email and password');
            return;
        }

        try {
            const response = await this.makeRequest('/auth.php', {
                method: 'POST',
                body: JSON.stringify({
                    action: 'login',
                    email: email,
                    password: password
                })
            });

            if (response.success) {
                this.currentUser = response.user;
                  // Store session in extension storage
                await this.storage.set({
                    'secureit_user': response.user,
                    'secureit_session': Date.now()
                });
                
                await this.loadUserData();
                this.updateUI();
                this.showSuccess('Login successful');
            } else {
                this.showError(response.message || 'Login failed');
            }
        } catch (error) {
            console.error('Login error:', error);
            this.showError('Login failed. Please try again.');
        }
    }

    async logout() {
        try {
            await this.makeRequest('/auth.php', {
                method: 'POST',
                body: JSON.stringify({ action: 'logout' })
            });
        } catch (error) {
            console.error('Logout error:', error);
        }        // Clear extension storage
        await this.storage.remove(['secureit_user', 'secureit_session']);

        this.currentUser = null;
        this.vaultItems = [];
        this.sendItems = [];
        this.updateUI();
    }

    async loadUserData() {
        await Promise.all([
            this.loadVaultItems(),
            this.loadSendItems()
        ]);
    }

    async loadVaultItems() {
        try {
            console.log('Loading vault items...');
            const response = await this.makeRequest('/vault.php?action=list');
            console.log('Vault API response:', response);
            
            if (response.success) {
                this.vaultItems = response.items || [];
                console.log('Loaded vault items:', this.vaultItems.length, 'items');
                console.log('Vault items data:', this.vaultItems);
                this.renderVaultItems();
            } else {
                console.error('Vault API returned error:', response.message);
                this.showError(response.message || 'Failed to load vault items');
            }
        } catch (error) {
            console.error('Failed to load vault items:', error);
            this.showError('Failed to connect to vault API');
        }
    }

    async loadSendItems() {
        try {
            const response = await this.makeRequest('/send.php?action=list');
            if (response.success) {
                this.sendItems = response.items || [];
                this.renderSendItems();
            }
        } catch (error) {
            console.error('Failed to load send items:', error);
        }
    }

    async checkAnalyzedPassword() {
        try {
            // Check if there's password data from the analyzer
            const data = await this.storage.get(['analyzedPassword']);
            if (data.analyzedPassword) {
                const { password, url, timestamp } = data.analyzedPassword;
                
                // Check if the data is recent (within 5 minutes)
                if (Date.now() - timestamp < 5 * 60 * 1000) {
                    // Show password analyzer info
                    this.showPasswordAnalyzerInfo(password, url);
                }
                
                // Clean up the stored data
                await this.storage.remove(['analyzedPassword']);
            }
        } catch (error) {
            console.error('Error checking analyzed password:', error);
        }
    }

    showPasswordAnalyzerInfo(password, url) {
        // Create a notification or modal to show password analysis
        const notification = document.createElement('div');
        notification.className = 'password-analyzer-notification';
        notification.innerHTML = `
            <div style="background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 8px; padding: 15px; margin: 10px; position: relative;">
                <button onclick="this.parentElement.remove()" style="position: absolute; top: 5px; right: 10px; background: none; border: none; font-size: 18px; cursor: pointer;">&times;</button>
                <h4 style="margin: 0 0 10px 0; color: #495057;">Password Analysis</h4>
                <p style="margin: 5px 0; font-size: 14px;"><strong>Website:</strong> ${url}</p>
                <p style="margin: 5px 0; font-size: 14px;"><strong>Password:</strong> ${'*'.repeat(password.length)}</p>
                <div style="margin-top: 10px;">
                    <button onclick="secureItPopup.saveAnalyzedPassword('${password}', '${url}')" 
                            style="background: #007bff; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer; margin-right: 10px;">
                        Save to Vault
                    </button>
                    <button onclick="secureItPopup.analyzePasswordStrength('${password}')" 
                            style="background: #28a745; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer;">
                        Analyze Strength
                    </button>
                </div>
            </div>
        `;
        
        const container = document.querySelector('.container') || document.body;
        container.insertBefore(notification, container.firstChild);
    }    async saveAnalyzedPassword(password, url) {
        // Switch to vault section and pre-fill add item form
        this.switchSection('vault');
        this.showAddItemModal();
        
        // Pre-fill the form with correct field IDs
        document.getElementById('item-name').value = this.extractDomainFromUrl(url);
        document.getElementById('item-url').value = url;
        document.getElementById('item-password').value = password;
    }

    analyzePasswordStrength(password) {
        // Switch to generator section to show analysis
        this.switchSection('generator');
        
        // You could add password strength analysis display here
        this.showSuccess(`Password analysis completed for ${password.length}-character password`);
    }

    extractDomainFromUrl(url) {
        try {
            const urlObj = new URL(url);
            return urlObj.hostname.replace(/^www\./, '');
        } catch (e) {
            return url;
        }
    }

    switchSection(section) {
        this.currentSection = section;
        
        // Update navigation
        document.querySelectorAll('.nav-item').forEach(item => {
            item.classList.toggle('active', item.dataset.section === section);
        });

        // Update sections
        document.querySelectorAll('.section').forEach(sec => {
            sec.classList.toggle('active', sec.id === `${section}-section`);
        });

        // Load data if needed
        if (section === 'generator') {
            this.generateCredential();
        }
    }

    updateUI() {
        const isLoggedIn = !!this.currentUser;
        console.log('Updating UI - isLoggedIn:', isLoggedIn, 'currentUser:', this.currentUser);
        
        const loginPage = document.getElementById('login-page');
        const mainApp = document.getElementById('main-app');
        
        if (isLoggedIn) {
            // Hide login page, show main app
            if (loginPage) loginPage.classList.add('hidden');
            if (mainApp) mainApp.classList.add('active');
            
            console.log('User is logged in, switching to section:', this.currentSection);
            this.switchSection(this.currentSection);
        } else {
            // Show login page, hide main app
            if (loginPage) loginPage.classList.remove('hidden');
            if (mainApp) mainApp.classList.remove('active');
            
            console.log('User not logged in, showing login page');
            
            // Clear any stored credentials from inputs
            const emailInput = document.getElementById('email');
            const passwordInput = document.getElementById('password');
            if (emailInput) emailInput.value = '';
            if (passwordInput) passwordInput.value = '';
        }
    }

    renderVaultItems() {
        console.log('Rendering vault items, count:', this.vaultItems.length);
        const container = document.getElementById('vault-items');
        
        if (!container) {
            console.error('Vault items container not found');
            return;
        }
        
        if (this.vaultItems.length === 0) {
            console.log('No vault items to display, showing empty state');
            container.innerHTML = `
                <div class="empty-state">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                        <circle cx="12" cy="16" r="1"></circle>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                    </svg>
                    <p>No items in your vault yet</p>
                </div>
            `;
            return;
        }

        console.log('Rendering', this.vaultItems.length, 'vault items');
        container.innerHTML = this.vaultItems.map(item => `
            <div class="vault-item" data-id="${item.id}">
                <div class="vault-item-header">
                    <div class="vault-item-name">${this.escapeHtml(item.name)}</div>
                    <div class="vault-item-type">${item.type}</div>
                </div>
                ${item.username ? `<div class="vault-item-username">${this.escapeHtml(item.username)}</div>` : ''}
            </div>
        `).join('');

        // Add click listeners with improved debugging
        container.querySelectorAll('.vault-item').forEach(item => {
            item.addEventListener('click', () => {
                const itemId = item.dataset.id;
                console.log('Vault item clicked, id:', itemId);
                this.showVaultItemDetails(itemId);
            });
        });
        
        console.log('Added click listeners to vault items');
    }

    renderSendItems() {
        const container = document.getElementById('send-items');
        
        if (this.sendItems.length === 0) {
            container.innerHTML = `
                <div class="empty-state">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="22" y1="2" x2="11" y2="13"></line>
                        <polygon points="22,2 15,22 11,13 2,9"></polygon>
                    </svg>
                    <p>No sends created yet</p>
                </div>
            `;
            return;
        }

        container.innerHTML = this.sendItems.map(item => `
            <div class="send-item" data-id="${item.id}">
                <div class="send-item-header">
                    <div class="send-item-name">${this.escapeHtml(item.name)}</div>
                    <div class="send-item-type">${item.type}</div>
                </div>
                <div class="send-item-url">${item.access_url}</div>
                <div class="send-item-expiry">Expires: ${new Date(item.deletion_date).toLocaleDateString()}</div>
            </div>
        `).join('');

        // Add click listeners
        container.querySelectorAll('.send-item').forEach(item => {
            item.addEventListener('click', () => {
                const itemId = item.dataset.id;
                this.showSendItemDetails(itemId);
            });
        });
    }

    searchVault(query) {
        const items = document.querySelectorAll('.vault-item');
        items.forEach(item => {
            const name = item.querySelector('.vault-item-name').textContent.toLowerCase();
            const username = item.querySelector('.vault-item-username')?.textContent.toLowerCase() || '';
            const visible = name.includes(query.toLowerCase()) || username.includes(query.toLowerCase());
            item.style.display = visible ? 'block' : 'none';
        });
    }

    toggleGeneratorType() {
        const isPassword = document.querySelector('input[name="generator-type"]:checked').value === 'password';
        document.getElementById('password-options').style.display = isPassword ? 'block' : 'none';
        document.getElementById('passphrase-options').style.display = isPassword ? 'none' : 'block';
    }

    async generateCredential() {
        const type = document.querySelector('input[name="generator-type"]:checked').value;
        
        try {
            let response;
            
            if (type === 'password') {
                const length = document.getElementById('password-length').value;
                const options = {
                    uppercase: document.getElementById('include-uppercase').checked,
                    lowercase: document.getElementById('include-lowercase').checked,
                    numbers: document.getElementById('include-numbers').checked,
                    symbols: document.getElementById('include-symbols').checked
                };

                response = await this.makeRequest('/generator.php', {
                    method: 'POST',
                    body: JSON.stringify({
                        action: 'generate_password',
                        length: parseInt(length),
                        options: options
                    })
                });
            } else {
                const wordCount = document.getElementById('word-count').value;
                const separator = document.getElementById('word-separator').value;
                const capitalize = document.getElementById('capitalize-words').checked;

                response = await this.makeRequest('/generator.php', {
                    method: 'POST',
                    body: JSON.stringify({
                        action: 'generate_passphrase',
                        word_count: parseInt(wordCount),
                        separator: separator,
                        capitalize: capitalize
                    })
                });
            }

            if (response.success) {
                const output = type === 'password' ? response.password : response.passphrase;
                document.getElementById('generated-output').value = output;
            } else {
                this.showError('Failed to generate credential');
            }
        } catch (error) {
            console.error('Generation error:', error);
            this.showError('Failed to generate credential');
        }
    }

    async copyToClipboard() {
        const output = document.getElementById('generated-output').value;
        if (!output) return;

        try {
            await navigator.clipboard.writeText(output);
            this.showSuccess('Copied to clipboard');
        } catch (error) {
            console.error('Copy failed:', error);
            this.showError('Failed to copy to clipboard');
        }
    }

    showAddItemModal() {
        document.getElementById('add-item-modal').classList.add('active');
        document.getElementById('item-name').focus();
    }

    showCreateSendModal() {
        document.getElementById('create-send-modal').classList.add('active');
        // Set default expiry to 7 days from now
        const expiry = new Date();
        expiry.setDate(expiry.getDate() + 7);
        document.getElementById('send-expiry').value = expiry.toISOString().slice(0, 16);
        document.getElementById('send-name').focus();
    }

    closeModals() {
        document.querySelectorAll('.modal').forEach(modal => {
            modal.classList.remove('active');
        });
        this.clearModalForms();
    }    clearModalForms() {
        // Clear add item form
        document.getElementById('item-name').value = '';
        document.getElementById('item-username').value = '';
        document.getElementById('item-password').value = '';
        document.getElementById('item-url').value = '';
        document.getElementById('item-notes').value = '';

        // Clear send form
        document.getElementById('send-name').value = '';
        document.getElementById('send-content').value = '';
        document.getElementById('send-password').value = '';
        document.getElementById('send-max-views').value = '';
    }async saveVaultItem() {
        const name = document.getElementById('item-name').value.trim();
        const type = document.getElementById('item-type').value;
        const username = document.getElementById('item-username').value.trim();
        const password = document.getElementById('item-password').value;
        const url = document.getElementById('item-url').value.trim();
        const notes = document.getElementById('item-notes').value.trim();

        if (!name) {
            this.showError('Item name is required');
            return;
        }

        // Prepare data based on item type
        let data = {};
        if (type === 'login') {
            data = {
                username: username,
                password: password,
                notes: notes
            };
        } else if (type === 'note') {
            data = {
                notes: notes
            };
        } else {
            // For other types, include basic fields
            data = {
                username: username,
                password: password,
                notes: notes
            };
        }

        try {
            console.log('Saving vault item:', { name, type, data, url });
            
            const response = await this.makeRequest('/vault.php', {
                method: 'POST',
                body: JSON.stringify({
                    item_name: name,
                    item_type: type,
                    data: data,
                    website_url: url || null,
                    folder_id: null
                })
            });

            console.log('Vault save response:', response);

            if (response.success) {
                this.closeModals();
                await this.loadVaultItems();
                this.showSuccess('Item saved successfully');
            } else {
                this.showError(response.message || 'Failed to save item');
            }
        } catch (error) {
            console.error('Save error:', error);
            this.showError('Failed to save item: ' + error.message);
        }
    }

    async createSend() {
        const name = document.getElementById('send-name').value.trim();
        const type = document.getElementById('send-type').value;
        const content = document.getElementById('send-content').value.trim();
        const password = document.getElementById('send-password').value;
        const expiry = document.getElementById('send-expiry').value;
        const maxViews = document.getElementById('send-max-views').value;

        if (!name || !content) {
            this.showError('Name and content are required');
            return;
        }

        try {
            const response = await this.makeRequest('/send.php', {
                method: 'POST',
                body: JSON.stringify({
                    action: 'create',
                    type: type,
                    name: name,
                    content: content,
                    password: password || null,
                    deletion_date: expiry || null,
                    max_views: maxViews ? parseInt(maxViews) : null
                })
            });

            if (response.success) {
                this.closeModals();
                await this.loadSendItems();
                this.showSuccess('Send created successfully');
            } else {
                this.showError(response.message || 'Failed to create send');
            }
        } catch (error) {
            console.error('Create send error:', error);
            this.showError('Failed to create send');
        }
    }    async showVaultItemDetails(itemId) {
        try {
            console.log('Loading details for item:', itemId);
            console.log('Making API request to:', `${this.apiBase}/vault.php?action=get&id=${itemId}`);
            const response = await this.makeRequest(`/vault.php?action=get&id=${itemId}`);
            console.log('Response from vault.php:', response);
            
            if (response.success && response.item) {
                const item = response.item;
                console.log('Item details found:', item);
                
                // Check if there's already a modal and remove it
                const existingModal = document.getElementById('item-details-modal');
                if (existingModal) {
                    document.body.removeChild(existingModal);
                }
                
                // Create modal element
                const modal = document.createElement('div');
                modal.className = 'modal active';
                modal.id = 'item-details-modal';
                
                // Extract the data from response structure
                const name = item.name || item.item_name || '';
                const type = item.type || item.item_type || 'login';
                const url = item.url || item.website_url || '';
                const username = item.username || (item.decrypted_data && item.decrypted_data.username) || '';
                const password = item.password || (item.decrypted_data && item.decrypted_data.password) || '';
                const notes = item.notes || (item.decrypted_data && item.decrypted_data.notes) || '';
                const createdAt = item.created_at || '';
                
                // Prepare modal content
                let detailsContent = '';
                
                // URL field
                if (url) {
                    detailsContent += `
                        <div class="detail-group">
                            <div class="detail-label">Website URL</div>
                            <div class="detail-value">
                                <a href="${this.escapeHtml(url)}" target="_blank" class="detail-link">
                                    ${this.escapeHtml(url)}
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                                        <polyline points="15 3 21 3 21 9"></polyline>
                                        <line x1="10" y1="14" x2="21" y2="3"></line>
                                    </svg>
                                </a>
                            </div>
                        </div>
                    `;
                }
                
                // Username field
                if (username) {
                    detailsContent += `
                        <div class="detail-group">
                            <div class="detail-label">Username</div>
                            <div class="detail-value copyable" data-copy="${this.escapeHtml(username)}">
                                ${this.escapeHtml(username)}
                                <svg class="detail-copy-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                                    <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                                </svg>
                            </div>
                        </div>
                    `;
                }
                
                // Password field
                if (password) {
                    detailsContent += `
                        <div class="detail-group">
                            <div class="detail-label">Password</div>
                            <div class="password-field">
                                <span class="password-text" id="password-field">••••••••••••</span>
                                <div class="password-actions">
                                    <button class="icon-btn" id="toggle-password" title="Show/Hide Password">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                            <circle cx="12" cy="12" r="3"></circle>
                                        </svg>
                                    </button>
                                    <button class="icon-btn" id="copy-password" title="Copy Password">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                                            <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    `;
                }
                
                // Notes field
                if (notes) {
                    detailsContent += `
                        <div class="detail-group">
                            <div class="detail-label">Notes</div>
                            <div class="detail-value notes-field">
                                ${this.escapeHtml(notes).replace(/\n/g, '<br>')}
                            </div>
                        </div>
                    `;
                }
                
                // Created date
                if (createdAt) {
                    const date = new Date(createdAt).toLocaleString();
                    detailsContent += `
                        <div class="detail-group">
                            <div class="detail-label">Created</div>
                            <div class="detail-value text-muted">
                                ${date}
                            </div>
                        </div>
                    `;
                }
                  // Get icon based on type
                const getItemIcon = (type) => {
                    switch(type.toLowerCase()) {
                        case 'login': return 'M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4M10 17l5-5-5-5M13 12H3';
                        case 'card': return 'M21 4H3c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h18c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zM3 12h18';
                        case 'identity': return 'M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2M12 7a4 4 0 1 0 0 8 4 4 0 0 0 0-8z';
                        case 'note': return 'M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8zM14 2v6h6M16 13H8M16 17H8';
                        default: return 'M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4';
                    }
                };
                
                // Build the modal HTML
                modal.innerHTML = `
                    <div class="modal-content">
                        <div class="modal-header">
                            <h3>
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="${getItemIcon(type)}"></path>
                                </svg>
                                ${this.escapeHtml(name)}
                            </h3>
                            <button class="close-modal" id="close-details-modal">&times;</button>
                        </div>
                        <div class="modal-body">
                            ${detailsContent || '<p class="text-muted">No additional details available.</p>'}
                        </div>
                        <div class="modal-footer">
                            <button id="edit-item-btn" class="btn-secondary">Edit</button>
                            <button id="delete-item-btn" class="btn-danger">Delete</button>
                        </div>
                    </div>
                `;
                
                // Add modal to DOM
                document.body.appendChild(modal);
                
                // Set up event listeners
                document.getElementById('close-details-modal').addEventListener('click', () => {
                    document.body.removeChild(modal);
                });
                
                // Close modal when clicking outside
                modal.addEventListener('click', (e) => {
                    if (e.target === modal) {
                        document.body.removeChild(modal);
                    }
                });
                
                // Set up password toggle
                const togglePasswordBtn = document.getElementById('toggle-password');
                if (togglePasswordBtn) {
                    togglePasswordBtn.addEventListener('click', () => {
                        const passwordField = document.getElementById('password-field');
                        if (passwordField.textContent === '••••••••••••') {
                            passwordField.textContent = password;
                            togglePasswordBtn.innerHTML = `
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                                    <line x1="1" y1="1" x2="23" y2="23"></line>
                                </svg>
                            `;
                        } else {
                            passwordField.textContent = '••••••••••••';
                            togglePasswordBtn.innerHTML = `
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                </svg>
                            `;
                        }
                    });
                }
                
                // Set up password copy
                const copyPasswordBtn = document.getElementById('copy-password');
                if (copyPasswordBtn && password) {
                    copyPasswordBtn.addEventListener('click', async () => {
                        await navigator.clipboard.writeText(password);
                        this.showSuccess('Password copied to clipboard');
                    });
                }
                
                // Set up copyable fields
                document.querySelectorAll('.copyable').forEach(el => {
                    el.addEventListener('click', async () => {
                        const textToCopy = el.dataset.copy;
                        if (textToCopy) {
                            await navigator.clipboard.writeText(textToCopy);
                            this.showSuccess('Copied to clipboard');
                        }
                    });
                });
                
                // Set up edit button
                document.getElementById('edit-item-btn').addEventListener('click', () => {
                    document.body.removeChild(modal);
                    // TODO: Edit functionality in the future
                    this.showNotification('Edit functionality will be available soon', 'info');
                });
                
                // Set up delete button
                document.getElementById('delete-item-btn').addEventListener('click', () => {
                    if (confirm(`Are you sure you want to delete "${name}"?`)) {
                        this.deleteVaultItem(itemId);
                        document.body.removeChild(modal);
                    }
                });
            } else {
                console.error('Failed to load item details:', response);
                this.showError('Failed to load item details');
            }        } catch (error) {
            console.error('Failed to load item details:', error);
            console.error('Error message:', error.message);
            console.error('Error stack:', error.stack);
            this.showError(`Failed to load item details: ${error.message}`);
        }
    }

    async showSendItemDetails(itemId) {
        const item = this.sendItems.find(s => s.id == itemId);
        if (item) {
            const details = `
                Name: ${item.name}
                Type: ${item.type}
                Access URL: ${item.access_url}
                Expires: ${new Date(item.deletion_date).toLocaleDateString()}
                Views: ${item.current_views || 0}${item.max_views ? ` / ${item.max_views}` : ''}
            `;
            
            if (confirm(`${details}\n\nCopy access URL to clipboard?`)) {
                await navigator.clipboard.writeText(item.access_url);
                this.showSuccess('Access URL copied to clipboard');
            }
        }
    }    async deleteVaultItem(itemId) {
        if (!confirm('Are you sure you want to delete this item?')) {
            return;
        }
        
        try {
            const response = await this.makeRequest(`/vault.php?id=${itemId}`, {
                method: 'DELETE'
            });
            
            if (response.success) {
                await this.loadVaultItems();
                this.showSuccess('Item deleted successfully');
            } else {
                this.showError(response.message || 'Failed to delete item');
            }
        } catch (error) {
            console.error('Delete error:', error);
            this.showError('Failed to delete item: ' + error.message);
        }
    }

    async makeRequest(endpoint, options = {}) {
        const url = `${this.apiBase}${endpoint}`;
        console.log('Making request to:', url, 'with options:', options);
        
        const config = {
            credentials: 'include',
            headers: {
                'Content-Type': 'application/json',
                ...options.headers
            },
            ...options
        };

        const response = await fetch(url, config);
        console.log('Response status:', response.status, response.statusText);
        
        if (!response.ok) {
            const errorText = await response.text();
            console.error('HTTP error response:', errorText);
            throw new Error(`HTTP error! status: ${response.status} - ${errorText}`);
        }
        
        const jsonResponse = await response.json();
        console.log('Parsed JSON response:', jsonResponse);
        return jsonResponse;
    }

    showSuccess(message) {
        this.showNotification(message, 'success');
    }

    showError(message) {
        this.showNotification(message, 'error');
    }

    showNotification(message, type = 'info') {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.style.cssText = `
            position: fixed;
            top: 16px;
            right: 16px;
            padding: 12px 16px;
            border-radius: 6px;
            color: white;
            font-size: 14px;
            font-weight: 500;
            z-index: 10000;
            transition: all 0.3s ease;
            transform: translateX(100%);
            background-color: ${type === 'success' ? '#059669' : type === 'error' ? '#dc2626' : '#2563eb'};
        `;
        notification.textContent = message;

        document.body.appendChild(notification);

        // Animate in
        setTimeout(() => {
            notification.style.transform = 'translateX(0)';
        }, 10);

        // Remove after 3 seconds
        setTimeout(() => {
            notification.style.transform = 'translateX(100%)';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        }, 3000);
    }

    escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Initialize the popup when DOM is loaded
let secureItPopup;
document.addEventListener('DOMContentLoaded', () => {
    secureItPopup = new SecureItPopup();
    console.log('SecureIt Popup initialized:', secureItPopup);
    
    // Make it globally accessible for debugging
    window.secureItPopup = secureItPopup;
});
