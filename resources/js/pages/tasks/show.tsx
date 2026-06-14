import { Head, useForm } from '@inertiajs/react';
import { ChevronDown, ExternalLink, Github } from 'lucide-react';
import { useState } from 'react';
import ReactMarkdown from 'react-markdown';
import {
    Bar,
    BarChart,
    CartesianGrid,
    Cell,
    ResponsiveContainer,
    Tooltip,
    XAxis,
    YAxis,
} from 'recharts';
import remarkGfm from 'remark-gfm';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { formatCost, formatTokens } from '@/lib/format';
import { dashboard } from '@/routes';
import { show as taskShow, update as taskUpdate } from '@/routes/tasks';
import type {
    TaskDetail,
    TaskPlanSummary,
    TaskSession,
    TaskTotals,
} from '@/types';

type Metric = 'cost' | 'tokens';

const BAR_COLORS = [
    'var(--color-chart-1, #6366f1)',
    'var(--color-chart-2, #8b5cf6)',
    'var(--color-chart-3, #ec4899)',
    'var(--color-chart-4, #14b8a6)',
    'var(--color-chart-5, #f59e0b)',
];

function shortSession(session: string): string {
    if (session === '—') {
        return 'unattributed';
    }

    return session.replace(/^ses_/, '').slice(0, 8);
}

function formatRange(firstAt: string | null, lastAt: string | null): string {
    if (!firstAt) {
        return '—';
    }

    const first = new Date(firstAt).toLocaleString();

    if (!lastAt || lastAt === firstAt) {
        return first;
    }

    return `${first} → ${new Date(lastAt).toLocaleString()}`;
}

type ChartDatum = TaskSession & { label: string };

function UsageChartTooltip({
    active,
    payload,
    currency,
}: {
    active?: boolean;
    payload?: { payload: ChartDatum }[];
    currency: string | null;
}) {
    if (!active || !payload?.length) {
        return null;
    }

    const session = payload[0].payload;

    return (
        <div className="rounded-lg border bg-popover px-3 py-2 text-sm shadow-md">
            <p className="font-medium">{session.label}</p>
            <p className="text-muted-foreground">
                {formatCost(session.costTotal, currency)} ·{' '}
                {formatTokens(session.tokensTotal)} tok
            </p>
            <p className="text-muted-foreground">
                {session.turns} {session.turns === 1 ? 'turn' : 'turns'} ·{' '}
                {session.model}
            </p>
        </div>
    );
}

function StatTile({ label, value }: { label: string; value: string }) {
    return (
        <div className="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
            <p className="text-sm text-muted-foreground">{label}</p>
            <p className="mt-1 text-2xl font-semibold tabular-nums">{value}</p>
        </div>
    );
}

function TaskContentCard({
    task,
    teamSlug,
}: {
    task: TaskDetail;
    teamSlug: string | null;
}) {
    const [editing, setEditing] = useState(false);
    const form = useForm({ content: task.content ?? '' });

    function save() {
        if (!teamSlug) {
            return;
        }

        form.patch(taskUpdate([teamSlug, task.id]).url, {
            preserveScroll: true,
            onSuccess: () => setEditing(false),
        });
    }

    return (
        <Card>
            <CardHeader className="flex flex-row items-center justify-between space-y-0">
                <CardTitle>Description</CardTitle>
                {!editing && teamSlug ? (
                    <Button
                        size="sm"
                        variant="ghost"
                        onClick={() => {
                            form.setData('content', task.content ?? '');
                            setEditing(true);
                        }}
                    >
                        Edit
                    </Button>
                ) : null}
            </CardHeader>
            <CardContent>
                {editing ? (
                    <div className="flex flex-col gap-3">
                        <textarea
                            value={form.data.content}
                            onChange={(event) =>
                                form.setData('content', event.target.value)
                            }
                            rows={8}
                            placeholder="Describe this task (markdown supported)…"
                            className="w-full resize-y rounded-md border bg-transparent p-3 font-mono text-sm outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50"
                        />
                        <div className="flex justify-end gap-2">
                            <Button
                                size="sm"
                                variant="ghost"
                                onClick={() => setEditing(false)}
                                disabled={form.processing}
                            >
                                Cancel
                            </Button>
                            <Button
                                size="sm"
                                onClick={save}
                                disabled={form.processing}
                            >
                                Save
                            </Button>
                        </div>
                    </div>
                ) : task.content ? (
                    <article className="prose prose-sm max-w-none dark:prose-invert">
                        <ReactMarkdown remarkPlugins={[remarkGfm]}>
                            {task.content}
                        </ReactMarkdown>
                    </article>
                ) : (
                    <p className="text-sm text-muted-foreground">
                        No description yet.
                    </p>
                )}
            </CardContent>
        </Card>
    );
}

type Props = {
    task: TaskDetail;
    sessions: TaskSession[];
    totals: TaskTotals;
    currency: string | null;
    latestPlan: TaskPlanSummary | null;
    currentTeam?: { slug: string } | null;
};

export default function TaskShow({
    task,
    sessions,
    totals,
    currency,
    latestPlan,
    currentTeam = null,
}: Props) {
    const [metric, setMetric] = useState<Metric>('cost');
    const [planOpen, setPlanOpen] = useState(true);

    const chartData: ChartDatum[] = sessions.map((session) => ({
        ...session,
        label: shortSession(session.session),
    }));

    const dataKey = metric === 'cost' ? 'costTotal' : 'tokensTotal';

    return (
        <>
            <Head title={task.name} />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                {/* Header */}
                <div className="flex flex-wrap items-start justify-between gap-3">
                    <div className="min-w-0">
                        <div className="flex items-center gap-3">
                            <h1 className="truncate text-xl font-semibold">
                                {task.name}
                            </h1>
                            <Badge
                                variant={
                                    task.status === 'completed'
                                        ? 'secondary'
                                        : 'default'
                                }
                            >
                                {task.status}
                            </Badge>
                        </div>
                        <p className="mt-1 text-sm text-muted-foreground">
                            {task.owner.name} · {task.departmentName}
                            {task.createdAt
                                ? ` · ${new Date(task.createdAt).toLocaleDateString()}`
                                : ''}
                        </p>
                    </div>
                    {task.externalUrl ? (
                        <Button variant="outline" asChild>
                            <a
                                href={task.externalUrl}
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                {task.externalProvider === 'github' ? (
                                    <Github className="size-4" />
                                ) : (
                                    <ExternalLink className="size-4" />
                                )}
                                View on{' '}
                                {task.externalProvider ?? 'Work Provider'}
                            </a>
                        </Button>
                    ) : null}
                </div>

                {/* Totals */}
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <StatTile
                        label="Total cost"
                        value={formatCost(totals.costTotal, currency)}
                    />
                    <StatTile
                        label="Total tokens"
                        value={formatTokens(
                            totals.tokensInput + totals.tokensOutput,
                        )}
                    />
                    <StatTile label="Total turns" value={String(totals.turns)} />
                    <StatTile
                        label="Sessions"
                        value={String(totals.sessionCount)}
                    />
                </div>

                {/* Description */}
                <TaskContentCard
                    task={task}
                    teamSlug={currentTeam?.slug ?? null}
                />

                {/* Usage graph */}
                <Card>
                    <CardHeader className="flex flex-row items-center justify-between space-y-0">
                        <CardTitle>Usage by session</CardTitle>
                        <div className="flex gap-1 rounded-lg border p-0.5">
                            <Button
                                size="sm"
                                variant={metric === 'cost' ? 'default' : 'ghost'}
                                onClick={() => setMetric('cost')}
                            >
                                Cost
                            </Button>
                            <Button
                                size="sm"
                                variant={
                                    metric === 'tokens' ? 'default' : 'ghost'
                                }
                                onClick={() => setMetric('tokens')}
                            >
                                Tokens
                            </Button>
                        </div>
                    </CardHeader>
                    <CardContent>
                        {chartData.length === 0 ? (
                            <p className="py-12 text-center text-sm text-muted-foreground">
                                No usage reported yet.
                            </p>
                        ) : (
                            <ResponsiveContainer width="100%" height={280}>
                                <BarChart data={chartData}>
                                    <CartesianGrid
                                        strokeDasharray="3 3"
                                        className="stroke-border"
                                    />
                                    <XAxis
                                        dataKey="label"
                                        tick={{ fontSize: 12 }}
                                    />
                                    <YAxis
                                        tick={{ fontSize: 12 }}
                                        tickFormatter={(value: number) =>
                                            metric === 'cost'
                                                ? formatCost(value, currency)
                                                : formatTokens(value)
                                        }
                                        width={72}
                                    />
                                    <Tooltip
                                        cursor={{ fillOpacity: 0.1 }}
                                        content={
                                            <UsageChartTooltip
                                                currency={currency}
                                            />
                                        }
                                    />
                                    <Bar
                                        dataKey={dataKey}
                                        radius={[4, 4, 0, 0]}
                                    >
                                        {chartData.map((entry, index) => (
                                            <Cell
                                                key={entry.session}
                                                fill={
                                                    BAR_COLORS[
                                                        index % BAR_COLORS.length
                                                    ]
                                                }
                                            />
                                        ))}
                                    </Bar>
                                </BarChart>
                            </ResponsiveContainer>
                        )}
                    </CardContent>
                </Card>

                {/* Per-session table */}
                {chartData.length > 0 ? (
                    <Card>
                        <CardHeader>
                            <CardTitle>Sessions</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="border-b text-left text-muted-foreground">
                                            <th className="py-2 pr-4 font-medium">
                                                Session
                                            </th>
                                            <th className="py-2 pr-4 font-medium">
                                                Turns
                                            </th>
                                            <th className="py-2 pr-4 font-medium">
                                                Cost
                                            </th>
                                            <th className="py-2 pr-4 font-medium">
                                                In
                                            </th>
                                            <th className="py-2 pr-4 font-medium">
                                                Out
                                            </th>
                                            <th className="py-2 pr-4 font-medium">
                                                Total
                                            </th>
                                            <th className="py-2 pr-4 font-medium">
                                                Model
                                            </th>
                                            <th className="py-2 font-medium">
                                                When
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {sessions.map((session) => (
                                            <tr
                                                key={session.session}
                                                className="border-b last:border-0"
                                            >
                                                <td className="py-2 pr-4 font-medium">
                                                    {shortSession(
                                                        session.session,
                                                    )}
                                                </td>
                                                <td className="py-2 pr-4 tabular-nums">
                                                    {session.turns}
                                                </td>
                                                <td className="py-2 pr-4 tabular-nums">
                                                    {formatCost(
                                                        session.costTotal,
                                                        currency,
                                                    )}
                                                </td>
                                                <td className="py-2 pr-4 tabular-nums">
                                                    {formatTokens(
                                                        session.tokensInput,
                                                    )}
                                                </td>
                                                <td className="py-2 pr-4 tabular-nums">
                                                    {formatTokens(
                                                        session.tokensOutput,
                                                    )}
                                                </td>
                                                <td className="py-2 pr-4 tabular-nums">
                                                    {formatTokens(
                                                        session.tokensTotal,
                                                    )}
                                                </td>
                                                <td className="py-2 pr-4 text-muted-foreground">
                                                    {session.model}
                                                </td>
                                                <td className="py-2 text-muted-foreground">
                                                    {formatRange(
                                                        session.firstAt,
                                                        session.lastAt,
                                                    )}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </CardContent>
                    </Card>
                ) : null}

                {/* Latest plan */}
                {latestPlan ? (
                    <Card>
                        <CardHeader>
                            <button
                                type="button"
                                onClick={() => setPlanOpen((open) => !open)}
                                className="flex w-full items-center justify-between gap-2 text-left"
                            >
                                <CardTitle>
                                    Plan
                                    {latestPlan.title
                                        ? ` — ${latestPlan.title}`
                                        : ''}
                                </CardTitle>
                                <ChevronDown
                                    className={`size-4 shrink-0 transition-transform ${
                                        planOpen ? 'rotate-180' : ''
                                    }`}
                                />
                            </button>
                        </CardHeader>
                        {planOpen ? (
                            <CardContent>
                                <article className="prose prose-sm max-w-none dark:prose-invert">
                                    <ReactMarkdown remarkPlugins={[remarkGfm]}>
                                        {latestPlan.body}
                                    </ReactMarkdown>
                                </article>
                            </CardContent>
                        ) : null}
                    </Card>
                ) : null}
            </div>
        </>
    );
}

TaskShow.layout = (props: {
    currentTeam?: { slug: string } | null;
    task: TaskDetail;
}) => ({
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: props.currentTeam ? dashboard(props.currentTeam.slug) : '/',
        },
        {
            title: props.task.name,
            href: props.currentTeam
                ? taskShow([props.currentTeam.slug, props.task.id])
                : '/',
        },
    ],
});
