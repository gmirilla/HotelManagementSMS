<button {{ $attributes->merge([
    'type' => 'submit',
    'class' => 'inline-flex w-full justify-center rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white
        shadow-sm shadow-brand-600/20 transition hover:bg-brand-500 hover:shadow-md hover:shadow-brand-600/25
        focus:outline focus:outline-2 focus:outline-offset-2 focus:outline-brand-600
        disabled:cursor-not-allowed disabled:opacity-60',
]) }}>
    {{ $slot }}
</button>
