<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Receival</title>
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
            align-items: flex-start;
            gap: 20px;
            margin-bottom: 30px;
            padding-bottom: 30px;
            border-bottom: 3px solid #2c3e50;
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
            font-size: 11px;
            line-height: 1.5;
            color: #555;
        }

        .document-title {
            text-align: center;
            font-size: 28px;
            font-weight: bold;
            margin: 30px 0;
            color: #2c3e50;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px 40px;
            margin-bottom: 30px;
            font-size: 12px;
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
            font-size: 12px;
        }

        th {
            background-color: #f0f0f0;
            padding: 10px;
            text-align: left;
            font-weight: 600;
            border: 1px solid #333;
        }

        td {
            padding: 10px;
            border: 1px solid #333;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .total-row {
            font-weight: bold;
            background-color: #f9f9f9;
        }

        .notes {
            margin: 20px 0;
            font-size: 12px;
        }

        .notes-label {
            font-weight: 600;
            margin-bottom: 5px;
        }

        .notes-content {
            color: #333;
        }

        .signatures {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 60px;
            margin-top: 60px;
        }

        .signature-box {
            text-align: center;
        }

        .signature-label {
            font-size: 12px;
            margin-bottom: 100px;
            color: #666;
        }

        .signature-name {
            font-size: 12px;
            font-weight: 600;
            border-top: 1px dotted #333;
            padding-top: 5px;
            display: inline-block;
            min-width: 250px;
        }

        .empty-rows {
            height: 40px;
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

        <!-- Document Title -->
        <div class="document-title">PURCHASE RECEIVAL</div>

        <!-- Info Section -->
        <div class="info-grid">
            <div class="info-item">
                <span class="info-label">Supplier:</span>
                <span class="info-value">{{ $purchaseReceival->supplier->name ?? 'N/A' }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Receival No.:</span>
                <span class="info-value">{{ $purchaseReceival->code }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Transport:</span>
                <span class="info-value">{{ $purchaseReceival->transport ?? '-' }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Receival Date:</span>
                <span class="info-value">{{ \Carbon\Carbon::parse($purchaseReceival->receival_date)->format('d M Y') }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Service Type:</span>
                <span class="info-value">{{ $purchaseReceival->service_type ?? '-' }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Order No.:</span>
                <span class="info-value">{{ $purchaseReceival->purchaseOrder->code ?? '-' }}</span>
            </div>
            <div class="info-item">
                <span class="info-label"></span>
                <span class="info-value"></span>
            </div>
            <div class="info-item">
                <span class="info-label">Status:</span>
                <span class="info-value">{{ ucfirst($purchaseReceival->status) }}</span>
            </div>
        </div>

        <!-- Table -->
        <table>
            <thead>
                <tr>
                    <th class="text-center" style="width: 50px;">No.</th>
                    <th class="text-center" style="width: 100px;">Kode Barang</th>
                    <th>Nama Barang</th>
                    <th class="text-center" style="width: 100px;">Qty Inv</th>
                    <th class="text-center" style="width: 80px;">Unit</th>
                </tr>
            </thead>
            <tbody>
                @forelse($purchaseReceival->details as $index => $detail)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="text-center">{{ $detail->serviceCharge->code ?? '-' }}</td>
                    <td>{{ $detail->serviceCharge->name ?? $detail->description ?? '-' }}</td>
                    <td class="text-center">{{ number_format($detail->qty, 2) }}</td>
                    <td class="text-center">{{ $detail->uom->name ?? '-' }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center">No items found</td>
                </tr>
                @endforelse

                <!-- Empty rows for spacing -->
                @if($purchaseReceival->details->count() < 3)
                    @for($i = $purchaseReceival->details->count(); $i < 3; $i++)
                    <tr class="empty-rows">
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                    </tr>
                    @endfor
                @endif

                @if($purchaseReceival->details->count() > 0)
                <tr class="total-row">
                    <td colspan="3" class="text-right"><strong>Total</strong></td>
                    <td class="text-center"><strong>{{ number_format($purchaseReceival->total_qty, 2) }}</strong></td>
                    <td></td>
                </tr>
                @endif
            </tbody>
        </table>

        <!-- Notes -->
        <div class="notes">
            <div class="notes-label">Catatan:</div>
            <div class="notes-content">{{ $purchaseReceival->note ?? '-' }}</div>
        </div>

        <!-- Signatures -->
        <div class="signatures">
            <div class="signature-box">
                <div class="signature-label">Supplier</div>
                <div class="signature-name">{{ $purchaseReceival->supplier->name ?? 'N/A' }}</div>
            </div>

            <div class="signature-box">
                <div class="signature-label">Diterima Oleh</div>
                <div class="signature-name">( . . . . . . . . . . . . . . . . . . )</div>
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
