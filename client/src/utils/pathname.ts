/**
 * pathname をプレフィックス照合向けに正規化する（末尾の `/` を削除
 */
export function normalizePathnameForMatch(pathname: string): string {
    if (pathname === '/' || pathname === '') {
        return pathname;
    }
    return pathname.replace(/\/+$/, '') || '/';
}
