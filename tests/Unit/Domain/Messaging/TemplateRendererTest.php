<?php

namespace Tests\Unit\Domain\Messaging;

use App\Domain\Messaging\MessageValues;
use App\Domain\Messaging\TemplateRenderer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class TemplateRendererTest extends TestCase
{
    /**
     * @param  array<string, string|null>  $overrides
     */
    private function values(array $overrides = []): MessageValues
    {
        return new MessageValues(array_merge([
            'nama_pelatihan' => 'Analisis Data',
            'link_pendaftaran' => 'https://digitalent.example.test/pelatihan/1',
            'tanggal_pendaftaran' => '1–10 Desember 2026',
            'tanggal_pelatihan' => null,
            'link_daftar_peserta' => '   ',
        ], $overrides));
    }

    public function test_replaces_whitelisted_placeholders_and_marks_missing_info(): void
    {
        $text = TemplateRenderer::render("📌 {{nama_pelatihan}}\nDaftar: {{link_pendaftaran}} ({{tanggal_pendaftaran}})\nPelatihan: {{tanggal_pelatihan}}\nPeserta: {{link_daftar_peserta}}", $this->values());

        $this->assertSame("📌 Analisis Data\nDaftar: https://digitalent.example.test/pelatihan/1 (1–10 Desember 2026)\nPelatihan: (menyusul)\nPeserta: (menyusul)", $text);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function injections(): iterable
    {
        yield 'echo Blade berisi kode' => ['{{ system("id") }} {{ $user->password }}'];
        yield 'echo tanpa escape' => ['{!! phpinfo() !!}'];
        yield 'direktif Blade' => ["@php echo 'x'; @endphp @include('layouts.app')"];
        yield 'tag PHP' => ['<?php echo 1; ?> <?= 2 ?>'];
        yield 'spasi di dalam placeholder' => ['{{ nama_pelatihan }}'];
    }

    #[DataProvider('injections')]
    public function test_code_like_text_stays_literal(string $template): void
    {
        $this->assertSame($template, TemplateRenderer::render($template, $this->values()));
    }

    public function test_values_are_not_expanded_again(): void
    {
        $text = TemplateRenderer::render('{{nama_pelatihan}} {{link_pendaftaran}}', $this->values(['nama_pelatihan' => '{{link_pendaftaran}} @php']));

        $this->assertSame('{{link_pendaftaran}} @php https://digitalent.example.test/pelatihan/1', $text);
    }
}
