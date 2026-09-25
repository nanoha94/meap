'use client';

import React from 'react';

import { Lock } from 'lucide-react';

import { Button, PayjpCardForm } from '@/components';
import {
    BUTTON_TYPE,
    BUTTON_VARIANT,
    COLOR_VARIANT,
} from '@/constants';
import { useDialog, useSnackbars } from '@/hooks';
import {
    BillingCheckoutOrderRow,
    IBillingStatus,
    PayjpCardFormHandle,
} from '@/types';
import {
    formatCardExpiration,
    formatMaskedCardNumber,
    hasBillingPaymentMethod,
} from '@/utils';

interface Props {
    billingStatus?: IBillingStatus | null;
    forceNewCard?: boolean;
    orderRows: BillingCheckoutOrderRow[];
    orderNote: string;
    submitButtonText: string;
    onSubmit: (cardToken?: string) => Promise<boolean>;
    onSuccess: () => void;
}

const BillingCheckoutPayment = ({
    billingStatus,
    forceNewCard = false,
    orderRows,
    orderNote,
    submitButtonText,
    onSubmit,
    onSuccess,
}: Props) => {
    const { closeDialog } = useDialog();
    const { addSnackbar } = useSnackbars();
    const payjpCardFormRef = React.useRef<PayjpCardFormHandle>(null);
    const [isCardFormComplete, setIsCardFormComplete] = React.useState(false);
    const hasRegisteredCard = hasBillingPaymentMethod(billingStatus ?? null);
    const [isPayjpCardFormShown, setIsPayjpCardFormShown] = React.useState(
        !hasRegisteredCard || forceNewCard,
    );
    const isDisabledSendButton =
        isPayjpCardFormShown && !isCardFormComplete;

    const handleSubmit = React.useCallback(async () => {
        if (!isPayjpCardFormShown) {
            const success = await onSubmit();

            if (success) {
                onSuccess();
            }
            return;
        }

        const tokenResult = await payjpCardFormRef.current?.createToken();

        if (!tokenResult?.ok) {
            addSnackbar(
                'error',
                tokenResult?.message ?? 'カード情報を確認してください。',
            );
            return;
        }

        const success = await onSubmit(tokenResult.token);

        if (success) {
            onSuccess();
        }
    }, [addSnackbar, onSubmit, onSuccess, isPayjpCardFormShown]);

    return (
        <div className="flex w-full flex-col gap-y-6">
            {orderRows.length > 0 && (
                <OrderSummary
                    rows={orderRows}
                    note={orderNote}
                />
            )}

            {forceNewCard && hasRegisteredCard && billingStatus && (
                <section className="flex flex-col gap-y-2">
                    <h3 className="text-base font-bold">現在のカード</h3>
                    <RegisteredCard billingStatus={billingStatus} />
                </section>
            )}

            <section className="flex flex-col gap-y-2">
                <h3 className="text-base font-bold">
                    {forceNewCard ? '新しいカード' : 'クレジットカード決済'}
                </h3>
                {!isPayjpCardFormShown && billingStatus ? (
                    <>
                        <RegisteredCard billingStatus={billingStatus} />
                        <button
                            type={BUTTON_TYPE.BUTTON}
                            className="text-primary-main w-fit font-bold underline transition-opacity hover:opacity-80"
                            onClick={() => {
                                setIsPayjpCardFormShown(true);
                            }}>
                            支払い方法を変更
                        </button>
                    </>
                ) : (
                    <PayjpCardForm
                        ref={payjpCardFormRef}
                        onChange={({ complete }) => {
                            setIsCardFormComplete(complete);
                        }}
                    />
                )}
            </section>

            {isPayjpCardFormShown && <PayjpSecurityNote />}

            <div className="flex gap-x-3 md:justify-end">
                <Button
                    type={BUTTON_TYPE.BUTTON}
                    variant={BUTTON_VARIANT.OUTLINED}
                    colorVariant={COLOR_VARIANT.GRAY}
                    className="sm:w-fit sm:min-w-[120px]"
                    onClick={() => {
                        closeDialog(false);
                    }}>
                    戻る
                </Button>
                <Button
                    type={BUTTON_TYPE.BUTTON}
                    className="sm:w-fit sm:min-w-[120px]"
                    disabled={isDisabledSendButton}
                    onClick={() => {
                        void handleSubmit();
                    }}>
                    {submitButtonText}
                </Button>
            </div>
        </div>
    );
};

export default BillingCheckoutPayment;

type OrderSummaryProps = {
    rows: BillingCheckoutOrderRow[];
    note: string;
};

const OrderSummary = ({ rows, note }: OrderSummaryProps) => (
    <section className="rounded-lg border border-gray-border bg-white p-4 shadow-card">
        <h3 className="mb-3 text-lg font-bold">ご注文内容</h3>
        <dl className="flex flex-col gap-y-2">
            {rows.map((row, index) => {
                const isLastRow = index === rows.length - 1;

                return (
                    <div
                        key={row.label}
                        className={`flex items-start justify-between gap-x-4 ${isLastRow
                            ? ''
                            : 'border-b border-gray-border pb-2'
                            }`}>
                        <dt>{row.label}</dt>
                        <dd
                            className={`text-right font-bold ${row.emphasis === 'total' ? 'text-lg' : ''
                                }`}>
                            {row.value}
                        </dd>
                    </div>
                );
            })}
        </dl>
        <p className="mt-3 text-sm leading-relaxed">{note}</p>
    </section>
);

type RegisteredCardProps = {
    billingStatus: IBillingStatus;
};

const RegisteredCard = ({ billingStatus }: RegisteredCardProps) => {
    const cardExpiration = formatCardExpiration(
        billingStatus.pmExpMonth,
        billingStatus.pmExpYear,
    );

    if (!billingStatus.pmLastFour) {
        return null;
    }

    return (
        <div className="rounded-lg border border-gray-border bg-white p-4">
            <p>
                <span className="mr-2">{billingStatus.pmType ?? 'カード'}</span>
                <span className="tracking-wider">
                    {formatMaskedCardNumber(
                        billingStatus.pmLastFour,
                        billingStatus.pmType,
                    )}
                </span>
            </p>
            {cardExpiration && (
                <p className="mt-1">
                    有効期限 {cardExpiration}
                </p>
            )}
        </div>
    );
};

const PayjpSecurityNote = () => (
    <p className="flex gap-x-2 text-xs">
        <Lock
            className="mt-0.5 size-4"
            aria-hidden
        />
        <span>
            カード情報は
            <span> </span>
            <a
                href="https://pay.jp/"
                target="_blank"
                rel="noopener noreferrer"
                className="text-primary-main font-bold underline">
                PAY.JP
            </a>
            <span> </span>
            の決済システムで安全に処理され、当サービスのサーバーには保存されません。
        </span>
    </p>
);
