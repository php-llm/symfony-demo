import './bootstrap.js';
import 'bootstrap/dist/css/bootstrap.min.css';
import './styles/app.css';

document.addEventListener('mousemove', function(e) {
    if (!document.getElementsByTagName('body')[0].classList.contains('chat')) {
        return;
    }
    const intensity = 20;
    const x = (e.clientX / window.innerWidth) * intensity - intensity;
    const y = (e.clientY / window.innerHeight) * intensity;
    document.documentElement.style.backgroundPosition = `calc(100% - ${x}px) calc(50% + ${y}px)`;
});
