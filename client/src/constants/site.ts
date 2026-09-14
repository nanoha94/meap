export const FRONTEND_BASE_URL =
    process.env.NEXT_PUBLIC_FRONTEND_URL ?? 'http://localhost:3000';

export const getPageUrl = (path: string) =>
    new URL(path, FRONTEND_BASE_URL).toString();
