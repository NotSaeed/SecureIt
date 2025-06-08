import React, { useState } from 'react';
import './Settings.css';

function Settings() {
  const [isOpen, setIsOpen] = useState(true);

  return (
    <div className="layout">
      <div className="sidebar">
        <div className="brand">Password Manager</div>
        <ul>
          <li>
            <div className="settings-toggle" onClick={() => setIsOpen(!isOpen)}>
              <span>⚙️ Settings</span>
              <span>{isOpen ? '▲' : '▼'}</span>
            </div>
            {isOpen && (
              <ul className="settings-submenu">
                <li><a href="#">My account</a></li>
                <li><a href="#">Security</a></li>
                <li><a href="#">Preferences</a></li>
                <li><a href="#">Subscription</a></li>
                <li><a href="#">Domain rules</a></li>
                <li><a href="#">Emergency access</a></li>
              </ul>
            )}
          </li>
        </ul>
      </div>
      <div className="main-content">
        <h2>Settings Panel</h2>
        <p>Select a section from the sidebar.</p>
      </div>
    </div>
  );
}

export default Settings;
