export interface IGetInvitationDetailResponse {
    token: string;
    expires_at: string;
    inviter: {
        id: string;
        custom_id: string;
        name: string;
    };
}
