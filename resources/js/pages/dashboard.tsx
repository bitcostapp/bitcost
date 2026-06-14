import { Head, Link } from '@inertiajs/react';
import { Shield } from 'lucide-react';
import { useMemo, useState } from 'react';
import PendingInvitationsModal from '@/components/pending-invitations-modal';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { formatCost, formatTokens } from '@/lib/format';
import { dashboard } from '@/routes';
import { show as taskShow } from '@/routes/tasks';
import type { DashboardInvitation, DepartmentTask } from '@/types';

type TaskStats = {
    openTasks: number;
    costTotal: number;
    currency: string | null;
    tokensTotal: number;
    turnsTotal: number;
};

function aggregateTaskStats(tasks: DepartmentTask[]): TaskStats {
    return tasks.reduce<TaskStats>(
        (stats, task) => ({
            openTasks: stats.openTasks + 1,
            costTotal: stats.costTotal + task.costTotal,
            currency: stats.currency ?? task.currency,
            tokensTotal:
                stats.tokensTotal + task.tokensInput + task.tokensOutput,
            turnsTotal: stats.turnsTotal + task.usageCount,
        }),
        {
            openTasks: 0,
            costTotal: 0,
            currency: null,
            tokensTotal: 0,
            turnsTotal: 0,
        },
    );
}

function StatRow({ label, value }: { label: string; value: string }) {
    return (
        <div className="flex items-baseline justify-between gap-2">
            <span className="text-sm text-muted-foreground">{label}</span>
            <span className="font-medium tabular-nums">{value}</span>
        </div>
    );
}

function TaskList({
    tasks,
    currentTeam,
    emptyMessage,
}: {
    tasks: DepartmentTask[];
    currentTeam: { slug: string } | null;
    emptyMessage: string;
}) {
    if (tasks.length === 0) {
        return <p className="text-sm text-muted-foreground">{emptyMessage}</p>;
    }

    return (
        <ul className="divide-y divide-border">
            {tasks.map((task) => (
                <li
                    key={task.id}
                    className="flex items-start justify-between gap-4 py-3"
                >
                    <span className="min-w-0">
                        {currentTeam ? (
                            <Link
                                href={taskShow([currentTeam.slug, task.id])}
                                className="block truncate font-medium hover:underline"
                            >
                                {task.name}
                            </Link>
                        ) : (
                            <span className="block truncate font-medium">
                                {task.name}
                            </span>
                        )}
                        {task.planTitle ? (
                            <span className="block truncate text-xs text-muted-foreground">
                                plan: {task.planTitle}
                            </span>
                        ) : null}
                    </span>
                    <span className="flex shrink-0 items-center gap-3 text-sm text-muted-foreground">
                        <span>{task.owner.name}</span>
                        <Badge variant="secondary">{task.status}</Badge>
                        <span>{formatCost(task.costTotal, task.currency)}</span>
                        <span>
                            {formatTokens(task.tokensInput + task.tokensOutput)}{' '}
                            tok
                        </span>
                        <span>
                            {task.usageCount}{' '}
                            {task.usageCount === 1 ? 'turn' : 'turns'}
                        </span>
                    </span>
                </li>
            ))}
        </ul>
    );
}

type TaskTab = 'team' | 'mine';

type Props = {
    pendingInvitations?: DashboardInvitation[];
    departmentTasks?: DepartmentTask[];
    myTasks?: DepartmentTask[];
    departmentName?: string | null;
    currentTeam?: { slug: string } | null;
};

export default function Dashboard({
    pendingInvitations = [],
    departmentTasks = [],
    myTasks = [],
    departmentName = null,
    currentTeam = null,
}: Props) {
    const [showInvitations, setShowInvitations] = useState(
        pendingInvitations.length > 0,
    );
    const [activeTab, setActiveTab] = useState<TaskTab>('team');

    const activeTasks = activeTab === 'team' ? departmentTasks : myTasks;

    const stats = useMemo(() => aggregateTaskStats(activeTasks), [activeTasks]);

    const tabs: { key: TaskTab; label: string; count: number }[] = [
        {
            key: 'team',
            label: departmentName ? `${departmentName} tasks` : 'Team tasks',
            count: departmentTasks.length,
        },
        { key: 'mine', label: 'My tasks', count: myTasks.length },
    ];

    return (
        <>
            <Head title="Dashboard" />
            <PendingInvitationsModal
                invitations={pendingInvitations}
                open={pendingInvitations.length > 0 && showInvitations}
                onOpenChange={setShowInvitations}
            />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div className="flex items-center justify-between gap-4">
                    <h1 className="text-xl font-semibold">
                        {departmentName
                            ? `${departmentName} dashboard`
                            : 'Dashboard'}
                    </h1>
                    <Button asChild variant="outline">
                        <a href="/admin">
                            <Shield className="size-4" />
                            Admin panel
                        </a>
                    </Button>
                </div>
                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">Statistics</CardTitle>
                    </CardHeader>
                    <CardContent className="grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
                        <StatRow
                            label="Open tasks"
                            value={String(stats.openTasks)}
                        />
                        <StatRow
                            label="Total cost"
                            value={formatCost(stats.costTotal, stats.currency)}
                        />
                        <StatRow
                            label="Total tokens"
                            value={formatTokens(stats.tokensTotal)}
                        />
                        <StatRow
                            label="Total turns"
                            value={String(stats.turnsTotal)}
                        />
                    </CardContent>
                </Card>
                <Card className="min-h-[100vh] flex-1 md:min-h-min">
                    <CardHeader>
                        <div className="flex flex-wrap items-center gap-1 rounded-lg bg-muted p-1">
                            {tabs.map((tab) => (
                                <button
                                    key={tab.key}
                                    type="button"
                                    onClick={() => setActiveTab(tab.key)}
                                    aria-pressed={activeTab === tab.key}
                                    className={`flex items-center gap-2 rounded-md px-3 py-1.5 text-sm font-medium transition-colors ${
                                        activeTab === tab.key
                                            ? 'bg-background text-foreground shadow-sm'
                                            : 'text-muted-foreground hover:text-foreground'
                                    }`}
                                >
                                    {tab.label}
                                    <Badge variant="secondary">
                                        {tab.count}
                                    </Badge>
                                </button>
                            ))}
                        </div>
                    </CardHeader>
                    <CardContent>
                        {!departmentName ? (
                            <p className="text-sm text-muted-foreground">
                                You are not part of a department yet.
                            </p>
                        ) : (
                            <TaskList
                                tasks={activeTasks}
                                currentTeam={currentTeam}
                                emptyMessage={
                                    activeTab === 'mine'
                                        ? 'You have no open tasks 🎉'
                                        : 'No open tasks 🎉'
                                }
                            />
                        )}
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

Dashboard.layout = (props: { currentTeam?: { slug: string } | null }) => ({
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: props.currentTeam ? dashboard(props.currentTeam.slug) : '/',
        },
    ],
});
