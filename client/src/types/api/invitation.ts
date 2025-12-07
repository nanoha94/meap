export interface IGetInvitationDetailResponse {
    token: string;
    expires_at: string;
    inviter: {
        id: string;
        name: string;
        avatar_seed: string;
    };
}
