import { IGetUserResponse } from '@/types/api';
import { redirect } from 'next/navigation';

/**
 * 認証状態に基づいてリダイレクト
 * @param user ユーザー情報
 * @param isAuthPage 認証ページかどうか
 */
export function handleAuthRedirect(
    user: IGetUserResponse | null,
    isAuthPage: boolean = false,
): void {
    if (user) {
        if (user.email_verified_at && isAuthPage) {
            // 認証済みユーザーが認証ページにアクセスした場合
            redirect('/plan');
        }
        // メール未認証ユーザーが保護されたページにアクセスした場合のみ
        else if (!user.email_verified_at && !isAuthPage) {
            redirect('/email/verify');
        }
        // メール未認証ユーザーが認証ページにアクセスした場合は何もしない
    } else {
        if (!isAuthPage) {
            // 未ログインユーザーが保護されたページにアクセスした場合
            redirect('/login');
        }
        // 未ログインユーザーが認証ページにアクセスした場合は何もしない
    }
}
