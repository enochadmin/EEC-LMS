<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Manage Certificates') }}
            </h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="bg-green-100 border border-green-200 text-green-800 px-4 py-3 rounded mb-4">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <x-search-form :route="route('admin.certificates.index')" placeholder="Search by student or module..." />
                    
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">
                        @if($isAdmin)
                            {{ __('All Certificates') }}
                        @else
                            {{ __('Certificates Issued By Me') }}
                        @endif
                    </h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50 text-left font-semibold text-gray-600">
                                <tr>
                                    <th class="px-4 py-2">{{ __('Certificate #') }}</th>
                                    <th class="px-4 py-2">{{ __('Student') }}</th>
                                    <th class="px-4 py-2">{{ __('Module') }}</th>
                                    @if($isAdmin)
                                        <th class="px-4 py-2">{{ __('Issued By') }}</th>
                                    @endif
                                    <th class="px-4 py-2">{{ __('Issued Date') }}</th>
                                    <th class="px-4 py-2 text-right">{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse ($certificates as $certificate)
                                    <tr>
                                        <td class="px-4 py-2 font-mono text-xs">{{ $certificate->certificate_number }}</td>
                                        <td class="px-4 py-2 font-medium text-gray-900">{{ $certificate->user->name }}</td>
                                        <td class="px-4 py-2 text-gray-600">{{ $certificate->module->title }}</td>
                                        @if($isAdmin)
                                            <td class="px-4 py-2 text-gray-600">
                                                {{ $certificate->issuer?->name ?? __('System') }}
                                            </td>
                                        @endif
                                        <td class="px-4 py-2 text-gray-500">
                                            {{ $certificate->issued_at->format('M d, Y') }}
                                        </td>
                                        <td class="px-4 py-2">
                                            <div class="flex items-center justify-end gap-2">
                                                <a href="{{ route('certificates.view', $certificate) }}" target="_blank" rel="noopener"
                                                    class="text-gray-900 hover:underline text-sm font-medium">
                                                    {{ __('View') }}
                                                </a>
                                                <a href="{{ route('certificates.download', $certificate) }}"
                                                    class="text-indigo-600 hover:underline text-sm">
                                                    {{ __('Download') }}
                                                </a>
                                                <form action="{{ route('admin.certificates.destroy', $certificate) }}" 
                                                      method="POST" class="inline"
                                                      onsubmit="return confirm('{{ __('Are you sure you want to revoke this certificate? This action cannot be undone.') }}');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="text-red-600 hover:underline text-sm">
                                                        {{ __('Revoke') }}
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-4 py-6 text-center text-gray-500">
                                            {{ __('No certificates found.') }}
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
    </div>
</x-app-layout>
