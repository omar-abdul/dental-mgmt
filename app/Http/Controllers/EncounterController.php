<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSoapNoteAmendmentRequest;
use App\Http\Requests\UpdateSoapNoteRequest;
use App\Models\Encounter;
use App\Models\SoapNoteAmendment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class EncounterController extends Controller
{
    public function show(Request $request, Encounter $encounter): Response
    {
        Gate::authorize('view', $encounter);

        $encounter->load([
            'patient',
            'dentist',
            'treatment',
            'soapNote.amendments.creator',
            'soapNote.signer',
        ]);

        $soapNote = $encounter->soapNote;
        $user = $request->user();

        return Inertia::render('encounters/Show', [
            'encounter' => [
                'id' => $encounter->id,
                'number' => $encounter->number,
                'visited_at_formatted' => $encounter->visited_at->format('M j, Y g:i A'),
                'patient' => [
                    'id' => $encounter->patient->id,
                    'full_name' => "{$encounter->patient->first_name} {$encounter->patient->last_name}",
                    'patient_number' => $encounter->patient->patient_number,
                ],
                'dentist_name' => $encounter->dentist->display_name,
                'treatment_id' => $encounter->treatment_id,
            ],
            'soapNote' => $soapNote !== null ? [
                'subjective' => $soapNote->subjective,
                'objective' => $soapNote->objective,
                'assessment' => $soapNote->assessment,
                'plan' => $soapNote->plan,
                'is_signed' => $soapNote->isSigned(),
                'signed_at_formatted' => $soapNote->signed_at?->format('M j, Y g:i A'),
                'signed_by_name' => $soapNote->signer?->name,
                'amendments' => $soapNote->amendments->map(fn (SoapNoteAmendment $amendment) => [
                    'id' => $amendment->id,
                    'body' => $amendment->body,
                    'created_at_formatted' => $amendment->created_at?->format('M j, Y g:i A'),
                    'author_name' => $amendment->creator?->name,
                ])->values(),
            ] : null,
            'canUpdateSoap' => $user?->can('updateSoap', $encounter) ?? false,
            'canSign' => $user?->can('sign', $encounter) ?? false,
            'canAmend' => $user?->can('amend', $encounter) ?? false,
        ]);
    }

    public function updateSoap(UpdateSoapNoteRequest $request, Encounter $encounter): RedirectResponse
    {
        $encounter->loadMissing('soapNote');
        $soapNote = $encounter->soapNote;

        if ($soapNote === null || $soapNote->isSigned()) {
            abort(403);
        }

        $validated = $request->validated();
        $userId = $request->user()?->id;

        $soapNote->update([
            ...$validated,
            'updated_by' => $userId,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('SOAP note saved.')]);

        return to_route('encounters.show', $encounter);
    }

    public function sign(Request $request, Encounter $encounter): RedirectResponse
    {
        Gate::authorize('sign', $encounter);

        $encounter->loadMissing('soapNote');
        $soapNote = $encounter->soapNote;

        if ($soapNote === null || $soapNote->isSigned()) {
            abort(403);
        }

        $user = $request->user();

        abort_unless($user !== null, 403);

        $soapNote->sign($user);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Encounter signed.')]);

        return to_route('encounters.show', $encounter);
    }

    public function storeAmendment(StoreSoapNoteAmendmentRequest $request, Encounter $encounter): RedirectResponse
    {
        $encounter->loadMissing('soapNote');
        $soapNote = $encounter->soapNote;

        if ($soapNote === null || ! $soapNote->isSigned()) {
            abort(403);
        }

        $userId = $request->user()?->id;

        SoapNoteAmendment::query()->create([
            'soap_note_id' => $soapNote->id,
            'body' => trim($request->validated('body')),
            'created_by' => $userId,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Amendment recorded.')]);

        return to_route('encounters.show', $encounter);
    }
}
