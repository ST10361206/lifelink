document.addEventListener('DOMContentLoaded', () => {
  const toggle = document.querySelector('.menu-toggle');
  const menu = document.getElementById('site-menu');
  if (toggle && menu) toggle.addEventListener('click', () => {
    const open = menu.classList.toggle('is-open');
    toggle.setAttribute('aria-expanded', String(open));
  });
  const observer = 'IntersectionObserver' in window ? new IntersectionObserver((entries) => {
    entries.forEach(entry => { if (entry.isIntersecting) { entry.target.classList.add('visible'); observer.unobserve(entry.target); } });
  }, { threshold: 0.08 }) : null;
  document.querySelectorAll('.reveal').forEach(el => observer ? observer.observe(el) : el.classList.add('visible'));
  const search = document.getElementById('centre-search');
  if (search) search.addEventListener('input', () => {
    const query = search.value.trim().toLowerCase(); let count = 0;
    document.querySelectorAll('.centre-card').forEach(card => {
      const match = card.dataset.search.includes(query); card.hidden = !match; if (match) count++;
    });
    document.getElementById('no-centres').hidden = count !== 0;
  });
  const form = document.getElementById('contact-form');
  if (form) form.addEventListener('submit', event => {
    event.preventDefault();
    const data = new FormData(form);
    const subject = encodeURIComponent('LifeLink enquiry: ' + data.get('subject'));
    const body = encodeURIComponent('Name: ' + data.get('name') + '\nEmail: ' + data.get('email') + '\n\n' + data.get('message'));
    // A team email has not been supplied yet. The user chooses the recipient in their mail app.
    window.location.href = 'mailto:?subject=' + subject + '&body=' + body;
  });
});
