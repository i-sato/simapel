<?php

namespace App\Http\Controllers;

use App\Application\Messaging\ResetCustomTemplate;
use App\Application\Messaging\SaveCustomTemplate;
use App\Application\Messaging\TrainingMessages;
use App\Domain\Messaging\Placeholder;
use App\Domain\Messaging\TemplateBody;
use App\Domain\Messaging\TemplateSet;
use App\Http\Requests\Training\MessageTemplateRequest;
use App\Models\TrainingRecord;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class MessageTemplateController extends Controller
{
    public function index(Request $request, TrainingRecord $training, TrainingMessages $messages): View
    {
        $data = $messages->for((int) $request->user()?->getAuthIdentifier(), $training->id);
        $custom = collect($data['custom'])->keyBy('id');

        $selected = $custom->get($request->integer('pilih'));
        $editing = $request->query('ubah');
        $editTemplate = is_string($editing) && ctype_digit($editing) ? $custom->get((int) $editing) : null;
        $creating = $editing === 'baru' && $custom->count() < TemplateSet::MAX_CUSTOM;

        return view('trainings.messages', [
            'training' => $training,
            'default' => $data['default'],
            'custom' => $data['custom'],
            'selected' => $selected,
            'preview' => $selected['text'] ?? $data['default']['text'],
            'editTemplate' => $editTemplate,
            'creating' => $creating,
            'editorBody' => $editTemplate['body'] ?? $data['default']['body'],
            'placeholders' => Placeholder::cases(),
            'required' => Placeholder::required(),
            'max' => TemplateSet::MAX_CUSTOM,
            'closingLines' => TemplateBody::CLOSING_LINES,
            'maxLength' => TemplateBody::MAX_LENGTH,
        ]);
    }

    public function store(MessageTemplateRequest $request, TrainingRecord $training, SaveCustomTemplate $save): RedirectResponse
    {
        return $this->saved($training, $save->handle((int) $request->user()?->getAuthIdentifier(), $training->id, null, $request->string('body')->toString()));
    }

    public function update(MessageTemplateRequest $request, TrainingRecord $training, int $template, SaveCustomTemplate $save): RedirectResponse
    {
        return $this->saved($training, $save->handle((int) $request->user()?->getAuthIdentifier(), $training->id, $template, $request->string('body')->toString()));
    }

    public function reset(Request $request, TrainingRecord $training, int $template, ResetCustomTemplate $reset): RedirectResponse
    {
        $reset->handle((int) $request->user()?->getAuthIdentifier(), $training->id, $template);

        return redirect()->route('trainings.messages.index', $training)->with('success', 'Template dikembalikan ke default.');
    }

    private function saved(TrainingRecord $training, ?int $id): RedirectResponse
    {
        return $id === null
            ? redirect()->route('trainings.messages.index', $training)->with('warning', 'Template identik dengan template default atau template Anda yang lain, sehingga digabung.')
            : redirect()->route('trainings.messages.index', ['training' => $training, 'pilih' => $id])->with('success', 'Template disimpan.');
    }
}
