/**
 * Confetti particle effect — fires from a DOM element's position.
 * Called after completing a task or taking medication.
 */
export function createConfetti(element) {
    const colors = ['#10B981', '#34D399', '#6EE7B7', '#A7F3D0', '#FBBF24', '#F59E0B'];
    const rect = element.getBoundingClientRect();

    for (let i = 0; i < 15; i++) {
        const particle = document.createElement('div');
        particle.style.cssText = `
            position: fixed;
            width: 8px; height: 8px;
            background: ${colors[Math.floor(Math.random() * colors.length)]};
            border-radius: ${Math.random() > 0.5 ? '50%' : '2px'};
            pointer-events: none; z-index: 9999;
            left: ${rect.left + rect.width / 2}px;
            top: ${rect.top + rect.height / 2}px;
        `;
        document.body.appendChild(particle);

        const angle = Math.random() * 2 * Math.PI;
        const velocity = 3 + Math.random() * 4;
        const vx = Math.cos(angle) * velocity;
        const vy = Math.sin(angle) * velocity;
        let x = 0, y = 0, opacity = 1;

        (function animate() {
            x += vx;
            y += vy + 1; // gravity
            opacity -= 0.02;
            particle.style.transform = `translate(${x}px, ${y}px) rotate(${x * 5}deg)`;
            particle.style.opacity = opacity;
            if (opacity > 0) requestAnimationFrame(animate);
            else particle.remove();
        })();
    }
}
