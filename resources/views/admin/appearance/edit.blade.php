@extends('layouts.panel')

@section('title', 'Apparence du site')
@section('breadcrumb', 'Administration / Apparence du site')

@section('content')
    <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h3 mb-1">Apparence du site</h1>
            <p class="text-muted mb-0">Gérez le bandeau d’accueil, les couleurs et les libellés publics.</p>
        </div>
        <a href="{{ route('home', ['locale' => 'fr']) }}" class="btn btn-outline-secondary" target="_blank" rel="noopener">
            <i class="bi bi-box-arrow-up-right me-1"></i>Voir le site
        </a>
    </div>

    <form action="{{ route('admin.appearance.update') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <section class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4">
                <h2 class="h5 mb-1">Diaporama de l’accueil</h2>
                <p class="small text-muted mb-3">Ajoutez une vidéo et/ou des photos. La vidéo, si elle existe, s'affiche en premier ; viennent ensuite les photos importées, puis toutes celles des véhicules sélectionnés, dans l'ordre de leur galerie. Quatre photos maximum au total ; sans photo, seul le bandeau de couleur s'affiche.</p>

                @if ($heroImages->isNotEmpty())
                    <div class="row g-3 mb-4">
                        @foreach ($heroImages as $image)
                            <div class="col-6 col-md-3">
                                <div class="border rounded p-2 h-100">
                                    <img src="{{ $image['url'] }}" alt="Photo actuelle du diaporama"
                                         class="img-fluid rounded mb-2" style="height: 130px; width: 100%; object-fit: cover;">
                                    <label class="form-check small mb-0">
                                        <input class="form-check-input" type="checkbox" name="remove_hero_images[]"
                                               value="{{ $image['path'] }}"
                                               @checked(in_array($image['path'], (array) old('remove_hero_images', []), true))>
                                        <span class="form-check-label">Retirer cette photo</span>
                                    </label>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

                <div class="mb-4">
                    <label class="form-label" for="hero_images">Ajouter des photos au diaporama</label>
                    <input class="form-control" id="hero_images" name="hero_images[]" type="file"
                           accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" multiple>
                    <div class="form-text">Formats JPG, PNG ou WebP. Maximum 2 Mo par image et quatre photos dans le diaporama.</div>
                    @error('hero_images')
                        <div class="text-danger small mt-1" role="alert">{{ $message }}</div>
                    @enderror
                    @for ($imageIndex = 0; $imageIndex < 4; $imageIndex++)
                        @error('hero_images.'.$imageIndex)
                            <div class="text-danger small mt-1" role="alert">{{ $message }}</div>
                        @enderror
                    @endfor
                </div>

                <div class="mb-4">
                    <label class="form-label" for="hero_video">Vidéo de présentation (facultative)</label>
                    @if ($heroVideoUrl !== '')
                        <div class="border rounded p-2 mb-2" style="max-width: 420px;">
                            <video src="{{ $heroVideoUrl }}" class="rounded mb-2 w-100" controls muted playsinline
                                   preload="metadata" style="max-height: 220px; background: #000;"></video>
                            <label class="form-check small mb-0">
                                <input class="form-check-input" type="checkbox" name="remove_hero_video" value="1"
                                       @checked(old('remove_hero_video'))>
                                <span class="form-check-label">Retirer la vidéo</span>
                            </label>
                        </div>
                    @endif
                    <input class="form-control" id="hero_video" name="hero_video" type="file"
                           accept=".mp4,.webm,video/mp4,video/webm">
                    <div class="form-text">Formats MP4 ou WebM, 20 Mo maximum. La vidéo est diffusée sans son et en boucle, avant les photos.</div>
                    @error('hero_video')
                        <div class="text-danger small mt-1" role="alert">{{ $message }}</div>
                    @enderror
                </div>

                @for ($slot = 0; $slot < 4; $slot++)
                    @php($selectedId = old('hero_vehicle_ids.'.$slot, $heroVehicleIds[$slot] ?? ''))
                    <div class="row align-items-center g-3 mb-3">
                        <div class="col-md-3">
                            <label class="form-label mb-0" for="hero_vehicle_{{ $slot }}">Véhicule {{ $slot + 1 }}</label>
                        </div>
                        <div class="col-md-9">
                            <select class="form-select" id="hero_vehicle_{{ $slot }}" name="hero_vehicle_ids[{{ $slot }}]">
                                <option value="">Sélection automatique / aucune</option>
                                @foreach ($vehicles as $vehicle)
                                    <option value="{{ $vehicle->id }}" @selected((string) $selectedId === (string) $vehicle->id)>
                                        {{ $vehicle->displayName() }} ({{ $vehicle->year }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                @endfor

                @error('hero_vehicle_ids')
                    <div class="text-danger small" role="alert">{{ $message }}</div>
                @enderror
                @if ($vehicles->isEmpty())
                    <div class="alert alert-info mb-0">Vous pouvez importer des photos ci-dessus. Pour utiliser une photo de véhicule, ajoutez d’abord une photo à un véhicule actif dans le Garage.</div>
                @endif
            </div>
        </section>

        <section class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4">
                <h2 class="h5 mb-1">Textes du bandeau d’accueil</h2>
                <p class="small text-muted mb-3">Le titre et le sous-titre affichés sur les photos d’accueil. L’anglais est facultatif : sans traduction, le texte français s’affiche.</p>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="hero_title_fr" class="form-label">Titre (FR)</label>
                        <input class="form-control" id="hero_title_fr" name="hero_title_fr" maxlength="150" required
                               value="{{ old('hero_title_fr', $heroTitleFr) }}">
                    </div>
                    <div class="col-md-6">
                        <label for="hero_title_en" class="form-label">Title (EN)</label>
                        <input class="form-control" id="hero_title_en" name="hero_title_en" maxlength="150"
                               value="{{ old('hero_title_en', $heroTitleEn) }}">
                    </div>
                    <div class="col-md-6">
                        <label for="hero_subtitle_fr" class="form-label">Sous-titre (FR)</label>
                        <textarea class="form-control" id="hero_subtitle_fr" name="hero_subtitle_fr" maxlength="500" rows="2" required>{{ old('hero_subtitle_fr', $heroSubtitleFr) }}</textarea>
                    </div>
                    <div class="col-md-6">
                        <label for="hero_subtitle_en" class="form-label">Subtitle (EN)</label>
                        <textarea class="form-control" id="hero_subtitle_en" name="hero_subtitle_en" maxlength="500" rows="2">{{ old('hero_subtitle_en', $heroSubtitleEn) }}</textarea>
                    </div>
                    <div class="col-md-6">
                        <label for="hero_eyebrow_fr" class="form-label">Accroche au-dessus du titre (FR)</label>
                        <input class="form-control" id="hero_eyebrow_fr" name="hero_eyebrow_fr" maxlength="100" required
                               value="{{ old('hero_eyebrow_fr', $heroEyebrowFr) }}">
                    </div>
                    <div class="col-md-6">
                        <label for="hero_eyebrow_en" class="form-label">Eyebrow above the title (EN)</label>
                        <input class="form-control" id="hero_eyebrow_en" name="hero_eyebrow_en" maxlength="100" required
                               value="{{ old('hero_eyebrow_en', $heroEyebrowEn) }}">
                    </div>
                </div>
            </div>
        </section>

        <section class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4">
                <h2 class="h5 mb-3">Identité visuelle</h2>
                <div class="row g-3">
                    <div class="col-md-2">
                        <label for="brand_mark" class="form-label">Monogramme</label>
                        <input class="form-control" id="brand_mark" name="brand_mark" maxlength="8"
                               value="{{ old('brand_mark', $brandMark) }}">
                        <div class="form-text">2 à 4 lettres.</div>
                    </div>
                    <div class="col-md-4">
                        <label for="brand_label" class="form-label">Nom à côté du logo</label>
                        <input class="form-control" id="brand_label" name="brand_label" maxlength="40" required
                               value="{{ old('brand_label', $brandLabel) }}">
                    </div>
                    <div class="col-md-3">
                        <label for="brand_color" class="form-label">Couleur principale</label>
                        <input class="form-control form-control-color d-block" type="color" id="brand_color" name="brand_color"
                               value="{{ old('brand_color', $brandColor) }}" title="Choisir la couleur principale">
                    </div>
                    <div class="col-md-3">
                        <label for="accent_color" class="form-label">Couleur d’accent</label>
                        <input class="form-control form-control-color d-block" type="color" id="accent_color" name="accent_color"
                               value="{{ old('accent_color', $accentColor) }}" title="Choisir la couleur d’accent">
                    </div>
                    <div class="col-12">
                        <label for="logo" class="form-label">Logo (image)</label>
                        <div class="d-flex flex-wrap align-items-center gap-3">
                            @if ($logoPath !== '')
                                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($logoPath) }}"
                                     alt="Logo actuel" class="rounded" style="max-height: 56px;">
                            @endif
                            <input class="form-control" type="file" id="logo" name="logo"
                                   accept="image/jpeg,image/png,image/webp" style="max-width: 320px;">
                        </div>
                        <div class="form-text">JPG, PNG ou WebP, 2 Mo maximum. Sans logo, le monogramme s’affiche.</div>
                        @error('logo')
                            <div class="text-danger small" role="alert">{{ $message }}</div>
                        @enderror
                        @if ($logoPath !== '')
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" id="remove_logo" name="remove_logo" value="1"
                                       @checked(old('remove_logo'))>
                                <label class="form-check-label" for="remove_logo">Retirer le logo actuel</label>
                            </div>
                        @endif
                    </div>
                    <div class="col-md-6">
                        <label for="footer_tagline_fr" class="form-label">Signature du pied de page (FR)</label>
                        <textarea class="form-control" id="footer_tagline_fr" name="footer_tagline_fr" maxlength="180" rows="2" required>{{ old('footer_tagline_fr', $footerTaglineFr) }}</textarea>
                    </div>
                    <div class="col-md-6">
                        <label for="footer_tagline_en" class="form-label">Footer tagline (EN)</label>
                        <textarea class="form-control" id="footer_tagline_en" name="footer_tagline_en" maxlength="180" rows="2" required>{{ old('footer_tagline_en', $footerTaglineEn) }}</textarea>
                    </div>
                </div>
            </div>
        </section>

        <section class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4">
                <h2 class="h5 mb-1">Libellés de navigation</h2>
                <p class="small text-muted mb-3">Les libellés sont réglables séparément en français et en anglais.</p>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th scope="col">Lien</th>
                                <th scope="col">Français</th>
                                <th scope="col">English</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach (['home' => 'Accueil', 'fleet' => 'Garage', 'driver' => 'Chauffeur', 'faq' => 'FAQ', 'contact' => 'Contact', 'lookup' => 'Consulter ma réservation'] as $key => $label)
                                <tr>
                                    <th scope="row">{{ $label }}</th>
                                    <td>
                                        <input class="form-control" name="nav_{{ $key }}_fr" maxlength="{{ $key === 'faq' ? 50 : 40 }}" required
                                               aria-label="{{ $label }} en français"
                                               value="{{ old('nav_'.$key.'_fr', $navigationLabels[$key]['fr']) }}">
                                    </td>
                                    <td>
                                        <input class="form-control" name="nav_{{ $key }}_en" maxlength="{{ $key === 'faq' ? 50 : 40 }}" required
                                               aria-label="{{ $label }} in English"
                                               value="{{ old('nav_'.$key.'_en', $navigationLabels[$key]['en']) }}">
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <div class="d-flex justify-content-end mb-4">
            <button class="btn btn-primary btn-lg" type="submit">
                <i class="bi bi-check2 me-1"></i>Enregistrer les changements
            </button>
        </div>
    </form>
@endsection
