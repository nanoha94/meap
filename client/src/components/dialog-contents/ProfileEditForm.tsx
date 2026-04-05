"use client";
import React from "react";

import { useForm } from "react-hook-form";

import { BUTTON_TYPE } from "@/constants";
import { useDialog, useNavigationGuard } from "@/hooks";
import { iconAvatar, useUserApi, useUserStore } from "@/models/user";
import Button from "../Button";
import { ImageEditField, VerticalRowField } from "../react-hook-form";
import { ProfileEditFormData } from "@/models/user/types";


const ProfileEditForm = () => {
    // store
    const loginUser = useUserStore(state => state.loginUser);

    // hook
    const { closeDialog, updateCurrentDialogConfig } = useDialog();
    const { updateUser } = useUserApi();

    const { control, handleSubmit, watch, reset } = useForm<ProfileEditFormData>({
        defaultValues: {
            name: loginUser.name ?? '',
            avatarImage: loginUser.avatar.image ?? null,
        },
    });

    // loginUserが読み込まれた後に確実にnullを設定
    React.useEffect(() => {
        reset({
            name: loginUser.name ?? '',
            avatarImage: loginUser.avatar.image ?? null,
        });
    }, [loginUser.name, loginUser.avatar.image, reset]);

    const watchedName = watch('name');
    const watchedAvatarImage = watch('avatarImage');

    /**
     * 送信ボタンの無効化判定
     * - 名前が空
     * - または初期値から変更なし（名前・アバターとも同じ）
     */
    const isDisabledSendButton = React.useMemo(() => {
        if (watchedName === '') return true;
        const nameUnchanged = watchedName === (loginUser.name ?? '');
        const avatarUnchanged = watchedAvatarImage?.src === loginUser.avatar.image?.src;
        return nameUnchanged && avatarUnchanged;
    }, [watchedName, watchedAvatarImage, loginUser.name, loginUser.avatar.image]);
    useNavigationGuard(!isDisabledSendButton);

    /**
     * 閉じる前確認の要否をフォーム状態に合わせて更新
     */
    React.useEffect(() => {
        updateCurrentDialogConfig({ isCheckBeforeClose: !isDisabledSendButton });
    }, [isDisabledSendButton, updateCurrentDialogConfig]);

    /**
     * フォームの送信処理
     * @param data フォームのデータ
     */
    const onSubmit = async (data: ProfileEditFormData) => {
        await updateUser({
            name: data.name,
            avatar_image_id: data.avatarImage?.id,
        }, data.avatarImage?.file ?? null);
        closeDialog(false);
    };

    return (
        <form onSubmit={handleSubmit(onSubmit)} className="w-full flex flex-col gap-y-10">
            <div className="mx-auto w-full flex flex-col gap-y-4">
                <div className="relative mx-auto w-[160px] h-auto aspect-square">
                    {!watchedAvatarImage?.src && <div
                        className="absolute top-0 left-0 w-full h-full rounded-full overflow-hidden"
                        dangerouslySetInnerHTML={{
                            __html: iconAvatar(
                                loginUser?.avatar.seed ?? '',
                            ).toString(),
                        }}
                    />}
                    <ImageEditField
                        control={control}
                        name="avatarImage"
                        styleConfig={{
                            iconMdSize: 32,
                            imageRounded: 'rounded-full',
                            containerClass: `aspect-square ${watchedAvatarImage?.src ? 'bg-gray-light' : 'bg-transparent'} `,
                            labelClass: 'gap-y-1 text-white bg-black/40',
                            overlayIconContainerClass: 'gap-x-2.5',
                            overlayIconClass: 'p-1.5',
                        }}
                    />

                </div>
                <VerticalRowField
                    control={control}
                    name="name"
                    label="ユーザー名">
                    {({ value, onChange, id }) => (
                        <input type="text" id={id} value={value as string} placeholder="ユーザー名を入力" onChange={e => onChange(e.target.value)} className="py-2 px-4 border rounded-lg outline-none border-gray-main" />
                    )}
                </VerticalRowField>
            </div>
            <Button type={BUTTON_TYPE.SUBMIT} disabled={isDisabledSendButton}>
                プロフィールを更新する
            </Button>
        </form>
    );
};

export default ProfileEditForm;
