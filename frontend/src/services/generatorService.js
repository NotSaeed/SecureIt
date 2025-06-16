const API_BASE_URL = 'http://localhost/SecureIT/backend/api';

class GeneratorService {
    async generatePassword(length = 14, options = {}) {
        try {
            const response = await fetch(`${API_BASE_URL}/generator.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                credentials: 'include',
                body: JSON.stringify({
                    action: 'generate_password',
                    length,
                    options
                })
            });

            const data = await response.json();
            if (!data.success) {
                throw new Error(data.message);
            }

            return data.password;
        } catch (error) {
            console.error('Password generation failed:', error);
            throw error;
        }
    }

    async generatePassphrase(wordCount = 6, separator = '-', capitalize = false, includeNumber = false) {
        try {
            const response = await fetch(`${API_BASE_URL}/generator.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                credentials: 'include',
                body: JSON.stringify({
                    action: 'generate_passphrase',
                    word_count: wordCount,
                    separator,
                    capitalize,
                    include_number: includeNumber
                })
            });

            const data = await response.json();
            if (!data.success) {
                throw new Error(data.message);
            }

            return data.passphrase;
        } catch (error) {
            console.error('Passphrase generation failed:', error);
            throw error;
        }
    }

    async generateUsername(type = 'random_word', capitalize = false, includeNumber = false) {
        try {
            const response = await fetch(`${API_BASE_URL}/generator.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                credentials: 'include',
                body: JSON.stringify({
                    action: 'generate_username',
                    type,
                    capitalize,
                    include_number: includeNumber
                })
            });

            const data = await response.json();
            if (!data.success) {
                throw new Error(data.message);
            }

            return data.username;
        } catch (error) {
            console.error('Username generation failed:', error);
            throw error;
        }
    }

    async checkPasswordStrength(password) {
        try {
            const response = await fetch(`${API_BASE_URL}/generator.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                credentials: 'include',
                body: JSON.stringify({
                    action: 'check_strength',
                    password
                })
            });

            const data = await response.json();
            if (!data.success) {
                throw new Error(data.message);
            }

            return data.strength;
        } catch (error) {
            console.error('Password strength check failed:', error);
            throw error;
        }
    }

    async getGeneratorHistory(type = null, limit = 10) {
        try {
            const params = new URLSearchParams({ action: 'history', limit });
            if (type) params.append('type', type);

            const response = await fetch(`${API_BASE_URL}/generator.php?${params}`, {
                method: 'GET',
                credentials: 'include',
            });

            const data = await response.json();
            if (!data.success) {
                throw new Error(data.message);
            }

            return data.history;
        } catch (error) {
            console.error('Failed to fetch generator history:', error);
            throw error;
        }
    }
}

export default new GeneratorService();
