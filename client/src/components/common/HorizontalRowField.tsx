import { Control, Controller, FieldValues, Path } from 'react-hook-form';

interface Props<T extends FieldValues> {
    control: Control<T>;
    name: Path<T>;
    label: string;
    children: (fieldProps: {
        value: unknown;
        onChange: (v: unknown) => void;
        id: string;
    }) => React.ReactElement;
}

const HorizontalRowField = <T extends FieldValues>({
    control,
    name,
    label,
    children,
}: Props<T>) => {
    return (
        <div className="grid grid-cols-[80px_1fr] items-center">
            <label className="text-base after:content-['：']" htmlFor={name}>
                {label}
            </label>
            <Controller
                control={control}
                name={name}
                render={({ field: { onChange, value } }) =>
                    children({ value, onChange, id: name })
                }
            />
        </div>
    );
};

export default HorizontalRowField;
