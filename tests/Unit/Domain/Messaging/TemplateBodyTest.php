<?php

namespace Tests\Unit\Domain\Messaging;

use App\Domain\Messaging\CustomTemplate;
use App\Domain\Messaging\DefaultTemplate;
use App\Domain\Messaging\InvalidMessageTemplate;
use App\Domain\Messaging\TemplateBody;
use App\Domain\Messaging\TemplateSet;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class TemplateBodyTest extends TestCase
{
    public const VALID = "Halo peserta {{nama_pelatihan}} 🎉\nDaftar: {{link_pendaftaran}}\nPendaftaran {{tanggal_pendaftaran}}, pelatihan {{tanggal_pelatihan}}\nPanduan: {{link_panduan_pelatihan}}\nPelatihan ini GRATIS.";

    public function test_accepts_valid_template_and_normalizes_whitespace(): void
    {
        $body = new TemplateBody("  \r\n".str_replace("\n", "   \r\n", self::VALID)."\n\n");

        $this->assertSame(self::VALID, $body->value);
        $this->assertTrue($body->equals(self::VALID.'  '));
    }

    public function test_default_templates_satisfy_custom_rules(): void
    {
        $this->assertStringEndsWith('Terima kasih', (new TemplateBody(DefaultTemplate::body(false)))->value);
        $this->assertStringContainsString('{{learning_path}}', (new TemplateBody(DefaultTemplate::body(true)))->value);
        $this->assertStringNotContainsString('{{learning_path}}', DefaultTemplate::body(false));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function invalid(): iterable
    {
        yield 'kosong' => ['   ', 'wajib diisi'];
        yield 'placeholder tak dikenal' => [self::VALID.' {{nomor_hp}}', 'tidak dikenal: {{nomor_hp}}'];
        yield 'kode di dalam kurung kurawal' => [self::VALID.' {{ system("id") }}', 'tidak dikenal'];
        yield 'placeholder berspasi' => [self::VALID.' {{ nama_pelatihan }}', 'tidak dikenal'];
        yield 'echo tanpa escape' => [self::VALID.' {!! $x !!}', 'tidak dikenal'];
        yield 'tanpa link pendaftaran' => [str_replace('{{link_pendaftaran}}', '-', self::VALID), '{{link_pendaftaran}}'];
        yield 'tanpa tanggal pelatihan' => [str_replace('{{tanggal_pelatihan}}', '-', self::VALID), '{{tanggal_pelatihan}}'];
        yield 'tanpa link panduan' => [str_replace('{{link_panduan_pelatihan}}', '-', self::VALID), '{{link_panduan_pelatihan}}'];
        yield 'tanpa kata gratis' => [str_replace('GRATIS', 'tanpa biaya', self::VALID), 'gratis'];
        yield 'gratis tidak di akhir' => ["Pelatihan gratis!\n".self::VALID."\na\nb\nc\nd\ne", 'gratis'];
        yield 'kata gratisan bukan gratis' => [str_replace('GRATIS', 'gratisan', self::VALID), 'gratis'];
        yield 'terlalu panjang' => [self::VALID."\n".str_repeat('a', TemplateBody::MAX_LENGTH), 'maksimal'];
    }

    #[DataProvider('invalid')]
    public function test_rejects_invalid_templates(string $body, string $message): void
    {
        try {
            new TemplateBody($body);
            $this->fail('Template seharusnya ditolak.');
        } catch (InvalidMessageTemplate $exception) {
            $this->assertSame('body', $exception->field());
            $this->assertStringContainsString($message, $exception->getMessage());
        }
    }

    public function test_php_like_text_is_allowed_because_it_is_never_executed(): void
    {
        $this->assertStringContainsString('@php', (new TemplateBody("@php echo 1; @endphp\n".self::VALID))->value);
    }

    public function test_limit_of_three_custom_templates(): void
    {
        $template = fn (int $id): CustomTemplate => new CustomTemplate($id, 1, 1, new TemplateBody(self::VALID.$id));

        TemplateSet::assertCanAdd([$template(1), $template(2)]);

        $this->expectException(InvalidMessageTemplate::class);
        TemplateSet::assertCanAdd([$template(1), $template(2), $template(3)]);
    }

    public function test_identical_templates_are_merged_keeping_the_first(): void
    {
        $default = DefaultTemplate::body(false);
        $templates = [
            new CustomTemplate(1, 1, 1, new TemplateBody(self::VALID)),
            new CustomTemplate(2, 1, 1, new TemplateBody($default."  \n")),
            new CustomTemplate(3, 1, 1, new TemplateBody(self::VALID."\n")),
            new CustomTemplate(4, 1, 1, new TemplateBody(self::VALID.'!')),
        ];

        $this->assertSame([2, 3], TemplateSet::redundant($templates, $default));
        $this->assertSame([], TemplateSet::redundant([$templates[0], $templates[3]], $default));
    }
}
