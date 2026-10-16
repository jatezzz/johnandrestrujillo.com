// Loaded before first paint so a saved theme never flashes the other one.
try {
  const saved = localStorage.getItem('theme');
  if (saved === 'light' || saved === 'dark') {
    document.documentElement.setAttribute('data-theme', saved);
  }
} catch (error) {
  // Storage unavailable (private mode, blocked site data): follow the system setting.
}
