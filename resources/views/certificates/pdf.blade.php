<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Certificate of Completion</title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            text-align: center;
            border: 10px solid #787878;
            padding: 20px;
            height: 90%;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .container {
            padding: 20px;
        }
        .header {
            font-size: 50px;
            font-weight: bold;
            margin-bottom: 20px;
            color: #333;
            text-transform: uppercase;
        }
        .sub-header {
            font-size: 25px;
            margin-bottom: 40px;
            color: #555;
        }
        .student-name {
            font-size: 40px;
            font-weight: bold;
            margin: 20px 0;
            border-bottom: 2px solid #333;
            display: inline-block;
            padding: 0 20px 10px;
            color: #2c3e50;
        }
        .content {
            font-size: 20px;
            margin-bottom: 30px;
            line-height: 1.5;
        }
        .module-title {
            font-weight: bold;
            font-size: 24px;
            color: #2c3e50;
        }
        .footer {
            margin-top: 60px;
            display: table;
            width: 100%;
        }
        .signature-block {
            display: table-cell;
            width: 50%;
            vertical-align: bottom;
        }
        .signature-line {
            border-top: 1px solid #333;
            width: 80%;
            margin: 10px auto 0;
            padding-top: 5px;
            font-weight: bold;
        }
        .signature {
            font-family: 'Brush Script MT', cursive;
            font-size: 30px;
            color: #1a202c;
            margin-bottom: 5px;
        }
        .date {
            font-size: 18px;
            color: #555;
        }
        .certificate-id {
            position: absolute;
            bottom: 20px;
            right: 20px;
            font-size: 12px;
            color: #999;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">Certificate of Completion</div>
        
        <div class="sub-header">This is to certify that</div>
        
        <div class="student-name">{{ $certificate->user->name }}</div>
        
        <div class="content">
            has successfully completed the training module<br><br>
            <span class="module-title">{{ $certificate->module->title }}</span>
        </div>
        
        <div class="footer">
            <div class="signature-block">
                <div class="date">Date: {{ $certificate->issued_at->format('F d, Y') }}</div>
            </div>
            <div class="signature-block">
                <div class="signature">
                    {{ $certificate->module->creator->name ?? 'Instructor' }}
                </div>
                <div class="signature-line">Instructor Signature</div>
            </div>
        </div>

        <div class="certificate-id">
            Certificate ID: {{ $certificate->certificate_number }}
        </div>
    </div>
</body>
</html>
