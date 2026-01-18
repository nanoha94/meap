import { cookies } from 'next/headers';
import { Agent } from 'undici';
import { TIMEOUT_MS } from '@/constants';

type ApiClientOptions = Omit<RequestInit, 'body'> & {
    body?: Record<string, unknown> | BodyInit | null;
};

/**
 * SSR環境で認証情報を付与してAPIリクエストを行う共通関数
 * @param path リクエストパス (例: '/shopping-items')
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

    const defaultHeaders: HeadersInit = {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        Referer: frontendUrl,
        ...(cookieHeader && { Cookie: cookieHeader }),
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
    };

    if (process.env.NODE_ENV === 'development') {
        // @ts-expect-error undiciのAgentをdispatcherに渡す
        fetchOptions.dispatcher = new Agent({
            connect: {
                rejectUnauthorized: false,
            },
        });
    }

    // APIリクエスト
    const response = await fetch(`${baseUrl}${path}`, fetchOptions);
    if (!response.ok) {
        const errorText = await response.text();
        console.error(
            `API Client Error: ${response.status} ${response.statusText} on ${path}`,
            errorText,
        );

        // 認証関連のエラーを統一
        if (response.status === 401 || response.status === 409) {
            throw new Error('AUTHENTICATION_REQUIRED');
        }

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

/**
 * タイムアウトとエラーハンドリングを内包したapiClientのラッパー関数
 * サーバーコンポーネントで使用する
 * エラーが発生しても例外を投げず、エラーメッセージを返す
 * @param path リクエストパス
 * @param options fetchに渡すオプション
 * @returns データとエラーメッセージを含むオブジェクト
 */
export type FetchDataResult<T> = {
    data: T | null;
    errorMessage: string;
};

export async function fetchData<T>(
    path: string,
    options: ApiClientOptions = {},
): Promise<FetchDataResult<T>> {
    let data: T | null = null;
    let errorMessage: string = '';

    // タイムアウトタイマー
    let timeoutId: NodeJS.Timeout | null = null;

    try {


        
        const controller = new AbortController();
        timeoutId = setTimeout(() => controller.abort(), TIMEOUT_MS);

        data = await apiClient<T>(path, {
            ...options,
            signal: controller.signal,
        });
    } catch (error) {
        console.error(`[fetchData] エラー発生: ${path}`, error);
        // エラーオブジェクトから安全に文字列を抽出
        if (error instanceof Error && error.name === 'AbortError') {
            errorMessage =
                'リクエストがタイムアウトしました。再度お試しください。';
        } else {
            errorMessage =
                error instanceof Error
                    ? error.message
                    : typeof error === 'string'
                      ? error
                      : 'データの取得に失敗しました';
        }
    } finally {
        // タイムアウトタイマーをクリア
        if (timeoutId) {
            clearTimeout(timeoutId);
        }
    }

    return { data, errorMessage };
}

/**
 * 複数のリクエストを並列実行し、タイムアウトとエラーハンドリングを内包した関数
 * サーバーコンポーネントで使用する
 * @param requests リクエスト関数の配列（各関数はAbortSignalを受け取る）
 * @returns データとエラーメッセージを含むオブジェクト
 */
export async function fetchDataParallel<T extends unknown[]>(
    requests: Array<(signal: AbortSignal) => Promise<T[number]>>,
): Promise<FetchDataResult<T>> {
    let data: T | null = null;
    let errorMessage: string = '';

    // タイムアウトタイマー
    let timeoutId: NodeJS.Timeout | null = null;

    try {
        const controller = new AbortController();
        timeoutId = setTimeout(() => controller.abort(), TIMEOUT_MS);

        data = (await Promise.all(
            requests.map(request => request(controller.signal)),
        )) as T;
    } catch (error) {
        console.error(
            `[fetchDataParallel] エラー発生: ${requests.length}件のリクエスト`,
            error,
        );
        // エラーオブジェクトから安全に文字列を抽出
        if (error instanceof Error && error.name === 'AbortError') {
            errorMessage =
                'リクエストがタイムアウトしました。再度お試しください。';
        } else {
            errorMessage =
                error instanceof Error
                    ? error.message
                    : typeof error === 'string'
                      ? error
                      : 'データの取得に失敗しました';
        }
    } finally {
        // タイムアウトタイマーをクリア
        if (timeoutId) {
            clearTimeout(timeoutId);
        }
    }

    return { data, errorMessage };
}
