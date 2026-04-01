/**
 * CRM JBNEXO – Sound System (Web Audio API)
 * No external files needed – all sounds are synthesized.
 * Supports global volume control via NexoSounds.setVolume(0-1)
 */
const NexoSounds = (() => {
    let ctx = null;
    let masterGain = null;
    let _volume = parseFloat(localStorage.getItem('nexo_volume') ?? '0.7');
    let _muted = localStorage.getItem('nexo_muted') === '1';

    function getCtx() {
        if (!ctx) {
            ctx = new (window.AudioContext || window.webkitAudioContext)();
            masterGain = ctx.createGain();
            masterGain.connect(ctx.destination);
            masterGain.gain.value = _muted ? 0 : _volume;
        }
        if (ctx.state === 'suspended') ctx.resume();
        return ctx;
    }

    function getMaster() {
        getCtx();
        return masterGain;
    }

    function play(fn) {
        try { fn(getCtx(), getMaster()); } catch (e) { /* silent fail */ }
    }

    function setVolume(v) {
        _volume = Math.max(0, Math.min(1, v));
        localStorage.setItem('nexo_volume', _volume);
        if (masterGain && !_muted) masterGain.gain.value = _volume;
    }

    function getVolume() { return _volume; }

    function toggleMute() {
        _muted = !_muted;
        localStorage.setItem('nexo_muted', _muted ? '1' : '0');
        if (masterGain) masterGain.gain.value = _muted ? 0 : _volume;
        return _muted;
    }

    function isMuted() { return _muted; }

    /* ── Theme toggle: dark mode ON ── */
    function darkOn() {
        play((c, m) => {
            const g = c.createGain();
            g.connect(m);
            g.gain.setValueAtTime(0.35, c.currentTime);
            g.gain.exponentialRampToValueAtTime(0.001, c.currentTime + 0.35);

            const o = c.createOscillator();
            o.type = 'sine';
            o.frequency.setValueAtTime(600, c.currentTime);
            o.frequency.exponentialRampToValueAtTime(350, c.currentTime + 0.25);
            o.connect(g);
            o.start(c.currentTime);
            o.stop(c.currentTime + 0.35);
        });
    }

    /* ── Theme toggle: light mode ON ── */
    function lightOn() {
        play((c, m) => {
            const g = c.createGain();
            g.connect(m);
            g.gain.setValueAtTime(0.35, c.currentTime);
            g.gain.exponentialRampToValueAtTime(0.001, c.currentTime + 0.35);

            const o = c.createOscillator();
            o.type = 'sine';
            o.frequency.setValueAtTime(400, c.currentTime);
            o.frequency.exponentialRampToValueAtTime(800, c.currentTime + 0.25);
            o.connect(g);
            o.start(c.currentTime);
            o.stop(c.currentTime + 0.35);
        });
    }

    /* ── New chat message received ── */
    function message() {
        play((c, m) => {
            const t = c.currentTime;
            [520, 660].forEach((freq, i) => {
                const g = c.createGain();
                g.connect(m);
                g.gain.setValueAtTime(0.3, t + i * 0.12);
                g.gain.exponentialRampToValueAtTime(0.001, t + i * 0.12 + 0.3);

                const o = c.createOscillator();
                o.type = 'sine';
                o.frequency.value = freq;
                o.connect(g);
                o.start(t + i * 0.12);
                o.stop(t + i * 0.12 + 0.3);
            });
        });
    }

    /* ── General notification (bell) ── */
    function notification() {
        play((c, m) => {
            const t = c.currentTime;
            [587, 784, 880].forEach((freq, i) => {
                const g = c.createGain();
                g.connect(m);
                g.gain.setValueAtTime(0.25, t + i * 0.1);
                g.gain.exponentialRampToValueAtTime(0.001, t + i * 0.1 + 0.25);

                const o = c.createOscillator();
                o.type = 'triangle';
                o.frequency.value = freq;
                o.connect(g);
                o.start(t + i * 0.1);
                o.stop(t + i * 0.1 + 0.25);
            });
        });
    }

    /* ── Message sent ── */
    function sent() {
        play((c, m) => {
            const g = c.createGain();
            g.connect(m);
            g.gain.setValueAtTime(0.2, c.currentTime);
            g.gain.exponentialRampToValueAtTime(0.001, c.currentTime + 0.15);

            const o = c.createOscillator();
            o.type = 'sine';
            o.frequency.setValueAtTime(480, c.currentTime);
            o.frequency.exponentialRampToValueAtTime(680, c.currentTime + 0.1);
            o.connect(g);
            o.start(c.currentTime);
            o.stop(c.currentTime + 0.15);
        });
    }

    return { darkOn, lightOn, message, notification, sent, setVolume, getVolume, toggleMute, isMuted };
})();
