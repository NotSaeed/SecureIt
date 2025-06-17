// SecureIt Autofill Content Script
class SecureItAutofill {
    constructor() {
        this.isEnabled = false;
        this.availableItems = [];
        this.observerActive = false;
        this.fillButton = null;
        
        this.init();
    }

    init() {
        // Listen for messages from background script
        chrome.runtime.onMessage.addListener((message, sender, sendResponse) => {
            this.handleMessage(message, sender, sendResponse);
        });

        // Check if we should enable autofill on page load
        this.checkPageForLoginForms();
    }

    handleMessage(message, sender, sendResponse) {
        switch (message.action) {
            case 'enableAutofill':
                this.enableAutofill(message.items);
                sendResponse({ success: true });
                break;

            case 'fillCredentials':
                this.fillCredentials(message.credentials);
                sendResponse({ success: true });
                break;

            case 'insertPassword':
                this.insertPasswordAtCursor(message.password);
                sendResponse({ success: true });
                break;

            case 'sessionExpired':
                this.disableAutofill();
                sendResponse({ success: true });
                break;

            default:
                sendResponse({ success: false, error: 'Unknown action' });
        }
    }

    checkPageForLoginForms() {
        // Look for login forms
        const passwordFields = document.querySelectorAll('input[type="password"]');
        const usernameFields = document.querySelectorAll('input[type="email"], input[type="text"][name*="user"], input[type="text"][name*="email"], input[type="text"][id*="user"], input[type="text"][id*="email"]');

        if (passwordFields.length > 0 && usernameFields.length > 0) {
            // Request vault items for this domain
            const domain = window.location.hostname;
            chrome.runtime.sendMessage({
                action: 'getVaultItems',
                domain: domain
            }, (response) => {
                if (response && response.success && response.items.length > 0) {
                    this.enableAutofill(response.items);
                }
            });
        }
    }

    enableAutofill(items) {
        this.isEnabled = true;
        this.availableItems = items;
        this.addAutofillButtons();
        this.setupFormObserver();
    }

    disableAutofill() {
        this.isEnabled = false;
        this.availableItems = [];
        this.removeAutofillButtons();
        this.stopFormObserver();
    }

    addAutofillButtons() {
        const passwordFields = document.querySelectorAll('input[type="password"]');
        
        passwordFields.forEach(field => {
            if (field.dataset.secureitAutofill) return; // Already has button
            
            // Mark field as processed
            field.dataset.secureitAutofill = 'true';
            
            // Create autofill button
            const button = this.createAutofillButton(field);
            this.positionAutofillButton(button, field);
            
            // Add to page
            document.body.appendChild(button);
        });
    }

    createAutofillButton(field) {
        const button = document.createElement('div');
        button.className = 'secureit-autofill-btn';
        button.innerHTML = `
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                <circle cx="12" cy="16" r="1"></circle>
                <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
            </svg>
        `;
        
        // Styles
        Object.assign(button.style, {
            position: 'absolute',
            width: '32px',
            height: '32px',
            backgroundColor: '#2563eb',
            color: 'white',
            border: 'none',
            borderRadius: '6px',
            cursor: 'pointer',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
            zIndex: '10000',
            boxShadow: '0 2px 8px rgba(0,0,0,0.15)',
            transition: 'all 0.2s ease'
        });

        // Hover effect
        button.addEventListener('mouseenter', () => {
            button.style.backgroundColor = '#1d4ed8';
            button.style.transform = 'scale(1.05)';
        });

        button.addEventListener('mouseleave', () => {
            button.style.backgroundColor = '#2563eb';
            button.style.transform = 'scale(1)';
        });

        // Click handler
        button.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            this.showAutofillMenu(button, field);
        });

        return button;
    }

    positionAutofillButton(button, field) {
        const updatePosition = () => {
            const rect = field.getBoundingClientRect();
            const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            const scrollLeft = window.pageXOffset || document.documentElement.scrollLeft;
            
            button.style.top = `${rect.top + scrollTop + (rect.height - 32) / 2}px`;
            button.style.left = `${rect.right + scrollLeft - 40}px`;
        };

        updatePosition();

        // Update position on scroll and resize
        const observer = new IntersectionObserver(() => updatePosition());
        observer.observe(field);
        
        window.addEventListener('scroll', updatePosition);
        window.addEventListener('resize', updatePosition);
        
        // Store cleanup function
        button._cleanup = () => {
            observer.disconnect();
            window.removeEventListener('scroll', updatePosition);
            window.removeEventListener('resize', updatePosition);
        };
    }

    showAutofillMenu(button, field) {
        // Remove existing menu
        const existingMenu = document.querySelector('.secureit-autofill-menu');
        if (existingMenu) {
            existingMenu.remove();
        }

        if (this.availableItems.length === 0) return;

        const menu = document.createElement('div');
        menu.className = 'secureit-autofill-menu';
        
        // Menu styles
        Object.assign(menu.style, {
            position: 'absolute',
            backgroundColor: 'white',
            border: '1px solid #e2e8f0',
            borderRadius: '8px',
            boxShadow: '0 10px 25px rgba(0,0,0,0.15)',
            zIndex: '10001',
            minWidth: '200px',
            maxHeight: '300px',
            overflowY: 'auto'
        });

        // Add items to menu
        this.availableItems.forEach(item => {
            const menuItem = document.createElement('div');
            menuItem.style.cssText = `
                padding: 12px 16px;
                cursor: pointer;
                border-bottom: 1px solid #f1f5f9;
                transition: background-color 0.2s ease;
            `;
            
            menuItem.innerHTML = `
                <div style="font-weight: 500; color: #0f172a; margin-bottom: 4px;">${this.escapeHtml(item.name)}</div>
                <div style="font-size: 12px; color: #64748b;">${this.escapeHtml(item.username || 'No username')}</div>
            `;

            menuItem.addEventListener('mouseenter', () => {
                menuItem.style.backgroundColor = '#f8fafc';
            });

            menuItem.addEventListener('mouseleave', () => {
                menuItem.style.backgroundColor = 'transparent';
            });

            menuItem.addEventListener('click', () => {
                this.fillCredentials({
                    username: item.username,
                    password: item.password
                });
                menu.remove();
            });

            menu.appendChild(menuItem);
        });

        // Position menu
        const buttonRect = button.getBoundingClientRect();
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        const scrollLeft = window.pageXOffset || document.documentElement.scrollLeft;
        
        menu.style.top = `${buttonRect.bottom + scrollTop + 5}px`;
        menu.style.left = `${buttonRect.left + scrollLeft}px`;

        document.body.appendChild(menu);

        // Close menu when clicking outside
        const closeMenu = (e) => {
            if (!menu.contains(e.target) && !button.contains(e.target)) {
                menu.remove();
                document.removeEventListener('click', closeMenu);
            }
        };

        setTimeout(() => {
            document.addEventListener('click', closeMenu);
        }, 0);
    }

    fillCredentials(credentials) {
        // Find username field
        const usernameSelectors = [
            'input[type="email"]',
            'input[type="text"][name*="user"]',
            'input[type="text"][name*="email"]',
            'input[type="text"][id*="user"]',
            'input[type="text"][id*="email"]',
            'input[name="username"]',
            'input[name="email"]',
            'input[id="username"]',
            'input[id="email"]'
        ];

        let usernameField = null;
        for (const selector of usernameSelectors) {
            usernameField = document.querySelector(selector);
            if (usernameField) break;
        }

        // Find password field
        const passwordField = document.querySelector('input[type="password"]');

        // Fill fields
        if (usernameField && credentials.username) {
            this.fillField(usernameField, credentials.username);
        }

        if (passwordField && credentials.password) {
            this.fillField(passwordField, credentials.password);
        }

        // Show success notification
        this.showNotification('Credentials filled successfully', 'success');
    }

    fillField(field, value) {
        // Set value
        field.value = value;

        // Trigger events to ensure the form recognizes the change
        const events = ['input', 'change', 'keyup', 'paste'];
        events.forEach(eventType => {
            const event = new Event(eventType, { bubbles: true, cancelable: true });
            field.dispatchEvent(event);
        });

        // For React/Vue apps
        const reactEvent = new Event('input', { bubbles: true });
        reactEvent.simulated = true;
        field.dispatchEvent(reactEvent);

        // Set cursor to end
        field.setSelectionRange(value.length, value.length);
    }

    insertPasswordAtCursor(password) {
        const activeElement = document.activeElement;
        
        if (activeElement && (activeElement.tagName === 'INPUT' || activeElement.tagName === 'TEXTAREA')) {
            const start = activeElement.selectionStart;
            const end = activeElement.selectionEnd;
            const currentValue = activeElement.value;
            
            const newValue = currentValue.substring(0, start) + password + currentValue.substring(end);
            activeElement.value = newValue;
            
            // Set cursor position after inserted password
            const newPos = start + password.length;
            activeElement.setSelectionRange(newPos, newPos);
            
            // Trigger change events
            this.fillField(activeElement, newValue);
            
            this.showNotification('Password inserted', 'success');
        }
    }

    removeAutofillButtons() {
        const buttons = document.querySelectorAll('.secureit-autofill-btn');
        buttons.forEach(button => {
            if (button._cleanup) {
                button._cleanup();
            }
            button.remove();
        });

        // Remove autofill markers
        const fields = document.querySelectorAll('input[data-secureit-autofill]');
        fields.forEach(field => {
            delete field.dataset.secureitAutofill;
        });
    }

    setupFormObserver() {
        if (this.observerActive) return;

        this.observer = new MutationObserver((mutations) => {
            let shouldUpdate = false;
            
            mutations.forEach(mutation => {
                mutation.addedNodes.forEach(node => {
                    if (node.nodeType === Node.ELEMENT_NODE) {
                        const hasPasswordField = node.querySelector && node.querySelector('input[type="password"]');
                        if (hasPasswordField || (node.tagName === 'INPUT' && node.type === 'password')) {
                            shouldUpdate = true;
                        }
                    }
                });
            });

            if (shouldUpdate) {
                setTimeout(() => this.addAutofillButtons(), 100);
            }
        });

        this.observer.observe(document.body, {
            childList: true,
            subtree: true
        });

        this.observerActive = true;
    }

    stopFormObserver() {
        if (this.observer) {
            this.observer.disconnect();
            this.observerActive = false;
        }
    }

    showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 12px 20px;
            background-color: ${type === 'success' ? '#059669' : '#2563eb'};
            color: white;
            border-radius: 6px;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            font-size: 14px;
            font-weight: 500;
            z-index: 10002;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            transform: translateX(100%);
            transition: transform 0.3s ease;
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
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Initialize autofill when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        new SecureItAutofill();
    });
} else {
    new SecureItAutofill();
}
