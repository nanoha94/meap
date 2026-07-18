'use client';

import React from 'react';
import { Brain, Copy, Save, Trash2 } from 'lucide-react';
import { useRouter } from 'next/navigation';
import { Controller, FormProvider } from 'react-hook-form';

import {
    AiUsageLimitUpsell,
    Header,
    HeaderTextButton,
    ImageEditField,
    StyledSelect,
    TextButton,
    VerticalRowField,
} from '@/components';
import { BUTTON_TYPE, COLOR_VARIANT, LINK_TO, colors } from '@/constants';
import { useAlertDialog, useNavigationGuard, useSnackbars, useTextCopy } from '@/hooks';
import {
    CategoryEditFields,
    IngredientEditFields,
    RECIPE_ALERT_DIALOG_CONFIGS,
    StepEditFields,
    useRecipeAiApi,
    useRecipeAiImport,
    useRecipeApi,
    useRecipeEditForm,
} from '@/models/recipe';
import { useUserStore } from '@/models/user';
import { useAiUsageStore } from '@/stores';
import { ActionButton, IRecipe } from '@/types';
import { isAiLimitReached as isAiLimitReachedUtil } from '@/utils';


interface Props {
    fetchedRecipe?: IRecipe;
    errorMessage?: string;
}

const lineTitleWrapperStyle =
    "relative w-full mx-auto flex justify-center after:content-[''] after:absolute after:top-1/2 after:inset-x-0 after:translate-y-[-50%] after:block after:h-[1px] after:bg-gray-main";

const lineTitleStyle = 'z-10 px-5 text-xl md:text-2xl bg-primary-background';

const RecipeEditPage = ({
    fetchedRecipe,
    errorMessage,
}: Props) => {
    // store
    const loginUser = useUserStore(state => state.loginUser);
    const users = useUserStore(state => state.users);
    const aiUsageStatus = useAiUsageStore(state => state.aiUsageStatus);

    // hook
    const { addSnackbar } = useSnackbars();
    const { openAlertDialog } = useAlertDialog();
    const { deleteRecipe } = useRecipeApi();
    const { parseRecipeFromImage } = useRecipeAiApi();
    const { convertToFormData, applyParsedRecipeToForm } = useRecipeAiImport();
    const {
        control,
        methods,
        onSubmit,
        errors,
        isDisabledSendButton,
    } = useRecipeEditForm(
        fetchedRecipe?.ownerUserId || loginUser.id,
        fetchedRecipe,
    );
    const { isTextCopied, copyToClipboard } = useTextCopy();
    const router = useRouter();
    const aiImportFileInputRef = React.useRef<HTMLInputElement>(null);
    useNavigationGuard(!isDisabledSendButton);

    const isAiLimitReached = isAiLimitReachedUtil(aiUsageStatus);

    const LoadRecipeAiButton = React.useMemo(() => (
        <div className='w-full flex flex-col gap-y-1'>
            <TextButton
                type={BUTTON_TYPE.BUTTON}
                colorVariant={COLOR_VARIANT.SECONDARY}
                className="w-full justify-center"
                disabled={isAiLimitReached}
                onClick={() => aiImportFileInputRef.current?.click()}>
                <Brain size={20} strokeWidth={2} />
                <span className='mb-1'>[AI] 画像からレシピを読み込む</span>
            </TextButton>
            {isAiLimitReached ? (
                <AiUsageLimitUpsell />
            ) : aiUsageStatus && (
                <p className="ml-auto flex flex-wrap justify-end text-sm text-alert-main">
                    <span>※AI利用回数</span>
                    <span>（月間残り{aiUsageStatus.monthlyRemaining}/{aiUsageStatus.monthlyLimit}回
                        {aiUsageStatus.packRemaining >= 0
                            ? `、買い切り残り ${aiUsageStatus.packRemaining} 回`
                            : ''}）
                    </span>
                </p>
            )}
        </div>
    ), [isAiLimitReached, aiUsageStatus]);

    /**
     * AI 読み込みで上書きされる項目に入力済みの内容があるか判定する
     * （memo / url / thumbnail / categories は上書きしない）
     */
    const hasFormContentForAiImport = React.useCallback((): boolean => {
        const values = methods.getValues();
        return !!(
            values.name?.trim() ||
            values.servingCount ||
            values.ingredients?.some(ingredient => ingredient.name?.trim()) ||
            values.steps?.some(
                step => step.instruction?.trim() || step.image?.src,
            )
        );
    }, [methods]);

    /**
     * 選択した画像を AI 解析し、結果をフォームへ反映する
     */
    const executeAiImport = React.useCallback(
        async (file: File) => {
            const parsed = await parseRecipeFromImage(file);
            if (!parsed) {
                return;
            }

            const formData = convertToFormData(parsed);
            applyParsedRecipeToForm(formData, methods);
        },
        [parseRecipeFromImage, convertToFormData, applyParsedRecipeToForm, methods],
    );

    /**
     * AI 読み込み用の画像選択後の処理
     */
    const handleAiImportFileSelected = React.useCallback(
        (file: File) => {
            const proceed = () => {
                void executeAiImport(file);
            };

            if (hasFormContentForAiImport()) {
                openAlertDialog(
                    RECIPE_ALERT_DIALOG_CONFIGS.aiImportOverwrite(),
                    proceed,
                );
                return;
            }

            proceed();
        },
        [hasFormContentForAiImport, openAlertDialog, executeAiImport],
    );

    /**
     * AI 読み込み用ファイル input の change イベント
     */
    const handleAiImportFileChange = React.useCallback(
        (event: React.ChangeEvent<HTMLInputElement>) => {
            const file = event.target.files?.[0];
            if (aiImportFileInputRef.current) {
                aiImportFileInputRef.current.value = '';
            }
            if (!file) {
                return;
            }

            handleAiImportFileSelected(file);
        },
        [handleAiImportFileSelected],
    );

    /**
     * ヘッダーのアクションボタン設定
     */
    const headerActionButtonConfigs: ActionButton[] = fetchedRecipe?.ownerUserId === loginUser?.id ? [
        // 削除できるのは、編集責任者のみ
        {
            label: '削除する',
            icon: <Trash2 size={20} strokeWidth={2} />,
            onClick: () => openAlertDialog(
                RECIPE_ALERT_DIALOG_CONFIGS.deleteItem(fetchedRecipe.name),
                async () => {
                    const success = await deleteRecipe(fetchedRecipe.id);
                    if (success) {
                        router.push(LINK_TO.RECIPE.TOP);
                    }
                },
            ),
            color: COLOR_VARIANT.ALERT,
        },
    ] : [];


    /**
     * エラーメッセージを表示
     * @returns void
     */
    React.useEffect(() => {
        if (errorMessage) {
            addSnackbar('error', errorMessage);
        }
    }, [errorMessage, addSnackbar]);

    return (
        <>
            <Header
                maxWidth="1200px"
                hasBackButton={true}
                onBackClick={() => router.back()}
                leftContent={
                    <div className="items-center gap-x-4 whitespace-nowrap w-[300px] hidden md:flex">
                        <span>編集責任者</span>
                        <Controller
                            control={control}
                            name="ownerUserId"
                            render={({ field: { value, onChange } }) => (
                                <StyledSelect
                                    value={value}
                                    name="ownerUserId"
                                    options={users}
                                    isShowPlaceholder={false}
                                    onChange={onChange}
                                />
                            )}
                        />
                    </div>
                }
                rightContent={
                    <>
                        <HeaderTextButton type={BUTTON_TYPE.SUBMIT} form="recipe-edit-form" colorVariant={COLOR_VARIANT.SECONDARY} disabled={isDisabledSendButton}>
                            <Save size={20} strokeWidth={2} />
                            保存
                        </HeaderTextButton>
                        {/* TODO: 外部公開 */}
                        {/* <HeaderTextButton colorVariant="gray"
                        onClick={() => {
                            console.log('save');
                        }}>
                        <Earth size={20} strokeWidth={2} />
                        外部公開
                    </HeaderTextButton> */}
                    </>
                }
                actionButtons={headerActionButtonConfigs}
            />
            <main className="pb-[60px] max-w-[1200px] mx-auto overflow-x-hidden">
                <FormProvider {...methods}>
                    <form
                        id="recipe-edit-form"
                        onSubmit={onSubmit}
                    >
                        {/* サムネイル画像 */}
                        <div className="flex flex-col items-center gap-y-3 md:hidden">
                            <ImageEditField control={control} name="thumbnail" />
                        </div>
                        <div className="pt-5 px-5 md:px-10 grid grid-cols-1 md:grid-cols-2 gap-10 md:gap-14">
                            {/* サムネイル画像 */}
                            <div className="min-w-0 flex-col items-center gap-y-3 hidden md:flex">
                                <ImageEditField control={control} name="thumbnail" />
                                {LoadRecipeAiButton}
                                <input
                                    ref={aiImportFileInputRef}
                                    type="file"
                                    accept="image/jpeg,image/png,image/webp"
                                    hidden
                                    onChange={handleAiImportFileChange}
                                />
                            </div>
                            <div className="flex-1 flex flex-col gap-y-8">
                                <div className="md:hidden">{LoadRecipeAiButton}</div>
                                {/* 料理名 */}
                                <VerticalRowField
                                    control={control}
                                    name="name"
                                    label="料理名">
                                    {({ value, onChange, id }) => (
                                        <input
                                            id={id}
                                            type="text"
                                            value={(value as string) ?? ''}
                                            placeholder="料理名を入力"
                                            onChange={e => onChange(e)}
                                            className="py-2 px-4 w-full min-w-0 border rounded-lg "
                                        />
                                    )}
                                </VerticalRowField>
                                {/* カテゴリー */}
                                <CategoryEditFields control={control} />
                                {/* メモ */}
                                <VerticalRowField
                                    control={control}
                                    name="memo"
                                    label="メモ"
                                // TODO: 外部公開機能実装時にコメントアウトを解除
                                // memo="※外部には公開されません"
                                >
                                    {({ value, onChange }) => (
                                        <textarea
                                            value={(value as string) ?? ''}
                                            rows={5}
                                            placeholder="メモを入力"
                                            onChange={e => onChange(e)}
                                            className="py-2 px-4 w-full min-w-0 border rounded-lg"
                                        />
                                    )}
                                </VerticalRowField>
                            </div>
                            <div className="flex-1 flex flex-col gap-y-8 min-w-0">
                                <div className={lineTitleWrapperStyle}>
                                    <span className={lineTitleStyle}>材料</span>
                                </div>
                                {/* 分量目安 */}
                                <VerticalRowField
                                    control={control}
                                    name="servingCount"
                                    label="分量目安">
                                    {({ value, onChange, id }) => (
                                        <div className="flex items-center gap-x-2 min-w-0">
                                            <input
                                                id={id}
                                                type="number"
                                                value={(value as string) ?? ''}
                                                min={1}
                                                placeholder="分量目安を入力"
                                                onChange={e => onChange(e)}
                                                className="py-2 px-4 flex-1 min-w-0 border rounded-lg"
                                            />
                                            人分
                                        </div>
                                    )}
                                </VerticalRowField>
                                {/* 材料 */}
                                <IngredientEditFields control={control} errors={errors} />
                            </div>
                            <div className="flex-1 flex flex-col gap-y-8 min-w-0">
                                <div className={lineTitleWrapperStyle}>
                                    <span className={lineTitleStyle}>作り方</span>
                                </div>
                                {/* レシピURL */}
                                <VerticalRowField
                                    control={control}
                                    name="url"
                                    label="レシピURL"
                                // TODO: 外部公開機能実装時にコメントアウトを解除
                                // memo="※外部に公開する際には空にしてください"
                                >
                                    {({ value, onChange, id }) => (
                                        <div className="flex flex-col gap-y-2 min-w-0">
                                            <div className="flex items-center gap-x-2 min-w-0">
                                                <input
                                                    id={id}
                                                    type="text"
                                                    value={(value as string) ?? ''}
                                                    placeholder="レシピURLを入力"
                                                    onChange={e => onChange(e)}
                                                    className="py-2 px-4 flex-1 min-w-0 border rounded-lg "
                                                />
                                                <button
                                                    type="button"
                                                    onClick={() =>
                                                        copyToClipboard(value as string)
                                                    }
                                                    className="p-1 w-fit h-fit rounded-full hover:bg-gray-light transition-colors disabled:opacity-0  disabled:cursor-default"
                                                    disabled={
                                                        !value ||
                                                        value === '' ||
                                                        value?.toString()?.length <= 0
                                                    }>
                                                    <Copy
                                                        size={28}
                                                        color={colors.primary.main}
                                                    />
                                                </button>
                                            </div>
                                            {isTextCopied && (
                                                <div className="min-h-[1.5rem]">
                                                    <p className="text-alert-main">
                                                        レシピURLをコピーしました
                                                    </p>
                                                </div>
                                            )}
                                        </div>
                                    )}
                                </VerticalRowField>
                                {/* 手順 */}
                                <StepEditFields control={control} errors={errors} />
                            </div>
                        </div>
                    </form>
                </FormProvider>
            </main>
        </>
    );
};

export default RecipeEditPage;
