import React from 'react';
import LoginForm from './components/LoginForm'; // or RegisterForm

function App() {
  return (
    <div>
      <LoginForm />
      {/* Or <RegisterForm /> if you want to test registration */}
    </div>
  );
}

export default App;
