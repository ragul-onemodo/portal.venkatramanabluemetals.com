document.documentElement.classList.add('js');
const observer=new IntersectionObserver(entries=>entries.forEach(entry=>{if(entry.isIntersecting){entry.target.classList.add('visible');observer.unobserve(entry.target)}}),{threshold:.08});
document.querySelectorAll('.reveal').forEach(el=>observer.observe(el));
const menu=document.querySelector('.menu'),nav=document.querySelector('nav');
menu.addEventListener('click',()=>{const open=nav.classList.toggle('open');menu.setAttribute('aria-expanded',String(open));menu.textContent=open?'Close':'Menu'});
nav.querySelectorAll('a').forEach(a=>a.addEventListener('click',()=>{nav.classList.remove('open');menu.setAttribute('aria-expanded','false');menu.textContent='Menu'}));
document.querySelectorAll('[data-filter]').forEach(button=>button.addEventListener('click',()=>{document.querySelectorAll('[data-filter]').forEach(b=>{b.classList.toggle('active',b===button);b.setAttribute('aria-pressed',String(b===button))});document.querySelectorAll('.material-card').forEach(card=>{card.hidden=button.dataset.filter!=='all'&&card.dataset.category!==button.dataset.filter})}));
document.querySelectorAll('[data-material]').forEach(button=>button.addEventListener('click',()=>{document.querySelector('#material').value=button.dataset.material;document.querySelector('#enquiry').scrollIntoView({behavior:matchMedia('(prefers-reduced-motion: reduce)').matches?'instant':'smooth'})}));
document.querySelectorAll('.step').forEach(step=>step.addEventListener('click',()=>{document.querySelectorAll('.step').forEach(s=>{const active=s===step;s.classList.toggle('active',active);s.setAttribute('aria-expanded',String(active));s.querySelector('b').textContent=active?'−':'+'})}));
document.querySelector('#year').textContent=new Date().getFullYear();

const requestedMaterial=new URLSearchParams(location.search).get('material');
const materialSelect=document.querySelector('#material');
if(materialSelect&&[...materialSelect.options].some(option=>option.value===requestedMaterial)){materialSelect.value=requestedMaterial;}

const requestedService=new URLSearchParams(location.search).get('service');
const serviceSelect=document.querySelector('#service');
if(serviceSelect&&[...serviceSelect.options].some(option=>option.value===requestedService)){serviceSelect.value=requestedService;}

function closeNavigation(returnFocus=false){nav.classList.remove('open');menu.setAttribute('aria-expanded','false');menu.textContent='Menu';if(returnFocus)menu.focus();}
document.addEventListener('keydown',event=>{if(event.key==='Escape'&&nav.classList.contains('open'))closeNavigation(true)});
document.addEventListener('click',event=>{if(nav.classList.contains('open')&&!event.target.closest('header'))closeNavigation()});
document.addEventListener('focusin',event=>{if(nav.classList.contains('open')&&!event.target.closest('header'))closeNavigation()});
const enquiryForm = document.querySelector('#enquiry-form');
enquiryForm?.addEventListener('submit', async event => {
  event.preventDefault();
  const button = enquiryForm.querySelector('button[type="submit"]');
  const status = document.querySelector('#form-status');
  button.disabled = true; button.textContent = 'Sending…'; status.textContent = '';
  try {
    const response = await fetch('/enquiry', { method: 'POST', body: new FormData(enquiryForm), headers: { Accept: 'application/json' } });
    if (!response.headers.get('content-type')?.includes('application/json')) throw new Error('Unable to send right now. Please try again or contact us by email.');
    const result = await response.json();
    if (!response.ok) throw new Error(result.message);
    status.textContent = result.message; enquiryForm.reset();
  } catch (error) { status.textContent = error.message || 'Unable to send. Please try again.'; }
  finally { button.disabled = false; button.textContent = 'Send enquiry'; }
});
