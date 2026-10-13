{{-- Fragment injecté en AJAX quand un filtre change : pas de rechargement de page,
     la position de défilement et l'historique du navigateur sont préservés. --}}
@foreach ($vehicles as $vehicle)
    <x-vehicle-row :vehicle="$vehicle" :period="$period"
                   :with-driver="(bool) request()->boolean('avec_chauffeur')" />
@endforeach
