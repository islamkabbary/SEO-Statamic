@extends('statamic::layout')
@section('title', __('silaseo::messages.redirects_title'))

@section('content')
    @include('silaseo::cp._theme')

    <header class="mb-6">
        <div class="silaseo-header">
            <h1>{{ __('silaseo::messages.redirects_title') }}</h1>
            <a href="{{ cp_route('silaseo.redirects.export') }}" class="silaseo-btn">
                {{ __('silaseo::messages.redirects_export') }}
            </a>
        </div>
        <p class="silaseo-muted text-sm mt-2">{{ __('silaseo::messages.redirects_intro') }}</p>
    </header>

    <div class="silaseo-card mb-6" style="padding: 1rem;">
        <form method="POST" action="{{ cp_route('silaseo.redirects.import') }}" enctype="multipart/form-data"
              style="display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap;">
            @csrf
            <input type="file" name="file" accept=".csv,text/csv,text/plain" required class="text-sm" />
            <button type="submit" class="silaseo-btn-accent">{{ __('silaseo::messages.redirects_import') }}</button>
            <span class="silaseo-muted" style="font-size: 0.7rem;">{{ __('silaseo::messages.redirects_csv_format') }}</span>
        </form>
    </div>

    @if (count($redirects))
        <div class="silaseo-card overflow-hidden">
            <table class="silaseo-table">
                <thead>
                    <tr>
                        <th class="silaseo-th">{{ __('silaseo::messages.redirects_from') }}</th>
                        <th class="silaseo-th">{{ __('silaseo::messages.redirects_to') }}</th>
                        <th class="silaseo-th" style="width: 6rem; text-align: end;">{{ __('silaseo::messages.redirects_status') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($redirects as $row)
                        <tr class="silaseo-row">
                            <td style="word-break: break-all;">{{ $row['from'] }}</td>
                            <td class="silaseo-muted" style="word-break: break-all;">{{ $row['to'] ?: '—' }}</td>
                            <td style="text-align: end; font-weight: 600;">{{ $row['status'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="silaseo-card silaseo-empty">
            {{ __('silaseo::messages.redirects_empty') }}
        </div>
    @endif
@endsection