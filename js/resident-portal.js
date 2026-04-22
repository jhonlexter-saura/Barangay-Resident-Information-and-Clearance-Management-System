document.addEventListener('DOMContentLoaded', () => {
  
  // Keep this! It's great for UX.
  const passwordInput = document.getElementById('password');
  const eyeIcon = document.getElementById('eyeIcon');
  const toggleBtn = document.querySelector('.input-end-icon');
  
  if (toggleBtn && passwordInput && eyeIcon) {
    toggleBtn.addEventListener('click', () => {
      const isHidden = passwordInput.type === 'password';
      passwordInput.type = isHidden ? 'text' : 'password';
      eyeIcon.className = isHidden ? 'bi bi-eye-slash' : 'bi bi-eye';
    });
  }

  // REMOVED: Form submission handler (Let PHP handle it via <form action="login.php" method="POST">)
});
