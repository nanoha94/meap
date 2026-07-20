'use client';

import React from 'react';
import { useForm, useWatch } from 'react-hook-form';

import { AiUsageConfirmation, Button, VerticalRowField } from '@/components';
import { BUTTON_TYPE, BUTTON_VARIANT, COLOR_VARIANT } from '@/constants';
import { useAlertDialog, useDialog } from '@/hooks';
import { RECIPE_ALERT_DIALOG_CONFIGS } from '@/models/recipe';
import { IPostAiRecipeParseUrlRequest } from '@/types';

interface Props {
    onImport: (url: string) => Promise<boolean>;
    hasFormContent: () => boolean;
}

type FormData = IPostAiRecipeParseUrlRequest;

/**
 * URL が有効かどうかをチェックする
 * @param value URL
 * @returns URL が有効かどうか
 */
const isValidUrl = (value: string): boolean => {
    try {
        const url = new URL(value);
        return url.protocol === 'http:' || url.protocol === 'https:';
    } catch {
        return false;
    }
};

const RecipeUrlImportForm: React.FC<Props> = ({
    onImport,
    hasFormContent,
}) => {
    const { closeDialog, updateCurrentDialogConfig } = useDialog();
    const { openAlertDialog } = useAlertDialog();
    const { control, handleSubmit, formState: { errors } } = useForm<FormData>({
        defaultValues: { url: '' },
    });
    const watchUrl = useWatch({ control, name: 'url' });

    /**
     * URL からレシピ情報を AI 解析する
     */
    const runImport = React.useCallback(
        async (url: string) => {
            const success = await onImport(url);
            if (success) {
                closeDialog(false);
            }
        },
        [onImport, closeDialog],
    );

    /**
     * フォーム送信
     * @param data フォームデータ
     * @returns フォーム送信
     * @description フォーム送信
     */
    const handleFormSubmit = React.useCallback(
        (data: IPostAiRecipeParseUrlRequest) => {
            const url = data.url.trim();

            if (hasFormContent()) {
                openAlertDialog(
                    RECIPE_ALERT_DIALOG_CONFIGS.aiImportOverwrite('url'),
                    () => {
                        void runImport(url);
                    },
                );
                return;
            }

            void runImport(url);
        },
        [hasFormContent, openAlertDialog, runImport],
    );

    /**
     * 送信ボタンの無効化
     * @returns 送信ボタンの無効化
     * @description 送信ボタンの無効化
     */
    const isDisabledSendButton = React.useMemo(
        () => watchUrl.trim().length <= 0 || !isValidUrl(watchUrl.trim()),
        [watchUrl],
    );

    /**
     * 閉じる前確認の要否をフォーム状態に合わせて更新
     */
    React.useEffect(() => {
        updateCurrentDialogConfig({
            isCheckBeforeClose: watchUrl.trim().length > 0,
        });
    }, [watchUrl, updateCurrentDialogConfig]);

    return (
        <form
            onSubmit={handleSubmit(handleFormSubmit)}
            className="w-full flex flex-col gap-y-10">
            <div className="flex flex-col gap-y-4">
                <p className='text-sm'>テキストで記載されたレシピページにのみ対応しています。<br />YouTube・Instagramなど、動画・画像のみで提供されているページは非対応です。</p>
                <VerticalRowField
                    control={control}
                    name="url"
                    label="レシピページのURL"
                    rules={{
                        required: 'URLを入力してください',
                        validate: value =>
                            isValidUrl(String(value).trim()) ||
                            '有効なURLを入力してください',
                    }}
                    errorMessage={
                        errors.url?.message ? [errors.url.message] : undefined
                    }>
                    {({ value, onChange, id }) => (
                        <input
                            autoFocus
                            id={id}
                            type="url"
                            value={value as string}
                            placeholder="https://example.com/recipe"
                            onChange={e => onChange(e.target.value)}
                            className="py-2 px-4 border rounded-lg border-gray-main"
                        />
                    )}
                </VerticalRowField>
                <AiUsageConfirmation />
            </div>
            <div className="mx-auto w-full flex gap-x-6">
                <Button
                    type={BUTTON_TYPE.BUTTON}
                    colorVariant={COLOR_VARIANT.GRAY}
                    variant={BUTTON_VARIANT.OUTLINED}
                    onClick={() => closeDialog()}>
                    戻る
                </Button>
                <Button type={BUTTON_TYPE.SUBMIT} disabled={isDisabledSendButton}>
                    レシピを読み込む
                </Button>
            </div>
        </form>
    );
};

export default RecipeUrlImportForm;
