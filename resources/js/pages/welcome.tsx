import { Head, Link, usePage } from '@inertiajs/react';
import {
    ArrowRight,
    BarChart3,
    Github,
    Layers,
    ListChecks,
    Lock,
    Plug,
    ShieldCheck,
    Terminal,
} from 'lucide-react';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { dashboard, login } from '@/routes';

const DEMO_MAILTO = 'mailto:hello@bitcost.dev?subject=Bitcost%20demo%20request';

export default function Welcome() {
    const { auth, currentTeam } = usePage().props;
    const dashboardUrl = currentTeam ? dashboard(currentTeam.slug) : '/';

    return (
        <>
            <Head title="Bitcost — Know what your AI actually costs" />

            <div className="min-h-screen bg-white text-slate-900 antialiased">
                {/* Nav */}
                <header className="sticky top-0 z-50 border-b border-slate-200/80 bg-white/80 backdrop-blur">
                    <div className="mx-auto flex h-16 max-w-6xl items-center justify-between px-6">
                        <Link href="/" className="flex items-center gap-2">
                            <span className="flex h-7 w-7 items-center justify-center rounded-md bg-blue-600 text-white">
                                <BarChart3 className="h-4 w-4" />
                            </span>
                            <span className="text-lg font-semibold tracking-tight">
                                Bitcost
                            </span>
                        </Link>

                        <nav className="hidden items-center gap-8 text-sm font-medium text-slate-600 md:flex">
                            <a href="#how" className="hover:text-slate-900">
                                How it works
                            </a>
                            <a href="#install" className="hover:text-slate-900">
                                Install CLI
                            </a>
                            <a href="#security" className="hover:text-slate-900">
                                Security
                            </a>
                        </nav>

                        <div className="flex items-center gap-2">
                            {auth.user ? (
                                <Button asChild>
                                    <Link href={dashboardUrl}>Dashboard</Link>
                                </Button>
                            ) : (
                                <>
                                    <Button variant="ghost" asChild>
                                        <Link href={login()}>Sign in</Link>
                                    </Button>
                                    <Button
                                        asChild
                                        className="bg-blue-600 hover:bg-blue-700"
                                    >
                                        <a href={DEMO_MAILTO}>Book a demo</a>
                                    </Button>
                                </>
                            )}
                        </div>
                    </div>
                </header>

                {/* Hero */}
                <section className="relative overflow-hidden">
                    <div className="pointer-events-none absolute inset-0 bg-[radial-gradient(60%_50%_at_50%_0%,theme(colors.blue.50),transparent)]" />
                    <div className="relative mx-auto grid max-w-6xl gap-12 px-6 py-20 lg:grid-cols-[1.05fr_1fr] lg:items-center lg:py-28">
                        <div>
                            <Badge
                                variant="secondary"
                                className="mb-5 rounded-full border border-blue-100 bg-blue-50 px-3 py-1 text-blue-700"
                            >
                                AI cost attribution for enterprise work
                            </Badge>
                            <h1 className="text-4xl font-semibold leading-[1.1] tracking-tight text-slate-900 sm:text-5xl">
                                Know what your AI actually costs —{' '}
                                <span className="text-blue-600">
                                    per task, per epic.
                                </span>
                            </h1>
                            <p className="mt-5 max-w-xl text-lg leading-relaxed text-slate-600">
                                Developers work in the Bitcost CLI and pick the
                                task they're on. Bitcost transparently proxies
                                their AI traffic, meters the tokens, and turns
                                them into dollars — so spend rolls up to every
                                task and epic.
                            </p>

                            <div className="mt-8 flex flex-wrap items-center gap-3">
                                <Button
                                    asChild
                                    size="lg"
                                    className="bg-blue-600 hover:bg-blue-700"
                                >
                                    <a href={DEMO_MAILTO}>
                                        Book a demo
                                        <ArrowRight className="h-4 w-4" />
                                    </a>
                                </Button>
                                <Button asChild size="lg" variant="outline">
                                    <Link
                                        href={auth.user ? dashboardUrl : login()}
                                    >
                                        Sign in
                                    </Link>
                                </Button>
                            </div>

                            <p className="mt-6 flex items-center gap-2 text-sm text-slate-500">
                                <Lock className="h-3.5 w-3.5" />
                                We never read your prompts to guess the task.
                            </p>
                        </div>

                        <HeroDashboard />
                    </div>
                </section>

                {/* Problem */}
                <section className="border-y border-slate-200 bg-slate-50">
                    <div className="mx-auto max-w-6xl px-6 py-16">
                        <h2 className="max-w-2xl text-2xl font-semibold tracking-tight sm:text-3xl">
                            Your AI bill is one number. Your work isn't.
                        </h2>
                        <p className="mt-3 max-w-2xl text-slate-600">
                            Enterprises hand developers AI accounts and assign
                            them work — then get a single lump-sum invoice with
                            no idea where it went.
                        </p>

                        <div className="mt-10 grid gap-6 sm:grid-cols-3">
                            {PROBLEMS.map((problem) => (
                                <div
                                    key={problem.title}
                                    className="rounded-xl border border-slate-200 bg-white p-6"
                                >
                                    <h3 className="font-semibold text-slate-900">
                                        {problem.title}
                                    </h3>
                                    <p className="mt-2 text-sm leading-relaxed text-slate-600">
                                        {problem.body}
                                    </p>
                                </div>
                            ))}
                        </div>
                    </div>
                </section>

                {/* How it works */}
                <section id="how" className="mx-auto max-w-6xl px-6 py-20">
                    <div className="mx-auto max-w-2xl text-center">
                        <Badge
                            variant="secondary"
                            className="mb-4 rounded-full bg-slate-100 text-slate-600"
                        >
                            How it works
                        </Badge>
                        <h2 className="text-3xl font-semibold tracking-tight">
                            Attribution that's declared, not guessed
                        </h2>
                        <p className="mt-3 text-slate-600">
                            Four steps from connected repo to cost you can defend
                            in a budget review.
                        </p>
                    </div>

                    <div className="mt-14 grid gap-6 md:grid-cols-2 lg:grid-cols-4">
                        {STEPS.map((step, index) => (
                            <div
                                key={step.title}
                                className="relative rounded-xl border border-slate-200 bg-white p-6"
                            >
                                <span className="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-50 text-blue-600">
                                    <step.icon className="h-5 w-5" />
                                </span>
                                <div className="mt-4 text-xs font-medium text-slate-400">
                                    Step {index + 1}
                                </div>
                                <h3 className="mt-1 font-semibold text-slate-900">
                                    {step.title}
                                </h3>
                                <p className="mt-2 text-sm leading-relaxed text-slate-600">
                                    {step.body}
                                </p>
                            </div>
                        ))}
                    </div>
                </section>

                {/* Install the CLI */}
                <section
                    id="install"
                    className="border-t border-slate-200 bg-slate-50"
                >
                    <div className="mx-auto grid max-w-6xl gap-12 px-6 py-20 lg:grid-cols-2 lg:items-center">
                        <div>
                            <Badge
                                variant="secondary"
                                className="mb-4 rounded-full bg-slate-100 text-slate-600"
                            >
                                <Terminal className="mr-1 h-3.5 w-3.5" />
                                Bitcost CLI
                            </Badge>
                            <h2 className="text-3xl font-semibold tracking-tight">
                                Install the CLI in under a minute
                            </h2>
                            <p className="mt-3 max-w-md text-slate-600">
                                The Bitcost CLI is a fork of opencode. Clone it,
                                install with Bun, and run — then sign in and pick
                                the task you're working on.
                            </p>
                            <ul className="mt-6 space-y-3 text-sm text-slate-600">
                                {CLI_STEPS.map((item) => (
                                    <li
                                        key={item}
                                        className="flex items-start gap-3"
                                    >
                                        <span className="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-blue-100 text-[11px] font-semibold text-blue-700">
                                            ✓
                                        </span>
                                        {item}
                                    </li>
                                ))}
                            </ul>
                            <p className="mt-6 text-sm text-slate-500">
                                Requires{' '}
                                <a
                                    href="https://bun.sh"
                                    className="font-medium text-blue-600 hover:underline"
                                >
                                    Bun
                                </a>{' '}
                                1.3+.
                            </p>
                        </div>

                        <CliInstall />
                    </div>
                </section>

                {/* Security */}
                <section
                    id="security"
                    className="border-y border-slate-200 bg-slate-900 text-slate-100"
                >
                    <div className="mx-auto grid max-w-6xl gap-12 px-6 py-20 lg:grid-cols-2 lg:items-center">
                        <div>
                            <Badge className="mb-4 rounded-full border-0 bg-white/10 text-slate-200">
                                Built for security reviews
                            </Badge>
                            <h2 className="text-3xl font-semibold tracking-tight">
                                We meter tokens, not your secrets
                            </h2>
                            <p className="mt-4 text-slate-300">
                                The task is chosen by the developer in the
                                Bitcost CLI and stamped onto each request — so we
                                never inspect prompt content to classify your
                                work. The proxy captures usage asynchronously and
                                computes cost from the model and token counts.
                            </p>
                            <div className="mt-8 flex flex-wrap items-center gap-3 text-sm text-slate-300">
                                <Button
                                    asChild
                                    size="lg"
                                    className="bg-white text-slate-900 hover:bg-slate-200"
                                >
                                    <a href={DEMO_MAILTO}>
                                        Book a demo
                                        <ArrowRight className="h-4 w-4" />
                                    </a>
                                </Button>
                            </div>
                        </div>

                        <div className="grid gap-4">
                            {SECURITY.map((item) => (
                                <div
                                    key={item.title}
                                    className="flex gap-4 rounded-xl border border-white/10 bg-white/5 p-5"
                                >
                                    <ShieldCheck className="h-5 w-5 shrink-0 text-emerald-400" />
                                    <div>
                                        <h3 className="font-semibold text-white">
                                            {item.title}
                                        </h3>
                                        <p className="mt-1 text-sm text-slate-300">
                                            {item.body}
                                        </p>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                </section>

                {/* Outcome / stats */}
                <section className="mx-auto max-w-6xl px-6 py-20">
                    <div className="grid gap-10 lg:grid-cols-[1fr_1.1fr] lg:items-center">
                        <div>
                            <h2 className="text-3xl font-semibold tracking-tight">
                                From lump sum to line item
                            </h2>
                            <p className="mt-3 max-w-md text-slate-600">
                                Find your most expensive work, set budgets that
                                mean something, and answer "what did this cost?"
                                without a spreadsheet.
                            </p>
                            <div className="mt-6 flex flex-wrap gap-2">
                                <Badge
                                    variant="secondary"
                                    className="rounded-full bg-slate-100 text-slate-600"
                                >
                                    <Github className="mr-1 h-3.5 w-3.5" />
                                    GitHub
                                </Badge>
                                <Badge
                                    variant="outline"
                                    className="rounded-full border-dashed text-slate-500"
                                >
                                    Jira — coming soon
                                </Badge>
                                <Badge
                                    variant="secondary"
                                    className="rounded-full bg-slate-100 text-slate-600"
                                >
                                    Works with your AI providers
                                </Badge>
                            </div>
                        </div>

                        <div className="grid grid-cols-3 gap-4">
                            {STATS.map((stat) => (
                                <div
                                    key={stat.label}
                                    className="rounded-xl border border-slate-200 bg-white p-6 text-center"
                                >
                                    <div className="font-mono text-2xl font-semibold text-slate-900">
                                        {stat.value}
                                    </div>
                                    <div className="mt-1 text-xs text-slate-500">
                                        {stat.label}
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                </section>

                {/* Final CTA */}
                <section className="border-t border-slate-200 bg-blue-600">
                    <div className="mx-auto flex max-w-6xl flex-col items-center gap-6 px-6 py-16 text-center">
                        <h2 className="max-w-2xl text-3xl font-semibold tracking-tight text-white">
                            See every dollar your AI spends on real work.
                        </h2>
                        <Button
                            asChild
                            size="lg"
                            className="bg-white text-blue-700 hover:bg-blue-50"
                        >
                            <a href={DEMO_MAILTO}>
                                Book a demo
                                <ArrowRight className="h-4 w-4" />
                            </a>
                        </Button>
                    </div>
                </section>

                {/* Footer */}
                <footer className="border-t border-slate-200 bg-white">
                    <div className="mx-auto flex max-w-6xl flex-col items-center justify-between gap-4 px-6 py-8 text-sm text-slate-500 sm:flex-row">
                        <div className="flex items-center gap-2">
                            <span className="flex h-5 w-5 items-center justify-center rounded bg-blue-600 text-white">
                                <BarChart3 className="h-3 w-3" />
                            </span>
                            <span className="font-medium text-slate-700">
                                Bitcost
                            </span>
                        </div>
                        <p>AI cost attribution for enterprise work.</p>
                    </div>
                </footer>
            </div>
        </>
    );
}

const PROBLEMS = [
    {
        title: 'One invoice, zero answers',
        body: 'A single AI bill lands each month with no breakdown by team, project, or task.',
    },
    {
        title: 'No per-task visibility',
        body: 'You can’t say what an epic cost in AI — so you can’t budget, forecast, or justify it.',
    },
    {
        title: 'No accountability',
        body: 'Spend grows without an owner. Nobody can tie cost back to the work that drove it.',
    },
];

const STEPS = [
    {
        icon: Plug,
        title: 'Connect GitHub',
        body: 'Link your repos so Bitcost knows the tasks and epics your team is working on.',
    },
    {
        icon: ListChecks,
        title: 'Pick a task in the CLI',
        body: 'Developers sign in to the Bitcost CLI, see their tasks, and select the one they’re on.',
    },
    {
        icon: Terminal,
        title: 'Work through the proxy',
        body: 'The CLI stamps each AI request with the task and routes it transparently through Bitcost.',
    },
    {
        icon: Layers,
        title: 'Cost rolls up',
        body: 'Tokens become dollars and attribute up from task to epic, ready for any budget review.',
    },
];

const SECURITY = [
    {
        title: 'Declared, not inferred',
        body: 'The developer chooses the task. We never read prompts to guess what you’re working on.',
    },
    {
        title: 'Transparent proxy',
        body: 'Requests pass straight through to your AI provider — Bitcost stays out of the way.',
    },
    {
        title: 'Async metering',
        body: 'Usage is captured after the fact and priced from model and token counts.',
    },
];

const STATS = [
    { value: '$0.00', label: 'Untracked spend' },
    { value: '1:1', label: 'Cost to task' },
    { value: 'Epic', label: 'Roll-up level' },
];

const CLI_STEPS = [
    'Run the install script, or build from source with Bun.',
    'Launch the CLI and authenticate to Bitcost as yourself.',
    'Pick your task — every AI request is metered against it.',
];

const CLI_INSTALL_ONE_LINER =
    'curl -fsSL https://raw.githubusercontent.com/bitcostapp/bitcost-cli/dev/install | bash';

const CLI_SOURCE_COMMANDS = [
    'git clone https://github.com/bitcostapp/bitcost-cli.git',
    'cd bitcost-cli',
    'bun install',
    'bun dev',
];

function CliInstall() {
    return (
        <div className="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900 shadow-xl shadow-slate-300/50">
            <div className="flex items-center gap-2 border-b border-slate-800 px-4 py-3">
                <span className="h-3 w-3 rounded-full bg-red-400/80" />
                <span className="h-3 w-3 rounded-full bg-yellow-400/80" />
                <span className="h-3 w-3 rounded-full bg-green-400/80" />
                <span className="ml-3 text-xs font-medium text-slate-400">
                    bitcost-cli — zsh
                </span>
            </div>
            <div className="px-5 py-5 font-mono text-sm leading-relaxed text-slate-100">
                <div className="text-[11px] font-medium tracking-wide text-slate-500 uppercase">
                    Quick install
                </div>
                <div className="mt-2 flex gap-2">
                    <span className="select-none text-emerald-400">$</span>
                    <span className="break-all">{CLI_INSTALL_ONE_LINER}</span>
                </div>
                <div className="mt-3 flex gap-2 text-slate-400">
                    <span className="select-none text-blue-400">›</span>
                    <span>Signed in. Select a task to start working.</span>
                </div>

                <div className="mt-6 border-t border-slate-800 pt-4 text-[11px] font-medium tracking-wide text-slate-500 uppercase">
                    Or build from source
                </div>
                {CLI_SOURCE_COMMANDS.map((command) => (
                    <div key={command} className="mt-2 flex gap-2">
                        <span className="select-none text-emerald-400">$</span>
                        <span>{command}</span>
                    </div>
                ))}
            </div>
        </div>
    );
}

function HeroDashboard() {
    const epics = [
        { name: 'Checkout v2', cost: '$4,210', pct: 92 },
        { name: 'Search rebuild', cost: '$2,680', pct: 58 },
        { name: 'Onboarding flow', cost: '$1,340', pct: 30 },
        { name: 'Billing migration', cost: '$760', pct: 16 },
    ];

    return (
        <div className="relative">
            <div className="absolute -inset-4 rounded-3xl bg-blue-100/40 blur-2xl" />
            <div className="relative rounded-2xl border border-slate-200 bg-white shadow-xl shadow-slate-200/60">
                <div className="flex items-center justify-between border-b border-slate-100 px-5 py-3">
                    <div className="flex items-center gap-2 text-sm font-medium text-slate-700">
                        <BarChart3 className="h-4 w-4 text-blue-600" />
                        Cost by epic
                    </div>
                    <span className="text-xs text-slate-400">Last 30 days</span>
                </div>

                <div className="space-y-4 px-5 py-5">
                    {epics.map((epic) => (
                        <div key={epic.name}>
                            <div className="mb-1.5 flex items-center justify-between text-sm">
                                <span className="text-slate-700">
                                    {epic.name}
                                </span>
                                <span className="font-mono font-medium text-slate-900">
                                    {epic.cost}
                                </span>
                            </div>
                            <div className="h-2 overflow-hidden rounded-full bg-slate-100">
                                <div
                                    className="h-full rounded-full bg-blue-600"
                                    style={{ width: `${epic.pct}%` }}
                                />
                            </div>
                        </div>
                    ))}
                </div>

                <div className="flex items-center justify-between border-t border-slate-100 px-5 py-3 text-sm">
                    <span className="text-slate-500">Total attributed</span>
                    <span className="font-mono text-base font-semibold text-slate-900">
                        $8,990
                    </span>
                </div>
            </div>
        </div>
    );
}
