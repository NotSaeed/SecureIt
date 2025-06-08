import React from 'react';
import './SendPage.css';

function SendPage() {
  return (
    <div className="vault-layout">
      <aside className="sidebar">
        <h2>Password Manager</h2>
        <nav>
          <ul>
            <li><a href="/vault">Vaults</a></li>
            <li className="active">Send</li>
            <li>Tools</li>
            <li>Reports</li>
            <li>Settings</li>
          </ul>
        </nav>
      </aside>

      <main className="vault-main">
        <header className="vault-header">
          <h1>Send</h1>
        </header>

        <div className="vault-content">
          <div className="filters">
            <input type="text" placeholder="Search Sends..." />
            <ul>
              <li>All Sends</li>
              <li>Text</li>
              <li>File</li>
            </ul>
          </div>

          <div className="send-empty-state">
            <img src="/images/send-icon.svg" alt="Send Icon" />
            <h2>No active Sends</h2>
            <p>Use Send to securely share encrypted information with anyone.</p>
            <button className="new-btn">New Send</button>
          </div>
        </div>
      </main>
    </div>
  );
}

export default SendPage;
