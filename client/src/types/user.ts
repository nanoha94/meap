export interface User {
    id?: string;
    custom_id?: string;
    name?: string;
    email?: string;
    email_verified_at?: string;
    created_at?: string;
    updated_at?: string;
}

export interface GroupUser {
    id: string;
    custom_id: string;
    name: string;
}
