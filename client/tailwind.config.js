const tailwindConfig = {
    content: ['./src/**/*.{js,jsx,ts,tsx}'],
    theme: {
        colors: {
            transparent: 'transparent',
            white: '#FFFFFF',
            black: '#333333',
            red: '#B32208',
            blue: '#0D0A8A',
            gray: {
                main: '#757575',
                placeholder: '#9e9e9e',
                border: '#BDBDBD',
                iconFill: '#E0E0E0',
                light: '#EEEEEE',
                background: '#F5F5F5',
            },
            primary: {
                main: '#927256',
                light: '#FCE7D0',
                background: '#FEFAF5',
            },
            secondary: {
                dark: '#69753F',
                main: '#7E8B55',
                light: '#CDD59A',
                background: '#F6F8ED',
            },
            accent: {
                main: '#A36062',
                light: '#eed8d8',
                background: '#F6F0F0',
            },

            alert: {
                main: '#CD3429',
                light: '#FECDD0',
                background: '#FFEBED',
            },
            success: {
                main: '#007100',
                background: '#EAFAE8',
            },
            category: {
                yellow: '#F5B12E',
                orange: '#F0762D',
                pink: '#F89DC0',
                red: '#EC3D33',
                'yellow-green': '#88C63C',
                green: '#4FA260',
                'sky-blue': '#439CFE',
                blue: '#2673B8',
                purple: '#6746B9',
            },
        },
        boxShadow: {
            card: '1px 1px 5px rgba(0, 0, 0, 15%)',
        },
    },
    plugins: [],
};

export default tailwindConfig;
