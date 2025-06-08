import React, { useState } from 'react';
import './PasswordGenerator.css';

function PasswordGenerator() {
  const [tab, setTab] = useState('password');

  const renderTab = () => {
    switch (tab) {
      case 'password':
        return (
          <div className="generator-options">
            <label>
              Length
              <input type="number" defaultValue={14} min={5} max={128} />
            </label>
            <div className="checkbox-row">
              <label><input type="checkbox" defaultChecked /> A-Z</label>
              <label><input type="checkbox" defaultChecked /> a-z</label>
              <label><input type="checkbox" defaultChecked /> 0-9</label>
              <label><input type="checkbox" /> !@#$%^&*</label>
            </div>
            <label>
              Minimum numbers
              <input type="number" defaultValue={1} />
            </label>
            <label>
              Minimum special
              <input type="number" defaultValue={0} />
            </label>
            <label><input type="checkbox" /> Avoid ambiguous characters</label>
          </div>
        );
      case 'passphrase':
        return (
          <div className="generator-options">
            <label>
              Number of words
              <input type="number" defaultValue={6} min={3} max={20} />
            </label>
            <label>
              Word separator
              <input type="text" defaultValue="-" />
            </label>
            <label><input type="checkbox" /> Capitalize</label>
            <label><input type="checkbox" /> Include number</label>
          </div>
        );
      case 'username':
        return (
          <div className="generator-options">
            <label>Type
              <select>
                <option>Random word</option>
                <option>UUID</option>
              </select>
            </label>
            <label><input type="checkbox" /> Capitalize</label>
            <label><input type="checkbox" /> Include number</label>
          </div>
        );
      default:
        return null;
    }
  };

  return (
    <div className="generator-page">
      <aside className="sidebar">
        <h2>Password Manager</h2>
        <ul>
          <li><a href="/vault">Vaults</a></li>
          <li><a href="/send">Send</a></li>
          <li className="active">Tools</li>
          
        </ul>
      </aside>

      <main className="generator-main">
        <h1>Generator</h1>
        <div className="tabs">
          <button className={tab === 'password' ? 'active' : ''} onClick={() => setTab('password')}>Password</button>
          <button className={tab === 'passphrase' ? 'active' : ''} onClick={() => setTab('passphrase')}>Passphrase</button>
          <button className={tab === 'username' ? 'active' : ''} onClick={() => setTab('username')}>Username</button>
        </div>

        <div className="generated-password">1EXo7BX5rywJ7Fk</div>

        <div className="generator-actions">
          <button>⟳</button>
          <button>📋</button>
        </div>

        {renderTab()}

        <div className="history-link">Generator history</div>
      </main>
    </div>
  );
}

export default PasswordGenerator;
