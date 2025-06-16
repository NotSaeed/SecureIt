import React, { useState, useEffect } from 'react';
import generatorService from '../services/generatorService';

const PasswordGenerator = () => {
    const [activeTab, setActiveTab] = useState('password');
    const [generatedValue, setGeneratedValue] = useState('');
    const [isGenerating, setIsGenerating] = useState(false);
    const [history, setHistory] = useState([]);
    const [showHistory, setShowHistory] = useState(false);

    // Password options
    const [passwordLength, setPasswordLength] = useState(14);
    const [passwordOptions, setPasswordOptions] = useState({
        uppercase: true,
        lowercase: true,
        numbers: true,
        symbols: false,
        avoid_ambiguous: false,
        min_numbers: 1,
        min_symbols: 0
    });

    // Passphrase options
    const [wordCount, setWordCount] = useState(6);
    const [separator, setSeparator] = useState('-');
    const [capitalize, setCapitalize] = useState(false);
    const [includeNumber, setIncludeNumber] = useState(false);

    // Username options
    const [usernameType, setUsernameType] = useState('random_word');
    const [usernameCapitalize, setUsernameCapitalize] = useState(false);
    const [usernameIncludeNumber, setUsernameIncludeNumber] = useState(false);

    // Password strength
    const [passwordStrength, setPasswordStrength] = useState(null);    useEffect(() => {
        // Auto-generate when component loads or tab changes
        if (activeTab === 'password') {
            generatePassword();
        } else if (activeTab === 'passphrase') {
            generatePassphrase();
        } else if (activeTab === 'username') {
            generateUsername();
        }
    }, [activeTab]);

    useEffect(() => {
        // Auto-regenerate when options change
        if (activeTab === 'password' && generatedValue) {
            generatePassword();
        }
    }, [passwordLength, passwordOptions]);

    useEffect(() => {
        // Auto-regenerate passphrase when options change
        if (activeTab === 'passphrase' && generatedValue) {
            generatePassphrase();
        }
    }, [wordCount, separator, capitalize, includeNumber]);

    useEffect(() => {
        // Auto-regenerate username when options change
        if (activeTab === 'username' && generatedValue) {
            generateUsername();
        }
    }, [usernameType, usernameCapitalize, usernameIncludeNumber]);    const generatePassword = async () => {
        setIsGenerating(true);
        try {
            const password = await generatorService.generatePassword(passwordLength, passwordOptions);
            setGeneratedValue(password);
            // Check strength for passwords
            checkPasswordStrength(password);
        } catch (error) {
            console.error('Password generation failed:', error);
            // Fallback to client-side generation
            const fallbackPassword = generateFallbackPassword();
            setGeneratedValue(fallbackPassword);
            checkPasswordStrength(fallbackPassword);
        } finally {
            setIsGenerating(false);
        }
    };

    const generateFallbackPassword = () => {
        let charset = '';
        if (passwordOptions.uppercase) charset += 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        if (passwordOptions.lowercase) charset += 'abcdefghijklmnopqrstuvwxyz';
        if (passwordOptions.numbers) charset += '0123456789';
        if (passwordOptions.symbols) charset += '!@#$%^&*()_+-=[]{}|;:,.<>?';
        
        if (passwordOptions.avoid_ambiguous) {
            charset = charset.replace(/[0O1lI|]/g, '');
        }
        
        let password = '';
        for (let i = 0; i < passwordLength; i++) {
            password += charset.charAt(Math.floor(Math.random() * charset.length));
        }
        return password;
    };    const generatePassphrase = async () => {
        setIsGenerating(true);
        try {
            const passphrase = await generatorService.generatePassphrase(
                wordCount, 
                separator, 
                capitalize, 
                includeNumber
            );
            setGeneratedValue(passphrase);
        } catch (error) {
            console.error('Passphrase generation failed:', error);
            // Fallback to client-side generation
            const fallbackPassphrase = generateFallbackPassphrase();
            setGeneratedValue(fallbackPassphrase);
        } finally {
            setIsGenerating(false);
        }
    };

    const generateFallbackPassphrase = () => {
        const words = ['apple', 'banana', 'cherry', 'dragon', 'eagle', 'forest', 'garden', 'happy',
            'island', 'jungle', 'kitten', 'lemon', 'mountain', 'ocean', 'purple', 'quiet',
            'rainbow', 'sunset', 'tiger', 'umbrella', 'violet', 'water', 'yellow', 'zebra',
            'adventure', 'butterfly', 'crystal', 'diamond', 'elephant', 'firefly', 'galaxy',
            'harmony', 'infinite', 'journey', 'kingdom', 'liberty', 'miracle', 'nature'];
        
        let selectedWords = [];
        for (let i = 0; i < wordCount; i++) {
            let word = words[Math.floor(Math.random() * words.length)];
            if (capitalize) {
                word = word.charAt(0).toUpperCase() + word.slice(1);
            }
            selectedWords.push(word);
        }
        
        if (includeNumber) {
            selectedWords.push(Math.floor(Math.random() * 900) + 100);
        }
        
        return selectedWords.join(separator);
    };    const generateUsername = async () => {
        setIsGenerating(true);
        try {
            const username = await generatorService.generateUsername(
                usernameType, 
                usernameCapitalize, 
                usernameIncludeNumber
            );
            setGeneratedValue(username);
        } catch (error) {
            console.error('Username generation failed:', error);
            // Fallback to client-side generation
            const fallbackUsername = generateFallbackUsername();
            setGeneratedValue(fallbackUsername);
        } finally {
            setIsGenerating(false);
        }
    };

    const generateFallbackUsername = () => {
        const words = ['voltage', 'thunder', 'phoenix', 'galaxy', 'storm', 'shadow', 'flame', 'frost'];
        const adjectives = ['swift', 'bright', 'clever', 'brave', 'calm', 'wise', 'bold', 'keen'];
        
        let username = '';
        
        if (usernameType === 'random_word') {
            username = words[Math.floor(Math.random() * words.length)];
        } else if (usernameType === 'combination') {
            const adj = adjectives[Math.floor(Math.random() * adjectives.length)];
            const noun = words[Math.floor(Math.random() * words.length)];
            username = adj + noun;
        } else if (usernameType === 'uuid') {
            return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
                const r = Math.random() * 16 | 0;
                const v = c === 'x' ? r : (r & 0x3 | 0x8);
                return v.toString(16);
            });
        }
        
        if (usernameCapitalize) {
            username = username.charAt(0).toUpperCase() + username.slice(1);
        }
        
        if (usernameIncludeNumber) {
            username += Math.floor(Math.random() * 900) + 100;
        }
        
        return username;
    };

    const checkPasswordStrength = async (password) => {
        try {
            const strength = await generatorService.checkPasswordStrength(password);
            setPasswordStrength(strength);
        } catch (error) {
            console.error('Password strength check failed:', error);
        }
    };

    const copyToClipboard = () => {
        navigator.clipboard.writeText(generatedValue);
        alert('Copied to clipboard!');
    };

    const loadHistory = async () => {
        try {
            const historyData = await generatorService.getGeneratorHistory(activeTab, 10);
            setHistory(historyData);
            setShowHistory(true);
        } catch (error) {
            console.error('Failed to load history:', error);
        }
    };    return (
        <div className="generator-container">
            <div className="generator-header">
                <h1>Generator</h1>
            </div>

            {/* Tab Navigation */}
            <div className="generator-tabs">
                <button 
                    className={`tab-button ${activeTab === 'password' ? 'active' : ''}`}
                    onClick={() => setActiveTab('password')}
                >
                    Password
                </button>
                <button 
                    className={`tab-button ${activeTab === 'passphrase' ? 'active' : ''}`}
                    onClick={() => setActiveTab('passphrase')}
                >
                    Passphrase
                </button>
                <button 
                    className={`tab-button ${activeTab === 'username' ? 'active' : ''}`}
                    onClick={() => setActiveTab('username')}
                >
                    Username
                </button>
            </div>

            {/* Generated Value Display */}
            <div className="generated-display">
                <div className="generated-value">
                    {generatedValue || 'Click regenerate to generate...'}
                </div>
                <div className="generated-actions">
                    <button 
                        className="action-btn" 
                        onClick={() => {
                            if (activeTab === 'password') generatePassword();
                            else if (activeTab === 'passphrase') generatePassphrase();
                            else generateUsername();
                        }}
                        disabled={isGenerating}
                        title="Regenerate"
                    >
                        🔄
                    </button>
                    <button 
                        className="action-btn" 
                        onClick={copyToClipboard}
                        disabled={!generatedValue}
                        title="Copy to clipboard"
                    >
                        📋
                    </button>
                </div>
            </div>

            {/* Options Section */}
            <div className="options-section">
                <h3>Options</h3>
                
                {activeTab === 'password' && (
                    <div className="password-options">
                        <div className="option-group">
                            <label className="option-label">Length</label>
                            <input
                                type="number"
                                min="5"
                                max="128"
                                value={passwordLength}
                                onChange={(e) => setPasswordLength(Number(e.target.value))}
                                className="length-input"
                            />
                            <div className="option-hint">
                                Value must be between 5 and 128. Use 14 characters or more to generate a strong password.
                            </div>
                        </div>

                        <div className="option-group">
                            <label className="section-label">Include</label>
                            <div className="checkbox-row">
                                <label className="checkbox-label">
                                    <input
                                        type="checkbox"
                                        checked={passwordOptions.uppercase}
                                        onChange={(e) => setPasswordOptions({
                                            ...passwordOptions,
                                            uppercase: e.target.checked
                                        })}
                                    />
                                    A-Z
                                </label>
                                <label className="checkbox-label">
                                    <input
                                        type="checkbox"
                                        checked={passwordOptions.lowercase}
                                        onChange={(e) => setPasswordOptions({
                                            ...passwordOptions,
                                            lowercase: e.target.checked
                                        })}
                                    />
                                    a-z
                                </label>
                                <label className="checkbox-label">
                                    <input
                                        type="checkbox"
                                        checked={passwordOptions.numbers}
                                        onChange={(e) => setPasswordOptions({
                                            ...passwordOptions,
                                            numbers: e.target.checked
                                        })}
                                    />
                                    0-9
                                </label>
                                <label className="checkbox-label">
                                    <input
                                        type="checkbox"
                                        checked={passwordOptions.symbols}
                                        onChange={(e) => setPasswordOptions({
                                            ...passwordOptions,
                                            symbols: e.target.checked
                                        })}
                                    />
                                    !@#$%*&
                                </label>
                            </div>
                        </div>

                        <div className="option-group">
                            <div className="number-inputs">
                                <div className="number-input-group">
                                    <label className="option-label">Minimum numbers</label>
                                    <input
                                        type="number"
                                        min="0"
                                        max="10"
                                        value={passwordOptions.min_numbers}
                                        onChange={(e) => setPasswordOptions({
                                            ...passwordOptions,
                                            min_numbers: Number(e.target.value)
                                        })}
                                        className="number-input"
                                    />
                                </div>
                                <div className="number-input-group">
                                    <label className="option-label">Minimum special</label>
                                    <input
                                        type="number"
                                        min="0"
                                        max="10"
                                        value={passwordOptions.min_symbols}
                                        onChange={(e) => setPasswordOptions({
                                            ...passwordOptions,
                                            min_symbols: Number(e.target.value)
                                        })}
                                        className="number-input"
                                    />
                                </div>
                            </div>
                        </div>

                        <div className="option-group">
                            <label className="checkbox-label">
                                <input
                                    type="checkbox"
                                    checked={passwordOptions.avoid_ambiguous}
                                    onChange={(e) => setPasswordOptions({
                                        ...passwordOptions,
                                        avoid_ambiguous: e.target.checked
                                    })}
                                />
                                Avoid ambiguous characters
                            </label>
                        </div>
                    </div>
                )}

                {activeTab === 'passphrase' && (
                    <div className="passphrase-options">
                        <div className="option-group">
                            <label className="option-label">Number of words</label>
                            <input
                                type="number"
                                min="3"
                                max="20"
                                value={wordCount}
                                onChange={(e) => setWordCount(Number(e.target.value))}
                                className="length-input"
                            />
                            <div className="option-hint">
                                Value must be between 3 and 20. Use 6 words or more to generate a strong passphrase.
                            </div>
                        </div>

                        <div className="option-group">
                            <label className="option-label">Word separator</label>
                            <input
                                type="text"
                                value={separator}
                                onChange={(e) => setSeparator(e.target.value)}
                                className="separator-input"
                                placeholder="Enter separator"
                            />
                        </div>

                        <div className="option-group">
                            <label className="checkbox-label">
                                <input
                                    type="checkbox"
                                    checked={capitalize}
                                    onChange={(e) => setCapitalize(e.target.checked)}
                                />
                                Capitalize
                            </label>
                        </div>

                        <div className="option-group">
                            <label className="checkbox-label">
                                <input
                                    type="checkbox"
                                    checked={includeNumber}
                                    onChange={(e) => setIncludeNumber(e.target.checked)}
                                />
                                Include number
                            </label>
                        </div>
                    </div>
                )}

                {activeTab === 'username' && (
                    <div className="username-options">
                        <div className="option-group">
                            <label className="option-label">Type</label>
                            <select
                                value={usernameType}
                                onChange={(e) => setUsernameType(e.target.value)}
                                className="type-select"
                            >
                                <option value="random_word">Random word</option>
                                <option value="combination">Word combination</option>
                                <option value="uuid">UUID</option>
                            </select>
                        </div>

                        {usernameType !== 'uuid' && (
                            <>
                                <div className="option-group">
                                    <label className="checkbox-label">
                                        <input
                                            type="checkbox"
                                            checked={usernameCapitalize}
                                            onChange={(e) => setUsernameCapitalize(e.target.checked)}
                                        />
                                        Capitalize
                                    </label>
                                </div>

                                <div className="option-group">
                                    <label className="checkbox-label">
                                        <input
                                            type="checkbox"
                                            checked={usernameIncludeNumber}
                                            onChange={(e) => setUsernameIncludeNumber(e.target.checked)}
                                        />
                                        Include number
                                    </label>
                                </div>
                            </>
                        )}
                    </div>
                )}
            </div>

            {/* Generator History */}
            <div className="history-section">
                <button 
                    className="history-toggle"
                    onClick={() => {
                        if (!showHistory) {
                            loadHistory();
                        } else {
                            setShowHistory(false);
                        }
                    }}
                >
                    Generator history
                </button>
                
                {showHistory && (
                    <div className="history-content">
                        {history.length === 0 ? (
                            <p className="no-history">No history available</p>
                        ) : (
                            <div className="history-list">
                                {history.map((item, index) => (
                                    <div key={index} className="history-item">
                                        <span className="history-value">{item.generated_value}</span>
                                        <span className="history-date">
                                            {new Date(item.created_at).toLocaleDateString()}
                                        </span>
                                    </div>
                                ))}
                            </div>
                        )}
                    </div>
                )}
            </div>

            <style jsx>{`
                .generator-container {
                    max-width: 800px;
                    margin: 0 auto;
                    padding: 2rem;
                    background: #ffffff;
                    min-height: 100vh;
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                }

                .generator-header h1 {
                    font-size: 2rem;
                    font-weight: 400;
                    color: #333;
                    margin: 0 0 2rem 0;
                }

                .generator-tabs {
                    display: flex;
                    margin-bottom: 2rem;
                    background: #f8f9fa;
                    border-radius: 8px;
                    padding: 4px;
                }

                .tab-button {
                    flex: 1;
                    padding: 12px 24px;
                    border: none;
                    background: transparent;
                    color: #666;
                    font-size: 1rem;
                    font-weight: 500;
                    border-radius: 6px;
                    cursor: pointer;
                    transition: all 0.2s ease;
                }

                .tab-button:hover {
                    color: #333;
                }

                .tab-button.active {
                    background: #175ddc;
                    color: white;
                    box-shadow: 0 2px 4px rgba(23, 93, 220, 0.2);
                }

                .generated-display {
                    display: flex;
                    align-items: center;
                    gap: 1rem;
                    padding: 1rem;
                    background: #f8f9fa;
                    border-radius: 8px;
                    margin-bottom: 2rem;
                    border: 1px solid #e9ecef;
                }

                .generated-value {
                    flex: 1;
                    font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
                    font-size: 1.1rem;
                    color: #333;
                    word-break: break-all;
                    line-height: 1.4;
                }

                .generated-actions {
                    display: flex;
                    gap: 0.5rem;
                }

                .action-btn {
                    width: 36px;
                    height: 36px;
                    border: none;
                    background: #e9ecef;
                    border-radius: 6px;
                    cursor: pointer;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 1rem;
                    transition: background 0.2s ease;
                }

                .action-btn:hover:not(:disabled) {
                    background: #dee2e6;
                }

                .action-btn:disabled {
                    opacity: 0.5;
                    cursor: not-allowed;
                }

                .options-section {
                    margin-bottom: 2rem;
                }

                .options-section h3 {
                    font-size: 1.25rem;
                    font-weight: 500;
                    color: #333;
                    margin: 0 0 1.5rem 0;
                }

                .option-group {
                    margin-bottom: 1.5rem;
                }

                .option-label {
                    display: block;
                    font-weight: 500;
                    color: #333;
                    margin-bottom: 0.5rem;
                    font-size: 0.9rem;
                }

                .section-label {
                    display: block;
                    font-weight: 500;
                    color: #333;
                    margin-bottom: 1rem;
                    font-size: 0.9rem;
                }

                .length-input, .number-input, .separator-input {
                    width: 100%;
                    max-width: 200px;
                    padding: 0.75rem;
                    border: 1px solid #ced4da;
                    border-radius: 6px;
                    font-size: 1rem;
                    background: white;
                }

                .type-select {
                    width: 100%;
                    max-width: 300px;
                    padding: 0.75rem;
                    border: 1px solid #ced4da;
                    border-radius: 6px;
                    font-size: 1rem;
                    background: white;
                }

                .option-hint {
                    font-size: 0.8rem;
                    color: #6c757d;
                    margin-top: 0.5rem;
                    line-height: 1.4;
                }

                .checkbox-row {
                    display: flex;
                    gap: 2rem;
                    flex-wrap: wrap;
                }

                .checkbox-label {
                    display: flex;
                    align-items: center;
                    gap: 0.5rem;
                    font-size: 0.9rem;
                    color: #333;
                    cursor: pointer;
                    margin: 0;
                }

                .checkbox-label input[type="checkbox"] {
                    width: 16px;
                    height: 16px;
                    margin: 0;
                }

                .number-inputs {
                    display: flex;
                    gap: 2rem;
                }

                .number-input-group {
                    flex: 1;
                }

                .number-input {
                    max-width: 100px;
                }

                .history-section {
                    border-top: 1px solid #e9ecef;
                    padding-top: 1.5rem;
                }

                .history-toggle {
                    background: none;
                    border: none;
                    color: #175ddc;
                    font-size: 1rem;
                    cursor: pointer;
                    padding: 0;
                    text-decoration: none;
                }

                .history-toggle:hover {
                    text-decoration: underline;
                }

                .history-content {
                    margin-top: 1rem;
                    padding: 1rem;
                    background: #f8f9fa;
                    border-radius: 8px;
                }

                .no-history {
                    color: #6c757d;
                    text-align: center;
                    margin: 0;
                    padding: 1rem;
                }

                .history-list {
                    display: flex;
                    flex-direction: column;
                    gap: 0.5rem;
                }

                .history-item {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    padding: 0.5rem;
                    background: white;
                    border-radius: 4px;
                    font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
                    font-size: 0.9rem;
                }

                .history-value {
                    color: #333;
                    word-break: break-all;
                }

                .history-date {
                    color: #6c757d;
                    font-size: 0.8rem;
                    white-space: nowrap;
                    margin-left: 1rem;
                }

                @media (max-width: 768px) {
                    .generator-container {
                        padding: 1rem;
                    }
                    
                    .checkbox-row {
                        gap: 1rem;
                    }
                    
                    .number-inputs {
                        flex-direction: column;
                        gap: 1rem;
                    }
                    
                    .history-item {
                        flex-direction: column;
                        align-items: flex-start;
                        gap: 0.25rem;
                    }
                    
                    .history-date {
                        margin-left: 0;
                    }
                }
            `}</style>
        </div>
    );
};

export default PasswordGenerator;
