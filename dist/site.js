'use strict';

document.getElementById('year').textContent = new Date().getFullYear();

const menu = document.querySelector('.menu');
const navigation = document.getElementById('navigation');
function closeMenu() {
  navigation.classList.remove('open');
  menu.setAttribute('aria-expanded', 'false');
  menu.setAttribute('aria-label', 'Open navigation');
}
menu.addEventListener('click', () => {
  const open = navigation.classList.toggle('open');
  menu.setAttribute('aria-expanded', String(open));
  menu.setAttribute('aria-label', open ? 'Close navigation' : 'Open navigation');
});
navigation.querySelectorAll('a').forEach(link => link.addEventListener('click', closeMenu));
document.addEventListener('keydown', event => {
  if (event.key === 'Escape' && navigation.classList.contains('open')) {
    closeMenu();
    menu.focus();
  }
});
document.addEventListener('click', event => {
  if (!event.target.closest('.site-header')) closeMenu();
});
window.matchMedia('(max-width: 800px)').addEventListener('change', closeMenu);

const tabs = [...document.querySelectorAll('.tab-btn')];
function selectForm(type, focus = false) {
  tabs.forEach(tab => {
    const selected = tab.dataset.form === type;
    tab.classList.toggle('active', selected);
    tab.setAttribute('aria-selected', String(selected));
    tab.tabIndex = selected ? 0 : -1;
    const panel = document.getElementById(tab.dataset.form);
    panel.classList.toggle('active', selected);
    panel.hidden = !selected;
    if (selected && focus) tab.focus();
  });
}
tabs.forEach((tab, index) => {
  tab.addEventListener('click', () => selectForm(tab.dataset.form));
  tab.addEventListener('keydown', event => {
    let next;
    if (event.key === 'ArrowRight') next = (index + 1) % tabs.length;
    if (event.key === 'ArrowLeft') next = (index + tabs.length - 1) % tabs.length;
    if (event.key === 'Home') next = 0;
    if (event.key === 'End') next = tabs.length - 1;
    if (next !== undefined) {
      event.preventDefault();
      selectForm(tabs[next].dataset.form, true);
    }
  });
});

document.querySelectorAll('.filter').forEach(button => {
  button.addEventListener('click', () => {
    document.querySelectorAll('.filter').forEach(filter => {
      const selected = filter === button;
      filter.classList.toggle('active', selected);
      filter.setAttribute('aria-pressed', String(selected));
    });
    let count = 0;
    document.querySelectorAll('.programme-row').forEach(row => {
      row.hidden = button.dataset.filter !== 'all' && row.dataset.category !== button.dataset.filter;
      if (!row.hidden) count++;
    });
    document.getElementById('programme-count').textContent = `${count} ${count === 1 ? 'subject' : 'subjects'}`;
  });
});
document.querySelectorAll('[data-course]').forEach(link => {
  link.addEventListener('click', () => {
    selectForm('application');
    document.getElementById('application-course').value = link.dataset.course;
  });
});
document.querySelectorAll('[data-open-form]').forEach(link => {
  link.addEventListener('click', () => selectForm(link.dataset.openForm));
});
document.querySelectorAll('a[href="#forms"]:not([data-course]):not([data-open-form])').forEach(link => {
  link.addEventListener('click', () => selectForm('application'));
});
function revealReference() {
  const id = location.hash.slice(1);
  const target = document.getElementById(id);
  if (target?.matches('details')) target.open = true;
}
document.querySelectorAll('a[href="#resources"],a[href="#leadership"]').forEach(link => {
  link.addEventListener('click', () => { document.querySelector(link.getAttribute('href')).open = true; });
});
window.addEventListener('hashchange', revealReference);
revealReference();

document.querySelectorAll('.mail-form').forEach(form => {
  const consent = document.createElement('div');
  consent.className = 'field full consent';
  consent.innerHTML = `<input id="consent-${form.dataset.type}" name="consent" type="checkbox" value="yes" required><label for="consent-${form.dataset.type}">I consent to AIMS using my information to process this submission, contact me and maintain necessary institutional records as described in the <a href="#resources">Privacy Notice</a>.</label>`;
  consent.querySelector('a').addEventListener('click', () => {
    document.getElementById('resources').open = true;
    document.querySelectorAll('.policy-grid details').forEach(detail => {
      if (detail.querySelector('summary').textContent === 'Privacy notice') detail.open = true;
    });
  });
  form.querySelector('.hp').before(consent);
  form.addEventListener('submit', async event => {
    event.preventDefault();
    if (!form.reportValidity()) return;
    const button = form.querySelector('button[type="submit"]');
    const status = form.querySelector('.form-status');
    const label = button.textContent;
    button.disabled = true;
    button.textContent = 'Sending…';
    form.setAttribute('aria-busy', 'true');
    status.className = 'form-status';
    const controller = new AbortController();
    const timeout = setTimeout(() => controller.abort(), 20000);
    try {
      const payload = Object.fromEntries(new FormData(form).entries());
      payload.form_type = form.dataset.type;
      const response = await fetch('https://aimsnigeria.org.ng/forms/submit.php', {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload), signal: controller.signal
      });
      let result;
      try { result = await response.json(); }
      catch { throw new Error('The service could not process your submission. Please try again or email contact@aimsnigeria.org.ng.'); }
      if (!response.ok || !result.ok) throw new Error(result.message || 'Submission could not be sent. Please try again or email contact@aimsnigeria.org.ng.');
      status.textContent = result.message || 'Thank you. Your submission has been received.';
      status.className = 'form-status show ok';
      form.reset();
    } catch (error) {
      status.textContent = error.name === 'AbortError'
        ? 'The request timed out. Please try again or email contact@aimsnigeria.org.ng.'
        : error.message || 'We could not send your form. Please email contact@aimsnigeria.org.ng.';
      status.className = 'form-status show error';
    } finally {
      clearTimeout(timeout);
      button.disabled = false;
      button.textContent = label;
      form.removeAttribute('aria-busy');
    }
  });
});

if ('IntersectionObserver' in window) {
  const sectionLinks = [...navigation.querySelectorAll('a[href^="#"]')];
  const observer = new IntersectionObserver(entries => {
    entries.forEach(entry => {
      if (entry.isIntersecting) sectionLinks.forEach(link => {
        if (link.hash === `#${entry.target.id}`) link.setAttribute('aria-current', 'location');
        else link.removeAttribute('aria-current');
      });
    });
  }, { rootMargin: '-15% 0px -60% 0px', threshold: 0 });
  sectionLinks.forEach(link => { const target = document.querySelector(link.hash); if (target) observer.observe(target); });
}
