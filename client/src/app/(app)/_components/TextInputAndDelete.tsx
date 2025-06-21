import { colors } from '@/constants/colors';
import { GripVertical, Trash } from 'lucide-react';
import React from 'react';

interface Props {
    defaultValue: string;
    onUpdate: (name: string) => void;
    onDelete: () => void;
    disabledDeleteButton: boolean;
}

const TextInputAndDelete: React.FC<Props> = ({
    defaultValue,
    onUpdate,
    onDelete,
    disabledDeleteButton,
}) => {
    const inputRef = React.useRef<HTMLInputElement | null>(null);
    const [inputValue, setInputVallue] = React.useState<string>(defaultValue);
    const [isComposing, setIsComposing] = React.useState(false);

    React.useEffect(() => {
        inputRef.current?.focus();
    }, []);

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

    const handleKeyDown = (event: React.KeyboardEvent<HTMLInputElement>) => {
        // スペースキーが押された時、変換中でない場合はデフォルト動作を防ぐ
        if (event.key === ' ' && !isComposing) {
            event.preventDefault();
        }
    };

    const handleCompositionStart = () => {
        setIsComposing(true);
    };

    const handleCompositionEnd = () => {
        setIsComposing(false);
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
                onKeyDown={handleKeyDown}
                onCompositionStart={handleCompositionStart}
                onCompositionEnd={handleCompositionEnd}
            />

            <button
                onClick={onDelete}
                className="p-1 w-fit h-fit rounded-full hover:bg-gray-light transition-colors disabled:opacity-0 disabled:cursor-default"
                disabled={disabledDeleteButton}>
                <Trash color={colors.primary.main} size={28} />
            </button>
        </div>
    );
};

export default TextInputAndDelete;
