@props(['disabled' => false])

<input {{ $disabled ? 'disabled' : '' }} {!! $attributes->merge([
    'class' => 'mt-1 block w-full rounded-lg border-slate-300 shadow-sm transition
        focus:border-brand-500 focus:ring-brand-500 sm:text-sm disabled:bg-slate-50 disabled:text-slate-500',
]) !!}>
