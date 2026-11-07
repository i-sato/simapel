<?php

namespace App\Http\Controllers;

use App\Application\Partner\SavePartner;
use App\Domain\Partner\Partner;
use App\Domain\Shared\WorkRegion;
use App\Models\PartnerRecord;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

final class PartnerController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim($request->string('q')->toString());

        $partners = PartnerRecord::query()
            ->withCount('trainings')
            ->when($search !== '', fn ($query) => $query->where('normalized_name', 'like', '%'.Partner::normalize($search).'%'))
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        return view('partners.index', [
            'partners' => $partners,
            'regions' => WorkRegion::cases(),
            'search' => $search,
            'editing' => $request->integer('ubah') ?: null,
        ]);
    }

    public function store(Request $request, SavePartner $save): RedirectResponse
    {
        $data = $this->validated($request);
        $save->handle((int) $request->user()?->getAuthIdentifier(), null, $data['partner_name'], $data['work_region']);

        return redirect()->route('partners.index')->with('success', 'Mitra didaftarkan.');
    }

    public function update(Request $request, PartnerRecord $partner, SavePartner $save): RedirectResponse
    {
        $data = $this->validated($request);
        $save->handle((int) $request->user()?->getAuthIdentifier(), $partner->id, $data['partner_name'], $data['work_region']);

        return redirect()->route('partners.index')->with('success', 'Mitra diperbarui.');
    }

    /**
     * @return array{partner_name: string, work_region: string}
     */
    private function validated(Request $request): array
    {
        /** @var array{partner_name: string, work_region: string} $data */
        $data = $request->validate([
            'partner_name' => ['required', 'string', 'max:'.Partner::MAX_NAME],
            'work_region' => ['required', Rule::enum(WorkRegion::class)],
        ], [], ['partner_name' => 'nama mitra', 'work_region' => 'wilayah kerja']);

        return $data;
    }
}
