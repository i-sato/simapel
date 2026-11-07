<?php

namespace App\Http\Controllers\Admin;

use App\Application\Identity\ImportEmployees;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ImportEmployeesRequest;
use App\Http\Support\EmployeeCsvReader;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\View\View;

final class EmployeeImportController extends Controller
{
    public function create(): View
    {
        return view('employees.import', [
            'header' => implode(',', EmployeeCsvReader::HEADER),
            'maxRows' => ImportEmployees::MAX_ROWS,
        ]);
    }

    public function store(ImportEmployeesRequest $request, EmployeeCsvReader $reader, ImportEmployees $importEmployees): RedirectResponse
    {
        /** @var UploadedFile $file */
        $file = $request->file('file');
        $result = $importEmployees->handle($reader->read($file), (int) $request->user()?->getAuthIdentifier());

        if (! $result->isSuccessful()) {
            return redirect()->route('employees.import.create')->with('import_errors', array_map(
                fn ($error): array => ['line' => $error->line, 'message' => $error->message],
                $result->errors,
            ));
        }

        return redirect()->route('employees.index')->with('success', sprintf(
            '%d pegawai diimpor. Tetapkan kata sandi sementara lewat "Ubah" agar pegawai dapat login.',
            $result->created,
        ));
    }
}
