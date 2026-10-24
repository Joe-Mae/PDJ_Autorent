<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ReviewStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveContentRequest;
use App\Models\Banner;
use App\Models\Faq;
use App\Models\Holiday;
use App\Models\Review;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

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

    public function save(SaveContentRequest $request, string $type, int $id): RedirectResponse
    {
        $config = $this->config($type);
        $item = $config['model']::query()->findOrFail($id);

        $item->update($this->validated($request));

        return back()->with('status', __('admin.content_saved'));
    }

    public function update(SaveContentRequest $request, string $type): RedirectResponse
    {
        $this->config($type);

        $data = $this->validated($request);

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

    /**
     * Données validées du contenu, avec les cases à cocher ramenées à des
     * booléens : « absente du POST » vaut false, c'est le seul moyen de
     * décocher une case depuis un formulaire HTML.
     *
     * @return array<string, mixed>
     */
    private function validated(SaveContentRequest $request): array
    {
        $data = $request->validated();

        foreach (['is_active', 'is_published', 'is_agency_closed', 'is_price_surcharged'] as $flag) {
            if (array_key_exists($flag, $data)) {
                $data[$flag] = (bool) $data[$flag];
            }
        }

        return $data;
    }
}
