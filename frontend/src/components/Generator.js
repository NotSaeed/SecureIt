import React from 'react';
import './Generator.css'; // Optional

function Generator() {
  return (
    <div className="layout">
      <aside className="sidebar">
        <div className="brand">Password Manager</div>
        <nav>
          <ul>
            <li><a href="/vault">Vaults</a></li>
            <li><a href="/send">Send</a></li>
            <li className="active"><a href="/tools/generator">Tools</a></li>
            <li><a href="/reports">Reports</a></li>
            <li><a href="/settings">Settings</a></li>
          </ul>
        </nav>
      </aside>

      <main className="main-content">
        <h2>Password Generator</h2>
        <div className="generator-section">
          <input type="text" placeholder="Generated password will appear here" />
          <div>
            <label>Length: <input type="number" min="5" max="128" defaultValue="14" /></label>
            <label><input type="checkbox" defaultChecked /> A-Z</label>
            <label><input type="checkbox" defaultChecked /> a-z</label>
            <label><input type="checkbox" defaultChecked /> 0-9</label>
            <label><input type="checkbox" /> @#$%</label>
          </div>
        </div>
      </main>
    </div>
  );
}

export default Generator;
