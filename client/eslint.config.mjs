import eslintConfigNext from "eslint-config-next/core-web-vitals";
import eslintConfigPrettier from "eslint-config-prettier/flat";
import tseslint from "typescript-eslint";

const eslintConfig = [
    ...eslintConfigNext,
    ...tseslint.configs.recommended,
    eslintConfigPrettier,
    {
        rules: {
            // Next / React Compiler と相性が悪く、実務上許容できるパターンが error になりやすいため warn に緩める
            "react-hooks/set-state-in-effect": "warn",
            "react-hooks/incompatible-library": "warn",
            "react-hooks/purity": "warn",
            "react-hooks/immutability": "warn",
            "react-hooks/refs": "warn",
            // 既存コードベースの依存配列は段階的に整える（CI を継続可能にする）
            "react-hooks/exhaustive-deps": "warn",

            "no-console": ["warn", { allow: ["error"] }],
            "no-nested-ternary": 0,
            "no-underscore-dangle": 0,
            "no-unused-expressions": ["error", { allowTernary: true }],
            camelcase: 0,
            "react/self-closing-comp": 1,
            "react/jsx-filename-extension": [
                1,
                { extensions: [".js", ".jsx", ".ts", ".tsx"] },
            ],
            "react/prop-types": 0,
            "react/destructuring-assignment": 0,
            "react/jsx-no-comment-textnodes": 0,
            "react/jsx-props-no-spreading": 0,
            "react/no-array-index-key": 0,
            "react/no-unescaped-entities": 0,
            "react/require-default-props": 0,
            "react/react-in-jsx-scope": 0,
            "linebreak-style": ["error", "unix"],
            semi: ["error", "always"],
        },
    },
];

export default eslintConfig;
