import React, { useState } from 'react';
import { Link } from 'react-router-dom';
import './Sidebar.css';

function Sidebar() {
  const [settingsOpen, setSettingsOpen] = useState(true); // Keep it expanded

  return (
    <div className="sidebar">
      <h2>Password Manager</h2>
      <nav>
        <Link to="/vault">Vaults</Link>
        <Link to="/send">Send</Link>
        <Link to="/tools/generator">Tools</Link>
        <Link to="/reports">Reports</Link>
        
        <div className="settings-section">
          <button onClick={() => setSettingsOpen(!settingsOpen)} className="settings-toggle">
            <span role="img" aria-label="settings">⚙️</span> Settings {settingsOpen ? '▲' : '▼'}
          </button>
          {settingsOpen && (
            <div className="settings-submenu">
              <Link to="/settings/account">My account</Link>
              <Link to="/settings/security">Security</Link>
              <Link to="/settings/preferences">Preferences</Link>
              <Link to="/settings/subscription">Subscription</Link>
              <Link to="/settings/domain-rules">Domain rules</Link>
              <Link to="/settings/emergency-access">Emergency access</Link>
            </div>
          )}
        </div>
      </nav>
    </div>
  );
}

export default Sidebar;
