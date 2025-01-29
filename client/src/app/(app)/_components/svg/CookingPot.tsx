import { colors } from '@/constants/colors';

interface Props {
    strokeColor?: string;
    fillColor?: string;
}

const CookingPot = ({
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
                d="M30.25 18V30C30.25 30.7956 29.9339 31.5587 29.3713 32.1213C28.8087 32.6839 28.0456 33 27.25 33H9.25C8.45435 33 7.69129 32.6839 7.12868 32.1213C6.56607 31.5587 6.25 30.7956 6.25 30V18"
                fill={fillColor}
                className="transition-colors"
            />
            <path
                d="M30.25 18V30C30.25 30.7956 29.9339 31.5587 29.3713 32.1213C28.8087 32.6839 28.0456 33 27.25 33H9.25C8.45435 33 7.69129 32.6839 7.12868 32.1213C6.56607 31.5587 6.25 30.7956 6.25 30V18"
                stroke={strokeColor}
                className="transition-colors"
                strokeWidth="2.5"
                strokeLinecap="round"
                strokeLinejoin="round"
            />
            <path
                d="M3.25 18H33.25"
                stroke={strokeColor}
                className="transition-colors"
                strokeWidth="2.5"
                strokeLinecap="round"
                strokeLinejoin="round"
            />
            <path
                d="M13.54 10.17L12.865 7.455C12.7683 7.07285 12.7478 6.67539 12.8048 6.28533C12.8618 5.89527 12.9951 5.52026 13.1971 5.18175C13.3991 4.84324 13.6658 4.54785 13.982 4.31248C14.2983 4.07711 14.6578 3.90636 15.04 3.81L17.95 3.09C18.3332 2.99353 18.7316 2.9737 19.1224 3.03164C19.5133 3.08959 19.8888 3.22417 20.2275 3.42766C20.5662 3.63115 20.8614 3.89954 21.096 4.21742C21.3307 4.5353 21.5003 4.89641 21.595 5.28L22.27 7.98"
                fill={fillColor}
                className="transition-colors"
            />
            <path
                d="M13.54 10.17L12.865 7.455C12.7683 7.07285 12.7478 6.67539 12.8048 6.28533C12.8618 5.89527 12.9951 5.52026 13.1971 5.18175C13.3991 4.84324 13.6658 4.54785 13.982 4.31248C14.2983 4.07711 14.6578 3.90636 15.04 3.81L17.95 3.09C18.3332 2.99353 18.7316 2.9737 19.1224 3.03164C19.5133 3.08959 19.8888 3.22417 20.2275 3.42766C20.5662 3.63115 20.8614 3.89954 21.096 4.21742C21.3307 4.5353 21.5003 4.89641 21.595 5.28L22.27 7.98"
                stroke={strokeColor}
                strokeWidth="2.5"
                strokeLinecap="round"
                strokeLinejoin="round"
            />
            <path
                d="M6.25 12L30.25 6"
                stroke={strokeColor}
                className="transition-colors"
                strokeWidth="2.5"
                strokeLinecap="round"
                strokeLinejoin="round"
            />
        </svg>
    );
};

export default CookingPot;
