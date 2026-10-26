<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $certificate->certificate_number }}</title>
    <style>
        @page { margin: 0; }

        body {
            font-family: 'DejaVu Sans', 'Helvetica', 'Arial', sans-serif;
            color: #1f2937;
            margin: 0;
            padding: 0;
        }

        .sheet {
            width: 100%;
            padding: 16mm 14mm;
        }

        .frame {
            border: 2px solid #1f3a5f;
            padding: 10mm 12mm 8mm;
        }

        .logo {
            text-align: center;
            margin-bottom: 4mm;
        }

        .logo img {
            height: 22mm;
        }

        .org-name {
            font-size: 13pt;
            letter-spacing: 1px;
            color: #1f3a5f;
            font-weight: bold;
        }

        .title {
            text-align: center;
            font-size: 28pt;
            font-weight: bold;
            letter-spacing: 2px;
            color: #1f3a5f;
            text-transform: uppercase;
            margin-top: 6mm;
        }

        .rule {
            width: 45mm;
            border-top: 2px solid #b08d57;
            margin: 4mm auto 6mm;
        }

        .lead {
            text-align: center;
            font-size: 12pt;
            color: #4b5563;
            margin-bottom: 5mm;
        }

        .student {
            text-align: center;
            font-size: 26pt;
            font-weight: bold;
            color: #111827;
            padding-bottom: 3mm;
            border-bottom: 1px solid #9ca3af;
            width: 60%;
            margin: 0 auto 6mm;
        }

        .body-text {
            text-align: center;
            font-size: 12pt;
            color: #4b5563;
            line-height: 1.6;
        }

        .module {
            display: block;
            font-size: 16pt;
            font-weight: bold;
            color: #1f3a5f;
            margin: 2mm 0 6mm;
        }

        .meta {
            width: 100%;
            margin-top: 10mm;
        }

        .meta td {
            font-size: 10pt;
            color: #4b5563;
            padding: 0 4mm;
        }

        .value {
            font-weight: bold;
            color: #111827;
        }

        .signatures {
            width: 100%;
            margin-top: 12mm;
        }

        .signatures td {
            width: 50%;
            text-align: center;
            vertical-align: bottom;
            padding: 0 8mm;
        }

        .signature-image {
            height: 16mm;
            margin-bottom: 1mm;
        }

        .signature-name {
            font-size: 12pt;
            font-weight: bold;
            border-top: 1px solid #374151;
            padding-top: 2mm;
        }

        .signature-title {
            font-size: 9pt;
            color: #6b7280;
            margin-top: 1mm;
        }

        .certificate-id {
            font-size: 9pt;
            color: #6b7280;
            letter-spacing: 1px;
        }

        .verify-url {
            font-size: 7.5pt;
            color: #6b7280;
            margin-top: 1.5mm;
            word-break: break-all;
        }
    </style>
</head>
<body>
    @php
        $logoPath = $signatory['logo_path'] ?? null;
        $signaturePath = $signatory['signature_path'] ?? null;

        $logoFile = ($logoPath && \Illuminate\Support\Facades\Storage::disk('public')->exists($logoPath))
            ? str_replace('\\', '/', \Illuminate\Support\Facades\Storage::disk('public')->path($logoPath))
            : null;

        $signatureFile = ($signaturePath && \Illuminate\Support\Facades\Storage::disk('public')->exists($signaturePath))
            ? str_replace('\\', '/', \Illuminate\Support\Facades\Storage::disk('public')->path($signaturePath))
            : null;

        $signatoryName = $signatory['name'] ?? ($certificate->issuer->name ?? null);
        $signatoryTitle = $signatory['title'] ?? null;
    @endphp

    <div class="sheet">
        <div class="frame">
            <div class="logo">
                @if ($logoFile)
                    <img src="file://{{ $logoFile }}" alt="EEC">
                @else
                    <div class="org-name">EEC</div>
                @endif
            </div>

            <div class="title">Certificate of Completion</div>
            <div class="rule"></div>

            <div class="lead">This is to certify that</div>
            <div class="student">{{ $certificate->user->name }}</div>

            <div class="body-text">
                has successfully completed the training module
                <span class="module">{{ $certificate->module->title }}</span>
            </div>

            <table class="meta">
                <tr>
                    <td>
                        Issued on: <span class="value">{{ $certificate->issued_at->format('F d, Y') }}</span>
                    </td>
                    <td style="text-align:right;">
                        Valid until:
                        <span class="value">
                            {{ $certificate->valid_until ? $certificate->valid_until->format('F d, Y') : 'No expiry' }}
                        </span>
                    </td>
                </tr>
            </table>

            <table class="signatures">
                <tr>
                    <td>
                        <div class="certificate-id">{{ $certificate->certificate_number }}</div>
                        <div class="verify-url">
                            Verify at {{ route('certificates.verify', $certificate->certificate_number) }}
                        </div>
                    </td>
                    <td>
                        @if ($signatureFile)
                            <img class="signature-image" src="file://{{ $signatureFile }}" alt="">
                        @endif
                        <div class="signature-name">{{ $signatoryName ?? 'Authorised Signatory' }}</div>
                        <div class="signature-title">{{ $signatoryTitle ?? 'EEC' }}</div>
                    </td>
                </tr>
            </table>
        </div>
    </div>
</body>
</html>
