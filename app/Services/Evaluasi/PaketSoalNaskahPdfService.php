<?php

namespace App\Services\Evaluasi;

use App\Models\LjkExam;
use App\Models\PaketSoal;
use App\Models\StudyGroup;
use App\Services\Ljk\LjkPdfFonts;
use App\Services\Ljk\LjkSoalSheet;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Carbon;

class PaketSoalNaskahPdfService
{
    public function data(PaketSoal $paket): array
    {
        $paket->loadMissing([
            'kisiKisi.subject',
            'kisiKisi.gradeLevel',
            'kisiKisi.academicYear',
            'items.soal.options',
            'items.soal.wacana',
        ]);

        $kisi = $paket->kisiKisi;
        $grade = $kisi?->gradeLevel;
        $studyGroup = new StudyGroup(['name' => $grade?->name ?? '']);
        if ($grade) {
            $studyGroup->setRelation('gradeLevel', $grade);
        }

        $exam = new LjkExam([
            'school_id' => $kisi?->school_id,
            'academic_year_id' => $kisi?->academic_year_id,
            'semester' => $kisi?->semester,
            'subject_id' => $kisi?->subject_id,
            'jenis_ujian' => $kisi?->jenis_ujian,
            'judul' => $paket->judul,
            'tanggal' => $paket->created_at ?? now(),
            'bahasa' => null,
        ]);
        $exam->setRelation('paketSoal', $paket);
        $exam->setRelation('school', $kisi?->school);
        $exam->setRelation('subject', $kisi?->subject);
        $exam->setRelation('academicYear', $kisi?->academicYear);
        $exam->setRelation('studyGroup', $studyGroup);
        $exam->setRelation('adminBook', null);

        $data = app(LjkSoalSheet::class)->data($exam);
        $layout = $paket->layoutSettings();

        $data['marginMm'] = [
            'top' => (float) $layout['margin_top'],
            'right' => (float) $layout['margin_right'],
            'bottom' => (float) $layout['margin_bottom'],
            'left' => (float) $layout['margin_left'],
        ];
        $data['fontSize'] = (float) $layout['font_size'];
        $data['lineHeight'] = (float) $layout['line_height'];
        $data['questionGap'] = 1.15;
        $data['paper'] = match ($layout['paper_size']) {
            'folio' => ['key' => 'f4', 'width_mm' => 215.0, 'height_mm' => 330.0, 'label' => 'F4 (Folio)'],
            'letter' => ['key' => 'letter', 'width_mm' => 215.9, 'height_mm' => 279.4, 'label' => 'Letter'],
            default => ['key' => 'a4', 'width_mm' => 210.0, 'height_mm' => 297.0, 'label' => 'A4'],
        };

        return $data;
    }

    public function html(PaketSoal $paket): string
    {
        return view('ljk.soal-pdf', $this->data($paket))->render();
    }

    public function pdf(PaketSoal $paket): string
    {
        $data = $this->data($paket);
        $options = new Options;
        $options->set('isRemoteEnabled', false);
        $options->set('isHtml5ParserEnabled', true);
        LjkPdfFonts::configure($options);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($this->html($paket));
        $paper = $data['paper'];
        $dompdf->setPaper([0, 0, $paper['width_mm'] * 2.834645669, $paper['height_mm'] * 2.834645669]);
        $dompdf->render();

        $canvas = $dompdf->getCanvas();
        $canvas->page_text($canvas->get_width() - 140, $canvas->get_height() - 5, 'Halaman {PAGE_NUM} dari {PAGE_COUNT}', 'serif', 8, [0.35, 0.35, 0.35]);

        return $dompdf->output();
    }
}
