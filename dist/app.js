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
document.querySelector('#enquiry-form')?.addEventListener('submit',event=>{event.preventDefault();const form=new FormData(event.currentTarget);const content='VBM QUARRY & CRUSHERS — MATERIAL ENQUIRY\n\n'+[...form].map(([key,value])=>key.toUpperCase()+': '+value).join('\n\n')+'\n\nPrepared locally. This enquiry has not been sent.\n';const url=URL.createObjectURL(new Blob([content],{type:'text/plain;charset=utf-8'}));const a=document.createElement('a');a.href=url;a.download='VBM-material-enquiry.txt';a.click();setTimeout(()=>URL.revokeObjectURL(url),1000);document.querySelector('#form-status').textContent='Your enquiry file is ready. Keep it to share with your supplier. No message has been sent.'});

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
