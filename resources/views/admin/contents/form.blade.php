@extends('layouts.panel')

@section('title', 'Modifier : '.$config['label'])

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 fw-bold mb-0">{{ $config['label'] }}</h1>
        <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.contents.edit', $type) }}">{{ __('admin.back') }}</a>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <form method="POST" action="{{ route('admin.contents.save', [$type, $item->id]) }}">
                @csrf

                @if ($type === 'bannieres')
                    <div class="pdj-card">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label" for="slot">Emplacement</label>
                                <select class="form-select" id="slot" name="slot">
                                    @foreach (['home_hero' => 'Bandeau principal', 'home_middle' => 'Milieu de page', 'home_bottom' => 'Bas de page'] as $value => $label)
                                        <option value="{{ $value }}" @selected($item->slot === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="title_fr">Titre (FR)</label>
                                <input type="text" class="form-control" id="title_fr" name="title_fr" value="{{ old('title_fr', $item->title_fr) }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="title_en">Title (EN)</label>
                                <input type="text" class="form-control" id="title_en" name="title_en" value="{{ old('title_en', $item->title_en) }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="subtitle_fr">Sous-titre (FR)</label>
                                <textarea class="form-control" id="subtitle_fr" name="subtitle_fr" rows="3">{{ old('subtitle_fr', $item->subtitle_fr) }}</textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="subtitle_en">Subtitle (EN)</label>
                                <textarea class="form-control" id="subtitle_en" name="subtitle_en" rows="3">{{ old('subtitle_en', $item->subtitle_en) }}</textarea>
                            </div>
                            <div class="col-12">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="is_active" value="1"
                                           id="is_active" @checked(old('is_active', $item->is_active))>
                                    <label class="form-check-label" for="is_active">{{ __('admin.active') }}</label>
                                </div>
                            </div>
                        </div>
                    </div>
                @elseif ($type === 'faq')
                    <div class="pdj-card">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="category_fr">CatÃ©gorie (FR)</label>
                                <input type="text" class="form-control" id="category_fr" name="category_fr" value="{{ old('category_fr', $item->category_fr) }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="category_en">Category (EN)</label>
                                <input type="text" class="form-control" id="category_en" name="category_en" value="{{ old('category_en', $item->category_en) }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="question_fr">Question (FR)</label>
                                <input type="text" class="form-control" id="question_fr" name="question_fr" value="{{ old('question_fr', $item->question_fr) }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="question_en">Question (EN)</label>
                                <input type="text" class="form-control" id="question_en" name="question_en" value="{{ old('question_en', $item->question_en) }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="answer_fr">RÃ©ponse (FR)</label>
                                <textarea class="form-control" id="answer_fr" name="answer_fr" rows="5" required>{{ old('answer_fr', $item->answer_fr) }}</textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="answer_en">Answer (EN)</label>
                                <textarea class="form-control" id="answer_en" name="answer_en" rows="5">{{ old('answer_en', $item->answer_en) }}</textarea>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="sort_order">Ordre</label>
                                <input type="number" class="form-control" id="sort_order" name="sort_order" value="{{ old('sort_order', $item->sort_order) }}">
                            </div>
                            <div class="col-md-8 d-flex align-items-end">
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" name="is_published" value="1"
                                           id="is_published" @checked(old('is_published', $item->is_published))>
                                    <label class="form-check-label" for="is_published">{{ __('admin.published') }}</label>
                                </div>
                            </div>
                        </div>
                    </div>
                @elseif ($type === 'feries')
                    <div class="pdj-card">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label" for="date">Date</label>
                                <input type="date" class="form-control" id="date" name="date" value="{{ old('date', $item->date?->toDateString()) }}" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="label_fr">LibellÃ© (FR)</label>
                                <input type="text" class="form-control" id="label_fr" name="label_fr" value="{{ old('label_fr', $item->label_fr) }}" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="label_en">Label (EN)</label>
                                <input type="text" class="form-control" id="label_en" name="label_en" value="{{ old('label_en', $item->label_en) }}" required>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="is_agency_closed" value="1"
                                           id="is_agency_closed" @checked(old('is_agency_closed', $item->is_agency_closed))>
                                    <label class="form-check-label" for="is_agency_closed">{{ __('admin.agency_closed') }}</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="is_price_surcharged" value="1"
                                           id="is_price_surcharged" @checked(old('is_price_surcharged', $item->is_price_surcharged))>
                                    <label class="form-check-label" for="is_price_surcharged">{{ __('admin.surcharge') }}</label>
                                </div>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="pdj-card">
                        <label class="form-label" for="agency_answer">{{ __('admin.answer') }}</label>
                        <textarea class="form-control" id="agency_answer" name="agency_answer" rows="4">{{ old('agency_answer', $item->agency_answer) }}</textarea>
                        <p class="small text-muted mt-2">{{ __('admin.answer_hint') }}</p>
                    </div>
                @endif

                <div class="d-flex gap-2 mt-3">
                    <button class="btn btn-primary">{{ __('admin.save') }}</button>
                </div>
            </form>
        </div>

        <div class="col-lg-4">
            <div class="pdj-card">
                <h2 class="h6 fw-bold mb-2">{{ __('admin.delete') }}</h2>
                <form method="POST" action="{{ route('admin.contents.destroy', [$type, $item->id]) }}"
                      onsubmit="return confirm('{{ __('admin.delete_confirm') }}')">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-outline-danger w-100">{{ __('admin.delete') }}</button>
                </form>
            </div>
        </div>
    </div>
@endsection