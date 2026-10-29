<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\AdditionalTaskType;
use App\Models\GradeLevel;
use App\Models\GtkCrossUnitAssignment;
use App\Models\GtkWorkUnit;
use App\Models\InstitutionDecree;
use App\Models\JenisGtk;
use App\Models\OtherTeacherTask;
use App\Models\School;
use App\Models\StudyGroup;
use App\Models\Subject;
use App\Services\InstitutionDecreeMatrixService;
use Illuminate\Database\Seeder;

/**
 * SK Pembagian Tugas Mengajar Semester Ganjil 2026/2027
 * SMA IT Putri Abu Hurairah Mataram — Nomor 300/SMA IT/SK/I/VII/2026.
 *
 * Idempoten: master yang sudah ada dipakai ulang; isi matriks SK
 * disinkronkan ulang bila seeder dijalankan lagi.
 */
class SkSmaItPutriGanjil2026Seeder extends Seeder
{
    public function run(): void
    {
        $school = School::where('name', 'like', '%SMA IT Putri Abu Hurairah%')->firstOrFail();
        $academicYear = AcademicYear::where('name', '2026/2027')
            ->where('semester', 'ganjil')
            ->firstOrFail();

        $payload = json_decode(<<<'JSON'
{
  "teachers": [
    {
      "user_id": "6816c594-66ea-4cc3-b7de-1979afe6738d",
      "name": "Ust. Gunawan Trianto, M.Pd.",
      "subjects": [],
      "tasks": [
        {
          "label": "Kepala Sekolah",
          "hours": 12,
          "group_col": null
        }
      ]
    },
    {
      "user_id": "00b57a6e-c285-43ad-9c84-a7ed5b0db91e",
      "name": "Rahim Imaduddin, S.T.",
      "subjects": [],
      "tasks": [
        {
          "label": "Wakasek dan Bendahara",
          "hours": 0,
          "group_col": null
        }
      ]
    },
    {
      "user_id": "52a9b441-afee-4445-9d66-612f704be678",
      "name": "Ust. Zohid Zuhendra, Lc.",
      "subjects": [
        {
          "label": "Manhaj",
          "hours": {
            "XIIA": 2,
            "XIIB": 2,
            "XIIC": 2,
            "XIID": 2
          }
        }
      ],
      "tasks": []
    },
    {
      "user_id": "fa1a3e82-d434-4801-9d50-9482a9354cae",
      "name": "Ust. Saparuddin, Lc., M.HI.",
      "subjects": [
        {
          "label": "Adab",
          "hours": {
            "XIIA": 2,
            "XIIB": 2,
            "XIIC": 2,
            "XIID": 2
          }
        }
      ],
      "tasks": []
    },
    {
      "user_id": "420a8964-2151-4577-b241-15ddc2535682",
      "name": "Ust. Sahirman, Lc., M.Pd.I.",
      "subjects": [
        {
          "label": "Aqidah",
          "hours": {
            "XIIA": 3,
            "XIIB": 3
          }
        }
      ],
      "tasks": []
    },
    {
      "user_id": "0d5abdd9-8f86-4f25-bb74-88a64909523c",
      "name": "Ust. Davidli Ihsan, M.H.I.",
      "subjects": [
        {
          "label": "Faroidh",
          "hours": {
            "XIIA": 2,
            "XIIB": 2
          }
        },
        {
          "label": "Aqidah",
          "hours": {
            "XIIC": 3,
            "XIID": 3
          }
        }
      ],
      "tasks": []
    },
    {
      "user_id": "7a349f93-220d-4c84-8734-f9574eb838fe",
      "name": "Ust. Muhammad Nadhir Atsigah, Lc.",
      "subjects": [
        {
          "label": "Ilmu Hadits",
          "hours": {
            "XIIA": 2,
            "XIIB": 2,
            "XIIC": 2,
            "XIID": 2
          }
        },
        {
          "label": "Aqidah",
          "hours": {
            "XIA": 3,
            "XIB": 3,
            "XIC": 3,
            "XID": 3
          }
        }
      ],
      "tasks": []
    },
    {
      "user_id": "c02fbcba-e479-4b85-8d35-adc4470f3c2a",
      "name": "Usth. Fadilah Muzhar Muhsin Ali, S.Pd.",
      "subjects": [
        {
          "label": "Bahasa Arab",
          "hours": {
            "XA": 8,
            "XB": 8
          }
        },
        {
          "label": "Bahasa Arab Tingkat Lanjut",
          "hours": {
            "XIA": 2,
            "XIB": 2,
            "XIIA": 2,
            "XIIB": 2
          }
        }
      ],
      "tasks": [
        {
          "label": "Wali kelas",
          "hours": 3,
          "group_col": "XA"
        }
      ]
    },
    {
      "user_id": "f799a5eb-8d01-4718-8655-cdc3f222e619",
      "name": "Usth. Siti Miftahul Jannah, Lc., M.Pd.",
      "subjects": [
        {
          "label": "Shorof",
          "hours": {
            "XA": 2,
            "XB": 2,
            "XC": 2,
            "XD": 2
          }
        },
        {
          "label": "Balaghoh",
          "hours": {
            "XIA": 2,
            "XIB": 2,
            "XIIA": 2,
            "XIIB": 2
          }
        }
      ],
      "tasks": [
        {
          "label": "Wali kelas",
          "hours": 3,
          "group_col": "XB"
        },
        {
          "label": "Koordinato Bahasa Arab",
          "hours": 6,
          "group_col": null
        }
      ]
    },
    {
      "user_id": "567e3c95-8aa4-48c5-8814-9acdd902f61b",
      "name": "Usth. Desiana Rahim Riani, Lc.",
      "subjects": [
        {
          "label": "Nahwu",
          "hours": {
            "XA": 2,
            "XB": 2,
            "XC": 2,
            "XD": 2,
            "XIA": 2,
            "XIB": 2,
            "XIC": 2,
            "XID": 2,
            "XIIA": 2,
            "XIIB": 2,
            "XIIC": 2,
            "XIID": 2
          }
        }
      ],
      "tasks": []
    },
    {
      "user_id": "dfd89df9-a847-4264-aaa2-49a156d4711b",
      "name": "Usth. Sri Rosita, Lc.",
      "subjects": [
        {
          "label": "Bahasa Arab",
          "hours": {
            "XC": 8,
            "XD": 8,
            "XIIA": 2,
            "XIIB": 2,
            "XIIC": 2,
            "XIID": 2
          }
        }
      ],
      "tasks": [
        {
          "label": "Wali kelas",
          "hours": 3,
          "group_col": "XC"
        }
      ]
    },
    {
      "user_id": "4a7b4cf9-747d-4647-95cc-1814a9d24173",
      "name": "Usth. Rima Suci Cahyani, Lc.",
      "subjects": [
        {
          "label": "Bahasa Arab",
          "hours": {
            "XIA": 8,
            "XIB": 8
          }
        },
        {
          "label": "Shorof",
          "hours": {
            "XIA": 1,
            "XIB": 1,
            "XIIA": 1,
            "XIIB": 1,
            "XIIC": 1,
            "XIID": 1
          }
        }
      ],
      "tasks": [
        {
          "label": "Wali kelas",
          "hours": 3,
          "group_col": "XIIB"
        }
      ]
    },
    {
      "user_id": "e02c546d-810f-4030-90f1-46b1247fac9f",
      "name": "Usth. Risnatul Aini, Lc.",
      "subjects": [
        {
          "label": "Bahasa Arab",
          "hours": {
            "XIC": 8,
            "XID": 8
          }
        },
        {
          "label": "Ta'bir",
          "hours": {
            "XA": 2,
            "XB": 2,
            "XC": 2,
            "XD": 2
          }
        }
      ],
      "tasks": [
        {
          "label": "Wali Kelas",
          "hours": 3,
          "group_col": "XID"
        }
      ]
    },
    {
      "user_id": "5da7c9a5-2d16-47f1-9446-3895ebfcbfd6",
      "name": "Usth. Anisaa Rabbani, B.A.",
      "subjects": [
        {
          "label": "Ta'bir",
          "hours": {
            "XIA": 2,
            "XIB": 2,
            "XIIA": 2,
            "XIIB": 2
          }
        },
        {
          "label": "Shorof",
          "hours": {
            "XIC": 1,
            "XID": 1
          }
        },
        {
          "label": "Hafalan Hadits",
          "hours": {
            "XA": 2,
            "XB": 2,
            "XC": 2,
            "XD": 2,
            "XIA": 2,
            "XIB": 2,
            "XIC": 2
          }
        }
      ],
      "tasks": []
    },
    {
      "user_id": "4ccc90c1-7a5f-483e-afef-f070b5aa5f73",
      "name": "Usth. Fadillah Al Hinduan",
      "subjects": [
        {
          "label": "Haf. Hadits",
          "hours": {
            "XID": 2,
            "XIIA": 2,
            "XIIB": 2,
            "XIIC": 2,
            "XIID": 2
          }
        },
        {
          "label": "Adab",
          "hours": {
            "XA": 2,
            "XB": 2,
            "XC": 2,
            "XD": 2
          }
        }
      ],
      "tasks": [
        {
          "label": "Koordinator Guru Agama",
          "hours": 6,
          "group_col": null
        }
      ]
    },
    {
      "user_id": "552ed709-2e57-4159-a91e-040580ff6fc9",
      "name": "Usth. Ummu Salmiyah Yusuf, Lc.",
      "subjects": [
        {
          "label": "Ushul Fiqh",
          "hours": {
            "XIIA": 2,
            "XIIB": 2,
            "XIIC": 2,
            "XIID": 2
          }
        },
        {
          "label": "Fikih",
          "hours": {
            "XIA": 2,
            "XIB": 2,
            "XIC": 2,
            "XID": 2,
            "XIIA": 2,
            "XIIB": 2,
            "XIIC": 2,
            "XIID": 2
          }
        }
      ],
      "tasks": []
    },
    {
      "user_id": "098597f3-b553-4780-a587-bdfa225ea6bf",
      "name": "Usth. Khaula Hanifa, S.Pd.",
      "subjects": [
        {
          "label": "Aqidah",
          "hours": {
            "XA": 3,
            "XB": 3,
            "XC": 3,
            "XD": 3
          }
        },
        {
          "label": "Fikih",
          "hours": {
            "XA": 2,
            "XB": 2
          }
        },
        {
          "label": "Adab",
          "hours": {
            "XIA": 2,
            "XIB": 2,
            "XIC": 2,
            "XID": 2
          }
        }
      ],
      "tasks": [
        {
          "label": "Wali Kelas",
          "hours": 3,
          "group_col": "XIB"
        }
      ]
    },
    {
      "user_id": "f8fe8f2e-3515-4f8f-82be-a4ac960ff34e",
      "name": "Usth. Haeratunnisa, S.Pd.",
      "subjects": [
        {
          "label": "Faroidh",
          "hours": {
            "XIA": 2,
            "XIB": 2
          }
        },
        {
          "label": "Fikih",
          "hours": {
            "XC": 2,
            "XD": 2
          }
        }
      ],
      "tasks": []
    },
    {
      "user_id": "40af5432-29b9-4466-aca5-93a6bfa46511",
      "name": "Vera Hidayati, SH.",
      "subjects": [
        {
          "label": "IPS",
          "hours": {
            "XA": 1,
            "XB": 1,
            "XC": 1,
            "XD": 1
          }
        },
        {
          "label": "PKN/Prakarya",
          "hours": {
            "XA": 1,
            "XB": 1,
            "XC": 1,
            "XD": 1
          }
        },
        {
          "label": "PKN/Sejarah",
          "hours": {
            "XIA": 1,
            "XIB": 1,
            "XIC": 1,
            "XID": 1,
            "XIIA": 1,
            "XIIB": 1,
            "XIIC": 1,
            "XIID": 1
          }
        },
        {
          "label": "PJOK/Prakarya",
          "hours": {
            "XIA": 2,
            "XIB": 2
          }
        }
      ],
      "tasks": []
    },
    {
      "user_id": "ff942804-cffe-483a-ac92-e22f04edc1c9",
      "name": "Fitria Ayu Ningsih, M.Pd.",
      "subjects": [
        {
          "label": "Bahasa Indonesia",
          "hours": {
            "XC": 2,
            "XD": 2,
            "XIIA": 2,
            "XIIB": 2,
            "XIIC": 2,
            "XIID": 2
          }
        },
        {
          "label": "Bahasa Indonesia Tingkat Lanjut",
          "hours": {
            "XIA": 2,
            "XIB": 2
          }
        }
      ],
      "tasks": [
        {
          "label": "Wali kelas",
          "hours": 3,
          "group_col": "XD"
        },
        {
          "label": "Koordinator Kesiswaan",
          "hours": 6,
          "group_col": null
        }
      ]
    },
    {
      "user_id": "3c821b86-0831-4c8b-8b92-0dd510843973",
      "name": "Wardatul Aini, M.Pd",
      "subjects": [
        {
          "label": "Bahasa Indonesia",
          "hours": {
            "XA": 2,
            "XB": 2,
            "XIA": 2,
            "XIB": 2,
            "XIC": 2,
            "XID": 2
          }
        },
        {
          "label": "Bahasa Indonesia Tingkat Lanjut",
          "hours": {
            "XIIA": 2,
            "XIIB": 2
          }
        },
        {
          "label": "PJOK",
          "hours": {
            "XC": 2,
            "XD": 2
          }
        }
      ],
      "tasks": [
        {
          "label": "Wali kelas",
          "hours": 3,
          "group_col": "XIA"
        }
      ]
    },
    {
      "user_id": "ea3fdbd3-b1ac-46f0-9ee4-9cbffeb4f170",
      "name": "Mastuni, S.Pd.",
      "subjects": [
        {
          "label": "Bahasa Inggris",
          "hours": {
            "XA": 2,
            "XB": 2,
            "XC": 2,
            "XIA": 2,
            "XIB": 2
          }
        },
        {
          "label": "Bahasa Inggris Tingkat Lanjut",
          "hours": {
            "XIIA": 2,
            "XIIB": 2
          }
        },
        {
          "label": "PJOK/Prakarya",
          "hours": {
            "XIC": 2
          }
        }
      ],
      "tasks": [
        {
          "label": "Wali kelas",
          "hours": 3,
          "group_col": "XIC"
        },
        {
          "label": "Koordinator Sarpras",
          "hours": 6,
          "group_col": null
        }
      ]
    },
    {
      "user_id": "2988a361-6c71-4e12-8cd6-231097f985fb",
      "name": "Nurshahifah Fithri, S.Pd.",
      "subjects": [
        {
          "label": "Bahasa Inggris",
          "hours": {
            "XD": 2,
            "XIC": 3,
            "XID": 3,
            "XIIA": 2,
            "XIIB": 2,
            "XIIC": 3,
            "XIID": 3
          }
        },
        {
          "label": "Bahasa Inggris Tingkat Lanjut",
          "hours": {
            "XIA": 2,
            "XIB": 2
          }
        },
        {
          "label": "PJOK/Prakarya",
          "hours": {
            "XID": 2
          }
        }
      ],
      "tasks": []
    },
    {
      "user_id": "82fe6114-d6dd-45e6-8993-e7d8b7267b60",
      "name": "Sri Handayani, S.Pd.",
      "subjects": [
        {
          "label": "Matematika",
          "hours": {
            "XA": 2,
            "XB": 2,
            "XIIA": 2,
            "XIIB": 2,
            "XIIC": 2,
            "XIID": 2
          }
        },
        {
          "label": "Matematika Tingkat Lanjut",
          "hours": {
            "XIC": 2,
            "XID": 2
          }
        },
        {
          "label": "PJOK/Prakarya",
          "hours": {
            "XIIC": 2,
            "XIID": 2
          }
        }
      ],
      "tasks": [
        {
          "label": "Wali kelas",
          "hours": 3,
          "group_col": "XIIC"
        }
      ]
    },
    {
      "user_id": "75da3d66-7904-4deb-96fd-d2e386edcb4b",
      "name": "Zulfa Rahmi Ekarini, S.Pd.",
      "subjects": [
        {
          "label": "Matematika",
          "hours": {
            "XC": 2,
            "XD": 2,
            "XIA": 2,
            "XIB": 2,
            "XIC": 2,
            "XID": 2
          }
        },
        {
          "label": "Matematika Peminatan",
          "hours": {
            "XIIC": 2,
            "XIID": 2
          }
        },
        {
          "label": "PJOK/Prakarya",
          "hours": {
            "XIIA": 2,
            "XIIB": 2
          }
        }
      ],
      "tasks": [
        {
          "label": "Wali kelas",
          "hours": 3,
          "group_col": "XIIA"
        }
      ]
    },
    {
      "user_id": "86dc20ce-4852-4f28-8049-09e5c1647f4c",
      "name": "Risa Herlina Hariati, S.Pd.",
      "subjects": [
        {
          "label": "Biologi",
          "hours": {
            "XA": 1,
            "XB": 1,
            "XC": 1,
            "XD": 1,
            "XIC": 3,
            "XID": 3,
            "XIIC": 3,
            "XIID": 3
          }
        },
        {
          "label": "Informatika",
          "hours": {
            "XC": 1,
            "XD": 1
          }
        }
      ],
      "tasks": [
        {
          "label": "Koordinator Guru Umum",
          "hours": 6,
          "group_col": null
        }
      ]
    },
    {
      "user_id": "9dd91118-144a-4515-a6ad-2c7ecc10b4d8",
      "name": "Novita Supiyani, S.Pd.",
      "subjects": [
        {
          "label": "Kimia",
          "hours": {
            "XA": 2,
            "XB": 2,
            "XC": 2,
            "XD": 2,
            "XIC": 3,
            "XID": 3,
            "XIIC": 3,
            "XIID": 3
          }
        },
        {
          "label": "Informatika",
          "hours": {
            "XA": 1,
            "XB": 1
          }
        }
      ],
      "tasks": [
        {
          "label": "Wali kelas",
          "hours": 3,
          "group_col": "XIID"
        }
      ]
    },
    {
      "user_id": "dece1528-3018-463c-bc5c-0661a833f658",
      "name": "Siti Nurlaelan Barorah, S.Pd.",
      "subjects": [
        {
          "label": "Fisika",
          "hours": {
            "XA": 2,
            "XB": 2,
            "XC": 2,
            "XD": 2,
            "XIC": 3,
            "XID": 3,
            "XIIC": 3,
            "XIID": 3
          }
        },
        {
          "label": "PJOK",
          "hours": {
            "XA": 2,
            "XB": 2
          }
        }
      ],
      "tasks": []
    },
    {
      "user_id": "55441893-61e7-43cd-b5f7-e44682ba2ea0",
      "name": "Usth. Helmina",
      "subjects": [
        {
          "label": "Muatan Lokal: Tahfizhul Qur'an",
          "hours": {
            "XIIA": 5,
            "XIIB": 5,
            "XIIC": 5,
            "XIID": 5
          }
        }
      ],
      "tasks": []
    },
    {
      "user_id": "d225350d-8cc9-4ac6-b748-32aa1802248f",
      "name": "Usth. Joharatul Fitri",
      "subjects": [],
      "tasks": [
        {
          "label": "Muatan Lokal: Tahfizhul Qur'an",
          "hours": 0,
          "group_col": null
        }
      ]
    },
    {
      "user_id": "4d2f3f56-5bce-481b-9f83-7d6cdc0a5563",
      "name": "Usth. Reni Anggraini, S.Pd.",
      "subjects": [],
      "tasks": [
        {
          "label": "Muatan Lokal: Tahfizhul Qur'an",
          "hours": 0,
          "group_col": null
        }
      ]
    },
    {
      "user_id": "5db2e213-aeb4-4a40-a122-dc4ac6e0d8b3",
      "name": "Usth. Sriatun",
      "subjects": [],
      "tasks": [
        {
          "label": "Muatan Lokal: Tahfizhul Qur'an",
          "hours": 0,
          "group_col": null
        }
      ]
    },
    {
      "user_id": "935c4aa7-eed4-4b36-b46e-8afdb02b2a92",
      "name": "Usth. Mahnun, S.Pd.I.",
      "subjects": [],
      "tasks": [
        {
          "label": "Muatan Lokal: Tahfizhul Qur'an",
          "hours": 0,
          "group_col": null
        }
      ]
    },
    {
      "user_id": "eb344986-0918-4e28-911e-41073c073c74",
      "name": "Usth. Farmiatun",
      "subjects": [
        {
          "label": "Muatan Lokal: Tahfizhul Qur'an",
          "hours": {
            "XA": 7,
            "XB": 7,
            "XC": 7,
            "XD": 7
          }
        }
      ],
      "tasks": []
    },
    {
      "user_id": "0bfc9f45-da40-420e-b1ab-f699b691d836",
      "name": "Usth. Misnah",
      "subjects": [],
      "tasks": [
        {
          "label": "Muatan Lokal: Tahfizhul Qur'an",
          "hours": 0,
          "group_col": null
        }
      ]
    },
    {
      "user_id": "d86b6c53-04d8-4a9c-993e-2d5d3211328d",
      "name": "Usth. Mu'annah",
      "subjects": [],
      "tasks": [
        {
          "label": "Muatan Lokal: Tahfizhul Qur'an",
          "hours": 0,
          "group_col": null
        }
      ]
    },
    {
      "user_id": "33cd0108-60cb-422a-bf39-fbc6254a17f9",
      "name": "Usth. Nur Khoiri",
      "subjects": [
        {
          "label": "Muatan Lokal: Tahfizhul Qur'an",
          "hours": {
            "XIA": 5,
            "XIB": 5,
            "XIC": 5,
            "XID": 5
          }
        }
      ],
      "tasks": []
    },
    {
      "user_id": "04da906a-bcc2-4c2a-8ed3-72c980bd57e7",
      "name": "Usth. Sukmawati",
      "subjects": [],
      "tasks": [
        {
          "label": "Muatan Lokal: Tahfizhul Qur'an",
          "hours": 0,
          "group_col": null
        }
      ]
    },
    {
      "user_id": "9f9d6762-0df0-4d4b-92fe-4c690be7f36e",
      "name": "Usth. Nadia Yuli Zurrahmati",
      "subjects": [],
      "tasks": [
        {
          "label": "Muatan Lokal: Tahfizhul Qur'an",
          "hours": 0,
          "group_col": null
        }
      ]
    }
  ]
}
JSON, true);

        $satuanPendidikan = JenisGtk::where('nama', 'like', '%Satuan Pendidikan%')->firstOrFail();

        $subjectDefinitions = [
            'Manhaj' => ['MNJ', 'Manhaj'],
            'Adab' => ['ADB', 'Adab'],
            'Aqidah' => ['AQA', 'Aqidah'],
            'Faroidh' => ['FRD', "Fara'id"],
            'Ilmu Hadits' => ['ILH', 'Ilmu Hadits'],
            'Bahasa Arab' => ['BAR', 'Bahasa Arab'],
            'Bahasa Arab Tingkat Lanjut' => ['BATL', 'Bahasa Arab Tingkat Lanjut'],
            'Shorof' => ['SRF', 'Shorof'],
            'Balaghoh' => ['BLG', 'Balagoh'],
            'Nahwu' => ['NHW', 'Nahwu'],
            "Ta'bir" => ['TBR', "Ta'bir"],
            'Hafalan Hadits' => ['HDS', 'Hafalan Hadits'],
            'Haf. Hadits' => ['HDS', 'Hafalan Hadits'],
            'Ushul Fiqh' => ['USH', 'Ushul Fiqh'],
            'Fikih' => ['FQH', 'Fiqih'],
            'IPS' => ['IPS', 'IPS (Ekonomi, Sejarah, Geografi & Sosiologi)'],
            'PKN/Prakarya' => ['PKP', 'PKN/Prakarya'],
            'PKN/Sejarah' => ['PKS', 'PKN/Sejarah'],
            'PJOK/Prakarya' => ['PJP', 'PJOK/Prakarya'],
            'Bahasa Indonesia' => ['BIN', 'Bahasa Indonesia'],
            'Bahasa Indonesia Tingkat Lanjut' => ['BITL', 'Bahasa Indonesia Tingkat Lanjut'],
            'Bahasa Inggris' => ['BIG', 'Bahasa Inggris'],
            'Bahasa Inggris Tingkat Lanjut' => ['BIGTL', 'Bahasa Inggris Tingkat Lanjut'],
            'PJOK' => ['PJOK', 'PJOK'],
            'Matematika' => ['MAT', 'Matematika'],
            'Matematika Tingkat Lanjut' => ['MTL', 'Matematika Tingkat Lanjut'],
            'Matematika Peminatan' => ['MPN', 'Matematika Peminatan'],
            'Biologi' => ['BIO', 'Biologi'],
            'Informatika' => ['INF', 'Informatika'],
            'Kimia' => ['KIM', 'Kimia'],
            'Fisika' => ['FIS', 'Fisika'],
            "Muatan Lokal: Tahfizhul Qur'an" => ['THF', "Tahfidzul Qur'an"],
        ];

        $subjectIds = [];
        foreach ($subjectDefinitions as $label => [$code, $name]) {
            // Kode mapel tidak unik lintas sekolah (mis. BIN/MAT dipakai banyak
            // sekolah). Cari dulu mapel milik sekolah ini, baru buat bila tidak ada.
            $subject = Subject::where('school_id', $school->id)
                ->where(fn ($q) => $q->where('code', $code)->orWhere('name', $name))
                ->first();

            if (! $subject) {
                $subject = Subject::create([
                    'school_id' => $school->id,
                    'code' => $code,
                    'name' => $name,
                    'credit_hours' => 2,
                    'is_active' => true,
                ]);
            }

            $subjectIds[$label] = $subject->id;
        }

        $gradeIds = GradeLevel::where('school_id', $school->id)->pluck('id', 'level');

        $groupMap = [
            'XA' => '10A', 'XB' => '10B', 'XC' => '10C', 'XD' => '10D',
            'XIA' => '11A', 'XIB' => '11B', 'XIC' => '11C', 'XID' => '11D',
            'XIIA' => '12A', 'XIIB' => '12B', 'XIIC' => '12C', 'XIID' => '12D',
        ];

        $groupIds = [];
        foreach ($groupMap as $column => $name) {
            $level = str_starts_with($name, '10') ? 10 : (str_starts_with($name, '11') ? 11 : 12);
            $group = StudyGroup::firstOrCreate(
                [
                    'school_id' => $school->id,
                    'academic_year_id' => $academicYear->id,
                    'name' => $name,
                ],
                [
                    'grade_level_id' => $gradeIds[$level] ?? null,
                    'code' => $name,
                    'capacity' => 32,
                    'curriculum_type' => 'merdeka',
                    'shift' => 'pagi',
                    'is_active' => true,
                ]
            );
            $groupIds[$column] = $group->id;
        }

        // Bersihkan jenis tugas yang sempat dibuat khusus untuk SK ini.
        // Data hanya boleh memakai master jabatan/tugas yang sudah ada.
        $legacyTaskTypeIds = AdditionalTaskType::whereIn('kode', [
            'kepala-sekolah',
            'wakasek-bendahara',
            'muatan-lokal-tahfizh',
        ])->pluck('id');

        if ($legacyTaskTypeIds->isNotEmpty()) {
            OtherTeacherTask::whereIn('additional_task_type_id', $legacyTaskTypeIds)->delete();
            AdditionalTaskType::whereIn('id', $legacyTaskTypeIds)->delete();
        }

        $taskDefinitions = [
            'Wali kelas' => ['wali-kelas', 'Wali Kelas'],
            'Wali Kelas' => ['wali-kelas', 'Wali Kelas'],
            'Koordinato Bahasa Arab' => ['koordinator-guru-bahasa-arab', 'Koordinator Guru Bahasa Arab'],
            'Koordinator Guru Agama' => ['koordinator-guru-agama', 'Koordinator Guru Agama'],
            'Koordinator Kesiswaan' => ['koordinator-kesiswaan', 'Koordinator Kesiswaan'],
            'Koordinator Sarpras' => ['koordinator-sarpras-satuan-pendidikan', 'Koordinator Sarpras Satuan Pendidikan'],
            'Koordinator Guru Umum' => ['koordinator-guru-umum', 'Koordinator Guru Umum'],
        ];

        $taskTypes = [];
        foreach ($taskDefinitions as $label => [$code, $name]) {
            $taskTypes[$label] = AdditionalTaskType::firstOrCreate(
                ['kode' => $code],
                [
                    'jenis_gtk_id' => $satuanPendidikan->id,
                    'nama' => $name,
                    'is_active' => true,
                    'urutan' => 99,
                ]
            );
        }

        $decree = InstitutionDecree::updateOrCreate(
            ['decree_number' => '300/SMA IT/SK/I/VII/2026'],
            [
                'decree_type' => 'SK Pembagian Tugas',
                'title' => 'Pembagian Tugas Mengajar Semester Ganjil Tahun Ajaran 2026/2027',
                'description' => 'Pembagian Tugas Mengajar Semester Ganjil Tahun Ajaran 2026/2027 di SMA IT Putri Abu Hurairah Mataram.',
                'school_id' => $school->id,
                'academic_year_id' => $academicYear->id,
                'issued_date' => '2026-07-01',
                'effective_date' => '2026-07-01',
                'end_date' => '2026-12-31',
                'signed_by' => '6816c594-66ea-4cc3-b7de-1979afe6738d',
                'signed_position' => 'Kepala Sekolah',
                'status' => 'active',
            ]
        );

        $assignments = [];
        $tasks = [];

        // GTK yang berasal dari satuan kerja lain tetap dihubungkan secara resmi
        // sebagai penugasan lintas lembaga (bukan mengubah data kepegawaiannya).
        $crossUnitSubjects = [
            '52a9b441-afee-4445-9d66-612f704be678' => 'Manhaj',        // Zohid Zuhendra
            'fa1a3e82-d434-4801-9d50-9482a9354cae' => 'Adab',          // Saparuddin
            '420a8964-2151-4577-b241-15ddc2535682' => 'Aqidah',        // Sahirman
            'f8fe8f2e-3515-4f8f-82be-a4ac960ff34e' => 'Faroidh',       // Haeratunnisa
        ];

        foreach ($crossUnitSubjects as $userId => $subjectLabel) {
            $homeWorkUnitId = GtkWorkUnit::where('user_id', $userId)->value('work_unit_id');

            if (! $homeWorkUnitId) {
                continue;
            }

            GtkCrossUnitAssignment::updateOrCreate(
                [
                    'user_id' => $userId,
                    'school_id' => $school->id,
                    'academic_year_id' => $academicYear->id,
                ],
                [
                    'home_work_unit_id' => $homeWorkUnitId,
                    'subject_id' => $subjectIds[$subjectLabel] ?? null,
                    'decree_number' => $decree->decree_number,
                    'start_date' => '2026-07-01',
                    'end_date' => '2026-12-31',
                    'status' => 'active',
                    'created_by' => '6816c594-66ea-4cc3-b7de-1979afe6738d',
                ]
            );
        }

        foreach ($payload['teachers'] as $teacher) {
            $teacherId = $teacher['user_id'];

            foreach ($teacher['subjects'] as $subjectRow) {
                $subjectId = $subjectIds[$subjectRow['label']] ?? null;
                if (! $subjectId) {
                    continue;
                }

                foreach ($subjectRow['hours'] as $column => $hours) {
                    $groupId = $groupIds[$column] ?? null;
                    if (! $groupId || (int) $hours <= 0) {
                        continue;
                    }
                    $assignments[$teacherId][$subjectId][$groupId] = (int) $hours;
                }
            }

            foreach ($teacher['tasks'] as $taskRow) {
                // Tahfizh tanpa JP cukup tampil lewat roster (bukan tugas tambahan).
                if (str_contains($taskRow['label'], 'Muatan Lokal')) {
                    continue;
                }

                // "Kepala Sekolah" adalah jabatan, bukan jenis tugas master.
                // Simpan sebagai label tugas bebas tanpa additional_task_type_id.
                if ($taskRow['label'] === 'Kepala Sekolah') {
                    $tasks[] = [
                        'teacher_id' => $teacherId,
                        'task_name' => 'Kepala Sekolah',
                        'task_code' => 'kepala-sekolah',
                        'weekly_hours' => (int) $taskRow['hours'],
                        'study_group_id' => null,
                        'additional_task_type_id' => null,
                    ];

                    continue;
                }

                if ($taskRow['label'] === 'Wakasek dan Bendahara') {
                    $tasks[] = [
                        'teacher_id' => $teacherId,
                        'task_name' => 'Wakasek dan Bendahara',
                        'task_code' => 'wakasek-bendahara',
                        'weekly_hours' => (int) $taskRow['hours'],
                        'study_group_id' => null,
                        'additional_task_type_id' => null,
                    ];

                    continue;
                }

                $definition = $taskDefinitions[$taskRow['label']] ?? null;
                if (! $definition) {
                    continue;
                }

                $groupId = $taskRow['group_col'] ? ($groupIds[$taskRow['group_col']] ?? null) : null;

                $tasks[] = [
                    'teacher_id' => $teacherId,
                    'task_name' => $definition[1],
                    'task_code' => $definition[0],
                    'weekly_hours' => (int) $taskRow['hours'],
                    'study_group_id' => $groupId,
                ];
            }
        }

        $result = app(InstitutionDecreeMatrixService::class)->sync(
            $decree,
            $assignments,
            $tasks,
            $school->id,
            true
        );

        app(InstitutionDecreeMatrixService::class)->refreshSnapshot($decree->fresh());

        $this->command?->info(sprintf(
            'SK %s: %d penugasan, %d tugas tambahan, %d Buku Admin.',
            $decree->decree_number,
            $result['assignments_created'] + $result['assignments_updated'],
            $result['tasks_created'] + $result['tasks_updated'],
            $result['books_created'] ?? 0
        ));
    }
}
