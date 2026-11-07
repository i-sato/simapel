<?php

namespace App\Http\Controllers;

use App\Application\Administration\DelegateDocument;
use App\Application\Administration\DocumentAccess;
use App\Application\Administration\UpdateDocument;
use App\Domain\Administration\DocumentStatus;
use App\Http\Requests\Training\DocumentRequest;
use App\Models\AdministrativeDocumentRecord;
use App\Models\TrainingRecord;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class AdministrativeDocumentController extends Controller
{
    public function edit(Request $request, TrainingRecord $training, AdministrativeDocumentRecord $document, DocumentAccess $access): View
    {
        $this->ensureBelongs($training, $document);
        abort_unless($access->updatableIn((int) $request->user()?->getAuthIdentifier(), $training->id)[$document->id] ?? false, 403);

        return view('trainings.document-form', [
            'training' => $training,
            'document' => $document,
            'statuses' => DocumentStatus::cases(),
        ]);
    }

    public function update(DocumentRequest $request, TrainingRecord $training, AdministrativeDocumentRecord $document, UpdateDocument $update): RedirectResponse
    {
        $this->ensureBelongs($training, $document);
        $changed = $update->handle((int) $request->user()?->getAuthIdentifier(), $document->id, $request->toInput());

        return redirect()->route('trainings.show', $training)
            ->with('success', $changed ? $document->type->label().' diperbarui.' : 'Tidak ada perubahan.');
    }

    public function delegate(Request $request, TrainingRecord $training, AdministrativeDocumentRecord $document, DelegateDocument $delegate): RedirectResponse
    {
        $this->ensureBelongs($training, $document);
        $data = $request->validate(['assignee_id' => ['nullable', 'integer']]);
        $assignee = isset($data['assignee_id']) ? (int) $data['assignee_id'] : null;

        $delegate->handle((int) $request->user()?->getAuthIdentifier(), $document->id, $assignee);

        return redirect()->route('trainings.show', $training)
            ->with('success', sprintf('Delegasi %s disimpan.', $document->type->label()));
    }

    /**
     * Dokumen harus milik pelatihan pada URL (mencegah IDOR lewat id dokumen).
     */
    private function ensureBelongs(TrainingRecord $training, AdministrativeDocumentRecord $document): void
    {
        abort_unless($document->training_id === $training->id, 404);
    }
}
