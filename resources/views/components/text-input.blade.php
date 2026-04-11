@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'w-full px-4 py-3 rounded-xl border-2 border-gray-200 text-gray-900 font-medium text-base placeholder:text-gray-400 focus:border-navy-500 focus:ring-2 focus:ring-navy-100 disabled:bg-gray-50 disabled:text-gray-400 disabled:cursor-not-allowed transition-colors duration-200']) }}>
