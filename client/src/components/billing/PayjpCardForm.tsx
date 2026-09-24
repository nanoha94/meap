'use client';

import React from 'react';

import { colors } from '@/constants';
import {
    getPayjpClient,
    isPayjpPublicKeyConfigured,
    subscribePayjpScriptLoaded,
} from '@/lib/payjpClient';
import {
    PayjpCardFormChangeState,
    PayjpCardFormHandle,
    PayjpCreateTokenResult,
    PayjpElement,
    PayjpElementOptions,
} from '@/types';

const PAYJP_CARD_ELEMENT_STYLE: PayjpElementOptions['style'] = {
    base: {
        color: colors.black,
        fontFamily: '"Noto Sans JP", sans-serif',
        fontSize: '16px',
        '::placeholder': {
            color: colors.gray.placeholder,
        },
    },
    invalid: {
        color: colors.alert.main,
    },
};

const CARD_FIELDS = [
    {
        type: 'cardNumber',
        label: 'カード番号',
        placeholder: '1234 5678 9012 3456',
    },
    {
        type: 'cardExpiry',
        label: '有効期限',
        placeholder: '01 / 23',
    },
    {
        type: 'cardCvc',
        label: 'セキュリティ番号',
        placeholder: '123',
    },
] as const;

const CVC_PLACEHOLDER_AMEX = '1234';
const CVC_PLACEHOLDER_DEFAULT = '123';
const BRAND_AMEX = 'American Express';

type CardFieldType = (typeof CARD_FIELDS)[number]['type'];

type CardFieldState = {
    complete: boolean;
    empty: boolean;
    errorMessage: string | null;
};

const MISSING_PUBLIC_KEY_MESSAGE =
    'PAY.JP の公開鍵が設定されていません（NEXT_PUBLIC_PAYJP_PUBLIC_KEY）。';

const INITIAL_FIELD_STATE: CardFieldState = {
    complete: false,
    empty: true,
    errorMessage: null,
};

const INITIAL_FIELD_STATES: Record<CardFieldType, CardFieldState> = {
    cardNumber: INITIAL_FIELD_STATE,
    cardExpiry: INITIAL_FIELD_STATE,
    cardCvc: INITIAL_FIELD_STATE,
};

const mountContainerClassName =
    'flex min-h-[48px] items-center rounded-lg border border-gray-border bg-white px-3 py-3 transition-colors [&>*]:w-full [&_iframe]:block [&.PayjpElement--focus]:border-primary-main [&:has(.PayjpElement--focus)]:border-primary-main [&.PayjpElement--invalid]:border-alert-main [&:has(.PayjpElement--invalid)]:border-alert-main [&.PayjpElement--invalid]:bg-alert-background [&:has(.PayjpElement--invalid)]:bg-alert-background';

interface Props {
    className?: string;
    onChange?: (state: PayjpCardFormChangeState) => void;
    onReady?: () => void;
}

const PayjpCardForm = React.forwardRef<PayjpCardFormHandle, Props>(
    ({ className, onChange, onReady }, ref) => {
        const reactId = React.useId().replace(/:/g, '');
        const mountIds = React.useMemo(
            () => ({
                cardNumber: `payjp-card-number-${reactId}`,
                cardExpiry: `payjp-card-expiry-${reactId}`,
                cardCvc: `payjp-card-cvc-${reactId}`,
            }),
            [reactId],
        );
        const cardElementsRef = React.useRef<Record<
            CardFieldType,
            PayjpElement
        > | null>(null);
        const fieldStatesRef = React.useRef(INITIAL_FIELD_STATES);
        const lastBrandRef = React.useRef<string>('unknown');
        const onChangeRef = React.useRef(onChange);
        const onReadyRef = React.useRef(onReady);
        const missingPublicKeyMessage = isPayjpPublicKeyConfigured()
            ? null
            : MISSING_PUBLIC_KEY_MESSAGE;
        const [fieldErrorMessage, setFieldErrorMessage] = React.useState<
            string | null
        >(null);

        React.useEffect(() => {
            onChangeRef.current = onChange;
        }, [onChange]);

        React.useEffect(() => {
            onReadyRef.current = onReady;
        }, [onReady]);

        React.useEffect(() => {
            if (!isPayjpPublicKeyConfigured()) {
                return;
            }

            let isActive = true;

            /**
             * カード入力フォームをマウントする
             */
            const mountCardElements = () => {
                const payjp = getPayjpClient();
                if (!payjp || !isActive || cardElementsRef.current) {
                    return;
                }

                const elements = payjp.elements();
                const created = {} as Record<CardFieldType, PayjpElement>;
                fieldStatesRef.current = { ...INITIAL_FIELD_STATES };

                CARD_FIELDS.forEach(field => {
                    const cardElement = elements.create(field.type, {
                        style: PAYJP_CARD_ELEMENT_STYLE,
                        placeholder: field.placeholder,
                    });

                    cardElement.on('change', event => {
                        fieldStatesRef.current = {
                            ...fieldStatesRef.current,
                            [field.type]: {
                                complete: event.complete,
                                empty: event.empty,
                                errorMessage: event.error?.message ?? null,
                            },
                        };

                        if (field.type === 'cardNumber' && event.brand && event.brand !== lastBrandRef.current) {
                            lastBrandRef.current = event.brand;
                            const cvcPlaceholder = event.brand === BRAND_AMEX
                                ? CVC_PLACEHOLDER_AMEX
                                : CVC_PLACEHOLDER_DEFAULT;
                            created.cardCvc.update({ placeholder: cvcPlaceholder });
                        }

                        const fieldStates = fieldStatesRef.current;
                        const errorMessage =
                            fieldStates.cardNumber.errorMessage ??
                            fieldStates.cardExpiry.errorMessage ??
                            fieldStates.cardCvc.errorMessage;
                        setFieldErrorMessage(errorMessage);
                        onChangeRef.current?.({
                            complete:
                                fieldStates.cardNumber.complete &&
                                fieldStates.cardExpiry.complete &&
                                fieldStates.cardCvc.complete,
                            empty:
                                fieldStates.cardNumber.empty &&
                                fieldStates.cardExpiry.empty &&
                                fieldStates.cardCvc.empty,
                            errorMessage,
                        });
                    });

                    cardElement.mount(`#${mountIds[field.type]}`);
                    created[field.type] = cardElement;
                });

                cardElementsRef.current = created;
                onReadyRef.current?.();
            };

            const unsubscribe = subscribePayjpScriptLoaded(mountCardElements);

            return () => {
                isActive = false;
                unsubscribe();
                if (cardElementsRef.current) {
                    Object.values(cardElementsRef.current).forEach(element => {
                        element.unmount();
                    });
                    cardElementsRef.current = null;
                }
            };
        }, [mountIds]);

        React.useImperativeHandle(
            ref,
            () => ({
                createToken: async (): Promise<PayjpCreateTokenResult> => {
                    if (!isPayjpPublicKeyConfigured()) {
                        return {
                            ok: false,
                            message: MISSING_PUBLIC_KEY_MESSAGE,
                        };
                    }

                    const payjp = getPayjpClient();
                    const cardNumberElement =
                        cardElementsRef.current?.cardNumber;

                    if (!payjp || !cardNumberElement) {
                        return {
                            ok: false,
                            message: 'カード入力フォームの準備ができていません。',
                        };
                    }

                    try {
                        const response = await payjp.createToken(cardNumberElement);

                        if (response.error) {
                            return {
                                ok: false,
                                message: response.error.message,
                            };
                        }

                        if (!response.id) {
                            return {
                                ok: false,
                                message: 'カードトークンの取得に失敗しました。',
                            };
                        }

                        return { ok: true, token: response.id };
                    } catch {
                        return {
                            ok: false,
                            message: 'カードトークンの取得に失敗しました。',
                        };
                    }
                },
            }),
            [],
        );

        const displayErrorMessage =
            missingPublicKeyMessage ?? fieldErrorMessage;

        return (
            <div className={className}>
                <div className="flex flex-col gap-y-3">
                    <PayjpCardField
                        label={CARD_FIELDS[0].label}
                        mountId={mountIds.cardNumber}
                    />
                    <div className="grid grid-cols-2 gap-x-3">
                        <PayjpCardField
                            label={CARD_FIELDS[1].label}
                            mountId={mountIds.cardExpiry}
                        />
                        <PayjpCardField
                            label={CARD_FIELDS[2].label}
                            mountId={mountIds.cardCvc}
                        />
                    </div>
                </div>
                {displayErrorMessage && (
                    <p className="mt-2 text-sm text-alert-main" role="alert">
                        {displayErrorMessage}
                    </p>
                )}
            </div>
        );
    },
);

PayjpCardForm.displayName = 'PayjpCardForm';

export default PayjpCardForm;

type PayjpCardFieldProps = {
    label: string;
    mountId: string;
};

const PayjpCardField = ({ label, mountId }: PayjpCardFieldProps) => (
    <div className="flex flex-col gap-y-1">
        <label htmlFor={mountId} className="text-sm">
            {label}
        </label>
        <div id={mountId} className={mountContainerClassName} />
    </div>
);
