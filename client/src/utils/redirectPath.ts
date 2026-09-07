/**
 * 同一オリジン内の相対パスのみ許可する（オープンリダイレクト対策）
 */
export function isSafeRedirectPath(path: string): boolean {
    if (!path.startsWith('/') || path.startsWith('//')) {
        return false;
    }

    if (path.includes('\\')) {
        return false;
    }

    if (/[\x00-\x1f\x7f]/.test(path)) {
        return false;
    }

    return true;
}
