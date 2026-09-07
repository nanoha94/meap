import { IImageWithFile } from "@/types";

// プロフィール編集画面のフォーム型
export interface ProfileEditFormData {
    name: string;
    avatarImage: IImageWithFile | null;
}