<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Bitcost — Connect your CLI</title>
    {{-- If embedded in an iframe, break out so form submission isn't sandboxed. --}}
    <script>if (window.top !== window.self) { window.top.location.href = window.location.href; }</script>
    <style>
        :root {
            --white: #ffffff; --slate-50: #f8fafc; --slate-200: #e2e8f0;
            --slate-500: #64748b; --slate-600: #475569; --slate-900: #0f172a;
            --blue-600: #2563eb; --blue-700: #1d4ed8;
            --green-bg: #ecfdf5; --green-fg: #047857;
            --red-bg: #fef2f2; --red-fg: #b91c1c;
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
            border-radius: 16px; padding: 2rem; width: 100%; max-width: 400px;
            box-shadow: 0 1px 3px rgba(15, 23, 42, .06), 0 10px 30px rgba(15, 23, 42, .04);
        }
        .brand { display: flex; align-items: center; gap: .5rem; margin-bottom: 1.5rem; }
        .brand .mark {
            display: flex; height: 28px; width: 28px; align-items: center; justify-content: center;
            border-radius: 8px; background: var(--blue-600); color: #fff;
        }
        .brand .word { font-size: 1.125rem; font-weight: 600; letter-spacing: -.01em; }
        h1 { font-size: 1.25rem; font-weight: 600; letter-spacing: -.01em; margin: 0 0 .25rem; }
        p.lead { color: var(--slate-500); font-size: .9rem; margin: 0 0 1.5rem; }
        label { display: block; font-size: .8rem; font-weight: 500; color: var(--slate-600); margin-bottom: .4rem; }
        input {
            width: 100%; padding: .8rem; font-size: 1.25rem; letter-spacing: .25em; text-align: center;
            text-transform: uppercase; background: var(--white); border: 1px solid var(--slate-200);
            border-radius: 10px; color: var(--slate-900); outline: none;
        }
        input:focus { border-color: var(--blue-600); box-shadow: 0 0 0 3px rgba(37, 99, 235, .15); }
        button {
            width: 100%; margin-top: 1rem; padding: .8rem; font-size: .95rem; font-weight: 600;
            border: 0; border-radius: 10px; background: var(--blue-600); color: #fff; cursor: pointer;
        }
        button:hover { background: var(--blue-700); }
        .status { padding: .7rem .85rem; border-radius: 10px; margin-bottom: 1.25rem; font-size: .85rem; }
        .ok { background: var(--green-bg); color: var(--green-fg); }
        .err { background: var(--red-bg); color: var(--red-fg); }
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

        <h1>Connect your CLI</h1>
        <p class="lead">Enter the code shown in your terminal to continue.</p>

        @if (session('status') === 'authorization-approved')
            <div class="status ok">Device approved. You can return to your terminal.</div>
        @elseif (session('status') === 'authorization-denied')
            <div class="status err">Device login was denied.</div>
        @endif

        @if ($errors->any())
            <div class="status err">{{ $errors->first() }}</div>
        @endif

        <form method="GET" action="{{ route('passport.device') }}">
            <label for="user_code">Device code</label>
            <input id="user_code" name="user_code" value="{{ old('user_code') }}"
                   placeholder="XXXX-XXXX" autofocus autocomplete="off" required>
            <button type="submit">Continue</button>
        </form>
    </div>
</body>
</html>
