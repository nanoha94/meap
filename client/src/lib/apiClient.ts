import { cookies } from 'next/headers';
import { Agent } from 'undici';

import { API_STATUS_CODE, TIMEOUT_MS } from '@/constants';

/**
 * APIエラー時にステータスコードを保持するためのエラークラス
 */
export class ApiClientError extends Error {
    constructor(
        message: string,
        public readonly statusCode: number,
    ) {
        super(message);
        this.name = 'ApiClientError';
    }
}

type ApiClientOptions = Omit<RequestInit, 'body'> & {
    body?: Record<string, unknown> | BodyInit | null;
    /**
     * true のとき、401 / 409（認証関連）では console.error しない。
     * ゲスト向けレイアウトでの /user 取得など「未認証であり得る」呼び出し向け。
     */
    suppressUnauthorizedLog?: boolean;
    /**
     * true のとき、404 では console.error しない。
     * 献立取得など「リソース未作成であり得る」呼び出し向け。
     */
    suppressNotFoundLog?: boolean;
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
        process.env.NEXT_PUBLIC_FRONTEND_URL ||
        'https://localhost:3000';

    const cookieStore = await cookies();
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

    const { body, suppressUnauthorizedLog, suppressNotFoundLog, ...restOptions } =
        options;
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
        const isAuthRelatedFailure =
            response.status === API_STATUS_CODE.UNAUTHORIZED ||
            response.status === API_STATUS_CODE.CONFLICT;
        const isNotFound = response.status === API_STATUS_CODE.NOT_FOUND;
        const skipErrorLog =
            (suppressUnauthorizedLog && isAuthRelatedFailure) ||
            (suppressNotFoundLog && isNotFound);
        if (!skipErrorLog) {
            console.error(
                `API Client Error: ${response.status} ${response.statusText} on ${path}`,
                errorText,
            );
        }

        // 認証関連のエラーを統一
        if (
            response.status == API_STATUS_CODE.UNAUTHORIZED ||
            response.status === API_STATUS_CODE.CONFLICT
        ) {
            throw new ApiClientError('AUTHENTICATION_REQUIRED', response.status);
        }

        throw new ApiClientError(`Request failed with status ${response.status}`, response.status);
    }

    // No Contentの場合は空のオブジェクトを返す
    if (
        response.status === API_STATUS_CODE.NO_CONTENT ||
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
    /** APIがエラーレスポンスを返した場合のHTTPステータスコード */
    statusCode?: number;
};

export async function fetchData<T>(
    path: string,
    options: ApiClientOptions = {},
): Promise<FetchDataResult<T>> {
    let data: T | null = null;
    let errorMessage: string = '';
    let statusCode: number | undefined;

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
        const suppressFetchLog =
            (options.suppressUnauthorizedLog &&
                error instanceof ApiClientError &&
                error.message === 'AUTHENTICATION_REQUIRED') ||
            (options.suppressNotFoundLog &&
                error instanceof ApiClientError &&
                error.statusCode === API_STATUS_CODE.NOT_FOUND);
        if (!suppressFetchLog) {
            console.error(`[fetchData] エラー発生: ${path}`, error);
        }
        if (error instanceof ApiClientError) {
            errorMessage = error.message;
            statusCode = error.statusCode;
        } else if (error instanceof Error && error.name === 'AbortError') {
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

    return { data, errorMessage, ...(statusCode !== undefined && { statusCode }) };
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
