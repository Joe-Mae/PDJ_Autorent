<?php

namespace App\Http\Controllers;

use App\Http\Requests\Site\SendContactMessageRequest;
use App\Models\ContactMessage;
use App\Models\Faq;
use App\Models\Page;
use App\Models\Review;
use App\Models\VehicleCategory;
use App\Services\AvailabilityService;
use App\Services\NotificationService;
use App\Services\SettingsService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

/**
 * Pages de contenu.
 *
 * Tout ce qui est éditorial (FAQ, avis, horaires) vient de la base et se modifie
 * depuis l'admin. Les pages purement juridiques restent ici, parce qu'elles
 * changent tous les ans et relèvent d'un juriste, pas d'un agent d'accueil.
 */
class PageController extends Controller
{
    public function __construct(
        private readonly SettingsService $settings,
        private readonly NotificationService $notifications,
        private readonly AvailabilityService $availability,
    ) {}

    public function agency(): View
    {
        return view('pages.agency', [
            'address' => $this->settings->string('agency.address'),
            'phone' => $this->settings->formattedPhone(),
            'open' => $this->settings->string('agency.open_time'),
            'close' => $this->settings->string('agency.close_time'),
            'whatsapp' => $this->settings->whatsappNumber(),
            'categories' => VehicleCategory::query()->active()->withCount('vehicles')->orderBy('sort_order')->get(),
        ]);
    }

    public function contact(): View
    {
        return view('pages.contact', [
            'phone' => $this->settings->formattedPhone(),
            'whatsapp' => $this->settings->whatsappNumber(),
            'email' => $this->settings->string('contact.email'),
            'address' => $this->settings->string('agency.address'),
            'open' => $this->settings->string('agency.open_time'),
            'close' => $this->settings->string('agency.close_time'),
        ]);
    }

    public function sendContact(SendContactMessageRequest $request): RedirectResponse
    {
        $data = $request->validated();

        // Le message est conservé en base : un visiteur qui écrit un dimanche
        // doit retrouver sa demande le lundi, même si personne n'a ouvert
        // WhatsApp entre-temps.
        ContactMessage::query()->create($data);

        // En V1 le formulaire alimente la boîte WhatsApp de l'agence : c'est là
        // que le message est lu dans la journée, pas dans une boîte mail.
        $text = collect($data)
            ->filter()
            ->map(fn ($value, $key) => "$key: $value")
            ->implode("\n");

        return back()->with('status', __('page.message_sent', [
            'phone' => $this->settings->formattedPhone(),
        ]))->with('whatsapp_draft', rawurlencode($text));
    }

    public function faq(): View
    {
        $locale = app()->getLocale();

        $faqs = Faq::query()
            ->where('is_published', true)
            ->orderBy('category_fr')
            ->orderBy('sort_order')
            ->get()
            ->groupBy(fn (Faq $faq) => $faq->{'category_'.$locale} ?: $faq->category_fr);

        return view('pages.faq', compact('faqs'));
    }

    public function reviews(): View
    {
        return view('pages.reviews', [
            'reviews' => Review::query()->published()->bestFirst()->paginate(12),
            'average' => round((float) Review::query()->published()->avg('rating'), 1),
            'total' => Review::query()->published()->count(),
        ]);
    }

    public function withDriver(): View
    {
        $settings = $this->settings;

        return view('pages.driver', [
            'vehicleDailyPrice' => $settings->int('driver.block_price'),
            'soloDailyPrice' => $settings->int('driver.solo_daily_price'),
            'soloDiscountDailyPrice' => $settings->int('driver.solo_discount_daily_price'),
            'soloDiscountAfterDays' => $settings->int('driver.solo_discount_after_days'),
        ]);
    }

    public function howToPay(): View
    {
        return view('pages.how-to-pay', [
            'categories' => VehicleCategory::query()->active()->orderBy('sort_order')->get(),
            'deposits' => VehicleCategory::query()->active()
                ->selectRaw('MIN(deposit_amount) as min_deposit, MAX(deposit_amount) as max_deposit')
                ->first(),
        ]);
    }

    public function deposit(): View
    {
        return view('pages.deposit', [
            'categories' => VehicleCategory::query()->active()->orderBy('sort_order')->get(),
        ]);
    }

    public function terms(): View
    {
        if ($page = $this->publishedPage('conditions-generales')) {
            return $this->renderPage($page);
        }

        $settings = $this->settings;

        return view('pages.terms', [
            'minAge' => $settings->int('booking.min_age'),
            'licenseYears' => $settings->int('booking.min_license_years'),
            'speedLimit' => $settings->int('booking.speed_limit_kmh'),
            'open' => $settings->string('agency.open_time'),
            'close' => $settings->string('agency.close_time'),
        ]);
    }

    public function insurance(): View
    {
        return view('pages.insurance');
    }

    public function privacy(): View
    {
        if ($page = $this->publishedPage('confidentialite')) {
            return $this->renderPage($page);
        }

        return view('pages.privacy');
    }

    public function legal(): View
    {
        if ($page = $this->publishedPage('mentions-legales')) {
            return $this->renderPage($page);
        }

        return view('pages.legal', [
            'company' => $this->settings->string('agency.name'),
            'address' => $this->settings->string('agency.address'),
            'phone' => $this->settings->formattedPhone(),
            'email' => $this->settings->string('contact.email'),
        ]);
    }

    /**
     * Page juridique publiée en base, si l'agence en a créé une. Sans elle, on
     * garde la version livrée avec l'application : le site ne tombe jamais sur
     * une page vide parce qu'un contenu n'a pas encore été saisi.
     */
    private function publishedPage(string $slug): ?Page
    {
        return Page::query()->active()->where('slug', $slug)->first();
    }

    private function renderPage(Page $page): View
    {
        return view('pages.dynamic', ['page' => $page]);
    }
}
