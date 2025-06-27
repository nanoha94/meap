import { colors } from '@/constants/colors';
import { TMP_ID_PREFIX } from '@/constants/ids';
import { IShoppingCategory } from '@/types/api';
import { useSortable } from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import { GripVertical, Trash } from 'lucide-react';
import { Control, Controller } from 'react-hook-form';

interface FormData {
    categories: IShoppingCategory[];
}

interface Props {
    index: number;
    control: Control<FormData>;
    onDelete: () => void;
    isDefault: boolean;
}

const EditItem: React.FC<Props> = ({ index, control, onDelete, isDefault }) => {
    const { attributes, listeners, setNodeRef, transform, transition } =
        useSortable({ id: `${TMP_ID_PREFIX.SHOPPING_CATEGORY}${index}` });

    const style = {
        transform: CSS.Transform.toString(transform),
        transition,
    };

    return (
        <div
            ref={setNodeRef}
            style={style}
            data-item-id={`${TMP_ID_PREFIX.SHOPPING_CATEGORY}${index}`}
            {...attributes}
            {...listeners}>
            {/* <TextInputAndDelete
            control={control}
            name={`categories.${index}.name`}
            onDelete={onDelete}
            disabledDeleteButton={isDefault}
        /> */}
            <div className="flex items-center gap-x-2">
                <GripVertical color={colors.gray.main} />
                <Controller
                    control={control}
                    name={`categories.${index}.name`}
                    render={({ field }) => (
                        <input
                            {...field}
                            type="text"
                            placeholder="カテゴリー名を入力"
                            className="py-2 px-4 flex-1 placeholder:text-gray-main outline-none bg-white rounded-lg border border-gray-main"
                        />
                    )}
                />
                <button
                    type="button"
                    onClick={onDelete}
                    className="p-1 w-fit h-fit rounded-full hover:bg-gray-light transition-colors disabled:opacity-0 disabled:cursor-default"
                    disabled={isDefault}>
                    <Trash color={colors.primary.main} size={28} />
                </button>
            </div>
        </div>
    );
};

export default EditItem;
