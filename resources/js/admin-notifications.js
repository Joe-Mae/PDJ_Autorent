import { Toast } from 'bootstrap';

/**
 * Cloche de l'administration : pop-up sonore à chaque nouvelle demande.
 *
 * Le back-office n'a pas de websocket. La page interroge donc un petit point
 * JSON toutes les quinze secondes. Au premier passage on mémorise la demande
 * la plus récente sans rien afficher : ouvrir une page ne doit pas rejouer la
 * sonnerie pour une réservation arrivée la veille. Ensuite, tout changement
 * de « dernière demande » déclenche le pop-up et quinze secondes de sonnerie.
 *
 * Le son est fabriqué avec l'API Web Audio : aucun fichier à charger, donc
 * aucun octet de plus sur les pages publiques. Les navigateurs n'autorisent
 * le son qu'après une interaction : on débloque l'audio au premier clic ou
 * au premier toucher, ce qui est le cas dès la connexion.
 */
const container = document.querySelector('[data-admin-notification-feed]');

if (container) {
    const feedUrl = container.dataset.adminNotificationFeed;
    const pollInterval = Number(container.dataset.pollInterval) || 15000;
    const toastElement = container.querySelector('#adminRequestToast');
    const referenceElement = container.querySelector('[data-toast-reference]');
    const detailsElement = container.querySelector('[data-toast-details]');
    const linkElement = container.querySelector('[data-toast-link]');
    const bell = document.querySelector('[data-admin-notification-bell]');
    const toast = toastElement ? Toast.getOrCreateInstance(toastElement, { autohide: false }) : null;

    const RING_DURATION = 15000;
    const CHIME_INTERVAL = 2500;

    let initialised = false;
    let lastSeen = null;
    let audioContext = null;
    let ringTimer = null;
    let stopTimer = null;
    let pollTimer = null;

    // ── Son ────────────────────────────────────────────────────────────────

    const audio = () => {
        if (audioContext) return audioContext;

        const Constructor = window.AudioContext || window.webkitAudioContext;

        if (!Constructor) return null;

        audioContext = new Constructor();

        return audioContext;
    };

    const unlockAudio = () => {
        const context = audio();

        if (context?.state === 'suspended') {
            context.resume().catch(() => {});
        }
    };

    ['pointerdown', 'keydown', 'touchstart'].forEach((event) => {
        document.addEventListener(event, unlockAudio, { once: true, passive: true });
    });

    const chime = () => {
        const context = audio();

        if (!context) return;

        if (context.state === 'suspended') {
            context.resume().catch(() => {});
        }

        const now = context.currentTime;

        [880, 1174].forEach((frequency, index) => {
            const oscillator = context.createOscillator();
            const gain = context.createGain();
            const start = now + index * 0.18;

            oscillator.type = 'sine';
            oscillator.frequency.value = frequency;
            gain.gain.setValueAtTime(0, start);
            gain.gain.linearRampToValueAtTime(0.16, start + 0.02);
            gain.gain.exponentialRampToValueAtTime(0.001, start + 0.45);

            oscillator.connect(gain).connect(context.destination);
            oscillator.start(start);
            oscillator.stop(start + 0.5);
        });
    };

    const stopRinging = () => {
        window.clearInterval(ringTimer);
        window.clearTimeout(stopTimer);
        ringTimer = null;
        stopTimer = null;
    };

    const ring = () => {
        stopRinging();
        chime();
        ringTimer = window.setInterval(chime, CHIME_INTERVAL);
        stopTimer = window.setTimeout(stopRinging, RING_DURATION);
    };

    toastElement?.addEventListener('hidden.bs.toast', stopRinging);

    // ── Cloche ─────────────────────────────────────────────────────────────

    const syncBadge = (pending) => {
        if (!bell) return;

        let badge = bell.querySelector('.admin-icon-btn__badge');

        if (pending <= 0) {
            badge?.remove();

            return;
        }

        if (!badge) {
            badge = document.createElement('span');
            badge.className = 'admin-icon-btn__badge';
            bell.append(badge);
        }

        badge.textContent = pending > 9 ? '9+' : String(pending);
    };

    // ── Pop-up ─────────────────────────────────────────────────────────────

    const showRequest = (request) => {
        if (referenceElement) referenceElement.textContent = request.reference;
        if (detailsElement) {
            detailsElement.textContent = [request.customer, request.vehicle]
                .filter((value) => value && value !== '—')
                .join(' — ');
        }
        if (linkElement) linkElement.href = request.url;

        toast?.show();
        ring();
    };

    // ── Interrogation ──────────────────────────────────────────────────────

    const poll = async () => {
        if (document.hidden) return;

        try {
            const response = await fetch(feedUrl, {
                headers: { Accept: 'application/json' },
                cache: 'no-store',
            });

            if (!response.ok) return;

            const data = await response.json();
            const latest = data.latest;

            syncBadge(Number(data.pending) || 0);

            const key = latest ? `${latest.kind}:${latest.id}:${latest.created_at}` : null;

            // Premier passage réussi : on prend la photo du présent, sans
            // sonner. Une page ouverte sans demande en attente reste donc
            // silencieuse, mais la première demande qui arrive ensuite sonne.
            if (!initialised) {
                initialised = true;
                lastSeen = key;

                return;
            }

            if (key === null) {
                lastSeen = null;

                return;
            }

            if (key === lastSeen) return;

            lastSeen = key;
            showRequest(latest);
        } catch {
            // Réseau coupé : on retentera au prochain tour, sans rien casser.
        }
    };

    const startPolling = () => {
        window.clearInterval(pollTimer);
        pollTimer = window.setInterval(poll, pollInterval);
    };

    document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
            window.clearInterval(pollTimer);
            stopRinging();

            return;
        }

        poll();
        startPolling();
    });

    poll();
    startPolling();
}
