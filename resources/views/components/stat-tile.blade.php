@props(['label', 'value'])

<div {{ $attributes->merge(['class' => 'rounded-xl border border-slate-200/70 bg-white p-4 shadow-sm shadow-slate-900/5']) }}>
    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ $label }}</p>
    <p class="mt-1 text-2xl font-semibold text-slate-900">{{ $value }}</p>
</div>
