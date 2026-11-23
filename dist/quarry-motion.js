// Decorative aggregate study. Three.js r180 is served locally with its MIT licence.
const hero = document.querySelector('.hero');
const motionPreference = matchMedia('(prefers-reduced-motion: reduce)');
const desktop = matchMedia('(min-width: 1051px)');
let started = false;

async function start() {
  if (!hero || started || !desktop.matches) return;
  started = true;
  let renderer;
  try {
    const THREE = await import('./assets/three/three.module.min.js');
    const host = document.createElement('div');
    host.className = 'quarry-motion';
    host.setAttribute('aria-hidden', 'true');
    renderer = new THREE.WebGLRenderer({ alpha: true, antialias: true, powerPreference: 'low-power' });
    renderer.setPixelRatio(Math.min(devicePixelRatio, 1.5));
    renderer.setClearColor(0x000000, 0);
    host.append(renderer.domElement);
    const label = document.createElement('span');
    label.className = 'quarry-motion-label';
    label.textContent = 'THE SHAPE OF STRONG FOUNDATIONS';
    host.append(label);
    hero.append(host);

    const toggle = document.createElement('button');
    toggle.className = 'quarry-motion-toggle';
    toggle.type = 'button';
    let paused = false, inView = false, lost = false, frame = 0, last = 0, elapsed = 0;
    const scene = new THREE.Scene();
    const camera = new THREE.PerspectiveCamera(38, 1, .1, 60);
    camera.position.set(0, .3, 9);
    camera.lookAt(0, 0, 0);
    scene.add(new THREE.HemisphereLight(0xeaf4ff, 0x263c35, 2.4));
    const key = new THREE.DirectionalLight(0xffd1ac, 3.5);
    key.position.set(-3, 5, 4); scene.add(key);
    const fill = new THREE.DirectionalLight(0x9ec5de, 2);
    fill.position.set(4, 0, -2); scene.add(fill);
    const group = new THREE.Group(); scene.add(group);
    const geometry = new THREE.IcosahedronGeometry(1, 1);
    // Deform shared vertices deterministically to create rough, faceted rock forms.
    const vertices = geometry.attributes.position;
    for (let i = 0; i < vertices.count; i++) {
      const x = vertices.getX(i), y = vertices.getY(i), z = vertices.getZ(i);
      const scale = 1 + .12 * Math.sin(x * 7 + y * 5 + z * 3);
      vertices.setXYZ(i, x * scale, y * scale * .82, z * scale);
    }
    geometry.computeVertexNormals();
    const material = new THREE.MeshStandardMaterial({ color: 0x869799, roughness: .95, metalness: .04, flatShading: true });
    const rocks = [];
    [[0,0,0,1.05],[-1.4,.9,-.4,.38],[1.45,.55,-.3,.48],[-1,-1.15,.2,.42],[1.1,-1,.5,.28]].forEach(([x,y,z,size], i) => {
      const rock = new THREE.Mesh(geometry, material);
      rock.position.set(x,y,z); rock.scale.setScalar(size); rock.rotation.set(i * .4, i * .7, .2);
      rock.userData.baseY = y; group.add(rock); rocks.push(rock);
    });
    const ringGeometry = new THREE.TorusGeometry(2.05, .008, 6, 100);
    const ringMaterial = new THREE.MeshBasicMaterial({ color: 0xe9b287, transparent: true, opacity: .65 });
    const ring = new THREE.Mesh(ringGeometry, ringMaterial);
    ring.rotation.set(1.05, .25, -.25); group.add(ring);

    function render() { if (!lost) renderer.render(scene, camera); }
    function resize() {
      const width = host.clientWidth, height = host.clientHeight;
      if (!width || !height) return;
      renderer.setSize(width, height); camera.aspect = width / height; camera.updateProjectionMatrix(); render();
    }
    function tick(now) {
      frame = requestAnimationFrame(tick);
      if (now - last < 1000 / 30) return;
      const delta = Math.min((now - last) / 1000, .05); last = now; elapsed += delta;
      group.rotation.y = Math.sin(elapsed * .16) * .3;
      rocks.forEach((rock, i) => {
        rock.rotation.y += delta * (i ? .09 : .045);
        rock.position.y = rock.userData.baseY + Math.sin(elapsed * .65 + i) * .06;
      });
      render();
    }
    function sync() {
      cancelAnimationFrame(frame); frame = 0;
      const reduced = motionPreference.matches;
      toggle.hidden = reduced || !desktop.matches || lost;
      toggle.textContent = paused ? 'Play animation' : 'Pause animation';
      toggle.setAttribute('aria-label', paused ? 'Play decorative rock animation' : 'Pause decorative rock animation');
      if (inView && !document.hidden && desktop.matches && !reduced && !paused && !lost) {
        last = performance.now(); frame = requestAnimationFrame(tick);
      } else if (desktop.matches) render();
    }
    toggle.addEventListener('click', () => { paused = !paused; sync(); });
    hero.append(toggle);
    const resizeObserver = new ResizeObserver(resize); resizeObserver.observe(host);
    const visibility = new IntersectionObserver(entries => { inView = entries[0].isIntersecting; sync(); }); visibility.observe(hero);
    document.addEventListener('visibilitychange', sync);
    motionPreference.addEventListener('change', sync);
    desktop.addEventListener('change', () => { resize(); sync(); });
    renderer.domElement.addEventListener('webglcontextlost', event => { event.preventDefault(); lost = true; host.hidden = true; sync(); });
    renderer.domElement.addEventListener('webglcontextrestored', () => { lost = false; host.hidden = false; resize(); sync(); });
    window.addEventListener('pagehide', () => { cancelAnimationFrame(frame); frame = 0; });
    window.addEventListener('pageshow', sync);
    resize(); sync();
  } catch {
    renderer?.dispose();
    hero.querySelector('.quarry-motion')?.remove();
    hero.querySelector('.quarry-motion-toggle')?.remove();
    // Photography, navigation and enquiries remain usable without WebGL.
  }
}

if (hero) {
  const loader = new IntersectionObserver(entries => {
    if (entries.some(entry => entry.isIntersecting)) start();
  });
  loader.observe(hero);
  desktop.addEventListener('change', start);
}
