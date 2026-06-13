import { Head } from '@inertiajs/react';
import { useState } from 'react';
import PendingInvitationsModal from '@/components/pending-invitations-modal';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { PlaceholderPattern } from '@/components/ui/placeholder-pattern';
import { dashboard } from '@/routes';
import type { DashboardInvitation, DepartmentTask } from '@/types';

type Props = {
    pendingInvitations?: DashboardInvitation[];
    departmentTasks?: DepartmentTask[];
    departmentName?: string | null;
};

export default function Dashboard({
    pendingInvitations = [],
    departmentTasks = [],
    departmentName = null,
}: Props) {
    const [showInvitations, setShowInvitations] = useState(
        pendingInvitations.length > 0,
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
                    <div className="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                        <PlaceholderPattern className="absolute inset-0 size-full stroke-neutral-900/20 dark:stroke-neutral-100/20" />
                    </div>
                    <div className="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                        <PlaceholderPattern className="absolute inset-0 size-full stroke-neutral-900/20 dark:stroke-neutral-100/20" />
                    </div>
                    <div className="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                        <PlaceholderPattern className="absolute inset-0 size-full stroke-neutral-900/20 dark:stroke-neutral-100/20" />
                    </div>
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
                                        className="flex items-center justify-between gap-4 py-3"
                                    >
                                        <span className="truncate font-medium">
                                            {task.name}
                                        </span>
                                        <span className="flex shrink-0 items-center gap-3">
                                            <span className="text-sm text-muted-foreground">
                                                {task.owner.name}
                                            </span>
                                            <Badge variant="secondary">
                                                {task.status}
                                            </Badge>
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
