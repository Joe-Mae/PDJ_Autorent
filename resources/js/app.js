import * as bootstrap from 'bootstrap';

/**
 * Interactions du site.
 *
 * Trois choses méritent du JavaScript, pas une de plus :
 *  1. le filtrage en AJAX, pour ne pas recharger la page à chaque case cochée ;
 *  2. la bascule chauffeur, pour recalculer le prix sans round-trip ;
 *  3. la galerie de la fiche véhicule.
 */

// ─── Filtres en AJAX ───────────────────────────────────────────────────────
const filtersForm = document.querySelector('[data-filters-form]');

if (filtersForm) {
    const results = document.querySelector('[data-results]');
    const counter = document.querySelector('[data-results-count]');
    const overlay = document.querySelector('[data-results-loading]');
    let debounce;

    const refresh = async (push = true) => {
        overlay?.classList.remove('d-none');

        const params = new URLSearchParams(new FormData(filtersForm));
        const query = params.toString();

        try {
            const response = await fetch(`${window.location.pathname}?${query}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });
            const data = await response.json();

            if (results) results.innerHTML = data.html;
            if (counter) counter.textContent = data.count;

            // L'URL devient partageable : c'est le but du filtre côté client.
            if (push) {
                window.history.replaceState({}, '', query ? `${window.location.pathname}?${query}` : window.location.pathname);
            }
        } finally {
            overlay?.classList.add('d-none');
        }
    };

    filtersForm.addEventListener('change', (event) => {
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
const gallery = document.querySelector('[data-gallery]');

if (gallery) {
    const main = gallery.querySelector('[data-gallery-main]');
    gallery.querySelectorAll('[data-gallery-thumb]').forEach((thumb) => {
        thumb.addEventListener('click', () => {
            main.src = thumb.dataset.galleryThumb;
            gallery.querySelectorAll('[data-gallery-thumb]').forEach((t) => t.classList.remove('is-active'));
            thumb.classList.add('is-active');
        });
    });
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
            || heroCarousel.matches(':hover')
            || heroCarousel.contains(document.activeElement)
        ) {
            return;
        }

        timer = window.setInterval(() => showSlide(activeIndex + 1), 6500);
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

        heroCarousel.addEventListener('mouseenter', stopRotation);
        heroCarousel.addEventListener('mouseleave', startRotation);
        heroCarousel.addEventListener('focusin', stopRotation);
        heroCarousel.addEventListener('focusout', startRotation);
        document.addEventListener('visibilitychange', startRotation);
        startRotation();
    }
}
