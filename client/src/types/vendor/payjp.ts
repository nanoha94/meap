export type PayjpFactory = (
    publicKey: string,
    options?: PayjpClientOptions,
) => PayjpClient;

export interface PayjpClientOptions {
    locale?: 'ja' | 'en';
    threeDSecureWorkflow?: 'iframe' | 'redirect' | 'subwindow';
}

export interface PayjpClient {
    elements(options?: { locale?: string }): PayjpElements;
    createToken(
        element: PayjpElement,
        data?: Record<string, unknown>,
        options?: Record<string, unknown>,
    ): Promise<PayjpTokenResponse>;
}

export type PayjpElementType = 'card' | 'cardNumber' | 'cardExpiry' | 'cardCvc';

export interface PayjpElements {
    create(type: PayjpElementType, options?: PayjpElementOptions): PayjpElement;
}

interface PayjpElementStyleState {
    color?: string;
    fontFamily?: string;
    fontSize?: string;
    '::placeholder'?: {
        color?: string;
    };
}

export interface PayjpElementOptions {
    style?: {
        base?: PayjpElementStyleState;
        invalid?: PayjpElementStyleState;
        complete?: PayjpElementStyleState;
        empty?: PayjpElementStyleState;
    };
    placeholder?: string;
    disabled?: boolean;
    hideIcon?: boolean;
}

export interface PayjpElement {
    mount(selector: string): void;
    unmount(): void;
    on(
        event: 'change',
        listener: (event: PayjpElementChangeEvent) => void,
    ): void;
    update(options: PayjpElementOptions): void;
}

export interface PayjpElementChangeEvent {
    complete: boolean;
    empty: boolean;
    error?: {
        type: string;
        code: string;
        message: string;
    };
    /** カード番号から判定されたブランド名。elementType が card / cardNumber の場合のみ */
    brand?: string;
}

export interface PayjpTokenResponse {
    id?: string;
    error?: {
        type: string;
        code: string;
        message: string;
    };
}

declare global {
    interface Window {
        Payjp?: PayjpFactory;
    }
}
