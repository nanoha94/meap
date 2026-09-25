'use client';

import React from 'react';
import dayjs from 'dayjs';
import { ChevronDown } from 'lucide-react';
import { useRouter } from 'next/navigation';

import {
    BillingCheckoutPayment,
    BillingFeatureList,
    Button,
    Header,
    PackPurchase,
    PayjpScript,
    PendingPlanChangeNote,
    PlanChangeHelpLink,
    SubscriptionPlanChange,
} from '@/components';
import { BILLING_PLAN_DETAILS, BUTTON_TYPE } from '@/constants';
import { useBillingApi, useDialog } from '@/hooks';
import { useAiUsageStore } from '@/stores';
import {
    IBillingInvoices,
    IBillingPastInvoice,
    IBillingStatus,
    IBillingUpcomingInvoice,
} from '@/types';
import {
    formatCardExpiration,
    formatDisplayDate,
    formatMaskedCardNumber,
    formatYen,
    getNoUpcomingInvoicePendingPlanChangeMessage,
} from '@/utils';

const sectionTitleClassName = 'text-lg';

const cardClassName =
    'rounded-lg bg-white p-5 shadow-card';

const usageRowClassName =
    'pb-1/2 flex items-end justify-between gap-x-4 border-b border-gray-border';

interface Props {
    billingStatus: IBillingStatus | null;
    billingInvoices: IBillingInvoices | null;
}

const BillingPage = ({ billingStatus, billingInvoices }: Props) => {
    return (
        <>
            <PayjpScript />
            <Header title="プラン管理" hasBackButton={true} />
            <main className="p-5 pb-[60px] md:px-10 max-w-[1000px] mx-auto flex flex-col gap-y-6">
                {/* 現在のプラン */}
                <CurrentPlanSection billingStatus={billingStatus} />
                {/* AI 利用状況 */}
                <AiUsageSection />
                {/* 支払い設定 */}
                <PaymentSettingsSection
                    billingStatus={billingStatus}
                />
                {/* 次回のお支払い予定 */}
                <UpcomingInvoiceSection
                    upcomingInvoice={billingInvoices?.upcomingInvoice ?? null}
                    billingStatus={billingStatus}
                />
                {/* 請求履歴 */}
                <PastInvoicesSection
                    pastInvoices={billingInvoices?.pastInvoices ?? []}
                />
            </main>
        </>
    );
};

export default BillingPage;

interface CurrentPlanSectionProps {
    billingStatus: IBillingStatus | null;
}

const CurrentPlanSection = ({
    billingStatus,
}: CurrentPlanSectionProps) => {
    const planDetail = billingStatus
        ? BILLING_PLAN_DETAILS[billingStatus.plan]
        : null;

    return (
        <section className={cardClassName}>
            {planDetail && billingStatus && (
                <div className='flex flex-col gap-y-4'>
                    <div className="flex items-start justify-between gap-x-4">
                        <div>
                            <span className="mb-1 text-sm text-gray-main">
                                現在のプラン
                            </span>
                            <h2 className="text-xl font-bold">
                                {planDetail.label}
                            </h2>
                        </div>
                        <div className="pc-only-sm">
                            <PlanManageButtons
                                billingStatus={billingStatus}
                            />
                        </div>
                    </div>
                    <ul className="flex flex-col gap-y-2 text-base leading-relaxed">
                        <li>
                            ・月間 AI 使用回数：
                            {planDetail.monthlyCredits} 回
                        </li>
                        <li>
                            <span>・使用可能な AI 機能</span>
                            <BillingFeatureList
                                features={planDetail.features}
                                variant="bullet"
                            />
                        </li>
                    </ul>
                    {billingStatus.pendingPlanChange && (
                        <PendingPlanChangeNote
                            currentPlanLabel={planDetail.label}
                            pendingPlanChange={
                                billingStatus.pendingPlanChange
                            }
                            size="base"
                        />
                    )}
                    <PlanChangeHelpLink />
                </div>
            )}
            <div className="mt-4 sp-only-sm">
                <PlanManageButtons billingStatus={billingStatus} />
            </div>
        </section>
    );
};

const AiUsageSection = () => {
    const aiUsageStatus = useAiUsageStore(state => state.aiUsageStatus);

    return (
        <section className={cardClassName}>
            <h2 className={`${sectionTitleClassName} mb-4`}>AI 利用状況</h2>
            {aiUsageStatus && (
                <dl className="flex flex-col gap-y-2">
                    <div className={usageRowClassName}>
                        <dt className="text-base">
                            月間枠残り<br className="sm:hidden" />
                            {aiUsageStatus.resetsAt && (
                                <>
                                    （
                                    {formatDisplayDate(aiUsageStatus.resetsAt)}{' '}
                                    に {aiUsageStatus.monthlyLimit}
                                    回にリセットされます）
                                </>
                            )}
                        </dt>
                        <dd className="text-base text-nowrap">
                            {aiUsageStatus.monthlyRemaining} /{' '}
                            {aiUsageStatus.monthlyLimit} 回
                        </dd>
                    </div>
                    <div className={usageRowClassName}>
                        <dt className="text-base">買い切りパック残り</dt>
                        <dd className="text-base text-nowrap">
                            {aiUsageStatus.packRemaining} 回
                        </dd>
                    </div>
                </dl>
            )}
        </section>
    );
};

interface PlanManageButtonsProps {
    billingStatus: IBillingStatus | null;
}

const PlanManageButtons = ({
    billingStatus,
}: PlanManageButtonsProps) => {
    const { openDialog } = useDialog();

    const handleOpenPlanChangeDialog = React.useCallback(() => {
        openDialog({
            title: 'プランを選択',
            children: (
                <SubscriptionPlanChange
                    billingStatus={billingStatus}
                />
            ),
            maxWidth: 800,
        });
    }, [openDialog, billingStatus]);

    const handleOpenPackPurchaseDialog = React.useCallback(() => {
        openDialog({
            title: '買い切りパックを購入',
            children: <PackPurchase billingStatus={billingStatus} />,
            maxWidth: 800,
        });
    }, [billingStatus, openDialog]);

    return (
        <div className="flex gap-x-3">
            <Button
                type={BUTTON_TYPE.BUTTON}
                onClick={handleOpenPlanChangeDialog}
                className="text-nowrap !w-fit">
                プランを変更
            </Button>
            <Button
                type={BUTTON_TYPE.BUTTON}
                onClick={handleOpenPackPurchaseDialog}
                className="text-nowrap !w-fit">
                買い切りパックを購入
            </Button>
        </div>
    );
};

interface PaymentSettingsSectionProps {
    billingStatus: IBillingStatus | null;
}

const PaymentSettingsSection = ({
    billingStatus,
}: PaymentSettingsSectionProps) => {
    const router = useRouter();
    const { closeAllDialogs, openDialog } = useDialog();
    const { updateCard } = useBillingApi();

    const cardExpiration = billingStatus
        ? formatCardExpiration(
            billingStatus.pmExpMonth,
            billingStatus.pmExpYear,
        )
        : null;

    const canChangePaymentMethod =
        billingStatus?.isSubscribed ||
        billingStatus?.pendingPlanChange != null ||
        billingStatus?.pmLastFour;

    const handleOpenCardUpdateDialog = React.useCallback(() => {
        if (!billingStatus) {
            return;
        }

        openDialog({
            title: '支払い方法を変更',
            children: (
                <BillingCheckoutPayment
                    billingStatus={billingStatus}
                    forceNewCard
                    orderRows={[]}
                    orderNote=""
                    submitButtonText="カードを更新"
                    onSubmit={async (cardToken) => {
                        if (!cardToken) {
                            return false;
                        }
                        return updateCard(cardToken);
                    }}
                    onSuccess={() => {
                        router.refresh();
                        closeAllDialogs();
                    }}
                />
            ),
            maxWidth: 480,
        });
    }, [billingStatus, closeAllDialogs, openDialog, router, updateCard]);

    return (
        <section className={cardClassName}>
            <div className="flex items-start justify-between gap-x-4">
                <h2 className={`${sectionTitleClassName} mb-4`}>支払い設定</h2>
                <div className="pc-only-sm">
                    {canChangePaymentMethod && (
                        <Button
                            type={BUTTON_TYPE.BUTTON}
                            className='!w-fit'
                            onClick={handleOpenCardUpdateDialog}
                        >
                            支払い方法を変更
                        </Button>
                    )}
                </div>
            </div>
            <div className="flex items-start justify-between gap-x-4">
                {billingStatus?.pmLastFour ? (
                    <div className="flex flex-col gap-y-1">
                        <p>
                            <span className="mr-2">
                                {billingStatus.pmType ?? 'カード'}
                            </span>
                            <span className="tracking-wider">
                                {formatMaskedCardNumber(
                                    billingStatus.pmLastFour,
                                    billingStatus.pmType,
                                )}
                            </span>
                        </p>
                        {cardExpiration && (
                            <p className="text-base">
                                有効期限 {cardExpiration}
                            </p>
                        )}
                    </div>
                ) : (
                    <p className="text-gray-main">
                        支払い方法が設定されていません
                    </p>
                )}

            </div>
            <div className="sp-only-sm mt-4">
                {canChangePaymentMethod && (
                    <Button
                        type={BUTTON_TYPE.BUTTON}
                        className='!w-fit'
                        onClick={handleOpenCardUpdateDialog}
                    >
                        支払い方法を変更
                    </Button>
                )}
            </div>
        </section>
    );
};

interface UpcomingInvoiceSectionProps {
    upcomingInvoice: IBillingUpcomingInvoice | null;
    billingStatus: IBillingStatus | null;
}

const UpcomingInvoiceSection = ({
    upcomingInvoice,
    billingStatus,
}: UpcomingInvoiceSectionProps) => {
    const pendingPlanChange = billingStatus?.pendingPlanChange ?? null;

    return (
        <section className={cardClassName}>
            <h2 className={`${sectionTitleClassName} mb-4`}>
                次回のお支払い予定
            </h2>
            {upcomingInvoice ? (
                <div className="flex flex-col gap-y-4">
                    <p>{formatDisplayDate(upcomingInvoice.date)}</p>
                    {upcomingInvoice.lines.length > 0 && (
                        <dl className="flex flex-col gap-y-2">
                            {upcomingInvoice.lines.map((line, index) => (
                                <div
                                    key={`${line.description}-${index}`}
                                    className="flex items-start justify-between gap-x-5 border-b border-gray-border pb-1/2">
                                    <dt>
                                        {line.description}
                                    </dt>
                                    <dd>
                                        {formatYen(line.amount)}
                                    </dd>
                                </div>
                            ))}
                        </dl>
                    )}
                    <dl className="ml-auto flex w-fit min-w-[180px] flex-col gap-y-1">
                        <div className="flex items-center justify-between gap-x-8">
                            <dt>小計</dt>
                            <dd>{formatYen(upcomingInvoice.subtotal)}</dd>
                        </div>
                        {upcomingInvoice.tax > 0 && (
                            <>
                                <div className="flex items-center justify-between gap-x-8">
                                    <dt>合計（税抜き）</dt>
                                    <dd>{formatYen(upcomingInvoice.subtotalExcludingTax)}</dd>
                                </div>
                                <div className="flex items-center justify-between gap-x-8">
                                    <dt>消費税（10%）</dt>
                                    <dd>{formatYen(upcomingInvoice.tax)}</dd>
                                </div>
                            </>
                        )}
                        <div className="flex items-center justify-between gap-x-8">
                            <dt>合計</dt>
                            <dd>{formatYen(upcomingInvoice.total)}</dd>
                        </div>
                        <div className="flex items-center justify-between gap-x-8 font-bold">
                            <dt>お支払い金額</dt>
                            <dd>{formatYen(upcomingInvoice.amountDue)}</dd>
                        </div>
                    </dl>
                </div>
            ) : pendingPlanChange ? (
                <p>
                    {getNoUpcomingInvoicePendingPlanChangeMessage(
                        pendingPlanChange.changesAt,
                    )}
                </p>
            ) : (
                <p>
                    予定されているお支払いはありません
                </p>
            )}
        </section>
    );
};

interface PastInvoicesSectionProps {
    pastInvoices: IBillingPastInvoice[];
}

const PastInvoicesSection = ({ pastInvoices }: PastInvoicesSectionProps) => {
    const [selectedMonth, setSelectedMonth] = React.useState('');

    /**
     * 請求履歴を月ごとにグループ化
     */
    const invoicesByMonth = React.useMemo(() => {
        const grouped = new Map<string, IBillingPastInvoice[]>();
        for (const invoice of pastInvoices) {
            const key = dayjs(invoice.date).format('YYYY-MM');
            const list = grouped.get(key) ?? [];
            list.push(invoice);
            grouped.set(key, list);
        }
        return grouped;
    }, [pastInvoices]);

    /**
     * 月のリスト
     */
    const months = React.useMemo(
        () => Array.from(invoicesByMonth.keys()).sort().reverse(),
        [invoicesByMonth],
    );

    /**
     * 選択された月
     */
    const activeMonth = months.includes(selectedMonth)
        ? selectedMonth
        : months[0] ?? '';

    /**
     * 選択された月の請求履歴
     */
    const filteredInvoices: IBillingPastInvoice[] = invoicesByMonth.get(activeMonth) ?? [];

    return (
        <section className={cardClassName}>
            <div className="mb-4 flex items-center justify-between">
                <h2 className={sectionTitleClassName}>請求履歴</h2>
                {months.length > 0 && (
                    <div className="relative">
                        <select
                            value={activeMonth}
                            onChange={e => setSelectedMonth(e.target.value)}
                            className="cursor-pointer appearance-none rounded-lg border border-gray-main bg-white py-1.5 pl-3 pr-8 text-base">
                            {months.map(month => (
                                <option key={month} value={month}>
                                    {dayjs(month).format('YYYY年M月')}
                                </option>
                            ))}
                        </select>
                        <div className="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2">
                            <ChevronDown size={16} aria-hidden="true" />
                        </div>
                    </div>
                )}
            </div>
            {filteredInvoices.length > 0 ? (
                <ul className="flex flex-col gap-y-2">
                    {filteredInvoices.map(invoice => (
                        <li
                            key={invoice.id}
                            className="pb-1/2 flex items-end justify-between gap-x-4 border-b border-gray-border">
                            <div className="flex flex-col">
                                <span>{formatDisplayDate(invoice.date)}</span>
                                <span className='text-sm'>
                                    {invoice.description}
                                </span>
                            </div>
                            <span className="text-nowrap">{formatYen(invoice.total)}</span>
                        </li>
                    ))}
                </ul>
            ) : (
                <p>
                    この月の請求はありません
                </p>
            )}
        </section>
    );
};
