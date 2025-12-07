export interface IGetUserResponse {
    name?: string;
    email?: string;
    email_verified_at?: string;
    language?: string;
    avatar_seed: string;
}

export interface IGetGroupUserResponse {
    data: {
        avatar_seed: string;
        name: string;
    }[];
    total: number;
}
