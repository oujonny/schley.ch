// close nav on link click
const navLinks = document.querySelectorAll('.nav-item')
const menuToggle = document.getElementById('navbarSupportedContent')
const bsCollapse = new bootstrap.Collapse(menuToggle, {
  toggle: false
})
navLinks.forEach((l) => {
    l.addEventListener('click', () => { bsCollapse.toggle() })
})

// randomize referenzen
const caourselItems = document.querySelectorAll('.carousel-item');
var item = caourselItems[Math.floor(Math.random()*caourselItems.length)];
item.classList.add('active')