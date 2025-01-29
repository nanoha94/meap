import { colors } from '@/constants/colors';

interface Props {
    strokeColor?: string;
    fillColor?: string;
}

const CalendarDate = ({
    strokeColor = colors.black,
    fillColor = colors.gray.iconFill,
}: Props) => {
    return (
        <svg
            width="37"
            height="36"
            viewBox="0 0 37 36"
            fill="none"
            xmlns="http://www.w3.org/2000/svg">
            <path
                d="M12.75 3V9"
                stroke={strokeColor}
                className="transition-colors"
                strokeWidth="2.5"
                strokeLinecap="round"
                strokeLinejoin="round"
            />
            <path
                d="M24.75 3V9"
                stroke={strokeColor}
                className="transition-colors"
                strokeWidth="2.5"
                strokeLinecap="round"
                strokeLinejoin="round"
            />
            <path
                d="M29.25 6H8.25C6.59315 6 5.25 7.34315 5.25 9V30C5.25 31.6569 6.59315 33 8.25 33H29.25C30.9069 33 32.25 31.6569 32.25 30V9C32.25 7.34315 30.9069 6 29.25 6Z"
                fill={fillColor}
                stroke={strokeColor}
                className="transition-colors"
                strokeWidth="2.5"
                strokeLinecap="round"
                strokeLinejoin="round"
            />
            <path
                d="M5.25 15H32.25"
                stroke={strokeColor}
                className="transition-colors"
                strokeWidth="2.5"
                strokeLinecap="round"
                strokeLinejoin="round"
            />
            <path
                d="M12.75 21H12.765"
                stroke={strokeColor}
                className="transition-colors"
                strokeWidth="2.5"
                strokeLinecap="round"
                strokeLinejoin="round"
            />
            <path
                d="M18.75 21H18.765"
                stroke={strokeColor}
                className="transition-colors"
                strokeWidth="2.5"
                strokeLinecap="round"
                strokeLinejoin="round"
            />
            <path
                d="M24.75 21H24.765"
                stroke={strokeColor}
                className="transition-colors"
                strokeWidth="2.5"
                strokeLinecap="round"
                strokeLinejoin="round"
            />
            <path
                d="M12.75 27H12.765"
                stroke={strokeColor}
                className="transition-colors"
                strokeWidth="2.5"
                strokeLinecap="round"
                strokeLinejoin="round"
            />
            <path
                d="M18.75 27H18.765"
                stroke={strokeColor}
                className="transition-colors"
                strokeWidth="2.5"
                strokeLinecap="round"
                strokeLinejoin="round"
            />
            <path
                d="M24.75 27H24.765"
                stroke={strokeColor}
                className="transition-colors"
                strokeWidth="2.5"
                strokeLinecap="round"
                strokeLinejoin="round"
            />
        </svg>
    );
};

export default CalendarDate;
