<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Settlement</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 20px;
            background-color: #f5f5f5;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background-color: white;
            padding: 20px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding-bottom: 5px;
            border-bottom: 3px solid #2c3e50;
            margin-bottom: 5px;
        }

        .logo-section {
            flex: 1;
        }

        .logo {

            height: 60px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 5px;
        }

        .logo-image {

            height: 60px;
            object-fit: contain;
            border-radius: 12px;
        }

        .company-info {
            flex: 1;
            text-align: right;
            color: #555;
            line-height: 1.8;
        }

        .company-info p {
            margin: 5px 0;
            font-size: 12px;
        }

        .company-info .address {
            max-width: 300px;
            margin-left: auto;
        }

        .title {
            text-align: center;
            margin: 15px 0;
        }

        .title h1 {
            font-size: 24px;
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .info-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 15px;
        }

        .info-left, .info-right {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .info-row {
            display: grid;
            grid-template-columns: 100px 1fr;
            gap: 10px;
            font-size: 12px;
        }

        .info-label {
            color: #666;
        }

        .info-value {
            color: #000;
            font-weight: 500;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        th {
            background-color: #f0f0f0;
            padding: 8px;
            text-align: left;
            font-weight: 600;
            font-size: 12px;
            border: 1px solid #ddd;
        }

        td {
            padding: 8px;
            border: 1px solid #ddd;
            font-size: 12px;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .total-row {
            font-weight: bold;
        }

        .section-title {
            font-size: 16px;
            font-weight: bold;
            margin: 15px 0 10px 0;
            color: #333;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
        }

        .signatures {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-top: 30px;
            margin-bottom: 20px;
        }

        .signature-box {
            text-align: center;
        }

        .signature-label {
            font-size: 12px;
            margin-bottom: 40px;
            color: #666;
        }

        .signature-name {
            font-size: 12px;
            font-weight: 600;
            border-top: 1px solid #333;
            padding-top: 5px;
            display: inline-block;
            min-width: 200px;
        }

        .footer {
            text-align: right;
            font-size: 10px;
            color: #666;
            font-style: italic;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
        }

        @media print {
            body {
                padding: 0;
                background-color: white;
            }

            .container {
                box-shadow: none;
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo-section">
                @if($mainCompany && $mainCompany->logo)
                    <img src="{{ asset('storage/' . $mainCompany->logo) }}" alt="Company Logo" class="logo-image">
                @else
                    <div class="logo"></div>
                @endif
            </div>
            <div class="company-info">
                @if($mainCompany)
                    <p class="address"><strong>Alamat:</strong><br>{{ $mainCompany->address ?? '-' }}<br>
                    <strong>Phone:</strong> {{ $mainCompany->phone ?? '-' }}<br>
                    <strong>Email:</strong> {{ $mainCompany->email ?? '-' }}</p>
                @endif
            </div>
        </div>

        <div class="title">
            <h1>SALES SETTLEMENT</h1>
        </div>

        <div class="info-section">
            <div class="info-left">
                <div class="info-row">
                    <span class="info-label">Nomor :</span>
                    <span class="info-value">{{ $salesSettlement->code }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Contact :</span>
                    <span class="info-value">{{ $salesSettlement->contact->name ?? 'N/A' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Date :</span>
                    <span class="info-value">{{ \Carbon\Carbon::parse($salesSettlement->date)->format('d F Y') }}</span>
                </div>
            </div>

            <div class="info-right">
                <div class="info-row">
                    <span class="info-label">Company :</span>
                    <span class="info-value">{{ config('app.name') }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Status :</span>
                    <span class="info-value">{{ ucfirst($salesSettlement->status) }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Created By :</span>
                    <span class="info-value">{{ $salesSettlement->createdBy->name ?? 'System' }}</span>
                </div>
            </div>
        </div>

        <div class="section-title">Payment Sources</div>
        <table>
            <thead>
                <tr>
                    <th class="text-center" style="width: 60px;">No.</th>
                    <th>Payment Method</th>
                    <th>Payment Code</th>
                    <th class="text-right" style="width: 200px;">Amount (IDR)</th>
                </tr>
            </thead>
            <tbody>
                @forelse($salesSettlement->sources as $index => $source)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ ucfirst($source->payment_method) }}</td>
                    <td>{{ $source->settleable_id }}</td>
                    <td class="text-right">{{ number_format($source->amount, 2, ',', '.') }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="text-center">No payment sources found</td>
                </tr>
                @endforelse
                @if($salesSettlement->sources->count() > 0)
                <tr class="total-row">
                    <td colspan="3" class="text-right">Total Source Amount :</td>
                    <td class="text-right">{{ number_format($salesSettlement->source_amount, 2, ',', '.') }}</td>
                </tr>
                @endif
            </tbody>
        </table>

        <div class="section-title">Settlement Details</div>
        <table>
            <thead>
                <tr>
                    <th class="text-center" style="width: 60px;">No.</th>
                    <th>Sales Invoice</th>
                    <th class="text-right" style="width: 200px;">Amount (IDR)</th>
                </tr>
            </thead>
            <tbody>
                @forelse($salesSettlement->details as $index => $detail)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $detail->sales_invoice_code ?? 'N/A' }}</td>
                    <td class="text-right">{{ number_format($detail->amount, 2, ',', '.') }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="3" class="text-center">No settlement details found</td>
                </tr>
                @endforelse
                @if($salesSettlement->details->count() > 0)
                <tr class="total-row">
                    <td colspan="2" class="text-right">Total Paid Amount :</td>
                    <td class="text-right">{{ number_format($salesSettlement->paid_amount, 2, ',', '.') }}</td>
                </tr>
                @endif
            </tbody>
        </table>

        <div class="signatures">
            <div class="signature-box">
                <div class="signature-label">Dibuat oleh,</div>
                <div class="signature-name">{{ $salesSettlement->createdBy->name ?? 'Finance' }}</div>
            </div>

            <div class="signature-box">
                <div class="signature-label">Diperiksa oleh,</div>
                <div class="signature-name">Accounting</div>
            </div>

            <div class="signature-box">
                <div class="signature-label">Disetujui oleh,</div>
                <div class="signature-name">Manager</div>
            </div>
        </div>

        <div class="footer">
            Print Out By {{ auth()->user()->name ?? 'System' }} - {{ now()->format('d M Y H:i') }}
        </div>
    </div>

    <script>
        window.print();
    </script>
</body>
</html>
