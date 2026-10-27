import * as bootstrap from 'bootstrap';

/**
 * Interactions du site.
 *
 * Trois choses méritent du JavaScript, pas une de plus :
 *  1. le filtrage en AJAX, pour ne pas recharger la page à chaque case cochée ;
 *  2. la bascule chauffeur, pour recalculer le prix sans round-trip ;
 *  3. les estimations de réservation et la galerie de la fiche véhicule.
 */

// ─── Devis en direct pour une demande sans compte ─────────────────────────
document.querySelectorAll('[data-guest-booking-form]').forEach((form) => {
    const start = form.elements.namedItem('du');
    const end = form.elements.namedItem('au');
    const pickup = form.elements.namedItem('heure_depart');
    const returnHour = form.elements.namedItem('heure_retour');
    const dailyPrice = form.querySelector('[data-guest-daily-price]');
    const rentalTotal = form.querySelector('[data-guest-rental-total]');
    const driverTotal = form.querySelector('[data-guest-driver-total]');
    const days = form.querySelector('[data-guest-days]');
    const total = form.querySelector('[data-guest-total]');
    const error = form.querySelector('[data-guest-quote-error]');
    let timer;
    let activeRequest;

    const refreshQuote = async () => {
        activeRequest?.abort();
        activeRequest = new AbortController();
        const params = new URLSearchParams();
        params.set('du', start.value);
        params.set('au', end.value);
        params.set('heure_depart', pickup.value);
        params.set('heure_retour', returnHour.value);

        const driver = form.elements.namedItem('avec_chauffeur');
        const driverChecked = driver?.type === 'checkbox' ? driver.checked : !!driver?.value;
        if (driverChecked) params.set('avec_chauffeur', '1');

        try {
            const response = await fetch(`${form.dataset.quoteUrl}?${params}`, {
                headers: { Accept: 'application/json' },
                signal: activeRequest.signal,
            });
            const data = await response.json();

            if (!response.ok) throw new Error(form.dataset.quoteError);

            const formatter = new Intl.NumberFormat(document.documentElement.lang || 'fr-FR');
            dailyPrice.textContent = formatter.format(data.daily_price);
            rentalTotal.textContent = formatter.format(data.price_rental);
            if (driverTotal) driverTotal.textContent = formatter.format(data.price_driver);
            days.textContent = formatter.format(data.days);
            total.textContent = formatter.format(data.price_total);
            error.hidden = true;
        } catch (exception) {
            if (exception.name === 'AbortError') return;

            error.textContent = exception.message || form.dataset.quoteError;
            error.hidden = false;
        }
    };

    const driverInput = form.elements.namedItem('avec_chauffeur');
    const driverCheckbox = driverInput?.type === 'checkbox' ? driverInput : null;
    const driverLine = form.querySelector('[data-guest-driver-line]');
    const driverInfo = form.querySelector('[data-guest-driver-info]');

    const toggleDriverUI = () => {
        const on = driverChecked();
        if (driverLine) driverLine.classList.toggle('d-none', !on);
        if (driverInfo) driverInfo.classList.toggle('d-none', !on);
    };
    const driverChecked = () => driverCheckbox ? driverCheckbox.checked : !!driverInput?.value;

    [start, end, pickup, returnHour].forEach((input) => {
        input.addEventListener('change', () => {
            if (input === start) end.min = start.value || end.min;

            clearTimeout(timer);
            timer = setTimeout(refreshQuote, 180);
        });
    });

    if (driverCheckbox) {
        driverCheckbox.addEventListener('change', () => {
            toggleDriverUI();
            clearTimeout(timer);
            timer = setTimeout(refreshQuote, 180);
        });
    }

    end.min = start.value || end.min;
    refreshQuote();
});

// ─── Filtres en AJAX ───────────────────────────────────────────────────────
const filtersForm = document.querySelector('[data-filters-form]');

if (filtersForm) {
    const results = document.querySelector('[data-results]');
    const counter = document.querySelector('[data-results-count]');
    const overlay = document.querySelector('[data-results-loading]');
    const endpoint = filtersForm.dataset.filtersUrl;
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    let debounce;

    const refresh = async (push = true) => {
        // L'endpoint renvoie du JSON. Interroger la page elle-même renvoyait
        // du HTML, et le `response.json()` échouait sans rien mettre à jour.
        if (!endpoint) return;

        overlay?.classList.remove('d-none');

        const formData = new FormData(filtersForm);
        const query = new URLSearchParams(formData).toString();

        try {
            const response = await fetch(endpoint, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'application/json',
                    ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}),
                },
                body: formData,
            });

            if (!response.ok) {
                throw new Error(`Filtres indisponibles (${response.status})`);
            }

            const data = await response.json();

            if (results) results.innerHTML = data.html;
            if (counter) counter.textContent = data.count;

            // L'URL devient partageable : c'est le but du filtre côté client.
            if (push) {
                window.history.replaceState({}, '', query ? `${window.location.pathname}?${query}` : window.location.pathname);
            }
        } catch (error) {
            console.error(error);
        } finally {
            overlay?.classList.add('d-none');
        }
    };

    filtersForm.addEventListener('change', () => {
        clearTimeout(debounce);
        debounce = setTimeout(() => refresh(), 220);
    });

    filtersForm.addEventListener('input', (event) => {
        if (event.target.type === 'range') {
            const output = document.querySelector(`[data-output="${event.target.name}"]`);
            if (output) output.textContent = Number(event.target.value).toLocaleString('fr-FR');
        }
    });

    // Le double curseur de prix : deux poignées, une seule valeur min/max.
    const rangeTrack = document.querySelector('[data-price-range]');
    if (rangeTrack) {
        const [min, max] = rangeTrack.querySelectorAll('input[type="range"]');

        const sync = () => {
            const low = Math.min(Number(min.value), Number(max.value) - 500);
            const high = Math.max(Number(max.value), Number(low) + 500);
            min.value = low;
            max.value = high;
            rangeTrack.style.setProperty('--pdj-price-min', `${((low - min.min) / (min.max - min.min)) * 100}%`);
            rangeTrack.style.setProperty('--pdj-price-max', `${((high - min.min) / (min.max - min.min)) * 100}%`);
        };

        [min, max].forEach((input) => input.addEventListener('input', sync));
        sync();
    }
}

// ─── Aperçu tarifaire des réservations avec chauffeur ──────────────────────
document.querySelectorAll('[data-driver-price-form]').forEach((form) => {
    const startInput = form.querySelector('[data-price-start]');
    const endInput = form.querySelector('[data-price-end]');
    const preview = form.querySelector('[data-driver-price-preview]');
    const emptyLabel = preview?.querySelector('[data-price-empty]');
    const breakdown = preview?.querySelector('[data-price-breakdown]');
    const total = preview?.querySelector('[data-price-total]');
    const initialEndMin = endInput?.min ?? '';

    if (!startInput || !endInput || !preview || !emptyLabel || !breakdown || !total) return;

    const daysBetween = (start, end) => {
        if (!/^\d{4}-\d{2}-\d{2}$/.test(start) || !/^\d{4}-\d{2}-\d{2}$/.test(end)) return null;

        const startTime = Date.parse(`${start}T00:00:00Z`);
        const endTime = Date.parse(`${end}T00:00:00Z`);
        const duration = (endTime - startTime) / 86400000;

        return Number.isInteger(duration) && duration >= 0 ? Math.max(1, duration) : null;
    };

    const updatePreview = () => {
        endInput.min = startInput.value || initialEndMin;

        const days = daysBetween(startInput.value, endInput.value);

        if (days === null) {
            emptyLabel.textContent = preview.dataset.emptyLabel;
            emptyLabel.hidden = false;
            breakdown.hidden = true;
            total.hidden = true;

            return;
        }

        const discountAfterDays = Number(preview.dataset.discountAfterDays);
        const discountRate = Number(preview.dataset.discountDailyRate);
        const dailyRate = days > discountAfterDays && Number.isFinite(discountRate)
            ? discountRate
            : Number(preview.dataset.dailyRate);
        const formatter = new Intl.NumberFormat(document.documentElement.lang || 'fr-FR');

        emptyLabel.hidden = true;
        breakdown.textContent = preview.dataset.dailyLabel
            .replace('__DAYS__', formatter.format(days))
            .replace('__DAILY__', formatter.format(dailyRate));
        breakdown.hidden = false;
        total.textContent = `${formatter.format(days * dailyRate)} ${preview.dataset.currency}`;
        total.hidden = false;
    };

    startInput.addEventListener('input', updatePreview);
    startInput.addEventListener('change', updatePreview);
    endInput.addEventListener('input', updatePreview);
    endInput.addEventListener('change', updatePreview);
    updatePreview();
});

// ─── Panneau de filtres plein écran sur mobile ─────────────────────────────
const filtersToggle = document.querySelector('[data-filters-toggle]');
const filtersPanel = document.querySelector('[data-filters-panel]');

filtersToggle?.addEventListener('click', () => {
    filtersPanel?.classList.toggle('d-none');
    filtersToggle.setAttribute('aria-expanded', String(!filtersPanel?.classList.contains('d-none')));
});

document.querySelector('[data-filters-close]')?.addEventListener('click', () => {
    filtersPanel?.classList.add('d-none');
    filtersToggle?.setAttribute('aria-expanded', 'false');
});

// ─── Bascule chauffeur : le prix se recalcule sans rechargement ─────────────
const driverToggle = document.querySelector('[data-driver-toggle]');

if (driverToggle) {
    driverToggle.addEventListener('change', async () => {
        const summary = document.querySelector('[data-price-summary]');
        const button = driverToggle.closest('form')?.querySelector('button[type="submit"]');
        button?.setAttribute('disabled', '');

        try {
            const response = await fetch(driverToggle.form.action, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: new FormData(driverToggle.form),
            });

            if (response.redirected || response.ok) {
                window.location.reload();
            }
        } finally {
            button?.removeAttribute('disabled');
        }

        return summary;
    });
}

// ─── Galerie de la fiche véhicule ─────────────────────────────────────────
// Les photos défilent seules toutes les cinq secondes. Le survol et le focus
// mettent la rotation en pause : on ne change pas l'image sous les yeux de
// quelqu'un qui est en train de la regarder. Un clic sur une vignette reprend
// la main et relance le compte à rebours.
const gallery = document.querySelector('[data-gallery]');

if (gallery) {
    const main = gallery.querySelector('[data-gallery-main]');
    const thumbs = Array.from(gallery.querySelectorAll('[data-gallery-thumb]'));
    const interval = Number(gallery.dataset.galleryInterval) || 5000;
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    let activeIndex = Math.max(0, thumbs.findIndex((thumb) => thumb.classList.contains('is-active')));
    let paused = false;
    let timer;

    const showPhoto = (index) => {
        if (!main || thumbs.length === 0) return;

        activeIndex = (index + thumbs.length) % thumbs.length;
        main.src = thumbs[activeIndex].dataset.galleryThumb;
        thumbs.forEach((thumb, thumbIndex) => {
            thumb.classList.toggle('is-active', thumbIndex === activeIndex);
        });
    };

    const stopRotation = () => window.clearInterval(timer);

    const startRotation = () => {
        stopRotation();

        if (thumbs.length < 2 || reducedMotion || paused || document.hidden) return;

        timer = window.setInterval(() => showPhoto(activeIndex + 1), interval);
    };

    thumbs.forEach((thumb, index) => {
        thumb.addEventListener('click', () => {
            showPhoto(index);
            startRotation();
        });
    });

    gallery.addEventListener('mouseenter', () => {
        paused = true;
        stopRotation();
    });

    gallery.addEventListener('mouseleave', () => {
        paused = false;
        startRotation();
    });

    gallery.addEventListener('focusin', () => {
        paused = true;
        stopRotation();
    });

    gallery.addEventListener('focusout', () => {
        paused = false;
        startRotation();
    });

    document.addEventListener('visibilitychange', startRotation);
    startRotation();
}

// ─── Aperçu du fichier avant envoi ────────────────────────────────────────
document.querySelectorAll('[data-upload]').forEach((input) => {
    input.addEventListener('change', () => {
        const file = input.files?.[0];
        const preview = input.closest('[data-upload-zone]')?.querySelector('[data-upload-preview]');
        const name = input.closest('[data-upload-zone]')?.querySelector('[data-upload-name]');

        if (!file || !preview) return;

        if (file.type.startsWith('image/')) {
            preview.src = URL.createObjectURL(file);
            preview.classList.remove('d-none');
        }

        if (name) name.textContent = file.name;
    });
});

// ─── Code de vérification à 6 cases ───────────────────────────────────────
const codeBoxes = document.querySelectorAll('[data-code-input]');
const codeHidden = document.querySelector('[data-code-hidden]');

// Les six cases n'ont pas de nom : un seul champ caché reçoit la valeur.
// Le formulaire reste donc fonctionnable même si ce script ne s'exécute pas.
const syncCode = () => {
    if (codeHidden) {
        codeHidden.value = Array.from(codeBoxes).map((box) => box.value).join('');
    }
};

codeBoxes.forEach((box, index) => {
    box.addEventListener('input', () => {
        box.value = box.value.replace(/\D/g, '').slice(0, 1);
        if (box.value && codeBoxes[index + 1]) codeBoxes[index + 1].focus();
        syncCode();
    });

    box.addEventListener('keydown', (event) => {
        if (event.key === 'Backspace' && !box.value && codeBoxes[index - 1]) {
            codeBoxes[index - 1].focus();
        }
    });

    box.addEventListener('paste', (event) => {
        const digits = (event.clipboardData.getData('text') || '').replace(/\D/g, '');
        if (!digits) return;

        event.preventDefault();
        codeBoxes.forEach((target, i) => { target.value = digits[i] || ''; });
        codeBoxes[Math.min(digits.length, codeBoxes.length - 1)].focus();
        syncCode();
    });
});

if (codeBoxes.length && window.matchMedia('(max-width: 575.98px)').matches) {
    codeBoxes[0]?.focus({ preventScroll: true });
}

document.addEventListener('DOMContentLoaded', () => {
    // Bootstrap 5.3 a retiré le raccourci statique Tooltip.initAll : le
    // remplacer par getOrCreateInstance évitait une TypeError sur chaque page
    // d'administration, qui masquait les vraies erreurs dans la console.
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((el) => {
        bootstrap.Tooltip.getOrCreateInstance(el);
    });
});

// ─── Champs obligatoires et bouton de validation ────────────────────────────
// Un attribut `required` nu affiche la bulle du navigateur : grise, fugace, et
// elle disparaît dès que le client clique ailleurs. Elle ne dit pas *quel*
// champ manque.
//
// Cette routine, appliquée à tous les formulaires de la page :
//   1. affiche « Information requise » après une tentative d'envoi invalide ;
//   2. retire chaque message dès que le champ concerné est rempli.
const CHAMPS_OBLIGATOIRES = 'input[required], select[required], textarea[required]';

/** Le message affiché sous un champ vide. */
const messageObligatoire = (champ) => {
    // On cherche uniquement parmi les frères directs : un `.pdj-required-msg`
    // dans un sous-panneau ne doit pas être réutilisé pour un autre champ.
    let message = champ.parentElement?.querySelector(':scope > .pdj-required-msg');

    if (!message) {
        message = document.createElement('span');
        message.className = 'pdj-required-msg';
        message.hidden = true;

        const cle = champ.id || champ.name || Math.random().toString(36).slice(2);

        message.id = `oblig-${cle}-msg`;
        champ.setAttribute('aria-describedby', message.id);

        // Juste après le champ, dans le même conteneur : le message suit le
        // champ dans la grille Bootstrap au lieu d'être projeté ailleurs.
        champ.insertAdjacentElement('afterend', message);
    }

    return message;
};

/** Un champ est-il réellement vide, du point de vue de l'utilisateur ? */
const estVide = (champ) => {
    if (champ.type === 'checkbox' || champ.type === 'radio') {
        return !champ.checked;
    }

    return String(champ.value ?? '').trim() === '';
};

/** Les champs obligatoires d'un formulaire qu'il faut réellement remplir. */
const champsVisibles = (formulaire) => [...formulaire.querySelectorAll(CHAMPS_OBLIGATOIRES)]
    .filter((champ) => {
        if (champ.disabled || champ.readOnly || champ.type === 'hidden') return false;
        // Un champ caché par un onglet ou un panneau replié ne se remplit pas.
        return champ.offsetParent !== null || champ.getClientRects().length > 0;
    });

/** Affiche ou masque le message d'un champ. */
const montrerMessage = (champ, afficher) => {
    const message = messageObligatoire(champ);

    message.textContent = window.PDJ?.requiredMessage ?? 'Information requise';
    message.hidden = !afficher;
    champ.classList.toggle('is-missing', afficher);

    if (afficher) {
        champ.setAttribute('aria-invalid', 'true');
    } else {
        champ.removeAttribute('aria-invalid');
    }
};

/** Affiche les erreurs présentes après une tentative de validation. */
const verifierFormulaire = (formulaire) => {
    const champs = champsVisibles(formulaire);
    let manquants = 0;

    champs.forEach((champ) => {
        const vide = estVide(champ);

        if (vide) manquants++;

        montrerMessage(champ, vide);
    });

    return manquants;
};

document.querySelectorAll('form').forEach((formulaire) => {
    const champs = champsVisibles(formulaire);

    if (champs.length === 0) return;

    // Le script prend en charge l'affichage des erreurs après une tentative ;
    // sans lui, le navigateur conserve sa validation native des attributs
    // `required`.
    formulaire.noValidate = true;
    let validationTentee = false;

    // Filet de sécurité : si l'attribut `disabled` était retiré à la main ou
    // par un autre script, l'envoi est tout de même refusé, avec le premier
    // champ vide mis en évidence.
    formulaire.addEventListener('submit', (evento) => {
        validationTentee = true;

        if (verifierFormulaire(formulaire) === 0) return;

        evento.preventDefault();

        const premier = champs.find(estVide);

        premier?.focus();
        premier?.scrollIntoView({ block: 'center', behavior: 'smooth' });
    }, true);

    // Après la première tentative, les messages suivent les corrections sans
    // apparaître sur un formulaire que le client n'a pas encore soumis.
    formulaire.addEventListener('input', () => {
        if (validationTentee) verifierFormulaire(formulaire);
    });
    formulaire.addEventListener('change', () => {
        if (validationTentee) verifierFormulaire(formulaire);
    });
});

// ─── Apparition des blocs au défilement ─────────────────────────────────────
// Trois règles, dans cet ordre, et le site ne peut pas rester vide :
//
//  1. seuls les blocs réellement SOUS la ligne de flottaison au chargement sont
//     masqués. Ce qui est déjà visible s'affiche immédiatement, sans attendre
//     l'observateur ;
//  2. un délai de sécurité révèle tout, quoi qu'il arrive — un robot qui
//     n'exécute pas l'observateur, une capture d'écran, un Ctrl+P, un
//     navigateur qui ne le déclenche jamais ;
//  3. `beforeprint` révèle tout également : le bon de réservation s'imprime.
//
// Masquer du contenu en JavaScript est un pari : le seul moyen acceptable est
// de garantir que la page finit toujours rendue.
const mouvementReduit = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

if (!mouvementReduit && 'IntersectionObserver' in window) {
    const cibles = Array.from(document.querySelectorAll('[data-reveal]'));
    const enAttente = cibles.filter((cible) => cible.getBoundingClientRect().top > window.innerHeight);

    document.documentElement.classList.add('js-reveal');
    enAttente.forEach((cible) => cible.classList.add('is-pending'));

    const observateur = new IntersectionObserver((entrees) => {
        entrees.forEach((entree) => {
            if (!entree.isIntersecting) return;
            entree.target.classList.add('is-visible');
            observateur.unobserve(entree.target);
        });
    }, { rootMargin: '0px 0px -6% 0px', threshold: 0.05 });

    enAttente.forEach((cible) => observateur.observe(cible));

    // Filet de sécurité : plus rien ne reste jamais invisible plus de 2,5 s.
    const toutReveler = () => cibles.forEach((cible) => cible.classList.add('is-visible'));

    setTimeout(toutReveler, 2500);
    window.addEventListener('beforeprint', toutReveler);
}

// ─── Diaporama photo de l'accueil ──────────────────────────────────────────
const heroCarousel = document.querySelector('[data-hero-carousel]');

if (heroCarousel) {
    const slides = Array.from(heroCarousel.querySelectorAll('[data-hero-slide]'));
    const dots = Array.from(heroCarousel.querySelectorAll('[data-hero-dot]'));
    const previous = heroCarousel.querySelector('[data-hero-previous]');
    const next = heroCarousel.querySelector('[data-hero-next]');
    const rotationToggle = heroCarousel.querySelector('[data-hero-toggle]');
    const interval = Number(heroCarousel.dataset.heroInterval) || 5000;
    let activeIndex = Math.max(0, slides.findIndex((slide) => slide.classList.contains('is-active')));
    let rotationPaused = false;
    let timer;

    const showSlide = (index) => {
        activeIndex = (index + slides.length) % slides.length;

        slides.forEach((slide, slideIndex) => {
            const isActive = slideIndex === activeIndex;
            slide.classList.toggle('is-active', isActive);
            slide.setAttribute('aria-hidden', String(!isActive));
        });

        dots.forEach((dot, dotIndex) => {
            dot.setAttribute('aria-current', String(dotIndex === activeIndex));
        });
    };

    const stopRotation = () => {
        window.clearInterval(timer);
    };

    const startRotation = () => {
        stopRotation();

        if (
            slides.length < 2
            || mouvementReduit
            || rotationPaused
            || document.hidden
        ) {
            return;
        }

        timer = window.setInterval(() => showSlide(activeIndex + 1), interval);
    };

    if (slides.length > 1) {
        previous?.addEventListener('click', () => {
            showSlide(activeIndex - 1);
            startRotation();
        });

        next?.addEventListener('click', () => {
            showSlide(activeIndex + 1);
            startRotation();
        });

        dots.forEach((dot) => {
            dot.addEventListener('click', () => {
                showSlide(Number(dot.dataset.heroDot));
                startRotation();
            });
        });

        rotationToggle?.addEventListener('click', () => {
            rotationPaused = !rotationPaused;
            rotationToggle.setAttribute('aria-pressed', String(rotationPaused));
            rotationToggle.setAttribute(
                'aria-label',
                rotationPaused ? rotationToggle.dataset.playLabel : rotationToggle.dataset.pauseLabel,
            );
            rotationToggle.querySelector('i')?.classList.toggle('bi-play-fill', rotationPaused);
            rotationToggle.querySelector('i')?.classList.toggle('bi-pause-fill', !rotationPaused);
            startRotation();
        });

        document.addEventListener('visibilitychange', startRotation);
        startRotation();
    }
}

// ─── Repli de la barre latérale de l'administration ──────────────────────────
// Le choix est mémorisé : un agent qui replie le menu sur son 13 pouces ne
// veut pas le replier à chaque chargement de page.
const adminApp = document.getElementById('adminApp');
const adminSidebarToggle = document.querySelector('[data-admin-sidebar-toggle]');

if (adminApp && adminSidebarToggle) {
    const storageKey = 'pdj.admin.sidebar';

    const appliquerRepli = (replie) => {
        adminApp.classList.toggle('is-sidebar-collapsed', replie);
        adminSidebarToggle.setAttribute('aria-expanded', String(!replie));

        const libelle = replie
            ? adminSidebarToggle.dataset.expandLabel
            : adminSidebarToggle.dataset.collapseLabel;

        adminSidebarToggle.setAttribute('aria-label', libelle);
        adminSidebarToggle.title = libelle;
    };

    let replie = false;

    try {
        replie = window.localStorage.getItem(storageKey) === '1';
    } catch {
        // Mode privé : le repli reste valable pour la page en cours seulement.
    }

    appliquerRepli(replie);

    // Le lien actif peut être hors de l'écran visible de la barre latérale
    // (écran court, menu replié). On le ramène dans le champ sans forcer un
    // défilement si déjà visible.
    const lienActif = document.querySelector('.admin-nav .nav-link.active');
    if (lienActif) {
        lienActif.scrollIntoView({ block: 'nearest', inline: 'nearest' });
    }

    adminSidebarToggle.addEventListener('click', () => {
        replie = !adminApp.classList.contains('is-sidebar-collapsed');
        appliquerRepli(replie);

        try {
            window.localStorage.setItem(storageKey, replie ? '1' : '0');
        } catch {
            // Rien à mémoriser.
        }
    });
}

