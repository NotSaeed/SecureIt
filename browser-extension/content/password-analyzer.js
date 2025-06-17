/**
 * SecureIt Password Analyzer Content Script
 * Real-time password strength analysis with vault checking
 */

class SecureItPasswordAnalyzer {
    constructor() {
        this.apiBase = 'http://localhost/SecureIt/backend/api';
        this.debounceTimeout = null;
        this.currentPasswordField = null;
        this.currentCircle = null;
        this.currentMessageBox = null;
        this.init();
    }

    init() {
        this.attachEventListeners();
        this.injectStyles();
    }

    injectStyles() {
        if (document.getElementById('secureit-password-analyzer-styles')) {
            return;
        }

        const style = document.createElement('style');
        style.id = 'secureit-password-analyzer-styles';
        style.textContent = `
            .secureit-feedback-circle {
                position: absolute;
                border-radius: 50%;
                width: 18px;
                height: 18px;
                cursor: pointer;
                z-index: 10000;
                transition: all 0.3s ease;
                border: 2px solid #fff;
                box-shadow: 0 2px 8px rgba(0,0,0,0.2);
            }

            .secureit-feedback-circle:hover {
                transform: scale(1.1);
                box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            }

            .secureit-feedback-message {
                position: absolute;
                padding: 8px 12px;
                border-radius: 8px;
                font-size: 12px;
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                z-index: 9999;
                transition: opacity 0.5s ease, transform 0.5s ease;
                opacity: 1;
                white-space: nowrap;
                max-width: 200px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                font-weight: 500;
            }

            .secureit-feedback-circle.leaked {
                background-color: #dc3545;
                animation: pulse-red 2s infinite;
            }

            .secureit-feedback-circle.weak {
                background-color: #fd7e14;
                animation: pulse-orange 2s infinite;
            }

            .secureit-feedback-circle.moderate {
                background-color: #ffc107;
            }

            .secureit-feedback-circle.strong {
                background-color: #28a745;
            }

            .secureit-feedback-circle.vault-match {
                background-color: #6f42c1;
                animation: pulse-purple 2s infinite;
            }

            @keyframes pulse-red {
                0% { box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.7); }
                70% { box-shadow: 0 0 0 10px rgba(220, 53, 69, 0); }
                100% { box-shadow: 0 0 0 0 rgba(220, 53, 69, 0); }
            }

            @keyframes pulse-orange {
                0% { box-shadow: 0 0 0 0 rgba(253, 126, 20, 0.7); }
                70% { box-shadow: 0 0 0 10px rgba(253, 126, 20, 0); }
                100% { box-shadow: 0 0 0 0 rgba(253, 126, 20, 0); }
            }

            @keyframes pulse-purple {
                0% { box-shadow: 0 0 0 0 rgba(111, 66, 193, 0.7); }
                70% { box-shadow: 0 0 0 10px rgba(111, 66, 193, 0); }
                100% { box-shadow: 0 0 0 0 rgba(111, 66, 193, 0); }
            }
        `;
        document.head.appendChild(style);
    }

    attachEventListeners() {
        // Listen for password field inputs
        document.addEventListener('input', (event) => {
            if (event.target.type === 'password' || 
                event.target.getAttribute('type') === 'password' ||
                event.target.name?.toLowerCase().includes('password') ||
                event.target.id?.toLowerCase().includes('password')) {
                this.handlePasswordInput(event.target);
            }
        });

        // Listen for focus to ensure we catch all password fields
        document.addEventListener('focus', (event) => {
            if (event.target.type === 'password' || 
                event.target.getAttribute('type') === 'password' ||
                event.target.name?.toLowerCase().includes('password') ||
                event.target.id?.toLowerCase().includes('password')) {
                if (event.target.value) {
                    this.handlePasswordInput(event.target);
                }
            }
        }, true);

        // Handle dynamic content
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                mutation.addedNodes.forEach((node) => {
                    if (node.nodeType === Node.ELEMENT_NODE) {
                        const passwordFields = node.querySelectorAll('input[type="password"]');
                        passwordFields.forEach(field => {
                            if (field.value) {
                                this.handlePasswordInput(field);
                            }
                        });
                    }
                });
            });
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }

    handlePasswordInput(passwordField) {
        this.currentPasswordField = passwordField;
        
        // Clear existing timeout
        if (this.debounceTimeout) {
            clearTimeout(this.debounceTimeout);
        }

        // Show circle immediately
        this.showFeedbackCircle(passwordField);

        // Debounce the analysis
        this.debounceTimeout = setTimeout(() => {
            this.analyzePassword(passwordField);
        }, 500);
    }

    async showFeedbackCircle(passwordField) {
        // Remove existing elements
        this.removeFeedbackElements();

        if (!passwordField.value) {
            return;
        }

        // Create circle
        this.currentCircle = document.createElement('div');
        this.currentCircle.className = 'secureit-feedback-circle';
        
        // Position the circle
        this.positionCircle(passwordField);

        // Add click listener
        this.currentCircle.addEventListener('click', () => {
            this.openSecureItPopup(passwordField.value);
        });

        document.body.appendChild(this.currentCircle);
    }

    positionCircle(passwordField) {
        if (!this.currentCircle) return;

        const rect = passwordField.getBoundingClientRect();
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        const scrollLeft = window.pageXOffset || document.documentElement.scrollLeft;

        this.currentCircle.style.left = `${rect.right + scrollLeft + 5}px`;
        this.currentCircle.style.top = `${rect.top + scrollTop + (rect.height / 2) - 9}px`;
    }

    async analyzePassword(passwordField) {
        if (!passwordField.value) {
            this.removeFeedbackElements();
            return;
        }

        try {
            // Check if password is in vault first
            const vaultCheck = await this.checkPasswordInVault(passwordField.value);
            
            if (vaultCheck.in_vault) {
                this.updateCircle('vault-match');
                this.showMessage(passwordField, `Used in vault: ${vaultCheck.matched_items[0]?.name || 'Unknown'}`, 'vault-match');
                return;
            }

            // Check if password is leaked
            const isLeaked = await this.checkPasswordLeaked(passwordField.value);
            
            if (isLeaked) {
                this.updateCircle('leaked');
                this.showMessage(passwordField, 'Password found in data breaches!', 'leaked');
                return;
            }

            // Analyze password strength
            const strength = this.analyzePasswordStrength(passwordField.value);
            this.updateCircle(strength.class);
            this.showMessage(passwordField, strength.message, strength.class);

        } catch (error) {
            console.error('SecureIt Password Analysis Error:', error);
            this.updateCircle('weak');
            this.showMessage(passwordField, 'Analysis error', 'weak');
        }
    }

    async checkPasswordInVault(password) {
        try {
            const response = await fetch(`${this.apiBase}/extension.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                credentials: 'include',
                body: JSON.stringify({
                    action: 'check_password_in_vault',
                    password: password,
                    url: window.location.href
                })
            });

            if (!response.ok) {
                throw new Error('Network response was not ok');
            }

            return await response.json();
        } catch (error) {
            console.error('Vault check error:', error);
            return { success: false, in_vault: false, matched_items: [] };
        }
    }

    async checkPasswordLeaked(password) {
        if (password.length < 4) return false;

        try {
            // Generate SHA-1 hash
            const hashBuffer = await crypto.subtle.digest('SHA-1', new TextEncoder().encode(password));
            const hashArray = Array.from(new Uint8Array(hashBuffer));
            const hashHex = hashArray.map(b => b.toString(16).padStart(2, '0')).join('').toUpperCase();
            
            const hashPrefix = hashHex.slice(0, 5);
            const hashSuffix = hashHex.slice(5);

            const response = await fetch(`https://api.pwnedpasswords.com/range/${hashPrefix}`);
            
            if (!response.ok) {
                return false;
            }

            const data = await response.text();
            const hashLines = data.split('\n');
            return hashLines.some(line => line.startsWith(hashSuffix));
        } catch (error) {
            console.error('Pwned password check error:', error);
            return false;
        }
    }

    analyzePasswordStrength(password) {
        if (password.length < 8) {
            return {
                class: 'weak',
                message: 'Password too short (min 8 chars)'
            };
        }

        const hasUpperCase = /[A-Z]/.test(password);
        const hasLowerCase = /[a-z]/.test(password);
        const hasNumbers = /[0-9]/.test(password);
        const hasSpecialChars = /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password);

        const criteriaCount = [hasUpperCase, hasLowerCase, hasNumbers, hasSpecialChars].filter(Boolean).length;

        if (criteriaCount < 3) {
            return {
                class: 'weak',
                message: 'Weak: Add uppercase, numbers, symbols'
            };
        }

        if (criteriaCount === 3 || password.length < 12) {
            return {
                class: 'moderate',
                message: 'Moderate: Consider longer password'
            };
        }

        return {
            class: 'strong',
            message: 'Strong password!'
        };
    }

    updateCircle(strengthClass) {
        if (!this.currentCircle) return;

        // Remove existing strength classes
        this.currentCircle.classList.remove('leaked', 'weak', 'moderate', 'strong', 'vault-match');
        this.currentCircle.classList.add(strengthClass);
    }

    showMessage(passwordField, message, strengthClass) {
        // Remove existing message
        if (this.currentMessageBox) {
            this.currentMessageBox.remove();
        }

        // Create message box
        this.currentMessageBox = document.createElement('div');
        this.currentMessageBox.className = 'secureit-feedback-message';
        this.currentMessageBox.textContent = message;

        // Style based on strength
        this.styleMessage(strengthClass);

        // Position the message
        this.positionMessage(passwordField);

        document.body.appendChild(this.currentMessageBox);

        // Auto-hide after 3 seconds
        setTimeout(() => {
            if (this.currentMessageBox) {
                this.currentMessageBox.style.opacity = '0';
                this.currentMessageBox.style.transform = 'translateX(20px)';
                setTimeout(() => {
                    if (this.currentMessageBox) {
                        this.currentMessageBox.remove();
                        this.currentMessageBox = null;
                    }
                }, 500);
            }
        }, 3000);
    }

    styleMessage(strengthClass) {
        if (!this.currentMessageBox) return;

        const styles = {
            'leaked': { bg: '#f8d7da', color: '#721c24' },
            'weak': { bg: '#fff3cd', color: '#856404' },
            'moderate': { bg: '#d1ecf1', color: '#0c5460' },
            'strong': { bg: '#d4edda', color: '#155724' },
            'vault-match': { bg: '#e2e3f3', color: '#383d47' }
        };

        const style = styles[strengthClass] || styles.weak;
        this.currentMessageBox.style.backgroundColor = style.bg;
        this.currentMessageBox.style.color = style.color;
    }

    positionMessage(passwordField) {
        if (!this.currentMessageBox) return;

        const rect = passwordField.getBoundingClientRect();
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        const scrollLeft = window.pageXOffset || document.documentElement.scrollLeft;

        this.currentMessageBox.style.left = `${rect.right + scrollLeft + 25}px`;
        this.currentMessageBox.style.top = `${rect.top + scrollTop + (rect.height / 2) - 12}px`;
    }

    openSecureItPopup(password) {
        // Send message to background script to open popup
        if (typeof chrome !== 'undefined' && chrome.runtime) {
            chrome.runtime.sendMessage({
                action: 'open_popup_with_password',
                password: password,
                url: window.location.href
            });
        }
    }

    removeFeedbackElements() {
        if (this.currentCircle) {
            this.currentCircle.remove();
            this.currentCircle = null;
        }
        if (this.currentMessageBox) {
            this.currentMessageBox.remove();
            this.currentMessageBox = null;
        }
    }

    // Handle window resize and scroll
    handlePositionUpdate() {
        if (this.currentPasswordField && this.currentCircle) {
            this.positionCircle(this.currentPasswordField);
        }
        if (this.currentPasswordField && this.currentMessageBox) {
            this.positionMessage(this.currentPasswordField);
        }
    }
}

// Initialize the password analyzer
const secureItAnalyzer = new SecureItPasswordAnalyzer();

// Handle position updates
window.addEventListener('scroll', () => {
    secureItAnalyzer.handlePositionUpdate();
});

window.addEventListener('resize', () => {
    secureItAnalyzer.handlePositionUpdate();
});

// Clean up on page unload
window.addEventListener('beforeunload', () => {
    secureItAnalyzer.removeFeedbackElements();
});
