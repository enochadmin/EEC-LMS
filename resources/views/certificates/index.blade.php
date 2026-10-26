<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Certificates') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-left text-gray-600 uppercase tracking-wide text-xs">
                            <tr>
                                <th class="px-4 py-2">{{ __('Certificate #') }}</th>
                                <th class="px-4 py-2">{{ __('Module') }}</th>
                                <th class="px-4 py-2">{{ __('Issued') }}</th>
                                <th class="px-4 py-2">{{ __('Valid Until') }}</th>
                                <th class="px-4 py-2 text-right">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($certificates as $certificate)
                                <tr>
                                    <td class="px-4 py-2 font-medium text-gray-900">{{ $certificate->certificate_number }}</td>
                                    <td class="px-4 py-2 text-gray-600">{{ $certificate->module?->title ?? __('N/A') }}</td>
                                    <td class="px-4 py-2">{{ $certificate->issued_at?->format('M d, Y') }}</td>
                                    <td class="px-4 py-2">
                                        {{ $certificate->valid_until?->format('M d, Y') ?? __('Lifetime') }}
                                    </td>
                                    <td class="px-4 py-2 text-right">
                                        <a href="{{ route('certificates.view', $certificate) }}" target="_blank" rel="noopener"
                                            class="inline-flex items-center px-3 py-1.5 border border-gray-900 rounded-md text-sm text-white bg-gray-900 hover:bg-gray-700">
                                            {{ __('View') }}
                                        </a>
                                        <a href="{{ route('certificates.download', $certificate) }}"
                                            class="ml-2 inline-flex items-center px-3 py-1.5 border border-gray-200 rounded-md text-sm text-gray-700 hover:bg-gray-50">
                                            {{ __('Download PDF') }}
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-6 text-center text-gray-500">
                                        {{ __('No certificates issued yet.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">
                    {{ $certificates->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

