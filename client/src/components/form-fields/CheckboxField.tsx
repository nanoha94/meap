import { colors } from "@/constants";
import { Check } from "lucide-react";

interface Props {
    id: string;
    checked: boolean;
    onChange: () => void;
    label: string;
}

/**
 * ラベルの背景色を返す
 * @param isChecked チェックされているかどうか
 * @returns カテゴリーのラベルの背景色
 */
const wrapperColorClass = (isChecked: boolean) => {
    return isChecked
        ? 'border-primary-main bg-primary-light'
        : 'border-gray-main bg-gray-light';
};

/**
* チェックボックスの背景色を返す
* @param isChecked チェックされているかどうか
* @returns カテゴリーのチェックボックスの背景色
*/
const boxColorClass = (isChecked: boolean) => {
    return isChecked
        ? 'bg-primary-main border-[transparent]'
        : 'bg-white border-gray-main';
};

const CheckboxField = ({ id, checked, onChange, label }: Props) => {
    return (
        <div>
            <input
                type="checkbox"
                id={id}
                checked={checked}
                onChange={() =>
                    onChange()
                }
                className="hidden"
            />
            <label
                htmlFor={id}
                className={`py-1 px-2 w-fit h-full flex items-center gap-x-2 whitespace-nowrap cursor-pointer border rounded ${wrapperColorClass(checked)} transition-opacity hover:opacity-70`}>
                <div
                    className={`relative w-4 h-4 rounded border-[1.5px] transition-colors ${boxColorClass(
                        checked,
                    )}`}>
                    {checked && (
                        <Check
                            strokeWidth={
                                3.5
                            }
                            color={
                                colors.white
                            }
                            size={16}
                            className="absolute top-1/2 -translate-y-1/2 left-0"
                        />
                    )}
                </div>
                {label}
            </label>
        </div>
    );
};

export default CheckboxField;