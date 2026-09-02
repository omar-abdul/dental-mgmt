export type User = {
    id: number;
    name: string;
    email: string;
    role: string;
    role_label: string;
    allowed_modules: string[];
    avatar?: string;
    email_verified_at?: string | null;
    created_at?: string;
    updated_at?: string;
};

export type Auth = {
    user: User | null;
};
