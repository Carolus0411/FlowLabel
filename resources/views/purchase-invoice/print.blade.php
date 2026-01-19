<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 40px;
            background-color: #f5f5f5;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
            background-color: white;
            padding: 40px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 30px;
            padding-bottom: 30px;
            border-bottom: 3px solid #2c3e50;
        }

        .header-left {
            display: flex;
            gap: 15px;
            align-items: flex-start;
            flex: 1;
        }

        .logo {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 10px;
            flex-shrink: 0;
        }

        .logo-image {
            width: 100px;
            height: 100px;
            object-fit: contain;
            border-radius: 10px;
        }

        .company-info {
            flex: 1;
        }

        .company-address {
            font-size: 10px;
            line-height: 1.5;
            color: #555;
        }

        .document-title {
            text-align: center;
            font-size: 32px;
            font-weight: bold;
            margin: 20px 0;
            color: #2c3e50;
        }

        .info-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
            font-size: 11px;
        }

        .info-left, .info-right {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .info-item {
            display: grid;
            grid-template-columns: 100px 1fr;
            gap: 10px;
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
            margin-bottom: 20px;
            font-size: 11px;
        }

        th {
            background-color: #f0f0f0;
            padding: 10px 8px;
            text-align: center;
            font-weight: 600;
            border: 1px solid #333;
        }

        td {
            padding: 10px 8px;
            border: 1px solid #333;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .empty-row {
            height: 50px;
        }

        .subtotal-row {
            font-weight: bold;
            background-color: #f9f9f9;
        }

        .tax-row {
            background-color: #f5f5f5;
        }

        .total-row {
            font-weight: bold;
            background-color: #e8e8e8;
        }

        .signatures {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 40px;
            margin-top: 50px;
        }

        .signature-box {
            text-align: center;
        }

        .signature-label {
            font-size: 11px;
            margin-bottom: 80px;
            color: #666;
        }

        .signature-name {
            font-size: 11px;
            font-weight: 600;
            border-top: 1px dotted #333;
            padding-top: 5px;
            display: inline-block;
            min-width: 180px;
        }

        @media print {
            body {
                padding: 0;
                background-color: white;
            }

            .container {
                box-shadow: none;
                padding: 20px;
            }

            @page {
                margin: 1cm;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="header-left">
                @if($mainCompany && $mainCompany->logo)
                    <img src="{{ asset('storage/' . $mainCompany->logo) }}" alt="Company Logo" class="logo-image">
                @else
                    <div class="logo"></div>
                @endif
                <div class="company-info">
                    <div class="company-address">
                        {{ $mainCompany ? $mainCompany->address : 'Wisma Soewarna Office-Suite 3 K-5, Pajang, Kec. Benda, Kota Tangerang, Banten' }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Document Title -->
        <div class="document-title">PURCHASE INVOICE</div>

        <!-- Info Section -->
        <div class="info-section">
            <div class="info-left">
                <div class="info-item">
                    <span class="info-label">Supplier:</span>
                    <span class="info-value">{{ $purchaseInvoice->supplier->name ?? 'N/A' }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Keterangan:</span>
                    <span class="info-value">{{ $purchaseInvoice->note ?? '-' }}</span>
                </div>
            </div>

            <div class="info-right">
                <div class="info-item">
                    <span class="info-label"></span>
                    <span class="info-value">{{ $purchaseInvoice->code }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Tgl. Invoice:</span>
                    <span class="info-value">{{ \Carbon\Carbon::parse($purchaseInvoice->invoice_date)->format('d-m-Y') }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Kurs:</span>
                    <span class="info-value">{{ number_format($purchaseInvoice->currency_rate ?? 1, 2) }}</span>
                </div>
            </div>
        </div>

        <!-- Table -->
        <table>
            <thead>
                <tr>
                    <th style="width: 40px;">No</th>
                    <th style="width: 80px;">Kode</th>
                    <th>Uraian</th>
                    <th style="width: 80px;">Quantity</th>
                    <th style="width: 60px;">Unit</th>
                    <th style="width: 110px;">Harga Satuan</th>
                    <th style="width: 120px;">Jumlah<br>(IDR)</th>
                </tr>
            </thead>
            <tbody>
                @forelse($purchaseInvoice->details as $index => $detail)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="text-center">{{ $detail->serviceCharge->code ?? '-' }}</td>
                    <td>{{ $detail->serviceCharge->name ?? $detail->description ?? '-' }}</td>
                    <td class="text-center">{{ number_format($detail->qty, 2) }}</td>
                    <td class="text-center">{{ $detail->uom->name ?? '-' }}</td>
                    <td class="text-right">{{ number_format($detail->price, 2, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($detail->amount, 2, ',', '.') }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center">No items found</td>
                </tr>
                @endforelse

                <!-- Empty rows for spacing -->
                @if($purchaseInvoice->details->count() < 4)
                    @for($i = $purchaseInvoice->details->count(); $i < 4; $i++)
                    <tr class="empty-row">
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                    </tr>
                    @endfor
                @endif

                @if($purchaseInvoice->details->count() > 0)
                <tr class="subtotal-row">
                    <td colspan="6" class="text-right"><strong>Sub Total</strong></td>
                    <td class="text-right"><strong>{{ number_format($purchaseInvoice->dpp_amount, 2, ',', '.') }}</strong></td>
                </tr>
                <tr class="tax-row">
                    <td colspan="6" class="text-right">PPN {{ $purchaseInvoice->ppn_amount > 0 ? '11.00%' : '0.00%' }}</td>
                    <td class="text-right">{{ number_format($purchaseInvoice->ppn_amount, 2, ',', '.') }}</td>
                </tr>
                @if($purchaseInvoice->pph_amount > 0)
                <tr class="tax-row">
                    <td colspan="6" class="text-right">PPH {{ $purchaseInvoice->pph->name ?? '' }}</td>
                    <td class="text-right">{{ number_format($purchaseInvoice->pph_amount, 2, ',', '.') }}</td>
                </tr>
                @endif
                <tr class="total-row">
                    <td colspan="6" class="text-right"><strong>Total</strong></td>
                    <td class="text-right"><strong>{{ number_format($purchaseInvoice->invoice_amount, 2, ',', '.') }}</strong></td>
                </tr>
                @endif
            </tbody>
        </table>

        <!-- Signatures -->
        <div class="signatures">
            <div class="signature-box">
                <div class="signature-label">Disetujui</div>
                <div class="signature-name">( . . . . . . . . . . . . . . . . . . )</div>
            </div>

            <div class="signature-box">
                <div class="signature-label">Diperiksa</div>
                <div class="signature-name">Manager</div>
            </div>

            <div class="signature-box">
                <div class="signature-label">Dibuat oleh</div>
                <div class="signature-name">{{ auth()->user()->name ?? 'AP' }}</div>
            </div>
        </div>
    </div>

    <script>
        // Fungsi untuk print
        function printDocument() {
            window.print();
        }

        // Uncomment baris di bawah jika ingin auto print saat halaman dibuka
        // window.onload = function() {
        //     printDocument();
        // };
    </script>
</body>
</html>
