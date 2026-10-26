<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Certificate Branding') }}</h2>
            <span class="text-xs text-gray-500">{{ __('Admin only') }}</span>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900">{{ __('Logo and signatory') }}</h3>
                <p class="text-sm text-gray-500 mt-1">
                    {{ __('The logo appears at the top of every certificate. The signatory name and title are printed under the signature line unless the issuing instructor has their own signature.') }}
                </p>

                <form action="{{ route('admin.branding.update') }}" method="POST" enctype="multipart/form-data" class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-5">
                    @csrf

                    <div>
                        <label class="block text-sm font-medium text-gray-700">{{ __('Signatory name') }}</label>
                        <input type="text" name="signatory_name" value="{{ old('signatory_name', $signatoryName) }}"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" placeholder="e.g. Abebe Kebede">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">{{ __('Signatory title') }}</label>
                        <input type="text" name="signatory_title" value="{{ old('signatory_title', $signatoryTitle) }}"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" placeholder="e.g. Training Director">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">{{ __('EEC logo') }}</label>
                        <input type="file" name="logo" accept="image/*"
                            class="mt-1 block w-full text-sm text-gray-700">
                        @if($logoPath)
                            <div class="mt-2 flex items-center gap-3">
                                <img src="{{ Storage::disk('public')->url($logoPath) }}" alt="EEC logo" class="h-12 bg-white border border-gray-200 rounded p-1">
                                <span class="text-xs text-gray-500">{{ $logoPath }}</span>
                            </div>
                        @else
                            <p class="mt-2 text-xs text-gray-500">{{ __('No logo uploaded yet — certificates fall back to the text "EEC".') }}</p>
                        @endif
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">{{ __('Default signature') }}</label>
                        <input type="file" name="signature" accept="image/*"
                            class="mt-1 block w-full text-sm text-gray-700">
                        @if($signaturePath)
                            <div class="mt-2 flex items-center gap-3">
                                <img src="{{ Storage::disk('public')->url($signaturePath) }}" alt="Signature" class="h-12 bg-white border border-gray-200 rounded p-1">
                                <span class="text-xs text-gray-500">{{ __('Used when the issuing instructor has no signature of their own.') }}</span>
                            </div>
                        @else
                            <p class="mt-2 text-xs text-gray-500">{{ __('No default signature uploaded yet.') }}</p>
                        @endif
                    </div>

                    <div class="md:col-span-2">
                        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-500">
                            {{ __('Save branding') }}
                        </button>
                    </div>
                </form>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900">{{ __('Instructor signatures') }}</h3>
                <p class="text-sm text-gray-500 mt-1">
                    {{ __('Attach a signature to a specific instructor. Certificates they issue will print this signature instead of the default.') }}
                </p>

                <div class="mt-4 divide-y divide-gray-100">
                    @forelse($signatories as $person)
                        <div class="py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                            <div>
                                <p class="font-medium text-gray-900">{{ $person->name }}</p>
                                <p class="text-xs text-gray-500">{{ $person->email }}</p>
                                <p class="text-xs mt-1">
                                    @if($person->signature_path)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-green-100 text-green-800">{{ __('Signature on file') }}</span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-gray-100 text-gray-600">{{ __('No signature') }}</span>
                                    @endif
                                </p>
                            </div>

                            <div class="flex items-center gap-3">
                                @if($person->signature_path)
                                    <img src="{{ Storage::disk('public')->url($person->signature_path) }}" alt="" class="h-10 border border-gray-200 rounded bg-white p-1">
                                @endif

                                <form action="{{ route('admin.branding.signature', $person) }}" method="POST" enctype="multipart/form-data" class="flex items-center gap-2">
                                    @csrf
                                    <input type="file" name="signature" accept="image/*" required class="text-xs text-gray-700 w-48">
                                    <button type="submit" class="px-3 py-1.5 text-xs font-medium bg-gray-900 text-white rounded-md hover:bg-gray-700">
                                        {{ __('Upload') }}
                                    </button>
                                </form>

                                @if($person->signature_path)
                                    <form action="{{ route('admin.branding.signature.destroy', $person) }}" method="POST"
                                        onsubmit="return confirm('{{ __('Remove this signature?') }}');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-xs text-red-600 hover:text-red-800">{{ __('Remove') }}</button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @empty
                        <p class="py-4 text-sm text-gray-500">{{ __('No instructors or admins found.') }}</p>
                    @endforelse
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
