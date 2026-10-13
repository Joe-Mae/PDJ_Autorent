<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ReviewStatus;
use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\Faq;
use App\Models\Holiday;
use App\Models\Review;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Contenus éditables du site : bannières, FAQ, avis, jours fériés.
 *
 * Tout ce que l'agence peut vouloir changer seule — un prix affiché en
 * bannière, une question qu'on reçoit tout le temps, un avis à publier — vit
 * ici. Rien n'oblige à redéployer pour corriger une coquille.
 */
class ContentController extends Controller
{
    /** Les quatre familles de contenus, et ce qu'elles pilotent. */
    private const TYPES = [
        'bannieres' => ['label' => 'Bannières', 'model' => Banner::class, 'title' => 'title_fr', 'body' => 'subtitle_fr'],
        'faq' => ['label' => 'FAQ', 'model' => Faq::class, 'title' => 'question_fr', 'body' => 'answer_fr'],
        'avis' => ['label' => 'Avis', 'model' => Review::class, 'title' => 'author_name', 'body' => 'comment'],
        'feries' => ['label' => 'Jours fériés', 'model' => Holiday::class, 'title' => 'label_fr', 'body' => 'label_en'],
    ];

    public function index(): View
    {
        return view('admin.contents.index', [
            'types' => collect(self::TYPES)->map(fn ($type, $key) => [
                'key' => $key,
                'label' => $type['label'],
                'count' => $type['model']::query()->count(),
            ]),
        ]);
    }

    public function edit(string $type): View
    {
        $config = $this->config($type);

        return view('admin.contents.edit', [
            'type' => $type,
            'config' => $config,
            'items' => $config['model']::query()->orderByDesc('id')->paginate(20),
        ]);
    }

    public function form(string $type, int $id): View
    {
        $config = $this->config($type);
        $item = $config['model']::query()->findOrFail($id);

        return view('admin.contents.form', [
            'type' => $type,
            'config' => $config,
            'item' => $item,
        ]);
    }

    public function save(Request $request, string $type, int $id): RedirectResponse
    {
        $config = $this->config($type);
        $item = $config['model']::query()->findOrFail($id);

        $item->update($this->validated($request, $type, $item));

        return back()->with('status', __('admin.content_saved'));
    }

    public function update(Request $request, string $type): RedirectResponse
    {
        $this->config($type);

        $data = $this->validated($request, $type);

        if ($type === 'avis' && isset($data['status'])) {
            Review::query()->whereKey($data['id'])->update(['status' => ReviewStatus::from($data['status'])]);
        }

        return back()->with('status', __('admin.content_saved'));
    }

    public function destroy(Request $request, string $type, int $id): RedirectResponse
    {
        $config = $this->config($type);
        $config['model']::query()->findOrFail($id)->delete();

        return back()->with('status', __('admin.content_deleted'));
    }

    /** @return array{label: string, model: class-string, title: string, body: string} */
    private function config(string $type): array
    {
        abort_unless(array_key_exists($type, self::TYPES), 404);

        return self::TYPES[$type];
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, string $type, mixed $item = null): array
    {
        $data = match ($type) {
            'bannieres' => $request->validate([
                'title_fr' => ['required', 'string', 'max:150'],
                'title_en' => ['nullable', 'string', 'max:150'],
                'subtitle_fr' => ['nullable', 'string', 'max:500'],
                'subtitle_en' => ['nullable', 'string', 'max:500'],
                'slot' => ['required', Rule::in(['home_hero', 'home_middle', 'home_bottom'])],
                'is_active' => ['nullable', 'boolean'],
            ]),
            'faq' => $request->validate([
                'category_fr' => ['required', 'string', 'max:80'],
                'category_en' => ['required', 'string', 'max:80'],
                'question_fr' => ['required', 'string', 'max:255'],
                'question_en' => ['required', 'string', 'max:255'],
                'answer_fr' => ['required', 'string', 'max:3000'],
                'answer_en' => ['nullable', 'string', 'max:3000'],
                'sort_order' => ['required', 'integer', 'min:0'],
                'is_published' => ['nullable', 'boolean'],
            ]),
            'avis' => $request->validate([
                'id' => ['nullable', 'exists:reviews,id'],
                'status' => ['nullable', Rule::in(array_column(ReviewStatus::cases(), 'value'))],
                'agency_answer' => ['nullable', 'string', 'max:500'],
            ]),
            'feries' => $request->validate([
                'date' => ['required', 'date'],
                'label_fr' => ['required', 'string', 'max:120'],
                'label_en' => ['required', 'string', 'max:120'],
                'is_agency_closed' => ['nullable', 'boolean'],
                'is_price_surcharged' => ['nullable', 'boolean'],
            ]),
            default => [],
        };

        // Les cases à cocher absentes du POST valent false : c'est le seul moyen
        // de les décocher depuis un formulaire HTML.
        foreach (['is_active', 'is_published', 'is_agency_closed', 'is_price_surcharged'] as $flag) {
            if (array_key_exists($flag, $data)) {
                $data[$flag] = (bool) $data[$flag];
            }
        }

        return $data;
    }
}
