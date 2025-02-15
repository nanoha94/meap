export interface IGetInvitationDetail {
    token: string;
    expires_at: string;
    inviter: {
        id: string;
        custom_id: string;
        name: string;
    };
}
