import { colors } from '@/constants/colors';

interface Props {
    strokeColor?: string;
    fillColor?: string;
}

const BookOpenCheck = ({
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
                d="M33.75 9V6C33.75 5.60218 33.592 5.22064 33.3107 4.93934C33.0294 4.65804 32.6478 4.5 32.25 4.5H24.75C23.1587 4.5 21.6326 5.13214 20.5074 6.25736C19.3821 7.38258 18.75 8.9087 18.75 10.5C18.75 8.9087 18.1179 7.38258 16.9926 6.25736C15.8674 5.13214 14.3413 4.5 12.75 4.5H5.25C4.85218 4.5 4.47064 4.65804 4.18934 4.93934C3.90804 5.22064 3.75 5.60218 3.75 6V25.5C3.75 25.8978 3.90804 26.2794 4.18934 26.5607C4.47064 26.842 4.85218 27 5.25 27H14.25C15.4435 27 16.5881 27.4741 17.432 28.318C18.2759 29.1619 18.75 30.3065 18.75 31.5C18.75 30.3065 19.2241 29.1619 20.068 28.318C20.9119 27.4741 22.0565 27 23.25 27H32.25C32.6478 27 33.0294 26.842 33.3107 26.5607C33.592 26.2794 33.75 25.8978 33.75 25.5V23.55"
                fill={fillColor}
                className="transition-colors"
            />
            <path
                d="M33.75 9V6C33.75 5.60218 33.592 5.22064 33.3107 4.93934C33.0294 4.65804 32.6478 4.5 32.25 4.5H24.75C23.1587 4.5 21.6326 5.13214 20.5074 6.25736C19.3821 7.38258 18.75 8.9087 18.75 10.5C18.75 8.9087 18.1179 7.38258 16.9926 6.25736C15.8674 5.13214 14.3413 4.5 12.75 4.5H5.25C4.85218 4.5 4.47064 4.65804 4.18934 4.93934C3.90804 5.22064 3.75 5.60218 3.75 6V25.5C3.75 25.8978 3.90804 26.2794 4.18934 26.5607C4.47064 26.842 4.85218 27 5.25 27H14.25C15.4435 27 16.5881 27.4741 17.432 28.318C18.2759 29.1619 18.75 30.3065 18.75 31.5C18.75 30.3065 19.2241 29.1619 20.068 28.318C20.9119 27.4741 22.0565 27 23.25 27H32.25C32.6478 27 33.0294 26.842 33.3107 26.5607C33.592 26.2794 33.75 25.8978 33.75 25.5V23.55"
                stroke={strokeColor}
                className="transition-colors"
                strokeWidth="2.5"
                strokeLinecap="round"
                strokeLinejoin="round"
            />
            <path
                d="M18.75 31.5V10.5"
                stroke={strokeColor}
                className="transition-colors"
                strokeWidth="2.5"
                strokeLinecap="round"
                strokeLinejoin="round"
            />
            <path
                d="M24.75 18L27.75 21L33.75 15"
                stroke={strokeColor}
                className="transition-colors"
                strokeWidth="2.5"
                strokeLinecap="round"
                strokeLinejoin="round"
            />
        </svg>
    );
};

export default BookOpenCheck;
