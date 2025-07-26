import { colors } from '@/constants/colors';
import { GripVertical, Trash } from 'lucide-react';
import { Control, Controller } from 'react-hook-form';
import { TMP_ID_PREFIX } from '../../constants';
import { IRecipeCategory } from '@/types/api/recipe';

interface FormData {
    categories: IRecipeCategory[];
}

interface Props {
    index: number;
    control: Control<FormData>;
    isDisabled?: boolean;
    onDelete: () => void;
}

const EditItem: React.FC<Props> = ({
    index,
    control,
    isDisabled = false,
    onDelete,
}) => {
    const prefix = TMP_ID_PREFIX.RECIPE_CATEGORY;

    return (
        <div className="flex items-center gap-x-2">
            <GripVertical color={colors.gray.main} />
            <Controller
                control={control}
                name={`categories.${index}.name`}
                render={({ field }) => (
                    <input
                        {...field}
                        data-item-id={`${prefix}${index}`}
                        type="text"
                        placeholder="カテゴリー名を入力"
                        autoFocus
                        className="py-2 px-4 flex-1 outline-none bg-white rounded-lg border border-gray-main"
                    />
                )}
            />
            <button
                type="button"
                onClick={onDelete}
                className="p-1 w-fit h-fit rounded-full hover:bg-gray-light transition-colors disabled:opacity-0 disabled:cursor-default"
                disabled={isDisabled}>
                <Trash color={colors.primary.main} size={28} />
            </button>
        </div>
    );
};

export default EditItem;
