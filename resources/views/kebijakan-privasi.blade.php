<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $policy['title'] }} — {{ config('app.name', 'Sengkuyung') }}</title>
    <meta name="description" content="Kebijakan privasi resmi Aplikasi Sengkuyung BAPENDA Provinsi Jawa Tengah.">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="{{ asset('LOGO_SENGKUYUNG/ICON_LOGO_SENGKUYUNG.png') }}">
    <style>
        body {
            background: #f4f7fb;
            color: #1f2937;
            line-height: 1.7;
        }

        .policy-wrap {
            max-width: 860px;
            margin: 2rem auto;
            padding: 0 1rem 3rem;
        }

        .policy-card {
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
            padding: 2rem 2.25rem;
        }

        .policy-brand {
            color: #005c99;
            font-weight: 700;
        }

        .policy-meta {
            font-size: 0.95rem;
            color: #6b7280;
        }

        .policy-section {
            margin-top: 2rem;
        }

        .policy-section h2 {
            font-size: 1.15rem;
            color: #005c99;
            margin-bottom: 0.75rem;
        }

        .policy-section p {
            margin-bottom: 0.75rem;
        }

        .policy-section ul {
            padding-left: 1.25rem;
        }

        .policy-contact {
            background: #f0f8ff;
            border-left: 4px solid #005c99;
            padding: 1rem 1.25rem;
            border-radius: 8px;
        }
    </style>
</head>

<body>
    <div class="policy-wrap">
        <div class="policy-card">
            <div class="text-center mb-4">
                <img src="{{ asset('LOGO_SENGKUYUNG/ICON_LOGO_SENGKUYUNG.png') }}" alt="Logo Sengkuyung" width="72"
                    class="mb-3">
                <h1 class="h3 policy-brand">{{ $policy['title'] }}</h1>
                <p class="policy-meta mb-1">{{ $policy['operator'] }}</p>
                <p class="policy-meta mb-0">
                    Berlaku sejak: {{ \Carbon\Carbon::parse($policy['effective_date'])->translatedFormat('d F Y') }}
                    &middot;
                    Diperbarui: {{ \Carbon\Carbon::parse($policy['last_updated'])->translatedFormat('d F Y') }}
                </p>
            </div>

            @foreach ($policy['sections'] as $section)
                <section class="policy-section" id="{{ $section['id'] }}">
                    <h2>{{ $section['title'] }}</h2>

                    @foreach ($section['paragraphs'] ?? [] as $paragraph)
                        <p>{{ $paragraph }}</p>
                    @endforeach

                    @if (!empty($section['list']))
                        <ul>
                            @foreach ($section['list'] as $item)
                                <li>{{ $item }}</li>
                            @endforeach
                        </ul>
                    @endif

                    @foreach ($section['paragraphs_after'] ?? [] as $paragraph)
                        <p>{{ $paragraph }}</p>
                    @endforeach

                    @if (!empty($section['contact']))
                        <div class="policy-contact">
                            <p class="mb-1"><strong>{{ $section['contact']['institution'] }}</strong></p>
                            <p class="mb-1">{{ $section['contact']['application'] }}</p>
                            <p class="mb-1">Situs:
                                <a href="{{ $section['contact']['website'] }}"
                                    target="_blank" rel="noopener noreferrer">{{ $section['contact']['website'] }}</a>
                            </p>
                            <p class="mb-0">Email: <a
                                    href="mailto:{{ $section['contact']['email'] }}">{{ $section['contact']['email'] }}</a>
                            </p>
                        </div>
                    @endif
                </section>
            @endforeach
        </div>
    </div>
</body>

</html>
