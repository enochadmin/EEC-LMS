<x-guest-layout :title="'Certificate verification'">
    <div class="w-full max-w-lg mx-auto">
        <div class="bg-white shadow-sm sm:rounded-2xl overflow-hidden">

            @if($status === 'valid')
                <div class="px-8 pt-8 pb-6 text-center border-b border-gray-100">
                    <div class="mx-auto w-14 h-14 rounded-full bg-green-100 flex items-center justify-center">
                        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                    <h1 class="mt-4 text-xl font-semibold text-gray-900">Certificate verified</h1>
                    <p class="text-sm text-gray-500 mt-1">This certificate is genuine and currently valid.</p>
                </div>
            @elseif($status === 'expired')
                <div class="px-8 pt-8 pb-6 text-center border-b border-gray-100">
                    <div class="mx-auto w-14 h-14 rounded-full bg-amber-100 flex items-center justify-center">
                        <svg class="w-8 h-8 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8v5m0 4h.01M10.3 3.9L2.4 18a2 2 0 001.7 3h15.8a2 2 0 001.7-3L13.7 3.9a2 2 0 00-3.4 0z"/>
                        </svg>
                    </div>
                    <h1 class="mt-4 text-xl font-semibold text-gray-900">Certificate found — expired</h1>
                    <p class="text-sm text-gray-500 mt-1">The certificate is genuine, but its validity period has ended.</p>
                </div>
            @else
                <div class="px-8 pt-8 pb-6 text-center border-b border-gray-100">
                    <div class="mx-auto w-14 h-14 rounded-full bg-red-100 flex items-center justify-center">
                        <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </div>
                    <h1 class="mt-4 text-xl font-semibold text-gray-900">Certificate not found</h1>
                    <p class="text-sm text-gray-500 mt-1">
                        No certificate matches <span class="font-mono font-medium text-gray-700">{{ $number }}</span>.
                        Check the number and try again.
                    </p>
                </div>
            @endif

            @if($certificate)
                <dl class="divide-y divide-gray-100">
                    <div class="px-8 py-4 flex justify-between gap-6">
                        <dt class="text-sm text-gray-500">Certificate number</dt>
                        <dd class="text-sm font-mono font-medium text-gray-900 text-right">{{ $certificate->certificate_number }}</dd>
                    </div>
                    <div class="px-8 py-4 flex justify-between gap-6">
                        <dt class="text-sm text-gray-500">Awarded to</dt>
                        <dd class="text-sm font-semibold text-gray-900 text-right">{{ $certificate->user->name ?? '—' }}</dd>
                    </div>
                    <div class="px-8 py-4 flex justify-between gap-6">
                        <dt class="text-sm text-gray-500">Training module</dt>
                        <dd class="text-sm text-gray-900 text-right">{{ $certificate->module->title ?? '—' }}</dd>
                    </div>
                    <div class="px-8 py-4 flex justify-between gap-6">
                        <dt class="text-sm text-gray-500">Issued on</dt>
                        <dd class="text-sm text-gray-900 text-right">{{ $certificate->issued_at?->format('F d, Y') ?? '—' }}</dd>
                    </div>
                    <div class="px-8 py-4 flex justify-between gap-6">
                        <dt class="text-sm text-gray-500">Valid until</dt>
                        <dd class="text-sm text-right {{ $status === 'expired' ? 'text-amber-700 font-medium' : 'text-gray-900' }}">
                            {{ $certificate->valid_until ? $certificate->valid_until->format('F d, Y') : 'No expiry' }}
                        </dd>
                    </div>
                    @if($certificate->issuer)
                        <div class="px-8 py-4 flex justify-between gap-6">
                            <dt class="text-sm text-gray-500">Issued by</dt>
                            <dd class="text-sm text-gray-900 text-right">{{ $certificate->issuer->name }}</dd>
                        </div>
                    @endif
                </dl>
            @endif

            <div class="px-8 py-5 bg-gray-50 text-center">
                <p class="text-xs text-gray-500">
                    {{ config('app.name') }} · certificate verification
                </p>
            </div>
        </div>
    </div>
</x-guest-layout>
