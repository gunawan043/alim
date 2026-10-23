<?php

namespace Database\Seeders;

use App\Models\DormitoryPointCatalog;
use App\Models\DormitoryPointRule;
use Illuminate\Database\Seeder;

class DormitoryPointSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['Binaan Ringan', 1, 14, 'Pembinaan oleh wali kamar', false, ['wali_kamar'], 1],
            ['Binaan Sedang', 15, 49, 'Panggilan wali dan pembinaan asrama', false, ['kepala_asrama'], 2],
            ['Peringatan Berat', 50, 100, 'Surat peringatan / rekomendasi skorsing', true, ['kepala_asrama'], 3],
            ['Sangat Berat', 101, null, 'Rekomendasi dikeluarkan dari pesantren', true, ['kepala_asrama','superadmin'], 4],
        ] as [$name, $min, $max, $action, $approval, $roles, $order]) {
            DormitoryPointRule::updateOrCreate(['name' => $name], [
                'min_points' => $min, 'max_points' => $max, 'action' => $action,
                'requires_approval' => $approval, 'notify_roles' => $roles,
                'is_active' => true, 'sort_order' => $order,
            ]);
        }

        $violations = [
            ['101','Datang terlambat ke kelas 3x','kedisiplinan',3,3],
            ['102','Tidak menghadiri liqo maftuh/hiwar','kedisiplinan',5,5],
            ['103','Menyalahgunakan perizinan','kedisiplinan',5,5],
            ['104','Terkunci di kamar saat kegiatan pembelajaran','kedisiplinan',5,5],
            ['105','Masuk kamar saat kegiatan pembelajaran tanpa izin','kedisiplinan',5,5],
            ['106','Melampaui batas waktu perizinan dalam/luar kota','kedisiplinan',10,10],
            ['107','Melampaui batas waktu perizinan liburan','kedisiplinan',50,50],
            ['108','Tidak menetap di masjid pada jam kajian/murojaah/ekskul','kedisiplinan',5,5],
            ['109','Tidak mengikuti acara OSIP/Mudabbir','kedisiplinan',5,5],
            ['110','Tidak berada di kamar pada jam istirahat malam','kedisiplinan',5,5],
            ['111','Keluar lingkungan pesantren tanpa izin','kedisiplinan',25,50],
            ['112','Bermalam di luar pesantren tanpa izin','kedisiplinan',30,50],
            ['113','Tidak mengikuti kegiatan pesantren tanpa izin','kedisiplinan',20,20],
            ['114','Terlambat shalat fardhu berjamaah 3x','kedisiplinan',5,5],
            ['115','Tidak ikut shalat fardhu berjamaah','kedisiplinan',5,10],
            ['201','Masuk ruang guru/kantor tanpa izin','etika',10,10],
            ['202','Menggunakan sarana prasarana tanpa izin','etika',10,10],
            ['203','Menyalahgunakan sarana prasarana','etika',15,15],
            ['204','Mengadakan kegiatan tanpa izin','etika',30,30],
            ['205','Membuat kegaduhan/keributan','etika',5,20],
            ['207','Potongan rambut menyalahi syariat','etika',25,25],
            ['208','Memanjangkan kuku','etika',5,5],
            ['213','Berbohong','etika',25,25],
            ['215','Musik, bernyanyi, berjoget','etika',10,30],
            ['219','Kata kotor/ejekan','etika',25,25],
            ['227','Pacaran','etika',50,200],
            ['228','Mengadu domba','etika',50,50],
            ['229','Perkelahian terencana','etika',100,100],
            ['230','Perkelahian tidak terencana','etika',50,50],
            ['238','Diantar/dijemput bukan mahrom','etika',50,100],
            ['242','Gejala homoseksual/lesbian','etika',100,100],
            ['243','Berzina/homoseksual/lesbian','etika',243,243],
            ['301','Membawa/menyimpan gambar artis','barang_terlarang',10,10],
            ['302','Membawa HP ke lingkungan pesantren','barang_terlarang',100,100],
            ['303','Meminjam/menyimpan/menggunakan HP','barang_terlarang',25,25],
            ['306','Membawa/menyimpan/mengisap rokok','barang_terlarang',75,75],
            ['307','Permainan monopoli/karambol/catur/kartu','barang_terlarang',10,10],
            ['312','Menyimpan media porno','barang_terlarang',30,50],
            ['313','Membawa senjata tajam/api','barang_terlarang',50,100],
            ['315','Menyewa/meminjam motor/mobil','barang_terlarang',50,100],
            ['401','Merusak/menghilangkan sarpras','kriminal',10,50],
            ['402','Mencederai orang lain','kriminal',50,100],
            ['403','Membuat oplosan berbahaya','kriminal',100,100],
            ['406','Mencuri','kriminal',50,100],
            ['408','Berjudi','kriminal',50,100],
            ['409','Mengkonsumsi miras/narkoba','kriminal',409,409],
            ['411','Terlibat tindak kriminal kepolisian','kriminal',411,411],
            ['415','Menganiaya GTK','kriminal',415,415],
        ];

        foreach ($violations as [$code,$name,$category,$min,$max]) {
            DormitoryPointCatalog::updateOrCreate(['code' => $code], [
                'kind' => 'violation', 'name' => $name, 'category' => $category,
                'severity' => $min >= 101 ? 'sangat_berat' : ($min >= 50 ? 'berat' : ($min >= 15 ? 'sedang' : 'ringan')),
                'points_min' => $min, 'points_max' => $max,
                'is_permanent' => in_array($code, ['302','306','409','415'], true),
                'is_active' => true, 'sort_order' => (int) $code,
            ]);
        }

        $rewards = [
            ['R-01','Ranking 1-3','prestasi','akademik',25], ['R-02','Ranking 4-10','prestasi','akademik',15],
            ['R-03','Juara classmeeting','prestasi','akademik',5], ['R-04','Tanpa alpha/izin/skorsing/sakit','prestasi','akademik',20],
            ['R-05','Nilai tertinggi per kelas','prestasi','akademik',30], ['R-06','Juara umum sekolah/madrasah','prestasi','akademik',50],
            ['R-07','Ketua kamar','prestasi','organisasi',20], ['R-08','Ketua kelas','prestasi','organisasi',20],
            ['R-09','Ketua OSIP','prestasi','organisasi',50], ['R-10','Pengurus OSIP','prestasi','organisasi',30],
            ['R-11','Hafal Quran melebihi target','prestasi','tahfidz',20], ['R-12','Hafal hadits melebihi target','prestasi','tahfidz',10],
            ['R-13','Reward sikap interpersonal','sikap','interpersonal',25], ['R-14','Reward sikap sosial','sikap','sosial',25],
        ];

        foreach ($rewards as [$code,$name,$category,$sub,$points]) {
            DormitoryPointCatalog::updateOrCreate(['code' => $code], [
                'kind' => 'reward', 'name' => $name, 'category' => $category,
                'severity' => $sub, 'points_min' => $points, 'points_max' => $points,
                'is_permanent' => false, 'is_active' => true, 'sort_order' => (int) substr($code, 2),
            ]);
        }
    }
}
