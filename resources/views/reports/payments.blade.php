<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <style>
        body {
            font-family: sans-serif;
            font-size: 12px;
            color: #222;
        }

        h1 {
            font-size: 18px;
            margin-bottom: 4px;
        }

        .subtitle {
            color: #666;
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 6px 8px;
            text-align: left;
        }

        th {
            background-color: #f3f4f6;
        }

        .status-pago {
            color: #15803d;
            font-weight: bold;
        }

        .status-pendente {
            color: #b45309;
            font-weight: bold;
        }

        .totals {
            margin-top: 20px;
        }

        .totals td {
            border: none;
            padding: 4px 8px;
        }
    </style>
</head>

<body>
    <h1>Relatório de Pagamentos</h1>
    <p class="subtitle">
        Usuário: {{ $userName }}
        @if ($month)
            | Referência: {{ \Carbon\Carbon::createFromFormat('Y-m', $month)->translatedFormat('F Y') }}
        @endif
    </p>

    <table>
        <thead>
            <tr>
                <th>Aluno</th>
                <th>Data</th>
                <th>Valor</th>
                <th>Método</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @php
                $metodosLabels = [
                    'pix' => 'Pix',
                    'cc' => 'Cartão de Crédito',
                    'dc' => 'Cartão de Débito',
                    'cash' => 'Dinheiro',
                ];
            @endphp
            @forelse($payments as $payment)
                <tr>
                    <td>{{ $payment['learner_name'] }}</td>
                    <td>{{ \Carbon\Carbon::parse($payment['date'])->format('d/m/Y') }}</td>
                    <td>R$ {{ number_format($payment['value'], 2, ',', '.') }}</td>
                    <td>{{ $metodosLabels[$payment['method']] }}</td>
                    <td class="{{ $payment['status'] === 'concluded' ? 'status-pago' : 'status-pendente' }}">
                        {{ $payment['status'] === 'concluded' ? 'Pago' : 'Pendente' }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">Nenhum pagamento encontrado para o período.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <table class="totals">
        <tr>
            <td><strong>Total Pago:</strong></td>
            <td>R$ {{ number_format($totalPago, 2, ',', '.') }}</td>
        </tr>
        <tr>
            <td><strong>Total Pendente:</strong></td>
            <td>R$ {{ number_format($totalPendente, 2, ',', '.') }}</td>
        </tr>
        <tr>
            <td><strong>Total Geral:</strong></td>
            <td>R$ {{ number_format($totalPago + $totalPendente, 2, ',', '.') }}</td>
        </tr>
    </table>
</body>

</html>
