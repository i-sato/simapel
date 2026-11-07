<?php

declare(strict_types=1);

namespace App\Domain\Messaging;

/**
 * Aturan kumpulan template kustom milik satu anggota pada satu pelatihan:
 * maksimal 3, dan template yang identik dengan default atau dengan template lain
 * digabung (yang pertama dipertahankan).
 */
final class TemplateSet
{
    public const MAX_CUSTOM = 3;

    /**
     * @param  list<CustomTemplate>  $existing
     *
     * @throws InvalidMessageTemplate
     */
    public static function assertCanAdd(array $existing): void
    {
        if (count($existing) >= self::MAX_CUSTOM) {
            throw new InvalidMessageTemplate(sprintf('Maksimal %d template kustom per pelatihan. Ubah atau reset template yang ada.', self::MAX_CUSTOM));
        }
    }

    /**
     * @param  list<CustomTemplate>  $templates  urut dari yang terlama
     * @return list<int> id template yang harus dihapus
     */
    public static function redundant(array $templates, string $defaultBody): array
    {
        $default = TemplateBody::normalize($defaultBody);
        $seen = [];
        $remove = [];

        foreach ($templates as $template) {
            $body = $template->body->value;

            if ($template->id !== null && ($body === $default || in_array($body, $seen, true))) {
                $remove[] = $template->id;

                continue;
            }

            $seen[] = $body;
        }

        return $remove;
    }
}
