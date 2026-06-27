// ContaDocs — App JS
document.addEventListener('DOMContentLoaded', function() {
  // Crear botón hamburguesa en topbar
  const topbar = document.querySelector('.topbar');
  if (topbar && window.innerWidth <= 768) {
    const btn = document.createElement('button');
    btn.className = 'hamburger';
    btn.onclick = () => {
      document.querySelector('.sidebar')?.classList.add('open');
      document.getElementById('sidebarOverlay')?.classList.add('open');
      document.body.style.overflow = 'hidden';
    };
    btn.innerHTML = '<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>';
    topbar.insertBefore(btn, topbar.firstChild);
  }
});
