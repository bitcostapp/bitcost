import { Head, Link } from '@inertiajs/react';
import { Github } from 'lucide-react';
import { useMemo, useState } from 'react';
import type { ReactNode } from 'react';
import PendingInvitationsModal from '@/components/pending-invitations-modal';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { formatCost, formatTokens } from '@/lib/format';
import { dashboard } from '@/routes';
import { show as taskShow } from '@/routes/tasks';
import type { DashboardInvitation, DepartmentTask } from '@/types';

function JiraIcon({ className }: { className?: string }) {
    return (
        <svg
            className={className}
            viewBox="0 0 24 24"
            fill="currentColor"
            aria-hidden="true"
        >
            <path d="M11.571 11.513H0a5.218 5.218 0 0 0 5.232 5.215h2.13v2.057A5.215 5.215 0 0 0 12.575 24V12.518a1.005 1.005 0 0 0-1.005-1.005zm5.723-5.756H5.736a5.215 5.215 0 0 0 5.215 5.214h2.129v2.058a5.218 5.218 0 0 0 5.215 5.214V6.758a1.001 1.001 0 0 0-1-1.001zM23.013 0H11.455a5.215 5.215 0 0 0 5.215 5.215h2.129v2.057A5.215 5.215 0 0 0 24 12.483V1.005A1.001 1.001 0 0 0 23.013 0z" />
        </svg>
    );
}

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

function ConnectCard({
    provider,
    icon,
}: {
    provider: string;
    icon: ReactNode;
}) {
    return (
        <Card className="flex aspect-video flex-col justify-between">
            <CardHeader className="flex flex-row items-center gap-3 space-y-0">
                <span className="flex size-10 shrink-0 items-center justify-center rounded-lg bg-muted text-foreground">
                    {icon}
                </span>
                <CardTitle className="text-base">{provider}</CardTitle>
            </CardHeader>
            <CardContent>
                <Button variant="outline" className="w-full" disabled>
                    Connect with {provider}
                </Button>
            </CardContent>
        </Card>
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

type Props = {
    pendingInvitations?: DashboardInvitation[];
    departmentTasks?: DepartmentTask[];
    departmentName?: string | null;
    currentTeam?: { slug: string } | null;
};

export default function Dashboard({
    pendingInvitations = [],
    departmentTasks = [],
    departmentName = null,
    currentTeam = null,
}: Props) {
    const [showInvitations, setShowInvitations] = useState(
        pendingInvitations.length > 0,
    );

    const stats = useMemo(
        () => aggregateTaskStats(departmentTasks),
        [departmentTasks],
    );

    return (
        <>
            <Head title="Dashboard" />
            <PendingInvitationsModal
                invitations={pendingInvitations}
                open={pendingInvitations.length > 0 && showInvitations}
                onOpenChange={setShowInvitations}
            />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div className="grid auto-rows-min gap-4 md:grid-cols-3">
                    <ConnectCard
                        provider="GitHub"
                        icon={<Github className="size-5" />}
                    />
                    <ConnectCard
                        provider="Jira"
                        icon={<JiraIcon className="size-5" />}
                    />
                    <Card className="flex aspect-video flex-col">
                        <CardHeader>
                            <CardTitle className="text-base">
                                Statistics
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="flex flex-1 flex-col justify-center gap-2">
                            <StatRow
                                label="Open tasks"
                                value={String(stats.openTasks)}
                            />
                            <StatRow
                                label="Total cost"
                                value={formatCost(
                                    stats.costTotal,
                                    stats.currency,
                                )}
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
                </div>
                <Card className="min-h-[100vh] flex-1 md:min-h-min">
                    <CardHeader>
                        <CardTitle>
                            {departmentName
                                ? `${departmentName} — open tasks`
                                : 'Department open tasks'}
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        {!departmentName ? (
                            <p className="text-sm text-muted-foreground">
                                You are not part of a department yet.
                            </p>
                        ) : departmentTasks.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                No open tasks 🎉
                            </p>
                        ) : (
                            <ul className="divide-y divide-border">
                                {departmentTasks.map((task) => (
                                    <li
                                        key={task.id}
                                        className="flex items-start justify-between gap-4 py-3"
                                    >
                                        <span className="min-w-0">
                                            {currentTeam ? (
                                                <Link
                                                    href={taskShow([
                                                        currentTeam.slug,
                                                        task.id,
                                                    ])}
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
                                            <Badge variant="secondary">
                                                {task.status}
                                            </Badge>
                                            <span>
                                                {formatCost(
                                                    task.costTotal,
                                                    task.currency,
                                                )}
                                            </span>
                                            <span>
                                                {formatTokens(
                                                    task.tokensInput +
                                                        task.tokensOutput,
                                                )}{' '}
                                                tok
                                            </span>
                                            <span>
                                                {task.usageCount}{' '}
                                                {task.usageCount === 1
                                                    ? 'turn'
                                                    : 'turns'}
                                            </span>
                                        </span>
                                    </li>
                                ))}
                            </ul>
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
