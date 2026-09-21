// Mobile navigation drawer for the homepage (index.html)
const menuToggle = document.getElementById('menuToggle');
const navLinks = document.getElementById('navLinks');

function closeMenu(){
  if(!navLinks || !menuToggle) return;
  navLinks.classList.remove('open');
  menuToggle.classList.remove('active');
  menuToggle.setAttribute('aria-expanded', 'false');
}

function openMenu(){
  if(!navLinks || !menuToggle) return;
  navLinks.classList.add('open');
  menuToggle.classList.add('active');
  menuToggle.setAttribute('aria-expanded', 'true');
}

if(menuToggle && navLinks){
  menuToggle.addEventListener('click', () => {
    const isOpen = navLinks.classList.contains('open');
    isOpen ? closeMenu() : openMenu();
  });

  // Close the drawer after a link inside it is tapped
  navLinks.addEventListener('click', (e) => {
    if(e.target.tagName === 'A'){
      closeMenu();
    }
  });

  // If the viewport grows back to desktop width, make sure the drawer resets
  const desktopQuery = window.matchMedia('(min-width: 961px)');
  desktopQuery.addEventListener('change', (e) => {
    if(e.matches){
      closeMenu();
    }
  });
}

// Close drawer on Escape key
document.addEventListener('keydown', (e) => {
  if(e.key === 'Escape'){
    closeMenu();
  }
});
