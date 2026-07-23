<button {{ $attributes->merge([
    'type' => 'submit',
    'class' => 'inline-flex w-full justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white
        shadow-sm hover:bg-indigo-500 focus:outline focus:outline-2 focus:outline-offset-2 focus:outline-indigo-600
        disabled:cursor-not-allowed disabled:opacity-60',
]) }}>
    {{ $slot }}
</button>
