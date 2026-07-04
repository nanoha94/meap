import { COLOR_VARIANT } from "@/constants";

export type LabelColorVariant =
    | (typeof COLOR_VARIANT)['ACCENT']
    | (typeof COLOR_VARIANT)['SECONDARY']
    | (typeof COLOR_VARIANT)['GRAY'];
