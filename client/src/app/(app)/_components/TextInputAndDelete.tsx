import { colors } from '@/constants/colors';
import { GripVertical, Trash } from 'lucide-react';
import React from 'react';

interface Props {
    defaultValue: string;
    onUpdate: (name: string) => void;
    onDelete: () => void;
}

const TextInputAndDelete: React.FC<Props> = ({
    defaultValue,
    onUpdate,
    onDelete,
}) => {
    const inputRef = React.useRef<HTMLInputElement | null>(null);
    const [inputValue, setInputVallue] = React.useState<string>(defaultValue);

    inputRef.current?.focus();

    const handleClickOutside = (event: MouseEvent) => {
        if (
            inputRef.current &&
            !inputRef.current.contains(event.target as Node)
        ) {
            const currentValue = inputRef.current.value;
            if (currentValue !== inputValue) {
                if (currentValue === '') {
                    setInputVallue(defaultValue);
                    return;
                }
                setInputVallue(currentValue);
                onUpdate(inputRef.current.value);
                return;
            }
        }
    };

    React.useEffect(() => {
        document.addEventListener('mousedown', handleClickOutside);
        return () => {
            document.removeEventListener('mousedown', handleClickOutside);
        };
    }, [inputValue]);

    return (
        <div className="flex items-center gap-x-2">
            <GripVertical color={colors.gray.main} />
            <input
                ref={inputRef}
                type="text"
                defaultValue={inputValue}
                placeholder="カテゴリー名を入力"
                className="py-2 px-4 flex-1 placeholder:text-gray-main outline-none bg-white rounded-lg border border-gray-main"
            />
            <button
                onClick={onDelete}
                className="p-1 w-fit h-fit rounded-full hover:bg-gray-light transition-colors">
                <Trash color={colors.primary.main} size={32} />
            </button>
        </div>
    );
};

export default TextInputAndDelete;
