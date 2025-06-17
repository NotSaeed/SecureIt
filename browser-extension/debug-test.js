// Debug test for SecureIt Extension
console.log('SecureIt Extension Debug Test');
console.log('API Base:', 'http://localhost/SecureIt/backend/api');

// Test basic API connectivity
async function testAPI() {
    try {
        console.log('Testing auth API...');
        const response = await fetch('http://localhost/SecureIt/backend/api/auth.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            credentials: 'include',
            body: JSON.stringify({ action: 'check_session' })
        });
        
        console.log('Auth API Response Status:', response.status);
        const data = await response.json();
        console.log('Auth API Response Data:', data);
        
        if (data.success && data.user) {
            console.log('User authenticated, testing vault API...');
            
            const vaultResponse = await fetch('http://localhost/SecureIt/backend/api/vault.php?action=list', {
                credentials: 'include'
            });
            
            console.log('Vault API Response Status:', vaultResponse.status);
            const vaultData = await vaultResponse.json();
            console.log('Vault API Response Data:', vaultData);
            
            if (vaultData.success) {
                console.log('Vault items found:', vaultData.items?.length || 0);
                vaultData.items?.forEach((item, index) => {
                    console.log(`Item ${index + 1}:`, item);
                });
            }
        } else {
            console.log('User not authenticated');
        }
        
    } catch (error) {
        console.error('API Test Error:', error);
    }
}

// Run test after DOM loads
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', testAPI);
} else {
    testAPI();
}
