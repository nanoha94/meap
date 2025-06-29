import { cookies } from 'next/headers';
import { Agent } from 'undici';

type ApiClientOptions = Omit<RequestInit, 'body'> & {
    body?: Record<string, unknown> | BodyInit | null;
};

/**
 * SSR環境で認証情報を付与してAPIリクエストを行う共通関数
 * @param path リクエストパス (例: '/shopping/items')
 * @param options fetchに渡すオプション (method, bodyなど)
 */
export async function apiClient<T>(
    path: string,
    options: ApiClientOptions = {},
): Promise<T> {
    const baseUrl =
        process.env.NEXT_PUBLIC_BACKEND_URL || 'https://localhost:8000';
    const frontendUrl =
        process.env.NEXT_PUBLIC_FRONTEND_URL || 'https://localhost:3000';

    const cookieStore = cookies();
    const cookieHeader = cookieStore
        .getAll()
        .map(c => `${c.name}=${c.value}`)
        .join('; ');
    const xsrfToken = cookieStore.get('XSRF-TOKEN')?.value;

    const defaultHeaders: HeadersInit = {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        Referer: frontendUrl,
        ...(cookieHeader && { Cookie: cookieHeader }),
        ...(xsrfToken && { 'X-XSRF-TOKEN': decodeURIComponent(xsrfToken) }),
    };

    const { body, ...restOptions } = options;
    let finalBody: BodyInit | null | undefined;

    // bodyがプレーンなオブジェクトの場合、JSONに変換してContent-Typeを設定
    if (
        body &&
        typeof body === 'object' &&
        !(body instanceof FormData) &&
        !(body instanceof URLSearchParams) &&
        !(body instanceof Blob)
    ) {
        defaultHeaders['Content-Type'] = 'application/json';
        finalBody = JSON.stringify(body);
    } else {
        finalBody = body as BodyInit | null | undefined;
    }

    const fetchOptions: RequestInit = {
        ...restOptions,
        body: finalBody,
        headers: {
            ...defaultHeaders,
            ...options.headers,
        },
        credentials: 'include',
        cache: 'no-store',
        // @ts-expect-error undiciのAgentをdispatcherに渡す
        dispatcher: new Agent({
            connect: {
                rejectUnauthorized: false,
            },
        }),
    };

    const response = await fetch(`${baseUrl}${path}`, fetchOptions);

    if (!response.ok) {
        const errorText = await response.text();
        console.error(
            `API Client Error: ${response.status} ${response.statusText} on ${path}`,
            errorText,
        );
        throw new Error(`Request failed with status ${response.status}`);
    }

    // No Contentの場合は空のオブジェクトを返す
    if (
        response.status === 204 ||
        response.headers.get('Content-Length') === '0'
    ) {
        return {} as T;
    }

    return response.json();
}
