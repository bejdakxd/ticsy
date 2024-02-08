<x-field-layout :field="$field">
    <div
        wire:key="{{ rand() }}"
        class="relative {{ $field->width }}"
        x-data="{
            error: @json($errors->has($field->name)),
            disabled: @json($field->isDisabled())
        }"
        >
            <textarea
                x-data
                x-autosize
                rows="3"
                @click="error = false"
                :class="error ? '{{ 'ring-1 ring-red-500 ' }}' : ''"
                class="{{ 'w-full ' . $field->style() }}"
                wire:model.lazy="{{ $field->wireModel }}"
                placeholder="{{ $field->placeholder }}"
                {{ ($field->isDisabled()) ? ' disabled' : '' }}
            >{{ $field->value }}</textarea>
        </div>
</x-field-layout>