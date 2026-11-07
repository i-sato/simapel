<?php

namespace Database\Seeders;

use App\Application\Administration\DelegateDocument;
use App\Application\Administration\DocumentInput;
use App\Application\Administration\UpdateDocument;
use App\Application\Assignment\UpdateTrainingAssignments;
use App\Application\Organization\AssignTeamMember;
use App\Application\Partner\SavePartner;
use App\Application\Shared\Contracts\Clock;
use App\Application\Sop\ActivateSopProvision;
use App\Application\Sop\CreateSopProvisionDraft;
use App\Application\Sop\SopProvisionInput;
use App\Application\Training\CreateTrainingDraft;
use App\Application\Training\FinalizeTraining;
use App\Application\Training\TrainingInput;
use App\Application\Training\UpdateTraining;
use App\Domain\Identity\Role;
use App\Domain\Organization\TeamRole;
use App\Domain\Organization\WorkTeamCode;
use App\Domain\Sop\SopProvisionStatus;
use App\Models\AdministrativeDocumentRecord;
use App\Models\SopProvisionRecord;
use App\Models\TrainingRecord;
use App\Models\User;
use App\Models\WorkTeam;
use DateTimeImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Data demo SINTETIS untuk UAT MVP-1 (langkah 8). Hanya untuk local/testing.
 *
 * - NIP selalu diawali 9999 (bukan NIP nyata); nama dan mitra fiktif.
 * - Semua data dibuat lewat use case aplikasi agar konsisten dengan aturan bisnis
 *   (salinan ketentuan SOP saat finalisasi, dokumen BAP/SK/ST, alasan bentrok).
 * - Tanggal relatif terhadap hari ini sehingga status waktu selalu beragam.
 * - Kata sandi demo dibuat acak dan ditampilkan sekali di terminal.
 */
final class DemoSeeder extends Seeder
{
    public const NIP_PREFIX = '99990000000002';

    /** @var array<string, int> kunci => id pengguna */
    private array $users = [];

    private int $actor = 0;

    private DateTimeImmutable $today;

    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('DemoSeeder hanya boleh dijalankan di lingkungan local atau testing.');
        }

        if (User::query()->where('nip', 'like', self::NIP_PREFIX.'%')->exists()) {
            throw new RuntimeException('Data demo sudah ada. Jalankan "make demo" untuk mengulang dari database kosong.');
        }

        $this->today = app(Clock::class)->now()->setTime(0, 0);
        $password = Str::password(16, symbols: false).'!a1';

        $this->createUsers($password);
        $this->assignTeams();
        $this->activateProvisions();
        $partners = $this->createPartners();
        $trainings = $this->createTrainings($partners);
        $this->assignCrews($trainings);
        $this->recordDocuments($trainings);

        $this->command?->warn('Data demo sintetis siap. Kata sandi SEMUA akun demo (hanya ditampilkan sekali):');
        $this->command?->line('  '.$password);
        $this->command?->info('Masuk dengan NIP berikut (lihat juga docs/mvp-1/uat.md):');

        foreach (self::accounts() as $key => [$name]) {
            $this->command?->line(sprintf('  %-22s %s  (%s)', $key, self::nip($key), $name));
        }
    }

    /**
     * Akun demo: kunci => [nama, jabatan, peran global, dapat menjadi Admin, nomor urut NIP].
     *
     * @return array<string, array{string, string, Role, bool, int}>
     */
    public static function accounts(): array
    {
        return [
            'superadmin' => ['Demo Super Admin', 'Kepala Seksi', Role::SuperAdmin, true, 1],
            'ketua_dta' => ['Demo Ketua DTA', 'Pranata Komputer Ahli Muda', Role::Pegawai, false, 11],
            'sekretaris_dta' => ['Demo Sekretaris DTA', 'Analis Kebijakan', Role::Pegawai, false, 12],
            'anggota_dta_1' => ['Demo Anggota DTA Satu', 'Pranata Komputer', Role::Pegawai, true, 13],
            'anggota_dta_2' => ['Demo Anggota DTA Dua', 'Pengelola Data', Role::Pegawai, false, 14],
            'anggota_dta_3' => ['Demo Anggota DTA Tiga', 'Pengadministrasi Umum', Role::Pegawai, false, 15],
            'ketua_kdd' => ['Demo Ketua KDD', 'Pranata Komputer Ahli Muda', Role::Pegawai, false, 21],
            'sekretaris_kdd' => ['Demo Sekretaris KDD', 'Analis SDM', Role::Pegawai, false, 22],
            'anggota_kdd_1' => ['Demo Anggota KDD Satu', 'Pranata Komputer', Role::Pegawai, true, 23],
            'anggota_kdd_2' => ['Demo Anggota KDD Dua', 'Pengelola Pelatihan', Role::Pegawai, false, 24],
            'anggota_kdd_3' => ['Demo Anggota KDD Tiga', 'Pengadministrasi Umum', Role::Pegawai, false, 25],
            'ketua_asn' => ['Demo Ketua ASN', 'Widyaiswara Ahli Muda', Role::Pegawai, false, 31],
            'sekretaris_asn' => ['Demo Sekretaris ASN', 'Analis Kepegawaian', Role::Pegawai, false, 32],
            'anggota_asn_1' => ['Demo Anggota ASN Satu', 'Pranata Komputer', Role::Pegawai, true, 33],
            'anggota_asn_2' => ['Demo Anggota ASN Dua', 'Pengelola Data', Role::Pegawai, false, 34],
            'anggota_asn_3' => ['Demo Anggota ASN Tiga', 'Pengadministrasi Umum', Role::Pegawai, false, 35],
            'pegawai_1' => ['Demo Pegawai Satu', 'Pengelola Keuangan', Role::Pegawai, false, 41],
            'pegawai_2' => ['Demo Pegawai Dua', 'Pranata Humas', Role::Pegawai, false, 42],
            'admin_lintas' => ['Demo Admin Lintas Tim', 'Pranata Komputer', Role::Pegawai, true, 43],
            'pegawai_nonaktif' => ['Demo Pegawai Nonaktif', 'Pengadministrasi Umum', Role::Pegawai, false, 44],
        ];
    }

    public static function nip(string $key): string
    {
        return self::NIP_PREFIX.sprintf('%04d', self::accounts()[$key][4]);
    }

    private function createUsers(string $password): void
    {
        foreach (self::accounts() as $key => [$name, $jabatan, $role, $canBeAdmin]) {
            $this->users[$key] = User::query()->create([
                'nip' => self::nip($key),
                'name' => $name,
                'email' => null,
                'jabatan' => $jabatan,
                'pangkat_golongan' => 'Penata Muda - III/a',
                'role' => $role,
                'can_be_admin' => $canBeAdmin,
                'is_active' => $key !== 'pegawai_nonaktif',
                'password' => $password,
                'password_change_required' => false,
            ])->id;
        }

        $this->actor = $this->users['superadmin'];
    }

    private function team(WorkTeamCode $code): int
    {
        $year = (int) $this->today->format('Y');

        return (int) (WorkTeam::query()->where('code', $code)->where('year', $year)->value('id')
            ?? WorkTeam::query()->create(['code' => $code, 'name' => $code->defaultName(), 'year' => $year])->id);
    }

    private function assignTeams(): void
    {
        $assign = app(AssignTeamMember::class);

        foreach (['dta' => WorkTeamCode::Dta, 'kdd' => WorkTeamCode::Kdd, 'asn' => WorkTeamCode::Asn] as $suffix => $code) {
            $team = $this->team($code);
            $assign->handle($team, $this->users['ketua_'.$suffix], TeamRole::Ketua, $this->actor);
            $assign->handle($team, $this->users['sekretaris_'.$suffix], TeamRole::Sekretaris, $this->actor);

            foreach ([1, 2, 3] as $n) {
                $assign->handle($team, $this->users["anggota_{$suffix}_{$n}"], TeamRole::Anggota, $this->actor);
            }
        }

        // Sekretaris KDD juga anggota tim DTA (satu pegawai di beberapa tim).
        $assign->handle($this->team(WorkTeamCode::Dta), $this->users['sekretaris_kdd'], TeamRole::Anggota, $this->actor);
    }

    /**
     * Ketentuan SOP Berlaku per tim untuk tahun berjalan. DTA memiliki revisi tengah tahun.
     */
    private function activateProvisions(): void
    {
        $year = (int) $this->today->format('Y');
        $create = app(CreateSopProvisionDraft::class);
        $activate = app(ActivateSopProvision::class);
        $deadlines = ['DTA' => [1, 2, 2, 3], 'KDD' => [5, 6, 6, 9], 'ASN' => [4, 4, 4, 6]];

        foreach (WorkTeamCode::cases() as $team) {
            $alreadyInForce = SopProvisionRecord::query()->where('team_code', $team)->where('fiscal_year', $year)
                ->where('status', SopProvisionStatus::Berlaku)->exists();

            if ($alreadyInForce) {
                continue;
            }

            [$bap, $sk, $st, $ready] = $deadlines[$team->value];
            $id = $create->handle($team, $year, new SopProvisionInput(
                sopNumber: sprintf('DEMO/SOP/%s/%d', $team->value, $year),
                sopEffectiveDate: $year.'-01-02',
                sopUrl: 'https://drive.example.test/demo/sop-'.strtolower($team->value),
                effectiveFrom: $year.'-01-02',
                bapDays: $bap, skDays: $sk, stDays: $st, readyDays: $ready,
                note: 'Ketentuan demo (sintetis).',
            ), $this->actor);
            $activate->handle($id, $this->actor);
        }

        // Revisi tengah tahun DTA: mulai 1 Juli, tenggat siap menjadi H+4.
        $revision = $create->handle(WorkTeamCode::Dta, $year, new SopProvisionInput(
            sopNumber: sprintf('DEMO/SOP/DTA/%d/REV', $year),
            sopEffectiveDate: $year.'-06-20',
            sopUrl: 'https://drive.example.test/demo/sop-dta-revisi',
            effectiveFrom: $year.'-07-01',
            bapDays: 1, skDays: 2, stDays: 2, readyDays: 4,
            note: 'Revisi tengah tahun (demo).',
        ), $this->actor);

        $current = SopProvisionRecord::query()->where('team_code', WorkTeamCode::Dta)->where('fiscal_year', $year)
            ->where('status', SopProvisionStatus::Berlaku)->value('effective_from');

        if ($current === null || $current->format('Y-m-d') < $year.'-07-01') {
            $activate->handle($revision, $this->actor);
        }
    }

    /**
     * @return array<string, string> kunci => nama mitra
     */
    private function createPartners(): array
    {
        $save = app(SavePartner::class);
        $partners = ['bali' => ['Demo Mitra Politeknik Bali', 'bali'], 'jateng' => ['Demo Mitra Universitas Jawa Tengah', 'jawa_tengah']];

        foreach ($partners as [$name, $region]) {
            $save->handle($this->actor, null, $name, $region);
        }

        return array_map(fn (array $partner): string => $partner[0], $partners);
    }

    private function day(int $offset): string
    {
        return $this->today->modify(sprintf('%+d days', $offset))->format('Y-m-d');
    }

    /**
     * @param  array<int, int>|null  $offsets  [awal daftar, akhir daftar, awal latih, akhir latih]
     * @param  array<string, mixed>  $overrides
     */
    private function input(string $name, ?array $offsets, array $overrides = []): TrainingInput
    {
        $values = array_merge([
            'name' => $name,
            'registrationUrl' => 'https://digitalent.example.test/demo/'.Str::slug($name),
            'registrationStart' => $offsets === null ? null : $this->day($offsets[0]),
            'registrationEnd' => $offsets === null ? null : $this->day($offsets[1]),
            'trainingStart' => $offsets === null ? null : $this->day($offsets[2]),
            'trainingEnd' => $offsets === null ? null : $this->day($offsets[3]),
            'partnerLocation' => 'Aula BLSDM Komdigi Yogyakarta',
            'method' => 'daring',
            'batch' => 1,
            'syllabusUrl' => 'https://drive.example.test/demo/silabus',
            'level' => 'dasar',
            'certification' => 'industri',
            'cocShowsScore' => true,
            'execution' => 'swakelola',
            'participantCategory' => 'umum',
            'zonation' => 'wilayah_kerja',
            'lessonHours' => 32,
            'participantTarget' => 100,
            'registrationQuota' => 300,
            'region' => 'diy',
        ], $overrides);

        return new TrainingInput(...$values);
    }

    /**
     * @param  array<string, string>  $partners
     * @return array<string, int> kunci => id pelatihan
     */
    private function createTrainings(array $partners): array
    {
        $create = app(CreateTrainingDraft::class);
        $finalize = app(FinalizeTraining::class);
        $dta = $this->team(WorkTeamCode::Dta);
        $kdd = $this->team(WorkTeamCode::Kdd);
        $asn = $this->team(WorkTeamCode::Asn);

        $specs = [
            // kunci => [tim, nama, offset, override, final?]
            'selesai' => [$kdd, 'Demo Literasi Digital untuk UMKM', [-60, -50, -45, -40], [], true],
            'berlangsung' => [$asn, 'Demo Transformasi Digital ASN', [-25, -12, -3, 3], ['method' => 'hybrid'], true],
            'penetapan' => [$kdd, 'Demo Keamanan Siber Dasar', [-12, -3, 2, 6], ['execution' => 'mitra', 'partnerName' => $partners['jateng']], true],
            'pendaftaran' => [$asn, 'Demo Analisis Data Pemerintahan', [-3, 6, 9, 12], ['execution' => 'mitra', 'partnerName' => $partners['bali'], 'region' => 'bali'], true],
            'akan_dibuka' => [$kdd, 'Demo Pemasaran Digital', [12, 20, 25, 28], [], true],
            'path_1' => [$dta, 'Demo VBL Web Developer Level 1', [-5, 10, 12, 20], ['newLearningPathName' => 'Demo VBL Web Developer'], true],
            'path_2' => [$dta, 'Demo VBL Web Developer Level 2', [21, 30, 32, 40], ['level' => 'menengah'], false],
            'path_3' => [$dta, 'Demo VBL Web Developer Level 3', [41, 50, 52, 60], ['level' => 'mahir', 'certification' => 'skkni'], false],
            'draft_kosong' => [$asn, 'Demo Draft Pelatihan Baru', null, ['registrationUrl' => null, 'syllabusUrl' => null], false],
        ];

        /** @var array<string, int> $ids */
        $ids = [];

        foreach ($specs as $key => [$team, $name, $offsets, $overrides, $final]) {
            if (str_starts_with($key, 'path_') && $key !== 'path_1') {
                $overrides['learningPathId'] = $this->learningPathOf($ids['path_1']);
            }

            $ids[$key] = $create->handle($this->actor, $team, $this->input($name, $offsets, $overrides));

            if ($final) {
                $finalize->handle($this->actor, $ids[$key]);
            }
        }

        // Link daftar peserta hanya setelah pendaftaran ditutup ("penetapan" sengaja dibiarkan kosong).
        $update = app(UpdateTraining::class);

        foreach (['selesai' => [$kdd, [-60, -50, -45, -40], []], 'berlangsung' => [$asn, [-25, -12, -3, 3], ['method' => 'hybrid']]] as $key => [$team, $offsets, $overrides]) {
            $name = $specs[$key][1];
            $update->handle($this->actor, $ids[$key], $team, $this->input($name, $offsets, $overrides + [
                'participantListUrl' => 'https://docs.example.test/demo/peserta-'.Str::slug($name),
            ]));
        }

        return $ids;
    }

    private function learningPathOf(int $trainingId): ?int
    {
        $id = TrainingRecord::query()->whereKey($trainingId)->value('learning_path_id');

        return $id === null ? null : (int) $id;
    }

    /**
     * @param  array<string, int>  $trainings
     */
    private function assignCrews(array $trainings): void
    {
        $assign = app(UpdateTrainingAssignments::class);
        $u = $this->users;
        $reason = 'Demo: pegawai menguasai materi sehingga tetap ditugaskan.';

        $assign->handle($this->actor, $trainings['selesai'], [$u['anggota_kdd_1']], [$u['anggota_kdd_2'], $u['anggota_kdd_3']], null);
        $assign->handle($this->actor, $trainings['berlangsung'], [$u['anggota_asn_1']], [$u['anggota_asn_2'], $u['pegawai_1']], null);
        // Bentrok: pegawai_1 dan anggota_asn_2 juga bertugas di pelatihan lain yang beririsan.
        $assign->handle($this->actor, $trainings['penetapan'], [$u['admin_lintas']], [$u['anggota_kdd_2'], $u['pegawai_1'], $u['ketua_asn']], $reason);
        $assign->handle($this->actor, $trainings['pendaftaran'], [$u['anggota_asn_1']], [$u['anggota_asn_2'], $u['sekretaris_dta']], $reason);
        $assign->handle($this->actor, $trainings['akan_dibuka'], [$u['anggota_kdd_1']], [$u['anggota_kdd_3']], null);
        $assign->handle($this->actor, $trainings['path_1'], [$u['anggota_dta_1']], [$u['anggota_dta_2'], $u['pegawai_2'], $u['admin_lintas']], $reason);
        // Admin Lintas Tim menjadi Admin draft Level 2 (UAT: Admin memfinalisasi draft yang ia admin-i).
        $assign->handle($this->actor, $trainings['path_2'], [$u['admin_lintas']], [$u['anggota_dta_3']], null);
    }

    /**
     * @param  array<string, int>  $trainings
     */
    private function recordDocuments(array $trainings): void
    {
        $delegate = app(DelegateDocument::class);
        $update = app(UpdateDocument::class);
        $documents = fn (string $key) => AdministrativeDocumentRecord::query()->where('training_id', $trainings[$key])->get()->keyBy(fn ($d) => $d->type->value);
        $done = fn (string $code, string $type): DocumentInput => new DocumentInput('selesai', sprintf('DEMO/%s/%s', strtoupper($type), $code), $this->day(-1), 'https://drive.example.test/demo/'.$type.'-'.$code);

        // Selesai: semua dokumen lengkap.
        foreach ($documents('selesai') as $type => $document) {
            $delegate->handle($this->actor, $document->id, $this->users['sekretaris_kdd']);
            $update->handle($this->actor, $document->id, $done('selesai', $type));
        }

        // Sedang berlangsung: BAP selesai, SK dalam proses, ST belum (memicu "kritis").
        $bap = $this->documentId($trainings['berlangsung'], 'bap');
        $sk = $this->documentId($trainings['berlangsung'], 'sk');
        $delegate->handle($this->actor, $bap, $this->users['sekretaris_asn']);
        $delegate->handle($this->actor, $sk, $this->users['sekretaris_asn']);
        $update->handle($this->actor, $bap, $done('berlangsung', 'bap'));
        $update->handle($this->actor, $sk, new DocumentInput('dalam_proses', 'DEMO/SK/DRAF'));

        // Penetapan peserta: didelegasikan ke Sekretaris KDD, belum dikerjakan.
        foreach ($documents('penetapan') as $document) {
            $delegate->handle($this->actor, $document->id, $this->users['sekretaris_kdd']);
        }
    }

    private function documentId(int $trainingId, string $type): int
    {
        return AdministrativeDocumentRecord::query()->where('training_id', $trainingId)->where('type', $type)->sole()->id;
    }
}
