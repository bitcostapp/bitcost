<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Bitcost — Authorize Device</title>
    {{-- If embedded in an iframe, break out so form submission isn't sandboxed. --}}
    <script>if (window.top !== window.self) { window.top.location.href = window.location.href; }</script>
    <style>
        :root {
            --white: #ffffff; --slate-50: #f8fafc; --slate-200: #e2e8f0;
            --slate-500: #64748b; --slate-600: #475569; --slate-700: #334155; --slate-900: #0f172a;
            --blue-600: #2563eb; --blue-700: #1d4ed8;
        }
        * { box-sizing: border-box; }
        body {
            font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", sans-serif;
            background: var(--slate-50); color: var(--slate-900);
            -webkit-font-smoothing: antialiased; margin: 0;
            min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 1.5rem;
        }
        .card {
            background: var(--white); border: 1px solid var(--slate-200);
            border-radius: 16px; padding: 2rem; width: 100%; max-width: 420px;
            box-shadow: 0 1px 3px rgba(15, 23, 42, .06), 0 10px 30px rgba(15, 23, 42, .04);
        }
        .brand { display: flex; align-items: center; gap: .5rem; margin-bottom: 1.5rem; }
        .brand .mark {
            display: flex; height: 28px; width: 28px; align-items: center; justify-content: center;
            border-radius: 8px; background: var(--blue-600); color: #fff;
        }
        .brand .word { font-size: 1.125rem; font-weight: 600; letter-spacing: -.01em; }
        h1 { font-size: 1.25rem; font-weight: 600; letter-spacing: -.01em; margin: 0 0 .75rem; }
        p { color: var(--slate-600); font-size: .9rem; margin: .3rem 0; }
        p .muted { color: var(--slate-500); }
        strong { color: var(--slate-900); font-weight: 600; }
        ul { color: var(--slate-600); font-size: .85rem; padding-left: 1.1rem; margin: .5rem 0 0; }
        .row { display: flex; gap: .75rem; margin-top: 1.5rem; }
        .row form { flex: 1; margin: 0; }
        button { width: 100%; padding: .8rem; font-size: .95rem; font-weight: 600; border: 0; border-radius: 10px; cursor: pointer; }
        .approve { background: var(--blue-600); color: #fff; }
        .approve:hover { background: var(--blue-700); }
        .deny { background: var(--white); color: var(--slate-700); border: 1px solid var(--slate-200); }
        .deny:hover { background: var(--slate-50); }
    </style>
</head>
<body>
    <div class="card">
        <div class="brand">
            <span class="mark">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 3v18h18"/><path d="M18 17V9"/><path d="M13 17V5"/><path d="M8 17v-3"/>
                </svg>
            </span>
            <span class="word">Bitcost</span>
        </div>

        <h1>Authorize {{ $client->name }}</h1>
        <p class="muted">Signed in as <strong>{{ $user->email }}</strong>.</p>
        <p><strong>{{ $client->name }}</strong> wants to access your Bitcost account.</p>

        @if (count($scopes) > 0)
            <p>This will grant the following permissions:</p>
            <ul>
                @foreach ($scopes as $scope)
                    <li>{{ $scope->description ?? $scope->id }}</li>
                @endforeach
            </ul>
        @endif

        <div class="row">
            <form method="POST" action="{{ route('passport.device.authorizations.approve') }}">
                @csrf
                <input type="hidden" name="auth_token" value="{{ $authToken }}">
                <button type="submit" class="approve">Approve</button>
            </form>

            <form method="POST" action="{{ route('passport.device.authorizations.deny') }}">
                @csrf
                @method('DELETE')
                <input type="hidden" name="auth_token" value="{{ $authToken }}">
                <button type="submit" class="deny">Deny</button>
            </form>
        </div>
    </div>
</body>
</html>
