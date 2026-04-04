'use client';

import dayjs, { Dayjs } from "dayjs";
import React from "react";
import { useForm, useWatch } from "react-hook-form";

import { StyledDatePicker } from "@/components/form-fields";
import { VerticaFromToField } from "@/components/react-hook-form";
import { MealPlanFilterFormData } from "@/models/meal";

interface Props {
    search: (filterOptions: MealPlanFilterFormData) => void;
    updateDateList: (dateList: Dayjs[]) => void;
}

const MealPlanSearchForm: React.FC<Props> = ({ search, updateDateList }) => {
    const { control, getValues, trigger, formState: { errors }, } = useForm<MealPlanFilterFormData>({
        defaultValues: { dateFrom: dayjs().format('YYYY-MM-DD'), dateTo: dayjs().format('YYYY-MM-DD') },
    });
    const watchedDateFrom = useWatch({ control, name: 'dateFrom' });
    const watchedDateTo = useWatch({ control, name: 'dateTo' });

    const dateList: Dayjs[] = React.useMemo(() => {
        if (!watchedDateFrom || !watchedDateTo) return [];
        const from = dayjs(watchedDateFrom);
        const to = dayjs(watchedDateTo);
        if (!from.isValid() || !to.isValid() || from.isAfter(to)) return [];

        const list: Dayjs[] = [];
        let current = from.startOf('day');
        const end = to.startOf('day');
        while (!current.isAfter(end)) {
            list.push(current.locale('ja'));
            current = current.add(1, 'day');
        }
        return list;
    }, [watchedDateFrom, watchedDateTo]);

    React.useEffect(() => {
        if (!watchedDateFrom || !watchedDateTo) return;

        const run = async () => {
            const isValid = await trigger('dateTo');
            if (isValid) {
                search({ dateFrom: watchedDateFrom, dateTo: watchedDateTo, includeIngredients: true });
                updateDateList(dateList);
            }
        };
        run();
    }, [watchedDateFrom, watchedDateTo]);

    return (
        <VerticaFromToField
            control={control}
            fromName="dateFrom"
            toName="dateTo"
            label="期間で献立の絞り込み"
            getValues={getValues}
            trigger={trigger}
            errorMessage={errors?.dateTo?.message ? [errors.dateTo.message] : undefined}
            rangeValidate={(from, to) => {
                if (!from || !to) return true;
                return from <= to ? true : '終了日は開始日より後の日付にしてください';
            }}>
            {({ value, onChange, hasError, isFrom, pairedFieldValue }) => {
                const dateValue = value && typeof value === 'string' ? new Date(value) : undefined;
                const pairedFieldData = pairedFieldValue && typeof pairedFieldValue === 'string'
                    ? new Date(pairedFieldValue)
                    : undefined;
                return (
                    <StyledDatePicker
                        value={dateValue}
                        minDate={!isFrom ? pairedFieldData : undefined}
                        maxDate={isFrom ? pairedFieldData : undefined}
                        hasClearButton={true}
                        hasError={hasError}
                        onChange={(d) => onChange(d ? dayjs(d).format('YYYY-MM-DD') : '')}
                    />
                );
            }}
        </VerticaFromToField>
    );
};

export default MealPlanSearchForm;