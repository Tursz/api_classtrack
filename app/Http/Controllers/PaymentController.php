<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PaymentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    private function buildPaymentsQuery(Request $request)
    {
        $user = $request->user();

        return $user->learners()
            ->with(['payments' => function ($query) use ($request) {
                if ($request->filled('month')) {
                    // "month" chega como "YYYY-MM"
                    $query->whereRaw("DATE_FORMAT(date, '%Y-%m') = ?", [$request->month]);
                }
                $query->orderBy('date');
            }]);
    }

    public function index(Request $request): JsonResponse
    {
        $learners = $this->buildPaymentsQuery($request)->get();

        $payments = $learners->flatMap(fn ($learner) => $learner->payments->map(
            fn ($payment) => [
                'id' => $payment->id,
                'learner_id' => $learner->id,
                'learner_name' => $learner->name,
                'value' => $payment->value,
                'method' => $payment->method,
                'date' => $payment->date,
                'status' => $payment->status,
            ]
        ))->values();

        return response()->json(['message' => 'Pagamentos encontrados', 'data' => $payments], Response::HTTP_OK);
    }

    public function report(Request $request)
    {
        $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
        ], [
            'month.date_format' => 'O mês deve estar no formato AAAA-MM (ex: 2026-05).',
        ]);

        $learners = $this->buildPaymentsQuery($request)->get();

        $payments = $learners->flatMap(fn ($learner) => $learner->payments->map(
            fn ($payment) => [
                'learner_name' => $learner->name,
                'value' => $payment->value,
                'method' => $payment->method,
                'date' => $payment->date,
                'status' => $payment->status,
            ]
        ))->values();

        $totalPago = $payments->where('status', 'concluded')->sum('value');
        $totalPendente = $payments->where('status', 'pending')->sum('value');

        $pdf = Pdf::loadView('reports.payments', [
            'payments' => $payments,
            'month' => $request->month,
            'userName' => $request->user()->name,
            'totalPago' => $totalPago,
            'totalPendente' => $totalPendente,
        ]);

        $fileName = 'relatorio-pagamentos' . ($request->month ? "-{$request->month}" : '') . '.pdf';

        return $pdf->download($fileName);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'learner_id' => ['required', 'exists:learners,id'],
            'value' => ['required', 'numeric'],
            'payment_method' => ['required', 'string'],
            'date' => ['required', 'date'],
            'status' => ['required', 'in:pending,concluded'],
        ], [
            'learner_id.required' => 'O aluno é obrigatório.',
            'learner_id.exists' => 'O aluno selecionado não foi encontrado.',
            'value.required' => 'O valor é obrigatório.',
            'value.numeric' => 'O valor deve ser um número.',
            'payment_method.required' => 'A forma de pagamento é obrigatória.',
            'payment_method.string' => 'A forma de pagamento deve ser um texto.',
            'date.required' => 'A data é obrigatória.',
            'date.date' => 'Informe uma data válida.',
            'status.required' => 'O status do pagamento é obrigatório.',
            'status.in' => 'O status deve ser "pending" (pendente) ou "concluded" (concluído).',
        ]);

        $learner = \App\Models\Learner::findOrFail($request->learner_id);
        if ($learner->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Ação não autorizada'], Response::HTTP_UNAUTHORIZED);
        }

        $payment = Payment::create([
            'learner_id' => $request->learner_id,
            'method' => $request->payment_method,
            'date' => $request->date,
            'status' => $request->status,
            'value' => $request->value,
        ]);

        return response()->json(['message' => 'Pagamento realizado com sucesso', 'data' => $payment], Response::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     */
    public function show(Payment $payment): JsonResponse
    {
        $payment->load('learner');
        return response()->json([
            'message' => 'Pagamento encontrado',
            'data' => $payment
        ], Response::HTTP_OK);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Payment $payment): JsonResponse
    {

        if (!$payment->learner->user_id == $request->user()->id) {
            return response()->json(['message' => 'Ação não autorizada'], Response::HTTP_UNAUTHORIZED);
        }

        $payment->update([
            'learner_id' => $request->learner_id ?: $payment->learner_id,
            'method' => $request->payment_method ?: $payment->method,
            'date' => $request->date ?: $payment->date,
            'status' => $request->status ?: $payment->status,
            'value' => $request->value ?: $payment->value
        ]);

        return response()->json(['message' => 'Pagamento atualizado com sucesso', 'data' => $payment], Response::HTTP_OK);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Payment $payment): JsonResponse|Response
    {
        if (!$payment->learner->user_id == $request->user()->id) {
            return response()->json(['message' => 'Ação não autorizada'], Response::HTTP_UNAUTHORIZED);
        }

        $payment->delete();

        return response()->noContent();
    }
}
