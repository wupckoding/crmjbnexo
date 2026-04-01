</div><!-- close flex -->

<script>
if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('sw.js').catch(() => {});
}

// GSAP page entrance — delayed to run AFTER Alpine renders x-for templates
setTimeout(() => {
    document.querySelectorAll('.stat-card, .panel-card').forEach(el => { el.style.opacity = '0'; el.style.transform = 'translateY(16px)'; });
    gsap.to('.stat-card', { opacity: 1, y: 0, stagger: 0.06, duration: 0.4, ease: 'power2.out', clearProps: 'all' });
    gsap.to('.panel-card', { opacity: 1, y: 0, stagger: 0.08, duration: 0.4, ease: 'power2.out', delay: 0.15, clearProps: 'all' });
}, 80);
</script>
</body>
</html>
