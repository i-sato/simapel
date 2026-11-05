<?php

namespace Tests\Architecture;

use PhpToken;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Menegakkan aturan dependensi clean architecture (lihat docs/arsitektur.md):
 *
 *   Presentation (Http, View, Console) -> Application -> Domain
 *   Infrastructure (Infrastructure, Models, Providers) -> Application & Domain
 *
 * Domain & Application tidak boleh bergantung pada framework/infrastruktur, dan
 * presentasi tidak boleh memakai kelas Infrastructure secara langsung.
 */
final class LayerDependencyTest extends TestCase
{
    private const FRAMEWORK_AND_OUTER_LAYERS = [
        'Illuminate\\', 'Laravel\\', 'Carbon\\', 'Symfony\\',
        'App\\Infrastructure\\', 'App\\Models\\', 'App\\Http\\', 'App\\View\\', 'App\\Console\\', 'App\\Providers\\',
    ];

    /**
     * @return iterable<string, array{string, list<string>}>
     */
    public static function layerRules(): iterable
    {
        yield 'Domain murni' => ['app/Domain', [...self::FRAMEWORK_AND_OUTER_LAYERS, 'App\\Application\\']];
        yield 'Application tanpa framework' => ['app/Application', self::FRAMEWORK_AND_OUTER_LAYERS];
        yield 'Http tanpa Infrastructure' => ['app/Http', ['App\\Infrastructure\\']];
        yield 'View tanpa Infrastructure' => ['app/View', ['App\\Infrastructure\\']];
        yield 'Console tanpa Infrastructure' => ['app/Console', ['App\\Infrastructure\\']];
        yield 'Infrastructure tanpa Presentation' => ['app/Infrastructure', ['App\\Http\\', 'App\\View\\', 'App\\Console\\']];
    }

    /**
     * @param  list<string>  $forbiddenPrefixes
     */
    #[DataProvider('layerRules')]
    public function test_layer_does_not_depend_on_forbidden_namespaces(string $directory, array $forbiddenPrefixes): void
    {
        $violations = [];

        foreach ($this->phpFiles($directory) as $file) {
            foreach ($this->referencedNames($file) as $name) {
                foreach ($forbiddenPrefixes as $prefix) {
                    if (str_starts_with($name, $prefix)) {
                        $violations[] = sprintf('%s -> %s', $this->relative($file), $name);
                    }
                }
            }
        }

        $this->assertSame([], array_values(array_unique($violations)), 'Pelanggaran dependensi antar-layer ditemukan.');
    }

    public function test_application_contracts_are_interfaces(): void
    {
        $nonInterfaces = [];

        foreach ($this->phpFiles('app/Application') as $file) {
            if (! str_contains($file, DIRECTORY_SEPARATOR.'Contracts'.DIRECTORY_SEPARATOR)) {
                continue;
            }

            $tokens = PhpToken::tokenize((string) file_get_contents($file));
            $declaresInterface = array_filter($tokens, fn (PhpToken $token): bool => $token->is(T_INTERFACE)) !== [];

            if (! $declaresInterface) {
                $nonInterfaces[] = $this->relative($file);
            }
        }

        $this->assertSame([], $nonInterfaces, 'Folder Contracts hanya boleh berisi interface (port).');
    }

    /**
     * @return list<string>
     */
    private function phpFiles(string $directory): array
    {
        $path = dirname(__DIR__, 2).'/'.$directory;

        if (! is_dir($path)) {
            return [];
        }

        $files = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS));

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }

        sort($files);

        return $files;
    }

    /**
     * Nama kelas/namespace yang dirujuk lewat `use` maupun nama lengkap di kode.
     *
     * @return list<string>
     */
    private function referencedNames(string $file): array
    {
        $names = [];

        foreach (PhpToken::tokenize((string) file_get_contents($file)) as $token) {
            if ($token->is([T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED])) {
                $names[] = ltrim($token->text, '\\');
            }
        }

        return $names;
    }

    private function relative(string $file): string
    {
        return str_replace(dirname(__DIR__, 2).'/', '', $file);
    }
}
