import { colors } from '@/constants/colors';
import { TMP_ID_PREFIX } from '@/constants/tmpIdPrefix';
import { IIngredientCategory } from '@/types/api/ingredient';
import { GripVertical, Trash } from 'lucide-react';
import { Control, Controller } from 'react-hook-form';

interface FormData {
    categories: IIngredientCategory[];
}

interface Props {
    index: number;
    control: Control<FormData>;
    onDelete: () => void;
}

const EditItem: React.FC<Props> = ({ index, control, onDelete }) => {
    return (
        <div className="flex items-center gap-x-2">
            <GripVertical color={colors.gray.main} />
            <Controller
                control={control}
                name={`categories.${index}.name`}
                render={({ field }) => (
                    <input
                        {...field}
                        data-item-id={`${TMP_ID_PREFIX.SHOPPING_CATEGORY}${index}`}
                        type="text"
                        placeholder="カテゴリー名を入力"
                        className="py-2 px-4 flex-1 outline-none bg-white rounded-lg border border-gray-main"
                    />
                )}
            />
            <button
                type="button"
                onClick={onDelete}
                className="p-1 w-fit h-fit rounded-full hover:bg-gray-light transition-colors disabled:opacity-0">
                <Trash color={colors.primary.main} size={28} />
            </button>
        </div>
    );
};

export default EditItem;
