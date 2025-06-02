import React, { useState } from 'react';
import './RegisterForm.css';

function RegisterForm() {
  const [email, setEmail] = useState('');
  const [name, setName] = useState('');
  const [newsletter, setNewsletter] = useState(true);

  const handleSubmit = (e) => {
    e.preventDefault();
    alert(`Registering ${name} with email ${email}`);
  };

  return (
    <div className="layout_frontend">
      <div className="register-wrapper">
        <img
          src="/images/logo.svg"
          alt="SecureIT"
          className="register-logo"
        />
        <div className="register-card">
          <h1 className="register-title">Create account</h1>
          <form onSubmit={handleSubmit} className="register-form">
            <label htmlFor="domain" className="register-label">
              Creating account on <span className="required">(required)</span>
            </label>
            <select id="domain" className="register-input">
              <option>secureit.com</option>
            </select>

            <label htmlFor="email" className="register-label">
              Email address <span className="required">(required)</span>
            </label>
            <input
              className="register-input"
              type="email"
              id="email"
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              required
            />

            <label htmlFor="name" className="register-label">
              Name
            </label>
            <input
              className="register-input"
              type="text"
              id="name"
              value={name}
              onChange={(e) => setName(e.target.value)}
            />

            <div className="newsletter-container">
              <input
                type="checkbox"
                id="newsletter"
                checked={newsletter}
                onChange={() => setNewsletter(!newsletter)}
              />
              <label htmlFor="newsletter">
                Get advice, announcements, and research opportunities from SecureIT in your inbox.{' '}
                <a href="#">Unsubscribe</a> at any time.
              </label>
            </div>

            <button className="register-button">Continue</button>
          </form>

          <p className="terms-text">
            By continuing, you agree to the <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a>
          </p>
        </div>

        <footer className="register-footer">
          <p>Already have an account? <a href="#">Log in</a></p>
          <small>© 2025 SecureIT Inc. Version 1.0.0</small>
        </footer>
      </div>
    </div>
  );
}

export default RegisterForm;
