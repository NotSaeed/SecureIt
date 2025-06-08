import React from 'react';
import './VaultDashboard.css';
import { Link } from 'react-router-dom';

function VaultDashboard() {
  return (
    <div className="vault-layout">
      <aside className="sidebar">
        <h2>Password Manager</h2>
        <nav>
<ul>
  <li><Link to="/vault">Vaults</Link></li>
  <li><Link to="/send">Send</Link></li>
  <li><Link to="/tools/generator">Tools</Link></li>
  <li><Link to="/reports">Reports</Link></li>
  <li><Link to="/settings">Settings</Link></li>
</ul>
        </nav>
      </aside>

      <main className="vault-main">
        <header className="vault-header">
          <h1>All vaults</h1>
          <button className="new-btn">+ New</button>
        </header>

        <div className="vault-content">
          <div className="filters">
            <input type="text" placeholder="Search logins..." />
            <ul>
              <li>All items</li>
              <li>Favorites</li>
              <li>Login</li>
              <li>Card</li>
              <li>Identity</li>
              <li>Note</li>
              <li>SSH Key</li>
              <li>Trash</li>
            </ul>
          </div>

          <div className="vault-list">
            <table>
              <thead>
                <tr>
                  <th>Name</th>
                  <th>Owner</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td>Icloud</td>
                  <td><span className="owner-pill">Me</span></td>
                </tr>
                <tr>
                  <td>Recovery key apple phone</td>
                  <td><span className="owner-pill">Me</span></td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </main>
    </div>
  );
}

export default VaultDashboard;
