interface Props extends React.LabelHTMLAttributes<HTMLLabelElement> {
    className?: string;
    children: React.ReactNode;
}

const Label = ({ className, children, ...props }: Props) => (
    <label className={`${className} block text-sm text-gray-700`} {...props}>
        {children}
    </label>
);

export default Label;
