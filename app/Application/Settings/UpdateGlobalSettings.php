<?php

declare(strict_types=1);

namespace App\Application\Settings;

use App\Application\Audit\Contracts\AuditTrail;
use App\Application\Settings\Contracts\SettingsRepository;
use App\Application\Shared\Contracts\Transaction;
use App\Domain\Audit\AuditEvent;
use App\Domain\Audit\AuditEventType;
use App\Domain\Settings\GlobalSetting;

final readonly class UpdateGlobalSettings
{
    public function __construct(
        private SettingsRepository $settings,
        private GlobalSettings $current,
        private AuditTrail $audit,
        private Transaction $transaction,
    ) {}

    /**
     * @param  array<string, string|null>  $input  key pengaturan => nilai baru; key yang tidak ada diabaikan
     * @return int jumlah pengaturan yang berubah
     */
    public function handle(array $input, int $actorId): int
    {
        $changes = [];

        foreach (GlobalSetting::cases() as $setting) {
            if (! array_key_exists($setting->value, $input)) {
                continue;
            }

            $new = $setting->normalize($input[$setting->value]);
            $old = $this->current->get($setting);

            if ($new !== $old) {
                $changes[] = [$setting, $old, $new];
            }
        }

        $this->transaction->run(function () use ($changes, $actorId): void {
            foreach ($changes as [$setting, $old, $new]) {
                $this->settings->put($setting, $new, $actorId);

                $this->audit->record(new AuditEvent(
                    type: AuditEventType::SettingUpdated,
                    description: sprintf('Pengaturan "%s" diubah.', $setting->label()),
                    actorId: $actorId,
                    subjectType: 'setting',
                    context: ['setting' => $setting->value, 'old_value' => $old, 'new_value' => $new],
                ));
            }
        });

        return count($changes);
    }
}
