import React from 'react';
import './LoginForm.css';
import { Link, useNavigate } from 'react-router-dom';

function LoginForm() {
  const navigate = useNavigate();

  const handleLogin = (e) => {
    e.preventDefault();
    // For now, skip actual login check
    navigate('/vault');
  };

  return (
    <div className="layout_frontend">
      <div className="login-wrapper">
        <img
          src="/images/logo.svg"
          alt="SecureIT"
          className="login-logo"
        />
        <div className="login-card">
          <h1 className="login-title">Log in to SecureIT</h1>
          <form className="login-form" onSubmit={handleLogin}>
            <label className="login-label" htmlFor="email">
              Email address <span className="required">(required)</span>
            </label>
            <input className="login-input" type="email" id="email" required />

            <div className="remember-container">
              <input type="checkbox" id="remember" />
              <label htmlFor="remember">Remember email</label>
            </div>

            <button className="login-button" type="submit">Continue</button>

            <div className="or-divider">or</div>

            <button className="alt-button" type="button">🔐 Log in with passkey</button>
            <button className="alt-button" type="button">🛡 Use single sign-on</button>
          </form>

          <p className="footer-text">
            New to SecureIT? <Link to="/register">Create account</Link>
          </p>
        </div>

        <footer className="login-footer">
          <small>© 2025 SecureIT Inc. <a href="#">Version 1.0.0</a></small>
        </footer>
      </div>
    </div>
  );
}

export default LoginForm;
