<!-- HEADER (Top Navigation Bar) -->
<header class="header">
  <div class="header-left">
    <img src="/tgif_bi/assets/images/logo.png" alt="TGIF Logo" class="header-logo">
    <h1 class="header-title">TGIF Business Intelligence</h1>
  </div>

  <div class="header-right">
    <span class="welcome-text">
      👋 Welcome, <strong><?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?></strong>
    </span>
    <a href="/tgif_bi/logout.php" class="logout-btn">Logout</a>
  </div>
</header>

<style>
.header {
  position: fixed;
  top: 0;
  left: 260px; /* same width as sidebar */
  right: 0;
  height: 60px;
  background: #334155;
  color: #f8fafc;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 25px;
  box-shadow: 0 2px 6px rgba(0,0,0,0.15);
  z-index: 1000;
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

.logout-btn {
  background: #f87171;
  color: white;
  padding: 8px 14px;
  border-radius: 6px;
  text-decoration: none;
  transition: background 0.2s ease;
}

.logout-btn:hover {
  background: #dc2626;
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
    font-size: 0.85rem;
    display: none; /* Hide on small screens */
  }
  
  .welcome-text strong {
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
