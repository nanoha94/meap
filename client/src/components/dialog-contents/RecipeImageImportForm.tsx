'use client';

import React from 'react';
import { useForm, useWatch } from 'react-hook-form';

import { AiUsageConfirmation, Button, ImageEditField } from '@/components';
import { BUTTON_TYPE, BUTTON_VARIANT, COLOR_VARIANT } from '@/constants';
import { useAlertDialog, useDialog } from '@/hooks';
import { RECIPE_ALERT_DIALOG_CONFIGS } from '@/models/recipe';
import { IPostAiRecipeParseImageRequest } from '@/types';

interface Props {
    onImport: (file: File) => Promise<boolean>;
    hasFormContent: () => boolean;
}

type FormData = IPostAiRecipeParseImageRequest;

const RecipeImageImportForm: React.FC<Props> = ({
    onImport,
    hasFormContent,
}) => {
    const { closeDialog, updateCurrentDialogConfig } = useDialog();
    const { openAlertDialog } = useAlertDialog();

    const { control, handleSubmit } = useForm<FormData>({
        defaultValues: {
            image: { file: null, src: '', width: 0, height: 0 },
        },
    });

    const imageValue = useWatch({ control, name: 'image' });
    const hasSelectedFile = !!imageValue?.file;

    const srcRef = React.useRef<string | null>(null);

    /**
     * 選択した画像を AI 解析する
     */
    const runImport = React.useCallback(
        async (file: File) => {
            const success = await onImport(file);
            if (success) {
                closeDialog(false);
            }
        },
        [onImport, closeDialog],
    );

    /**
     * フォーム送信
     */
    const onSubmit = React.useCallback(
        ({ image }: FormData) => {
            if (!image.file) {
                return;
            }

            if (hasFormContent()) {
                openAlertDialog(
                    RECIPE_ALERT_DIALOG_CONFIGS.aiImportOverwrite('image'),
                    () => {
                        void runImport(image.file!);
                    },
                );
                return;
            }

            void runImport(image.file);
        },
        [hasFormContent, openAlertDialog, runImport],
    );

    /**
     * 閉じる前確認の要否をフォーム状態に合わせて更新
     */
    React.useEffect(() => {
        updateCurrentDialogConfig({
            isCheckBeforeClose: hasSelectedFile,
        });
    }, [hasSelectedFile, updateCurrentDialogConfig]);

    /**
     * object URL をトラッキングし、アンマウント時に解放
     */
    React.useEffect(() => {
        srcRef.current = imageValue?.src ?? null;
        return () => {
            if (srcRef.current) {
                URL.revokeObjectURL(srcRef.current);
            }
        };
    }, [imageValue?.src]);

    return (
        <form
            onSubmit={handleSubmit(onSubmit)}
            className="w-full flex flex-col gap-y-10">
            <div className="flex flex-col gap-y-4">
                <p className="text-sm">
                    JPEG・PNG・WebP形式の画像に対応しています。
                </p>
                <div className="w-full mx-auto max-w-sm">
                    <ImageEditField
                        control={control}
                        name="image"
                    />
                </div>
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
                <Button
                    type={BUTTON_TYPE.SUBMIT}
                    disabled={!hasSelectedFile}>
                    レシピを読み込む
                </Button>
            </div>
        </form>
    );
};

export default RecipeImageImportForm;
