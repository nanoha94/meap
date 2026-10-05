'use client';

import React from 'react';

import { SQUARE_CARD_ELEMENT_STYLE } from '@/constants/square';
import {
    getSquarePayments,
    isSquareCredentialsConfigured,
    subscribeSquareScriptLoaded,
} from '@/lib/squareClient';
import { useUserStore } from '@/models/user';
import {
    BillingTokenizationIntent,
    CardCreateTokenResult,
    CardFormChangeState,
    CardFormHandle,
    ILoginUser,
} from '@/types';
import {
    BillingContact,
    Card,
    CardFieldName,
    CardInputEvent,
    SQUARE_CARD_EVENT_TYPE,
    SQUARE_TOKEN_RESULT_STATUS,
    SQUARE_VERIFICATION_INTENT,
    TokenResult,
    VerificationDetails,
} from '@/types/vendor/square';

const CORE_CARD_FIELDS: CardFieldName[] = [
    'cardNumber',
    'expirationDate',
    'cvv',
];

const MISSING_CREDENTIALS_MESSAGE =
    'Square の Application ID / Location ID が設定されていません（NEXT_PUBLIC_SQUARE_APPLICATION_ID / NEXT_PUBLIC_SQUARE_LOCATION_ID）。';

const mountContainerClassName =
    'flex flex-col gap-y-3 [&>*]:w-full [&_iframe]:block';

const nameInputClassName =
    'w-full min-w-0 py-2 px-4 border rounded-lg border-gray-main';

type CoreFieldValidity = {
    cardNumber: boolean;
    cvv: boolean;
    expirationDate: boolean;
};

const INITIAL_CORE_FIELD_VALIDITY: CoreFieldValidity = {
    cardNumber: false,
    expirationDate: false,
    cvv: false,
};

interface Props {
    chargeAmountYen?: number; // CHARGE 時の決済金額（パック購入時のみ指定）
    tokenizationIntent: BillingTokenizationIntent;
    className?: string;
    onChange?: (state: CardFormChangeState) => void;
    onReady?: () => void;
}

const SquareCardForm = React.forwardRef<CardFormHandle, Props>(
    (
        { chargeAmountYen, tokenizationIntent, className, onChange, onReady },
        ref,
    ) => {
        const reactId = React.useId().replace(/:/g, '');
        const mountId = React.useMemo(
            () => `square-card-${reactId}`,
            [reactId],
        );
        const familyNameId = `square-card-family-name-${reactId}`;
        const givenNameId = `square-card-given-name-${reactId}`;
        const loginUser = useUserStore(state => state.loginUser);
        const cardRef = React.useRef<Card | null>(null);
        const mountGenerationRef = React.useRef(0);
        const coreFieldValidityRef = React.useRef<CoreFieldValidity>({
            ...INITIAL_CORE_FIELD_VALIDITY,
        });
        const isPostalCodeRequiredRef = React.useRef(false);
        const isPostalCodeValidRef = React.useRef(false);
        const onChangeRef = React.useRef(onChange);
        const onReadyRef = React.useRef(onReady);
        const missingCredentialsMessage = isSquareCredentialsConfigured()
            ? null
            : MISSING_CREDENTIALS_MESSAGE;
        const fieldErrorMessageRef = React.useRef<string | null>(null);
        const [fieldErrorMessage, setFieldErrorMessage] = React.useState<
            string | null
        >(null);
        const [familyName, setFamilyName] = React.useState('');
        const [givenName, setGivenName] = React.useState('');
        React.useEffect(() => {
            onChangeRef.current = onChange;
        }, [onChange]);

        React.useEffect(() => {
            onReadyRef.current = onReady;
        }, [onReady]);

        /**
         * カード入力フォームの状態を変更する
         */
        const emitChangeState = React.useCallback(() => {
            // コアフィールドがすべて入力されているか
            const isCoreComplete = CORE_CARD_FIELDS.every(
                field => coreFieldValidityRef.current[field],
            );
            // 郵便番号が入力されているか
            const isPostalComplete =
                !isPostalCodeRequiredRef.current ||
                isPostalCodeValidRef.current;
            // カード入力フォームが入力されているか
            const isComplete = isCoreComplete && isPostalComplete;
            // カード入力フォームが空か
            const isEmpty = CORE_CARD_FIELDS.every(
                field => !coreFieldValidityRef.current[field],
            );

            // カード入力フォームの状態を変更する
            onChangeRef.current?.({
                complete: isComplete,
                empty: isEmpty,
                errorMessage: fieldErrorMessageRef.current,
            });
        }, []);

        /**
         * カード入力フォームをマウントする
         */
        React.useEffect(() => {
            if (!isSquareCredentialsConfigured()) {
                return;
            }

            const generation = ++mountGenerationRef.current;

            const isMountStale = (): boolean =>
                generation !== mountGenerationRef.current;

            /**
             * カード入力イベントを処理する
             */
            const handleCardInputEvent = (event: CardInputEvent) => {
                const field = event.detail.field;
                const isValid =
                    event.detail.currentState?.isCompletelyValid ?? false;

                if (field === 'postalCode') {
                    isPostalCodeRequiredRef.current = true;
                    isPostalCodeValidRef.current = isValid;
                }
                else if (
                    field &&
                    CORE_CARD_FIELDS.includes(field)
                ) {
                    coreFieldValidityRef.current = {
                        ...coreFieldValidityRef.current,
                        [field]: isValid,
                    };
                }

                emitChangeState();
            };

            /**
             * カード入力フォームをマウントする
             */
            const mountCard = async () => {
                let payments: Awaited<ReturnType<typeof getSquarePayments>>;
                try {
                    payments = await getSquarePayments();
                } catch (error) {
                    if (isMountStale()) {
                        return;
                    }
                    if (process.env.NODE_ENV === 'development') {
                        console.error('Square payments initialization failed', error);
                    }
                    const message =
                        'カード入力フォームの準備に失敗しました。';
                    fieldErrorMessageRef.current = message;
                    setFieldErrorMessage(message);
                    return;
                }

                if (!payments || isMountStale()) {
                    return;
                }

                try {
                    if (cardRef.current) {
                        await cardRef.current.destroy();
                        cardRef.current = null;
                    }

                    const card = await payments.card({
                        style: SQUARE_CARD_ELEMENT_STYLE,
                    });
                    if (isMountStale()) {
                        await card.destroy();
                        return;
                    }

                    await card.attach(`#${mountId}`);
                    if (isMountStale()) {
                        await card.destroy();
                        return;
                    }

                    const eventTypes = [
                        SQUARE_CARD_EVENT_TYPE.FOCUS_CLASS_REMOVED,
                        SQUARE_CARD_EVENT_TYPE.ERROR_CLASS_ADDED,
                        SQUARE_CARD_EVENT_TYPE.ERROR_CLASS_REMOVED,
                    ] as const;

                    eventTypes.forEach(eventType => {
                        card.addEventListener(eventType, handleCardInputEvent);
                    });

                    cardRef.current = card;
                    coreFieldValidityRef.current = {
                        ...INITIAL_CORE_FIELD_VALIDITY,
                    };
                    isPostalCodeRequiredRef.current = false;
                    isPostalCodeValidRef.current = false;
                    fieldErrorMessageRef.current = null;
                    setFieldErrorMessage(null);
                    onReadyRef.current?.();
                } catch (error) {
                    if (isMountStale()) {
                        return;
                    }
                    if (process.env.NODE_ENV === 'development') {
                        console.error('Square card mount failed', error);
                    }
                    const message =
                        'カード入力フォームの準備に失敗しました。';
                    fieldErrorMessageRef.current = message;
                    setFieldErrorMessage(message);
                }
            };

            const unsubscribe = subscribeSquareScriptLoaded(() => {
                void mountCard();
            });

            return () => {
                mountGenerationRef.current += 1;
                unsubscribe();
                const card = cardRef.current;
                cardRef.current = null;
                if (card) {
                    void card.destroy();
                }
            };
        }, [emitChangeState, mountId]);

        /**
         * 決済トークンを作成する
         * @returns 決済トークン
         */
        React.useImperativeHandle(
            ref,
            () => ({
                createToken: async (): Promise<CardCreateTokenResult> => {
                    if (!isSquareCredentialsConfigured()) {
                        return {
                            ok: false,
                            message: MISSING_CREDENTIALS_MESSAGE,
                        };
                    }

                    const card = cardRef.current;
                    if (!card) {
                        return {
                            ok: false,
                            message: 'カード入力フォームの準備ができていません。',
                        };
                    }

                    if (
                        tokenizationIntent === SQUARE_VERIFICATION_INTENT.CHARGE &&
                        (chargeAmountYen == null || chargeAmountYen <= 0)
                    ) {
                        return {
                            ok: false,
                            message: '決済金額の設定が不正です。',
                        };
                    }

                    // card.tokenize() に渡す本人確認情報を作る
                    const verificationDetails: VerificationDetails =
                        buildVerificationDetails(
                            tokenizationIntent,
                            chargeAmountYen,
                            loginUser,
                            familyName,
                            givenName,
                        );

                    try {
                        // card.tokenize() を呼び出して決済トークンを作成する
                        const result: TokenResult = await card.tokenize(verificationDetails);

                        // 決済トークンが作成できたらトークンを返す
                        if (
                            result.status === SQUARE_TOKEN_RESULT_STATUS.OK &&
                            result.token
                        ) {
                            return { ok: true, token: result.token };
                        }

                        // 決済トークンが作成できなかったらエラーメッセージを返す
                        if (
                            result.status === SQUARE_TOKEN_RESULT_STATUS.CANCEL
                        ) {
                            return {
                                ok: false,
                                message: 'カード認証がキャンセルされました。',
                            };
                        }

                        const message =
                            result.errors?.[0]?.message ??
                            'カードトークンの取得に失敗しました。';

                        return { ok: false, message };
                    } catch {
                        return {
                            ok: false,
                            message: 'カードトークンの取得に失敗しました。',
                        };
                    }
                },
            }),
            [
                chargeAmountYen,
                familyName,
                givenName,
                loginUser,
                tokenizationIntent,
            ],
        );

        const displayErrorMessage =
            missingCredentialsMessage ?? fieldErrorMessage;

        return (
            <div className={className}>
                <div className="flex flex-col gap-y-4">
                    <div className="flex flex-col gap-y-2">
                        <div className="flex flex-col gap-y-1">
                            <p>カード名義</p>
                            <p className="text-xs whitespace-pre-line">
                                カード券面に記載のローマ字で入力してください
                            </p>
                        </div>
                        <div className="grid grid-cols-2 gap-x-3">
                            <div className="flex flex-col gap-y-1">
                                <label
                                    htmlFor={familyNameId}
                                    className="text-sm"
                                >
                                    姓
                                </label>
                                <input
                                    id={familyNameId}
                                    type="text"
                                    placeholder="YAMADA"
                                    value={familyName}
                                    onChange={e =>
                                        setFamilyName(e.target.value)
                                    }
                                    className={nameInputClassName}
                                />
                            </div>
                            <div className="flex flex-col gap-y-1">
                                <label
                                    htmlFor={givenNameId}
                                    className="text-sm"
                                >
                                    名
                                </label>
                                <input
                                    id={givenNameId}
                                    type="text"
                                    placeholder="TARO"
                                    value={givenName}
                                    onChange={e =>
                                        setGivenName(e.target.value)
                                    }
                                    className={nameInputClassName}
                                />
                            </div>
                        </div>
                    </div>
                    <div className="flex flex-col gap-y-2">
                        <label htmlFor={mountId}>
                            カード情報
                        </label>
                        <div className="flex flex-col gap-y-1">
                            <div
                                id={mountId}
                                className={mountContainerClassName}
                            />
                            {displayErrorMessage && (
                                <p
                                    className="text-sm text-alert-main"
                                    role="alert"
                                >
                                    {displayErrorMessage}
                                </p>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        );
    },
);

SquareCardForm.displayName = 'SquareCardForm';

export default SquareCardForm;

/**
 * 請求先情報を作成する
 * @param loginUser ログインユーザー
 * @param familyName カード名義の姓。空なら送らない
 * @param givenName カード名義の名。空なら送らない
 * @returns 請求先情報
 */
const buildBillingContact = (
    loginUser: ILoginUser,
    familyName: string,
    givenName: string,
): BillingContact => {
    const contact: BillingContact = { country: 'JP' };

    if (loginUser.email) {
        contact.email = loginUser.email;
    }

    const trimmedFamilyName = familyName.trim();
    const trimmedGivenName = givenName.trim();
    if (trimmedFamilyName) {
        contact.familyName = trimmedFamilyName;
    }
    if (trimmedGivenName) {
        contact.givenName = trimmedGivenName;
    }

    return contact;
};

/**
 * card.tokenize() に渡す本人確認情報を作る
 * STORE はカード保存、CHARGE は決済用で金額を含める
 * @param tokenizationIntent カード保存（STORE）か決済（CHARGE）か
 * @param chargeAmountYen 決済金額。CHARGE のときだけ使う
 * @param loginUser ログインユーザー
 * @param familyName カード名義の姓
 * @param givenName カード名義の名
 * @returns tokenize() に渡す本人確認情報
 */
const buildVerificationDetails = (
    tokenizationIntent: BillingTokenizationIntent,
    chargeAmountYen: number | undefined,
    loginUser: ILoginUser,
    familyName: string,
    givenName: string,
): VerificationDetails => {
    // 請求先情報を作成する
    const billingContact: BillingContact = buildBillingContact(
        loginUser,
        familyName,
        givenName,
    );
    const shared = {
        customerInitiated: true, // 顧客が入力した
        sellerKeyedIn: false, // 販売者が購入者の代わりに入力していない
        billingContact, // 請求先情報
    };

    // CHARGE 時は金額と通貨を含める
    if (tokenizationIntent === SQUARE_VERIFICATION_INTENT.CHARGE) {
        return {
            intent: SQUARE_VERIFICATION_INTENT.CHARGE,
            amount: String(chargeAmountYen),
            currencyCode: 'JPY',
            ...shared,
        };
    }

    // STORE 時は金額と通貨を含めない
    return {
        intent: SQUARE_VERIFICATION_INTENT.STORE,
        ...shared,
    };
};
