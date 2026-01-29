import { COLOR_VARIANT } from "@/constants";
import { LucideProps } from "lucide-react";

export interface ActionButton {
    label: string;
    icon: React.ReactElement<LucideProps>;
    onClick: () => void;
    color?: (typeof COLOR_VARIANT)['ALERT'];
}
