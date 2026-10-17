<?php

namespace App\Http\Middleware;

use App\Models\TeacherAdminBook;
use App\Services\AcademicPeriodService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * ARSIP PERIODE: blokir perubahan buku nilai (guru mapel) yang periode
 * tahun ajarannya sudah ditutup (terkunci) atau bukan TA aktif.
 */
class EnsureBookPeriodWritable
{
    public function __construct(private readonly AcademicPeriodService $periods) {}

    public function handle(Request $request, Closure $next): Response
    {
        $bookId = $request->route('adminBookId');

        if ($bookId) {
            $book = TeacherAdminBook::query()->find($bookId);

            if ($book) {
                $this->periods->assertWritable($book->academic_year_id, 'Buku nilai');
            }
        }

        return $next($request);
    }
}
