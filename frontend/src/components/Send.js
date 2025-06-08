import React from 'react';
import './Send.css'; // Optional, for styling

function Send() {
  return (
    <div className="layout">
      <aside className="sidebar">
        <div className="brand">Password Manager</div>
        <nav>
          <ul>
            <li><a href="/vault">Vaults</a></li>
            <li className="active"><a href="/send">Send</a></li>
            <li><a href="/tools/generator">Tools</a></li>
            <li><a href="/reports">Reports</a></li>
            <li><a href="/settings">Settings</a></li>
          </ul>
        </nav>
      </aside>

      <main className="main-content">
        <div className="send-container">
          <h2>Send</h2>
          <p>No active Sends</p>
          <button className="new-send-btn">New Send</button>
        </div>
      </main>
    </div>
  );
}

export default Send;
