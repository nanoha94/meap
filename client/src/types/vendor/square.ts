interface SquareWebPaymentsSDK {
    payments(applicationId: string, locationId?: string): Promise<Payments>;
}

export interface Payments {
    card(options?: CardOptions): Promise<Card>;
}

interface CardOptions {
    style?: CardStyle;
}

/**
 * @see https://developer.squareup.com/reference/sdks/web/payments/objects/CardClassSelectors
 */
export interface CardStyle {
    [key: string]: CardStyleState | undefined;
    '.input-container'?: CardStyleState;
    '.input-container.is-focus'?: CardStyleState;
    '.input-container.is-error'?: CardStyleState;
    '.message-text'?: CardStyleState;
    '.message-icon'?: CardStyleState;
    '.message-text.is-error'?: CardStyleState;
    '.message-icon.is-error'?: CardStyleState;
    input?: CardStyleState;
    'input::placeholder'?: CardStyleState;
    'input.is-error'?: CardStyleState;
}

interface CardStyleState {
    backgroundColor?: string;
    borderColor?: string;
    borderWidth?: string;
    borderRadius?: string;
    color?: string;
    fontFamily?: string;
    fontSize?: string;
    fontWeight?: string;
}

/**
 * @see https://developer.squareup.com/reference/sdks/web/payments/objects/Card
 */
export interface Card {
    attach(selector: string): Promise<void>;
    destroy(): Promise<void>;
    tokenize(verificationDetails?: VerificationDetails): Promise<TokenResult>;
    addEventListener(
        eventType: CardEventType,
        callback: (event: CardInputEvent) => void,
    ): void;
    removeEventListener(
        eventType: CardEventType,
        callback: (event: CardInputEvent) => void,
    ): void;
}

export const SQUARE_CARD_EVENT_TYPE = {
    FOCUS_CLASS_ADDED: 'focusClassAdded',
    FOCUS_CLASS_REMOVED: 'focusClassRemoved',
    ERROR_CLASS_ADDED: 'errorClassAdded',
    ERROR_CLASS_REMOVED: 'errorClassRemoved',
    CARD_BRAND_CHANGED: 'cardBrandChanged',
    POSTAL_CODE_CHANGED: 'postalCodeChanged',
} as const;

type CardEventType =
    (typeof SQUARE_CARD_EVENT_TYPE)[keyof typeof SQUARE_CARD_EVENT_TYPE];

export type CardFieldName =
    | 'cardNumber'
    | 'expirationDate'
    | 'cvv'
    | 'postalCode';

interface CardInputEventState {
    hasErrorClass: boolean;
    hasFocusClass: boolean;
    isCompletelyValid: boolean;
    isEmpty: boolean;
    isPotentiallyValid: boolean;
}

/**
 * @see https://developer.squareup.com/reference/sdks/web/payments/objects/CardInputEvent
 */
export interface CardInputEvent {
    currentTarget: Card;
    detail: {
        cardBrand?: string;
        currentState?: CardInputEventState;
        eventType?: CardEventType;
        field?: CardFieldName;
        postalCodeValue?: string;
        previousState?: CardInputEventState;
    };
    type: CardEventType;
}

export const SQUARE_TOKEN_RESULT_STATUS = {
    OK: 'OK',
    CANCEL: 'Cancel',
    ERROR: 'Error',
} as const;

type TokenResultStatus =
    (typeof SQUARE_TOKEN_RESULT_STATUS)[keyof typeof SQUARE_TOKEN_RESULT_STATUS];

export interface TokenResult {
    status: TokenResultStatus;
    token?: string;
    details?: CardDetails;
    errors?: TokenError[];
}

interface CardDetails {
    brand?: string;
    expMonth?: number;
    expYear?: number;
    last4?: string;
    billing?: BillingContact;
}

interface TokenError {
    type: string;
    field?: string;
    message: string;
}

export interface BillingContact {
    addressLines?: string[];
    city?: string;
    country?: string;
    email?: string;
    familyName?: string;
    givenName?: string;
    phone?: string;
    postalCode?: string;
    state?: string;
}

/**
 * 決済トークンの作成意図
 * - STORE: カード情報を保存（決済トークンにしない）
 * - CHARGE: 決済トークンを作る
 */
export const SQUARE_VERIFICATION_INTENT = {
    STORE: 'STORE',
    CHARGE: 'CHARGE',
} as const;

export type VerificationIntent =
    (typeof SQUARE_VERIFICATION_INTENT)[keyof typeof SQUARE_VERIFICATION_INTENT];

/**
 * カード情報を保存するための詳細
 * @see https://developer.squareup.com/reference/sdks/web/payments/objects/StoreCardVerificationDetails
 */
interface StoreCardVerificationDetails {
    intent: typeof SQUARE_VERIFICATION_INTENT.STORE;
    customerInitiated: boolean; // 顧客が入力したかどうか
    sellerKeyedIn: boolean; // 販売者が購入者の代わりに入力したかどうか
    billingContact?: BillingContact; // 請求先情報
}

/**
 * 決済トークンを作るための詳細
 * @see https://developer.squareup.com/reference/sdks/web/payments/objects/ChargeCardVerificationDetails
 */
interface ChargeCardVerificationDetails {
    intent: typeof SQUARE_VERIFICATION_INTENT.CHARGE;
    amount: string; // 決済金額
    currencyCode: string; // 通貨コード
    customerInitiated: boolean; // 顧客が入力したかどうか
    sellerKeyedIn: boolean; // 販売者が購入者の代わりに入力したかどうか
    billingContact?: BillingContact; // 請求先情報
}

export type VerificationDetails =
    | StoreCardVerificationDetails
    | ChargeCardVerificationDetails;

declare global {
    interface Window {
        Square?: SquareWebPaymentsSDK;
    }
}
