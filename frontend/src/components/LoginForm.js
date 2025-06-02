import React from 'react';
import './LoginForm.css';

function LoginForm() {
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
          <form className="login-form">
            <label className="login-label" htmlFor="email">
              Email address <span className="required">(required)</span>
            </label>
            <input className="login-input" type="email" id="email" required />

            <div className="remember-container">
              <input type="checkbox" id="remember" />
              <label htmlFor="remember">Remember email</label>
            </div>

            <button className="login-button">Continue</button>

            <div className="or-divider">or</div>

            <button className="alt-button">🔐 Log in with passkey</button>
            <button className="alt-button">🛡 Use single sign-on</button>
          </form>

          <p className="footer-text">
            New to SecureIT? <a href="#">Create account</a>
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
