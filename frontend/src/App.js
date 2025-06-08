import { BrowserRouter as Router, Routes, Route } from 'react-router-dom';
import Sidebar from './components/Sidebar';
import VaultPage from './components/VaultPage';
import SendPage from './components/SendPage';
import GeneratorPage from './components/GeneratorPage';
import ReportsPage from './components/ReportsPage';
import SettingsAccount from './components/SettingsAccount';
import SettingsSecurity from './components/SettingsSecurity';

function App() {
  return (
    <Router>
      <div className="app-container">
        <Sidebar />
        <div className="main-content">
          <Routes>
            <Route path="/vault" element={<VaultPage />} />
            <Route path="/send" element={<SendPage />} />
            <Route path="/tools/generator" element={<GeneratorPage />} />
            <Route path="/reports" element={<ReportsPage />} />
            <Route path="/settings/account" element={<SettingsAccount />} />
            <Route path="/settings/security" element={<SettingsSecurity />} />
            {/* Add more subroutes as needed */}
          </Routes>
        </div>
      </div>
    </Router>
  );
}

export default App;
