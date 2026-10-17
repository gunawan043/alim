<?php

namespace App\Http\Controllers;

use App\Models\RecruitmentPipeline;
use App\Models\RecruitmentPipelineStage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SettingController extends Controller
{
    public function index(string $userId)
    {
        $stages = RecruitmentPipelineStage::with('recruitmentPipeline')
            ->orderBy('recruitment_pipeline_id')
            ->orderBy('urutan')
            ->get()
            ->groupBy('recruitment_pipeline_id');

        $pipelines = RecruitmentPipeline::withCount('stages')->get();

        $emailTemplates = $this->getEmailTemplates();
        $recruitmentSettings = $this->getRecruitmentSettings();

        return view('recruitment.settings.index', compact(
            'userId', 'stages', 'pipelines', 'emailTemplates', 'recruitmentSettings'
        ));
    }

    public function update(Request $request, string $userId)
    {
        $validated = $request->validate([
            'nama_tahapan' => 'nullable|string|max:255',
            'durasi_default' => 'nullable|integer|min:1|max:365',
            'bobot_adm' => 'nullable|integer|min:0|max:100',
            'bobot_tes' => 'nullable|integer|min:0|max:100',
            'bobot_wawancara' => 'nullable|integer|min:0|max:100',
            'nilai_min_lulus' => 'nullable|numeric|min:0|max:100',
            'notif_email' => 'nullable|boolean',
            'notif_whatsapp' => 'nullable|boolean',
        ]);

        foreach ($validated as $key => $value) {
            if ($value !== null) {
                setting_put("recruitment.{$key}", $value);
            }
        }

        return redirect()->back()->with('success', 'Pengaturan berhasil disimpan.');
    }

    public function updateStages(Request $request, string $userId)
    {
        $validated = $request->validate([
            'stages' => 'required|array',
            'stages.*.id' => 'required|exists:recruitment_pipeline_stages,id',
            'stages.*.urutan' => 'required|integer|min:1',
            'stages.*.nama_tahapan' => 'required|string|max:255',
            'stages.*.durasi_hari' => 'nullable|integer|min:1|max:365',
            'stages.*.warna' => 'nullable|string|max:20',
            'stages.*.icon' => 'nullable|string|max:50',
            'stages.*.is_wajib' => 'nullable|boolean',
        ]);

        DB::transaction(function () use ($validated) {
            foreach ($validated['stages'] as $data) {
                RecruitmentPipelineStage::where('id', $data['id'])->update([
                    'urutan' => $data['urutan'],
                    'nama_tahapan' => $data['nama_tahapan'],
                    'durasi_hari' => $data['durasi_hari'] ?? null,
                    'warna' => $data['warna'] ?? null,
                    'icon' => $data['icon'] ?? null,
                    'is_wajib' => $data['is_wajib'] ?? 1,
                ]);
            }
        });

        return redirect()->back()->with('success', 'Tahapan berhasil diperbarui.');
    }

    public function updateEmailTemplates(Request $request, string $userId)
    {
        $validated = $request->validate([
            'templates' => 'required|array',
            'templates.lolos_adm.subject' => 'nullable|string|max:255',
            'templates.lolos_adm.body' => 'nullable|string',
            'templates.tidak_lolos_adm.subject' => 'nullable|string|max:255',
            'templates.tidak_lolos_adm.body' => 'nullable|string',
            'templates.interview.subject' => 'nullable|string|max:255',
            'templates.interview.body' => 'nullable|string',
            'templates.diterima.subject' => 'nullable|string|max:255',
            'templates.diterima.body' => 'nullable|string',
            'templates.ditolak.subject' => 'nullable|string|max:255',
            'templates.ditolak.body' => 'nullable|string',
            'templates.reminder.subject' => 'nullable|string|max:255',
            'templates.reminder.body' => 'nullable|string',
        ]);

        foreach ($validated['templates'] as $key => $tmpl) {
            setting_put("recruitment.email_templates.{$key}", $tmpl);
        }

        return redirect()->back()->with('success', 'Template email berhasil disimpan.');
    }

    private function getEmailTemplates(): array
    {
        $defaults = [
            'lolos_adm' => [
                'subject' => 'Selamat! Anda Lolos Seleksi Administrasi',
                'body' => "Yth. {{nama}},\n\nSelamat! Anda telah lolos seleksi administrasi untuk posisi {{posisi}}.\n\nLangkah selanjutnya adalah tes tertulis yang akan diumumkan kemudian.\n\nHormat kami,\nTim Rekrutmen",
            ],
            'tidak_lolos_adm' => [
                'subject' => 'Pengumuman Hasil Seleksi Administrasi',
                'body' => "Yth. {{nama}},\n\nTerima kasih telah melamar posisi {{posisi}}.\n\nMohon maaf, setelah melalui proses seleksi administrasi, kami belum dapat melanjutkan aplikasi Anda ke tahap berikutnya.\n\nHormat kami,\nTim Rekrutmen",
            ],
            'interview' => [
                'subject' => 'Undangan Interview - {{posisi}}',
                'body' => "Yth. {{nama}},\n\nKami mengundang Anda untuk mengikuti interview pada:\n\nHari/Tanggal: {{tanggal}}\nWaktu: {{waktu}}\nTempat: {{lokasi}}\n\nMohon hadir 15 menit sebelum waktunya.\n\nHormat kami,\nTim Rekrutmen",
            ],
            'diterima' => [
                'subject' => 'Selamat! Anda Diterima sebagai {{posisi}}',
                'body' => "Yth. {{nama}},\n\nDengan senang hati kami informasikan bahwa Anda diterima sebagai {{posisi}}.\n\nPengumuman lebih lanjut mengenai onboarding akan dikirimkan melalui email ini.\n\nSelamat dan selamat bergabung!\n\nHormat kami,\nTim Rekrutmen",
            ],
            'ditolak' => [
                'subject' => 'Pengumuman Hasil Rekrutmen Akhir',
                'body' => "Yth. {{nama}},\n\nTerima kasih telah mengikuti seluruh proses rekrutmen untuk posisi {{posisi}}.\n\nMohon maaf, setelah mempertimbangkan seluruh aspek, kami belum dapat menerima Anda pada kesempatan ini.\n\nKami tetap menghargai kualifikasi Anda dan mendorong Anda untuk melamar di posisi lain di masa depan.\n\nHormat kami,\nTim Rekrutmen",
            ],
            'reminder' => [
                'subject' => 'Reminder: Jadwal {{tahapan}} besok',
                'body' => "Yth. {{nama}},\n\nIni adalah pengingat bahwa Anda memiliki jadwal {{tahapan}} besok.\n\nWaktu: {{waktu}}\nTempat: {{lokasi}}\n\nMohon hadir tepat waktu.\n\nHormat kami,\nTim Rekrutmen",
            ],
        ];

        foreach ($defaults as $key => $tmpl) {
            $saved = setting("recruitment.email_templates.{$key}");
            if ($saved) {
                $defaults[$key] = array_merge($tmpl, (array) $saved);
            }
        }

        return $defaults;
    }

    private function getRecruitmentSettings(): array
    {
        return [
            'nama_tahapan' => setting('recruitment.nama_tahapan', 'Tahapan Seleksi'),
            'durasi_default' => (int) setting('recruitment.durasi_default', 7),
            'bobot_adm' => (int) setting('recruitment.bobot_adm', 20),
            'bobot_tes' => (int) setting('recruitment.bobot_tes', 30),
            'bobot_wawancara' => (int) setting('recruitment.bobot_wawancara', 50),
            'nilai_min_lulus' => (float) setting('recruitment.nilai_min_lulus', 70),
            'notif_email' => (bool) setting('recruitment.notif_email', true),
            'notif_whatsapp' => (bool) setting('recruitment.notif_whatsapp', false),
        ];
    }
}
