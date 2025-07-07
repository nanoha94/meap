export interface IGetUserResponse {
    id?: string;
    name?: string;
    email?: string;
    email_verified_at?: string;
    created_at?: string;
    updated_at?: string;
}

export interface IGetGroupUserResponse {
    data: {
        id: string;
        name: string;
    }[];
    total: number;
}
