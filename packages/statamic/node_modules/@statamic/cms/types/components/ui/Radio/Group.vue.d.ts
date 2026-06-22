declare const _default: typeof __VLS_export;
export default _default;
declare const __VLS_export: __VLS_WithSlots<typeof __VLS_base, __VLS_Slots>;
type __VLS_WithSlots<T, S> = T & (new () => {
    $slots: S;
});
declare const __VLS_base: import("vue").DefineComponent<import("vue").ExtractPropTypes<{
    /** When `true`, displays radio buttons horizontally */
    inline: {
        type: BooleanConstructor;
        default: boolean;
    };
    /** The controlled value of the radio group */
    modelValue: {
        type: StringConstructor;
        default: null;
    };
    /** Name attribute for the radio group */
    name: {
        type: StringConstructor;
        default: () => string;
    };
    required: {
        type: BooleanConstructor;
        default: boolean;
    };
}>, {
    focus: () => void;
}, {}, {}, {}, import("vue").ComponentOptionsMixin, import("vue").ComponentOptionsMixin, {
    "update:modelValue": (...args: any[]) => void;
}, string, import("vue").PublicProps, Readonly<import("vue").ExtractPropTypes<{
    /** When `true`, displays radio buttons horizontally */
    inline: {
        type: BooleanConstructor;
        default: boolean;
    };
    /** The controlled value of the radio group */
    modelValue: {
        type: StringConstructor;
        default: null;
    };
    /** Name attribute for the radio group */
    name: {
        type: StringConstructor;
        default: () => string;
    };
    required: {
        type: BooleanConstructor;
        default: boolean;
    };
}>> & Readonly<{
    "onUpdate:modelValue"?: ((...args: any[]) => any) | undefined;
}>, {
    name: string;
    required: boolean;
    modelValue: string;
    inline: boolean;
}, {}, {}, {}, string, import("vue").ComponentProvideOptions, true, {}, any>;
type __VLS_Slots = {
    default?: ((props: {}) => any) | undefined;
};
