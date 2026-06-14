export type TeamRole = 'owner' | 'admin' | 'member';

export type Team = {
    id: number;
    name: string;
    slug: string;
    isPersonal: boolean;
    role?: TeamRole;
    roleLabel?: string;
    isCurrent?: boolean;
};

export type TeamMember = {
    id: number;
    name: string;
    email: string;
    avatar?: string | null;
    role: TeamRole;
    role_label: string;
};

export type TeamInvitation = {
    code: string;
    email: string;
    role: TeamRole;
    role_label: string;
    created_at: string;
};

export type TeamInvitationContext = {
    code: string;
    teamName: string;
};

export type DashboardInvitation = {
    code: string;
    inviterName: string;
    team: {
        name: string;
        slug: string;
    };
};

export type TeamPermissions = {
    canUpdateTeam: boolean;
    canDeleteTeam: boolean;
    canAddMember: boolean;
    canUpdateMember: boolean;
    canRemoveMember: boolean;
    canCreateInvitation: boolean;
    canCancelInvitation: boolean;
};

export type RoleOption = {
    value: TeamRole;
    label: string;
};

export type DepartmentTask = {
    id: number;
    name: string;
    status: string; // 'open'
    owner: { name: string };
    usageCount: number;
    tokensInput: number;
    tokensOutput: number;
    costTotal: number;
    currency: string | null;
    planTitle: string | null;
};

export type TaskDetail = {
    id: number;
    name: string;
    content: string | null;
    status: string;
    owner: { name: string };
    departmentName: string;
    createdAt: string | null;
    externalUrl: string | null;
    externalProvider: string | null;
};

export type TaskSession = {
    session: string; // '—' when unattributed
    turns: number;
    costTotal: number;
    tokensInput: number;
    tokensOutput: number;
    tokensTotal: number;
    provider: string;
    model: string;
    firstAt: string | null;
    lastAt: string | null;
};

export type TaskTotals = {
    sessionCount: number;
    turns: number;
    tokensInput: number;
    tokensOutput: number;
    costTotal: number;
};

export type TaskPlanSummary = {
    title: string | null;
    body: string;
};
