<!-- HEADER (Top Navigation Bar) -->
<header class="header green-header">
  <div class="header-left">
    <img src="/tgif_bi/assets/images/logo.png" alt="TGIF Logo" class="header-logo">
    <h1 class="header-title">TGIF Business Intelligence</h1>
  </div>

  <div class="header-right">
    <span class="welcome-text">
      👋 Welcome, <strong><?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?></strong>
    </span>
  </div>
</header>

<style>
/* ✅ ONLY this header is green */
.green-header {
  background: linear-gradient(135deg, #1b5e20, #2e7d32);
  color: #f1f5f9;
  border-bottom: 3px solid #145a1f;
}

.header {
  position: fixed;
  top: 0;
  left: 260px;
  right: 0;
  height: 60px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 25px;
  box-shadow: 0 2px 6px rgba(0,0,0,0.25);
  z-index: 1000;
}

.green-header * {
  color: #ffffff;
}

.header-left {
  display: flex;
  align-items: center;
  gap: 10px;
}

.header-logo {
  width: 38px;
  height: 38px;
  border-radius: 8px;
  background: #f1f5f9;
  object-fit: cover;
}

.header-title {
  font-size: 1.2rem;
  font-weight: 600;
}

.header-right {
  display: flex;
  align-items: center;
  gap: 20px;
}

.welcome-text {
  font-size: 0.95rem;
}

/* Responsive Header */
@media (max-width: 768px) {
  .header {
    left: 0;
    padding: 0 15px;
    height: 55px;
  }

  .header-title {
    font-size: 1rem;
  }

  .header-logo {
    width: 32px;
    height: 32px;
  }

  .welcome-text {
    display: none;
  }

  .logout-btn {
    padding: 6px 12px;
    font-size: 0.9rem;
  }
}

@media (max-width: 480px) {
  .header {
    padding: 0 10px;
    height: 50px;
  }

  .header-title {
    font-size: 0.9rem;
  }

  .header-logo {
    width: 28px;
    height: 28px;
  }

  .logout-btn {
    padding: 5px 10px;
    font-size: 0.8rem;
  }
}
</style>
