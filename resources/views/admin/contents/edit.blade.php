@extends('layouts.panel')

@section('title', $config['label'])

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 fw-bold mb-0">{{ $config['label'] }}</h1>
        <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.contents.index') }}">{{ __('admin.back') }}</a>
    </div>

    @if ($type === 'avis')
        <div class="pdj-card p-0 overflow-hidden">
            <table class="table admin-table mb-0">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('admin.client') }}</th>
                        <th class="text-end">{{ __('admin.rating') }}</th>
                        <th>{{ __('admin.comment') }}</th>
                        <th>{{ __('admin.status') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($items as $item)
                        <tr>
                            <td class="small">{{ $item->author_name }}<br><span class="text-muted">{{ $item->author_city }}</span></td>
                            <td class="text-end text-warning">{{ str_repeat('â˜…', $item->rating) }}</td>
                            <td class="small">{{ $item->comment }}</td>
                            <td>
                                <form method="POST" action="{{ route('admin.contents.update', 'avis') }}" class="d-flex gap-2">
                                    @csrf
                                    <input type="hidden" name="id" value="{{ $item->id }}">
                                    <select class="form-select form-select-sm" name="status">
                                        @foreach (\App\Enums\ReviewStatus::cases() as $status)
                                            <option value="{{ $status->value }}" @selected($item->status === $status)>{{ $status->label() }}</option>
                                        @endforeach
                                    </select>
                                    <button class="btn btn-sm btn-outline-primary">{{ __('admin.save') }}</button>
                                </form>
                            </td>
                            <td class="text-end">
                                <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.contents.form', ['avis', $item->id]) }}">
                                    <i class="bi bi-pencil"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">{{ __('admin.no_content') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @else
        <div class="pdj-card p-0 overflow-hidden">
            <table class="table admin-table mb-0">
                <thead class="table-light">
                    <tr>
                        <th>{{ $config['title'] }}</th>
                        <th>{{ $config['body'] }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($items as $item)
                        <tr>
                            <td class="small fw-semibold">{{ $item->{$config['title']} }}</td>
                            <td class="small text-muted">{{ str($item->{$config['body']})->limit(90) }}</td>
                            <td class="text-end">
                                <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.contents.form', [$type, $item->id]) }}">
                                    <i class="bi bi-pencil"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-center text-muted py-4">{{ __('admin.no_content') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif

    <div class="mt-3">{{ $items->links() }}</div>
@endsection