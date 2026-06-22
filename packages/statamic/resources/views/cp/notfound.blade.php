@extends('statamic::layout')
@section('title', __('silaseo::messages.notfound_title'))

@section('content')
    @include('silaseo::cp._theme')

    <header class="mb-6">
        <div class="silaseo-header">
            <h1>{{ __('silaseo::messages.notfound_title') }}</h1>

            @if (count($entries))
                <form method="POST"
                      action="{{ cp_route('silaseo.404-log.clear') }}"
                      onsubmit="return confirm('{{ __('silaseo::messages.notfound_clear_confirm') }}')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="silaseo-btn-danger">
                        {{ __('silaseo::messages.notfound_clear') }}
                    </button>
                </form>
            @endif
        </div>
        <p class="silaseo-muted text-sm mt-2">{{ __('silaseo::messages.notfound_intro') }}</p>
    </header>

    @if (count($entries))
        <div class="silaseo-card overflow-hidden">
            <table class="silaseo-table">
                <thead>
                    <tr>
                        <th class="silaseo-th">{{ __('silaseo::messages.notfound_path') }}</th>
                        <th class="silaseo-th" style="width: 6rem; text-align: end;">{{ __('silaseo::messages.notfound_hits') }}</th>
                        <th class="silaseo-th" style="width: 12rem;">{{ __('silaseo::messages.notfound_last_seen') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($entries as $row)
                        <tr class="silaseo-row">
                            <td>
                                <a href="{{ $row['path'] }}" target="_blank" rel="noopener" class="silaseo-link" style="word-break: break-all;">
                                    {{ $row['path'] }}
                                </a>
                            </td>
                            <td style="text-align: end; font-weight: 600;">{{ $row['hits'] }}</td>
                            <td class="silaseo-muted">{{ $row['last_seen'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="silaseo-card silaseo-empty">
            {{ __('silaseo::messages.notfound_empty') }}
        </div>
    @endif
@endsection