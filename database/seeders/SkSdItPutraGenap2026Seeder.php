<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\AdditionalTaskType;
use App\Models\GradeLevel;
use App\Models\GtkWorkUnit;
use App\Models\InstitutionDecree;
use App\Models\School;
use App\Models\StructuralPosition;
use App\Models\StudyGroup;
use App\Models\Subject;
use App\Services\InstitutionDecreeMatrixService;
use Illuminate\Database\Seeder;

/**
 * SK Pemenuhan Beban Kerja GTK & Pembagian Tugas
 * SDIT Putra Abu Hurairah Mataram — 110/PAH/SDIT-Pa/SK/VII/2026.
 *
 * Hanya memakai master yang sudah ada. Tidak membuat jenis tugas baru.
 */
class SkSdItPutraGenap2026Seeder extends Seeder
{
    public function run(): void
    {
        $school = School::where('name', 'like', '%Sd It Putra Abu Hurairah%')
            ->orWhere('name', 'like', '%SDIT Putra Abu Hurairah%')
            ->firstOrFail();

        $academicYear = AcademicYear::where('name', '2025/2026')
            ->where('semester', 'genap')
            ->firstOrFail();

        $payload = json_decode(<<<'JSON'
{
  "teachers": [
    {
      "name": "Marjuni, S. Pd.",
      "user_id": "869532f1-5fc5-4d7b-a59c-5fbc4d9e4948",
      "subjects": [
        {
          "label": "Matematika",
          "subject_id": "e7ad5aa9-cee5-4a38-97d0-21bb74cb61bd",
          "hours": {
            "6A": 4,
            "6B": 4,
            "6C": 4
          }
        },
        {
          "label": "PKn",
          "subject_id": "53e638b2-82a3-4378-b203-74cdf3464ea3",
          "hours": {
            "6C": 1
          }
        },
        {
          "label": "Seni budaya",
          "subject_id": "eeeeb1e3-73f7-4376-8a90-13815d9830ea",
          "hours": {
            "6C": 1
          }
        }
      ],
      "tasks": [
        {
          "label": "Wali Kelas",
          "group": "6C"
        }
      ],
      "positions": [
        "Wakil Kepala Satuan Pendidikan"
      ],
      "jtm": "12"
    },
    {
      "name": "Ferry Agusmansyah Ma'as, S.Kom.",
      "user_id": "5b93f910-ea32-4c34-a19f-778dba011059",
      "subjects": [],
      "tasks": [],
      "positions": [
        "Kepala Tata Usaha"
      ],
      "jtm": "0"
    },
    {
      "name": "Agus Sofian, S.Pd.",
      "user_id": "d0bcb4d9-f0d7-4e03-a4ae-f356305769cb",
      "subjects": [],
      "tasks": [
        {
          "label": "Bendahara Sekolah",
          "group": null
        }
      ],
      "positions": [
        "Tata Usaha"
      ],
      "jtm": "0"
    },
    {
      "name": "Hamrol Hairomi, S.Tp",
      "user_id": "fecf5b05-0006-4858-bfe8-ac64009644b6",
      "subjects": [],
      "tasks": [
        {
          "label": "Koord. Sarpras",
          "group": null
        }
      ],
      "positions": [
        "Tata Usaha"
      ],
      "jtm": "0"
    },
    {
      "name": "Thamrin Hidayat, A.Md.",
      "user_id": "299fe697-8419-4e73-942e-5c0f2a53ae48",
      "subjects": [],
      "tasks": [],
      "positions": [
        "Operator Sekolah"
      ],
      "jtm": "0"
    },
    {
      "name": "Syaifullah, M.Pd.I",
      "user_id": "287dec66-0027-4cad-a5b7-c17fabc13f45",
      "subjects": [
        {
          "label": "Adab",
          "subject_id": "11589638-7bfb-426f-9bad-42b498783658",
          "hours": {
            "1A": 2,
            "1B": 2,
            "1C": 2,
            "1D": 2,
            "2A": 2,
            "2B": 2,
            "2C": 2,
            "2D": 2
          }
        },
        {
          "label": "PKn",
          "subject_id": "53e638b2-82a3-4378-b203-74cdf3464ea3",
          "hours": {
            "1A": 2,
            "1B": 2,
            "1C": 2,
            "1D": 2,
            "2A": 2,
            "2B": 2
          }
        }
      ],
      "tasks": [],
      "positions": [],
      "jtm": "16"
    },
    {
      "name": "Ardiyan, Lc",
      "user_id": "cb4e9b94-c3b9-4ffd-99a9-43764ca703d2",
      "subjects": [
        {
          "label": "Aqidah",
          "subject_id": "d023e743-6247-457f-a88e-ef8da8b1966e",
          "hours": {
            "1A": 2,
            "1B": 2,
            "1C": 2,
            "1D": 2,
            "2A": 2,
            "2B": 2,
            "2C": 2,
            "2D": 2,
            "3A": 2,
            "3B": 2,
            "3C": 2
          }
        }
      ],
      "tasks": [
        {
          "label": "Koord. Guru Agama",
          "group": null
        }
      ],
      "positions": [],
      "jtm": "22"
    },
    {
      "name": "Sulthon Mahendra, S.Pd.",
      "user_id": "df130d1b-081c-4b55-9d55-18db924200f4",
      "subjects": [
        {
          "label": "Hadits",
          "subject_id": "09619f2d-93ac-45bb-bb19-3e510c60b590",
          "hours": {
            "3A": 2,
            "3B": 2
          }
        },
        {
          "label": "Bahasa Arab",
          "subject_id": "e715da00-dfb4-40a3-87a2-51620547933f",
          "hours": {
            "3A": 6,
            "3B": 6,
            "3C": 6
          }
        }
      ],
      "tasks": [
        {
          "label": "Koord. Guru B. Arab",
          "group": null
        }
      ],
      "positions": [],
      "jtm": "4"
    },
    {
      "name": "Lukmanul Hakim, S.Pd.",
      "user_id": "c2bd6a7b-5064-47c9-9640-4e2179eaeb7a",
      "subjects": [
        {
          "label": "Tahfidz",
          "subject_id": "562ac821-1e7a-41ed-aceb-c8227b2cc07a",
          "hours": {
            "3A": 2,
            "3B": 2,
            "3C": 2,
            "4A": 2,
            "4B": 2,
            "4C": 2,
            "5A": 2,
            "5B": 2,
            "5C": 2,
            "6A": 2,
            "6B": 2,
            "6C": 2
          }
        }
      ],
      "tasks": [
        {
          "label": "Koord. Guru Tahfizh",
          "group": null
        }
      ],
      "positions": [],
      "jtm": "24"
    },
    {
      "name": "Wildan, Lc.",
      "user_id": "46d34276-d06f-42d8-b134-f668e84eb0ca",
      "subjects": [
        {
          "label": "Adab",
          "subject_id": "11589638-7bfb-426f-9bad-42b498783658",
          "hours": {
            "5A": 2,
            "5B": 2,
            "5C": 2,
            "6A": 2,
            "6B": 2,
            "6C": 2
          }
        },
        {
          "label": "Aqidah",
          "subject_id": "d023e743-6247-457f-a88e-ef8da8b1966e",
          "hours": {
            "5A": 2,
            "5B": 2,
            "5C": 2,
            "6A": 2,
            "6B": 2,
            "6C": 2
          }
        },
        {
          "label": "Hadits",
          "subject_id": "09619f2d-93ac-45bb-bb19-3e510c60b590",
          "hours": {
            "1C": 2,
            "1D": 2
          }
        }
      ],
      "tasks": [],
      "positions": [],
      "jtm": "12"
    },
    {
      "name": "Syahrul Ramdani, S.H.",
      "user_id": "44b81410-e7aa-4098-977c-59ef554250aa",
      "subjects": [
        {
          "label": "Hadits",
          "subject_id": "09619f2d-93ac-45bb-bb19-3e510c60b590",
          "hours": {
            "2A": 2,
            "2B": 2,
            "2C": 2,
            "2D": 2
          }
        },
        {
          "label": "Fikih",
          "subject_id": "b128bb84-e4c3-4cef-a366-122de578d861",
          "hours": {
            "4A": 2,
            "4B": 2,
            "4C": 2,
            "5A": 2,
            "5B": 2,
            "5C": 2,
            "6A": 2,
            "6B": 2,
            "6C": 2
          }
        },
        {
          "label": "PKn",
          "subject_id": "53e638b2-82a3-4378-b203-74cdf3464ea3",
          "hours": {
            "3B": 2
          }
        }
      ],
      "tasks": [],
      "positions": [],
      "jtm": "8"
    },
    {
      "name": "Baihaqi, Lc.",
      "user_id": "efdfd18b-a5b6-4c5c-b4f7-9eca89c1ee33",
      "subjects": [
        {
          "label": "Hadits",
          "subject_id": "09619f2d-93ac-45bb-bb19-3e510c60b590",
          "hours": {
            "1A": 2,
            "1B": 2
          }
        },
        {
          "label": "Fiqih",
          "subject_id": "b128bb84-e4c3-4cef-a366-122de578d861",
          "hours": {
            "1A": 2,
            "1B": 2,
            "1C": 2,
            "1D": 2,
            "2A": 2,
            "2B": 2,
            "2C": 2,
            "2D": 2,
            "3A": 2,
            "3B": 2,
            "3C": 2
          }
        },
        {
          "label": "PKn",
          "subject_id": "53e638b2-82a3-4378-b203-74cdf3464ea3",
          "hours": {
            "3A": 2
          }
        }
      ],
      "tasks": [],
      "positions": [],
      "jtm": "4"
    },
    {
      "name": "Jaya Miharja, S.Pd.",
      "user_id": "351e95d0-a208-4224-941e-6b80dba65772",
      "subjects": [
        {
          "label": "Bahasa Inggris",
          "subject_id": "390fe0dc-626a-4049-9e9f-ba3d403a16a3",
          "hours": {
            "1A": 2,
            "1B": 2,
            "1C": 2,
            "1D": 2,
            "2A": 2,
            "2B": 2,
            "2C": 2,
            "2D": 2,
            "3A": 2,
            "3B": 2,
            "3C": 2,
            "4A": 2,
            "4B": 2,
            "4C": 2
          }
        }
      ],
      "tasks": [],
      "positions": [],
      "jtm": "28"
    },
    {
      "name": "Ahmad Zaeroni, Lc.",
      "user_id": "90eb7e4b-01d0-4ddd-a810-0cd22e34913c",
      "subjects": [
        {
          "label": "Bahasa Arab",
          "subject_id": "e715da00-dfb4-40a3-87a2-51620547933f",
          "hours": {
            "4A": 6,
            "4B": 6,
            "4C": 6
          }
        },
        {
          "label": "Hadits",
          "subject_id": "09619f2d-93ac-45bb-bb19-3e510c60b590",
          "hours": {
            "3C": 2,
            "4A": 2,
            "4B": 2,
            "4C": 2
          }
        },
        {
          "label": "PKn",
          "subject_id": "53e638b2-82a3-4378-b203-74cdf3464ea3",
          "hours": {
            "3C": 2
          }
        }
      ],
      "tasks": [],
      "positions": [],
      "jtm": "18"
    },
    {
      "name": "L. Sopyan Harianto, S.Pd.",
      "user_id": "5e8731b0-c885-442e-9254-7146a468d040",
      "subjects": [
        {
          "label": "PJOK",
          "subject_id": "af946a5b-48bc-4f38-a3aa-8bf51196945d",
          "hours": {
            "3A": 2,
            "3B": 2,
            "3C": 2,
            "4A": 2,
            "4B": 2,
            "4C": 2,
            "5A": 2,
            "5B": 2,
            "5C": 2,
            "6A": 2,
            "6B": 2,
            "6C": 2
          }
        },
        {
          "label": "PKn",
          "subject_id": "53e638b2-82a3-4378-b203-74cdf3464ea3",
          "hours": {
            "2C": 2,
            "2D": 2
          }
        }
      ],
      "tasks": [],
      "positions": [],
      "jtm": "24"
    },
    {
      "name": "Indah Nurul Aimah, S.Pd.I",
      "user_id": "c9c1d35f-9e60-4454-8982-55871ad59ccf",
      "subjects": [
        {
          "label": "PJOK",
          "subject_id": "af946a5b-48bc-4f38-a3aa-8bf51196945d",
          "hours": {
            "1A": 2,
            "1C": 2,
            "1D": 2
          }
        },
        {
          "label": "Matematika",
          "subject_id": "e7ad5aa9-cee5-4a38-97d0-21bb74cb61bd",
          "hours": {
            "1A": 4,
            "1B": 2,
            "1D": 4
          }
        },
        {
          "label": "Bahasa Indonesia",
          "subject_id": "a01d65b8-33bf-4984-8fbd-5e76290208ff",
          "hours": {
            "1A": 4,
            "1B": 4,
            "1C": 2,
            "1D": 2
          }
        },
        {
          "label": "Seni Budaya",
          "subject_id": "eeeeb1e3-73f7-4376-8a90-13815d9830ea",
          "hours": {
            "1A": 1
          }
        }
      ],
      "tasks": [
        {
          "label": "Wali Kelas",
          "group": "1A"
        }
      ],
      "positions": [],
      "jtm": "6"
    },
    {
      "name": "Hilda Syafitri, S.Pd.",
      "user_id": "392098d8-6b8a-4231-98ce-8d199fd80b50",
      "subjects": [
        {
          "label": "PJOK",
          "subject_id": "af946a5b-48bc-4f38-a3aa-8bf51196945d",
          "hours": {
            "1B": 2,
            "1C": 2,
            "1D": 2
          }
        },
        {
          "label": "Matematika",
          "subject_id": "e7ad5aa9-cee5-4a38-97d0-21bb74cb61bd",
          "hours": {
            "1A": 2,
            "1B": 4,
            "1C": 2,
            "1D": 2
          }
        },
        {
          "label": "Bahasa Indonesia",
          "subject_id": "a01d65b8-33bf-4984-8fbd-5e76290208ff",
          "hours": {
            "1A": 4,
            "1B": 4,
            "1C": 2,
            "1D": 2
          }
        },
        {
          "label": "Seni Budaya",
          "subject_id": "eeeeb1e3-73f7-4376-8a90-13815d9830ea",
          "hours": {
            "1B": 1
          }
        }
      ],
      "tasks": [
        {
          "label": "Wali Kelas",
          "group": "1B"
        }
      ],
      "positions": [],
      "jtm": "6"
    },
    {
      "name": "Isnaini, S.Pd.",
      "user_id": "05d6a68e-80aa-4863-a92f-ff51081f8b74",
      "subjects": [
        {
          "label": "PJOK",
          "subject_id": "af946a5b-48bc-4f38-a3aa-8bf51196945d",
          "hours": {
            "1A": 2,
            "1B": 2,
            "1C": 2,
            "1D": 2
          }
        },
        {
          "label": "Matematika",
          "subject_id": "e7ad5aa9-cee5-4a38-97d0-21bb74cb61bd",
          "hours": {
            "1C": 4,
            "1D": 4
          }
        },
        {
          "label": "Bahasa Indonesia",
          "subject_id": "a01d65b8-33bf-4984-8fbd-5e76290208ff",
          "hours": {
            "1A": 2,
            "1B": 2,
            "1C": 4,
            "1D": 4
          }
        },
        {
          "label": "Seni Budaya",
          "subject_id": "eeeeb1e3-73f7-4376-8a90-13815d9830ea",
          "hours": {
            "1C": 1
          }
        }
      ],
      "tasks": [
        {
          "label": "Wali Kelas",
          "group": "1C"
        }
      ],
      "positions": [],
      "jtm": "8"
    },
    {
      "name": "Maulida Salsabila, S.Pd.",
      "user_id": "5fec86d4-1d05-4e47-86d9-7982b425398d",
      "subjects": [
        {
          "label": "Bahasa Arab",
          "subject_id": "e715da00-dfb4-40a3-87a2-51620547933f",
          "hours": {
            "1A": 7,
            "1B": 7,
            "1C": 7,
            "1D": 7
          }
        },
        {
          "label": "Seni Budaya",
          "subject_id": "eeeeb1e3-73f7-4376-8a90-13815d9830ea",
          "hours": {
            "1D": 1
          }
        }
      ],
      "tasks": [
        {
          "label": "Wali Kelas",
          "group": "1D"
        }
      ],
      "positions": [],
      "jtm": "28"
    },
    {
      "name": "Fatmi Hidayah, S.Pd.",
      "user_id": "a23588ea-960d-4a42-aab8-c2b572cc7eab",
      "subjects": [
        {
          "label": "PJOK",
          "subject_id": "af946a5b-48bc-4f38-a3aa-8bf51196945d",
          "hours": {
            "2A": 2,
            "2C": 2,
            "2D": 2
          }
        },
        {
          "label": "Matematika",
          "subject_id": "e7ad5aa9-cee5-4a38-97d0-21bb74cb61bd",
          "hours": {
            "2A": 4,
            "2B": 2,
            "2D": 4
          }
        },
        {
          "label": "Bahasa Indonesia",
          "subject_id": "a01d65b8-33bf-4984-8fbd-5e76290208ff",
          "hours": {
            "2A": 4,
            "2B": 4,
            "2C": 2,
            "2D": 2
          }
        },
        {
          "label": "Seni Budaya",
          "subject_id": "eeeeb1e3-73f7-4376-8a90-13815d9830ea",
          "hours": {
            "2A": 1
          }
        }
      ],
      "tasks": [
        {
          "label": "Wali Kelas",
          "group": "2A"
        }
      ],
      "positions": [],
      "jtm": "6"
    },
    {
      "name": "Nihayah Bagis, S.Pd.I",
      "user_id": "98193f49-72e9-4cf9-a813-85ef80e110da",
      "subjects": [
        {
          "label": "PJOK",
          "subject_id": "af946a5b-48bc-4f38-a3aa-8bf51196945d",
          "hours": {
            "2B": 2,
            "2C": 2,
            "2D": 2
          }
        },
        {
          "label": "Matematika",
          "subject_id": "e7ad5aa9-cee5-4a38-97d0-21bb74cb61bd",
          "hours": {
            "2A": 2,
            "2B": 4,
            "2C": 2,
            "2D": 2
          }
        },
        {
          "label": "Bahasa Indonesia",
          "subject_id": "a01d65b8-33bf-4984-8fbd-5e76290208ff",
          "hours": {
            "2A": 4,
            "2B": 4,
            "2C": 2,
            "2D": 2
          }
        },
        {
          "label": "Seni Budaya",
          "subject_id": "eeeeb1e3-73f7-4376-8a90-13815d9830ea",
          "hours": {
            "2B": 1
          }
        }
      ],
      "tasks": [
        {
          "label": "Wali Kelas",
          "group": "2B"
        }
      ],
      "positions": [],
      "jtm": "6"
    },
    {
      "name": "Nudia Salim, S.Pd.",
      "user_id": "7555272c-6d8d-48ce-99a6-3251386ed2e2",
      "subjects": [
        {
          "label": "PJOK",
          "subject_id": "af946a5b-48bc-4f38-a3aa-8bf51196945d",
          "hours": {
            "2A": 2,
            "2B": 2,
            "2C": 2,
            "2D": 2
          }
        },
        {
          "label": "Matematika",
          "subject_id": "e7ad5aa9-cee5-4a38-97d0-21bb74cb61bd",
          "hours": {
            "2C": 4,
            "2D": 4
          }
        },
        {
          "label": "Bahasa Indonesia",
          "subject_id": "a01d65b8-33bf-4984-8fbd-5e76290208ff",
          "hours": {
            "2A": 2,
            "2B": 2,
            "2C": 4,
            "2D": 4
          }
        },
        {
          "label": "Seni Budaya",
          "subject_id": "eeeeb1e3-73f7-4376-8a90-13815d9830ea",
          "hours": {
            "2C": 1
          }
        }
      ],
      "tasks": [
        {
          "label": "Wali Kelas",
          "group": "2C"
        }
      ],
      "positions": [],
      "jtm": "8"
    },
    {
      "name": "Miftahul Jannah, S.Pd.I.",
      "user_id": "a6756fdc-547d-418b-8967-10c149a0057d",
      "subjects": [
        {
          "label": "Bahasa Arab",
          "subject_id": "e715da00-dfb4-40a3-87a2-51620547933f",
          "hours": {
            "2A": 7,
            "2B": 7,
            "2C": 7,
            "2D": 7
          }
        },
        {
          "label": "Seni Budaya",
          "subject_id": "eeeeb1e3-73f7-4376-8a90-13815d9830ea",
          "hours": {
            "2D": 1
          }
        }
      ],
      "tasks": [
        {
          "label": "Wali Kelas",
          "group": "2D"
        }
      ],
      "positions": [],
      "jtm": "28"
    },
    {
      "name": "Baiq Nia Dwi Lisna, S.Pd.",
      "user_id": "6fa270f0-2bda-461e-8ab9-8874e4650a5f",
      "subjects": [
        {
          "label": "Bahasa Indonesia",
          "subject_id": "a01d65b8-33bf-4984-8fbd-5e76290208ff",
          "hours": {
            "3A": 2,
            "3B": 2,
            "3C": 2
          }
        },
        {
          "label": "Matematika",
          "subject_id": "e7ad5aa9-cee5-4a38-97d0-21bb74cb61bd",
          "hours": {
            "3A": 4,
            "3B": 4,
            "3C": 4
          }
        },
        {
          "label": "IPAS",
          "subject_id": "4b19e25a-0b5c-45b3-8bc6-56a08458823e",
          "hours": {
            "3A": 3,
            "3B": 3,
            "3C": 3
          }
        },
        {
          "label": "Seni Budaya",
          "subject_id": "eeeeb1e3-73f7-4376-8a90-13815d9830ea",
          "hours": {
            "3A": 1
          }
        }
      ],
      "tasks": [
        {
          "label": "Wali Kelas",
          "group": "3A"
        }
      ],
      "positions": [],
      "jtm": "6"
    },
    {
      "name": "Syeha Baktir, S.Pd.",
      "user_id": "9a8b05b6-68da-4c58-8657-d0cc547157cb",
      "subjects": [
        {
          "label": "Bahasa Indonesia",
          "subject_id": "a01d65b8-33bf-4984-8fbd-5e76290208ff",
          "hours": {
            "3A": 2,
            "3B": 2,
            "3C": 2
          }
        },
        {
          "label": "Matematika",
          "subject_id": "e7ad5aa9-cee5-4a38-97d0-21bb74cb61bd",
          "hours": {
            "3A": 4,
            "3B": 4,
            "3C": 4
          }
        },
        {
          "label": "IPAS",
          "subject_id": "4b19e25a-0b5c-45b3-8bc6-56a08458823e",
          "hours": {
            "3A": 3,
            "3B": 3,
            "3C": 3
          }
        },
        {
          "label": "Seni Budaya",
          "subject_id": "eeeeb1e3-73f7-4376-8a90-13815d9830ea",
          "hours": {
            "3B": 1
          }
        }
      ],
      "tasks": [
        {
          "label": "Wali Kelas",
          "group": "3B"
        }
      ],
      "positions": [],
      "jtm": "6"
    },
    {
      "name": "Sarafiah, S.Pd.",
      "user_id": "1c683441-2ee2-4b96-a6d0-9c8d54f54845",
      "subjects": [
        {
          "label": "Bahasa Indonesia",
          "subject_id": "a01d65b8-33bf-4984-8fbd-5e76290208ff",
          "hours": {
            "3A": 2,
            "3B": 2,
            "3C": 2
          }
        },
        {
          "label": "Matematika",
          "subject_id": "e7ad5aa9-cee5-4a38-97d0-21bb74cb61bd",
          "hours": {
            "3A": 4,
            "3B": 4,
            "3C": 4
          }
        },
        {
          "label": "IPAS",
          "subject_id": "4b19e25a-0b5c-45b3-8bc6-56a08458823e",
          "hours": {
            "3A": 3,
            "3B": 3,
            "3C": 3
          }
        },
        {
          "label": "Seni Budaya",
          "subject_id": "eeeeb1e3-73f7-4376-8a90-13815d9830ea",
          "hours": {
            "3C": 1
          }
        }
      ],
      "tasks": [
        {
          "label": "Wali Kelas",
          "group": "3C"
        }
      ],
      "positions": [],
      "jtm": "6"
    },
    {
      "name": "Ahyar Rosidi, S.Pd.I",
      "user_id": "b7bba2a9-1615-4c39-a232-d1f00079ceea",
      "subjects": [
        {
          "label": "Adab",
          "subject_id": "11589638-7bfb-426f-9bad-42b498783658",
          "hours": {
            "3A": 2,
            "3B": 2,
            "3C": 2,
            "4A": 2,
            "4B": 2,
            "4C": 2
          }
        },
        {
          "label": "Aqidah",
          "subject_id": "d023e743-6247-457f-a88e-ef8da8b1966e",
          "hours": {
            "4A": 2,
            "4B": 2,
            "4C": 2
          }
        },
        {
          "label": "PKn",
          "subject_id": "53e638b2-82a3-4378-b203-74cdf3464ea3",
          "hours": {
            "4A": 2,
            "4B": 2,
            "4C": 2
          }
        },
        {
          "label": "Seni Budaya",
          "subject_id": "eeeeb1e3-73f7-4376-8a90-13815d9830ea",
          "hours": {
            "4A": 1
          }
        }
      ],
      "tasks": [
        {
          "label": "Wali Kelas",
          "group": "4A"
        }
      ],
      "positions": [],
      "jtm": "12"
    },
    {
      "name": "Raden Mulawarman, S.Pd.",
      "user_id": "01f37c58-ec66-416e-96d0-4b2448f410ae",
      "subjects": [
        {
          "label": "Bahasa Indonesia",
          "subject_id": "a01d65b8-33bf-4984-8fbd-5e76290208ff",
          "hours": {
            "4A": 2,
            "4B": 2,
            "4C": 2
          }
        },
        {
          "label": "Matematika",
          "subject_id": "e7ad5aa9-cee5-4a38-97d0-21bb74cb61bd",
          "hours": {
            "4A": 4,
            "4B": 4,
            "4C": 4
          }
        },
        {
          "label": "IPAS",
          "subject_id": "4b19e25a-0b5c-45b3-8bc6-56a08458823e",
          "hours": {
            "4A": 3,
            "4B": 3,
            "4C": 3
          }
        },
        {
          "label": "Seni Budaya",
          "subject_id": "eeeeb1e3-73f7-4376-8a90-13815d9830ea",
          "hours": {
            "4B": 1
          }
        }
      ],
      "tasks": [
        {
          "label": "Koord. Guru Umum",
          "group": null
        },
        {
          "label": "Wali Kelas",
          "group": "4B"
        }
      ],
      "positions": [],
      "jtm": "6"
    },
    {
      "name": "Zohdi Tarmizi, S.Pd.",
      "user_id": "1c3f3eb9-e03c-4fb7-91cc-82836f4c919f",
      "subjects": [
        {
          "label": "Bahasa Indonesia",
          "subject_id": "a01d65b8-33bf-4984-8fbd-5e76290208ff",
          "hours": {
            "4A": 2,
            "4B": 2,
            "4C": 2
          }
        },
        {
          "label": "Matematika",
          "subject_id": "e7ad5aa9-cee5-4a38-97d0-21bb74cb61bd",
          "hours": {
            "4A": 4,
            "4B": 4,
            "4C": 4
          }
        },
        {
          "label": "IPAS",
          "subject_id": "4b19e25a-0b5c-45b3-8bc6-56a08458823e",
          "hours": {
            "4A": 3,
            "4B": 3,
            "4C": 3
          }
        },
        {
          "label": "Seni Budaya",
          "subject_id": "eeeeb1e3-73f7-4376-8a90-13815d9830ea",
          "hours": {
            "4C": 1
          }
        }
      ],
      "tasks": [
        {
          "label": "Wali Kelas",
          "group": "4C"
        }
      ],
      "positions": [],
      "jtm": "6"
    },
    {
      "name": "A.L. Setyawan S., S.Pd.",
      "user_id": "3f803666-714e-4983-9906-679551ee8c25",
      "subjects": [
        {
          "label": "Bahasa Indonesia",
          "subject_id": "a01d65b8-33bf-4984-8fbd-5e76290208ff",
          "hours": {
            "5A": 2,
            "5B": 2,
            "5C": 2,
            "6A": 2,
            "6B": 2,
            "6C": 2
          }
        },
        {
          "label": "Seni Budaya",
          "subject_id": "eeeeb1e3-73f7-4376-8a90-13815d9830ea",
          "hours": {
            "5A": 1
          }
        },
        {
          "label": "Bahasa Inggris",
          "subject_id": "390fe0dc-626a-4049-9e9f-ba3d403a16a3",
          "hours": {
            "5A": 2,
            "5B": 2,
            "5C": 2,
            "6A": 2,
            "6B": 2,
            "6C": 2
          }
        },
        {
          "label": "PKn",
          "subject_id": "53e638b2-82a3-4378-b203-74cdf3464ea3",
          "hours": {
            "5A": 1
          }
        }
      ],
      "tasks": [
        {
          "label": "Koord. Kesiswaan",
          "group": null
        },
        {
          "label": "Wali Kelas",
          "group": "5A"
        }
      ],
      "positions": [],
      "jtm": "12"
    },
    {
      "name": "Mustaring , S.Pd.",
      "user_id": "ef632030-0531-4462-b35b-9014ff126438",
      "subjects": [
        {
          "label": "Matematika",
          "subject_id": "e7ad5aa9-cee5-4a38-97d0-21bb74cb61bd",
          "hours": {
            "5A": 4,
            "5B": 4,
            "5C": 4
          }
        },
        {
          "label": "IPAS",
          "subject_id": "4b19e25a-0b5c-45b3-8bc6-56a08458823e",
          "hours": {
            "5A": 2,
            "5B": 2,
            "5C": 2
          }
        },
        {
          "label": "PKn",
          "subject_id": "53e638b2-82a3-4378-b203-74cdf3464ea3",
          "hours": {
            "5B": 1
          }
        },
        {
          "label": "Seni Budaya",
          "subject_id": "eeeeb1e3-73f7-4376-8a90-13815d9830ea",
          "hours": {
            "5B": 1
          }
        },
        {
          "label": "Koding",
          "subject_id": null,
          "hours": {
            "5A": 1,
            "5B": 1,
            "5C": 1,
            "6A": 1,
            "6B": 1,
            "6C": 1
          }
        }
      ],
      "tasks": [
        {
          "label": "Wali Kelas",
          "group": "5B"
        }
      ],
      "positions": [],
      "jtm": "12"
    },
    {
      "name": "Dwi Agus Suramto, S.Pd.",
      "user_id": "d15aaa5e-10d9-48bc-b6ad-e29aa4a11116",
      "subjects": [
        {
          "label": "Hadits",
          "subject_id": "09619f2d-93ac-45bb-bb19-3e510c60b590",
          "hours": {
            "5A": 2,
            "5B": 2,
            "5C": 2
          }
        },
        {
          "label": "Bahasa Arab",
          "subject_id": "e715da00-dfb4-40a3-87a2-51620547933f",
          "hours": {
            "5A": 6,
            "5B": 6,
            "5C": 6
          }
        },
        {
          "label": "Seni Budaya",
          "subject_id": "eeeeb1e3-73f7-4376-8a90-13815d9830ea",
          "hours": {
            "5C": 1
          }
        },
        {
          "label": "PKn",
          "subject_id": "53e638b2-82a3-4378-b203-74cdf3464ea3",
          "hours": {
            "5C": 1
          }
        }
      ],
      "tasks": [
        {
          "label": "Wali Kelas",
          "group": "5C"
        }
      ],
      "positions": [],
      "jtm": "6"
    },
    {
      "name": "Jihad Al Fatawa, S.Pd.",
      "user_id": "33baaef9-97b2-4f64-a1f0-91dddc10813e",
      "subjects": [
        {
          "label": "PKn",
          "subject_id": "53e638b2-82a3-4378-b203-74cdf3464ea3",
          "hours": {
            "6A": 1
          }
        },
        {
          "label": "Bahasa Arab",
          "subject_id": "e715da00-dfb4-40a3-87a2-51620547933f",
          "hours": {
            "6A": 6,
            "6B": 6,
            "6C": 6
          }
        },
        {
          "label": "Hadits",
          "subject_id": "09619f2d-93ac-45bb-bb19-3e510c60b590",
          "hours": {
            "6A": 2,
            "6B": 2,
            "6C": 2
          }
        },
        {
          "label": "Seni Budaya",
          "subject_id": "eeeeb1e3-73f7-4376-8a90-13815d9830ea",
          "hours": {
            "6A": 1
          }
        }
      ],
      "tasks": [
        {
          "label": "Wali Kelas",
          "group": "6A"
        }
      ],
      "positions": [],
      "jtm": "1"
    },
    {
      "name": "Nul Hakim, S.Pd",
      "user_id": "e44b0433-9dd8-4f6f-b642-5ac84d7d7a4f",
      "subjects": [
        {
          "label": "Matematika",
          "subject_id": "e7ad5aa9-cee5-4a38-97d0-21bb74cb61bd",
          "hours": {
            "6A": 2,
            "6B": 2,
            "6C": 2
          }
        },
        {
          "label": "IPAS",
          "subject_id": "4b19e25a-0b5c-45b3-8bc6-56a08458823e",
          "hours": {
            "5A": 3,
            "5B": 3,
            "5C": 3,
            "6A": 3,
            "6B": 3,
            "6C": 3
          }
        },
        {
          "label": "PKn",
          "subject_id": "53e638b2-82a3-4378-b203-74cdf3464ea3",
          "hours": {
            "6B": 1
          }
        },
        {
          "label": "Seni Budaya",
          "subject_id": "eeeeb1e3-73f7-4376-8a90-13815d9830ea",
          "hours": {
            "6B": 1
          }
        }
      ],
      "tasks": [
        {
          "label": "Wali Kelas",
          "group": "6B"
        }
      ],
      "positions": [],
      "jtm": "6"
    },
    {
      "name": "Saupiah",
      "user_id": "ced6f02e-b871-42ff-af1c-a180f5c4b3ed",
      "subjects": [
        {
          "label": "Guru Tahfizh",
          "subject_id": "562ac821-1e7a-41ed-aceb-c8227b2cc07a",
          "hours": {
            "1A": 9,
            "1B": 9,
            "1C": 9,
            "1D": 9
          }
        }
      ],
      "tasks": [],
      "positions": [],
      "jtm": "36"
    },
    {
      "name": "Zannul Yatin",
      "user_id": "63c20e0c-ab29-4011-b8cf-6a23a80ef601",
      "subjects": [
        {
          "label": "Guru Tahfizh",
          "subject_id": "562ac821-1e7a-41ed-aceb-c8227b2cc07a",
          "hours": {
            "1A": 9,
            "1B": 9,
            "1C": 9,
            "1D": 9
          }
        }
      ],
      "tasks": [],
      "positions": [],
      "jtm": "36"
    },
    {
      "name": "Amirah  Muhammad Umar, S.Pd.",
      "user_id": "4abae8de-a62e-4efb-9b0f-7d0335d6f366",
      "subjects": [
        {
          "label": "Guru Tahfizh",
          "subject_id": "562ac821-1e7a-41ed-aceb-c8227b2cc07a",
          "hours": {
            "1A": 9,
            "1B": 9,
            "1C": 9,
            "1D": 9
          }
        }
      ],
      "tasks": [],
      "positions": [],
      "jtm": "36"
    },
    {
      "name": "Hidayatul Mardiyah",
      "user_id": "7092e2c7-be15-4351-b596-d27bd7edaa98",
      "subjects": [
        {
          "label": "Guru Tahfizh",
          "subject_id": "562ac821-1e7a-41ed-aceb-c8227b2cc07a",
          "hours": {
            "2A": 9,
            "2B": 9,
            "2C": 9,
            "2D": 9
          }
        }
      ],
      "tasks": [],
      "positions": [],
      "jtm": "36"
    },
    {
      "name": "Amiatun Martini",
      "user_id": "0fadff1c-cbc3-4348-a38d-2616d380bc11",
      "subjects": [
        {
          "label": "Guru Tahfizh",
          "subject_id": "562ac821-1e7a-41ed-aceb-c8227b2cc07a",
          "hours": {
            "2A": 9,
            "2B": 9,
            "2C": 9,
            "2D": 9
          }
        }
      ],
      "tasks": [],
      "positions": [],
      "jtm": "36"
    },
    {
      "name": "Istiqomah",
      "user_id": "cccfc852-9717-4332-98e6-5d3c097b4826",
      "subjects": [
        {
          "label": "Guru Tahfizh",
          "subject_id": "562ac821-1e7a-41ed-aceb-c8227b2cc07a",
          "hours": {
            "2A": 9,
            "2B": 9,
            "2C": 9,
            "2D": 9
          }
        }
      ],
      "tasks": [],
      "positions": [],
      "jtm": "36"
    },
    {
      "name": "Ahmad Yusup, S.Pd.",
      "user_id": "621c69a4-0592-4da3-a1c6-5ee135457dbb",
      "subjects": [
        {
          "label": "Guru Tahfizh",
          "subject_id": "562ac821-1e7a-41ed-aceb-c8227b2cc07a",
          "hours": {
            "3A": 9,
            "4A": 9,
            "5A": 9,
            "6A": 9
          }
        }
      ],
      "tasks": [],
      "positions": [],
      "jtm": "36"
    },
    {
      "name": "Lalu Raditya  Tripamunkas,",
      "user_id": "48d8efc9-fd07-4165-9cc6-1bca483d07d9",
      "subjects": [
        {
          "label": "Guru Tahfizh",
          "subject_id": "562ac821-1e7a-41ed-aceb-c8227b2cc07a",
          "hours": {
            "3A": 9,
            "4A": 9,
            "5A": 9,
            "6A": 9
          }
        }
      ],
      "tasks": [],
      "positions": [],
      "jtm": "36"
    },
    {
      "name": "Ichwanuddin, S.H., M.Kn.",
      "user_id": "88024bd2-737e-4637-ae42-afd8f10b072f",
      "subjects": [
        {
          "label": "Guru Tahfizh",
          "subject_id": "562ac821-1e7a-41ed-aceb-c8227b2cc07a",
          "hours": {
            "3A": 9,
            "4A": 9,
            "5A": 9,
            "6A": 9
          }
        }
      ],
      "tasks": [],
      "positions": [],
      "jtm": "36"
    },
    {
      "name": "Rosihin",
      "user_id": "8288ea2b-299c-4f2f-84e1-60fde605180d",
      "subjects": [
        {
          "label": "Guru Tahfizh",
          "subject_id": "562ac821-1e7a-41ed-aceb-c8227b2cc07a",
          "hours": {
            "3A": 9,
            "4A": 9,
            "5A": 9,
            "6A": 9
          }
        }
      ],
      "tasks": [],
      "positions": [],
      "jtm": "36"
    },
    {
      "name": "Munawwir",
      "user_id": "3914b7d9-50cc-44dd-8c29-a443d202cd43",
      "subjects": [
        {
          "label": "Guru Tahfizh",
          "subject_id": "562ac821-1e7a-41ed-aceb-c8227b2cc07a",
          "hours": {
            "3B": 9,
            "4B": 9,
            "5B": 9,
            "6B": 9
          }
        }
      ],
      "tasks": [],
      "positions": [],
      "jtm": "36"
    },
    {
      "name": "Heru Kusuma Wijaya, S.Pd.",
      "user_id": "28f96e56-62e7-4225-af44-a1ad4cd79a68",
      "subjects": [
        {
          "label": "Guru Tahfizh",
          "subject_id": "562ac821-1e7a-41ed-aceb-c8227b2cc07a",
          "hours": {
            "3B": 9,
            "4B": 9,
            "5B": 9,
            "6B": 9
          }
        }
      ],
      "tasks": [],
      "positions": [],
      "jtm": "36"
    },
    {
      "name": "Zaeni",
      "user_id": "609bf76f-8c89-4409-b82a-a69a1258011f",
      "subjects": [
        {
          "label": "Guru Tahfizh",
          "subject_id": "562ac821-1e7a-41ed-aceb-c8227b2cc07a",
          "hours": {
            "3B": 9,
            "4B": 9,
            "5B": 9,
            "6B": 9
          }
        }
      ],
      "tasks": [],
      "positions": [],
      "jtm": "36"
    },
    {
      "name": "Muzammil, S.Ag",
      "user_id": "565b1e6b-92d1-447a-a3e5-7ec127cb4f86",
      "subjects": [
        {
          "label": "Guru Tahfizh",
          "subject_id": "562ac821-1e7a-41ed-aceb-c8227b2cc07a",
          "hours": {
            "3B": 9,
            "4B": 9,
            "5B": 9,
            "6B": 9
          }
        }
      ],
      "tasks": [],
      "positions": [],
      "jtm": "36"
    },
    {
      "name": "Sadiyah",
      "user_id": "7937b911-35a6-471e-83d0-f73fc9e80501",
      "subjects": [
        {
          "label": "Guru Tahfizh",
          "subject_id": "562ac821-1e7a-41ed-aceb-c8227b2cc07a",
          "hours": {
            "3C": 9,
            "4C": 9,
            "5C": 9,
            "6C": 9
          }
        }
      ],
      "tasks": [],
      "positions": [],
      "jtm": "36"
    },
    {
      "name": "Mizan Syarif, S.Pd.",
      "user_id": "aa6e6748-7dfc-44cf-b926-de2791098b57",
      "subjects": [
        {
          "label": "Guru Tahfizh",
          "subject_id": "562ac821-1e7a-41ed-aceb-c8227b2cc07a",
          "hours": {
            "3C": 9,
            "4C": 9,
            "5C": 9,
            "6C": 9
          }
        }
      ],
      "tasks": [],
      "positions": [],
      "jtm": "36"
    },
    {
      "name": "Sudirman",
      "user_id": "87d5d522-e666-443c-8e6b-11068fe7d8e3",
      "subjects": [
        {
          "label": "Guru Tahfizh",
          "subject_id": "562ac821-1e7a-41ed-aceb-c8227b2cc07a",
          "hours": {
            "3C": 9,
            "4C": 9,
            "5C": 9,
            "6C": 9
          }
        }
      ],
      "tasks": [],
      "positions": [],
      "jtm": "36"
    },
    {
      "name": "Abdul Mukti",
      "user_id": "039689eb-7a50-45a9-aa68-e5e8c787d3fe",
      "subjects": [
        {
          "label": "Guru Tahfizh",
          "subject_id": "562ac821-1e7a-41ed-aceb-c8227b2cc07a",
          "hours": {
            "3C": 9,
            "4C": 9,
            "5C": 9,
            "6C": 9
          }
        }
      ],
      "tasks": [],
      "positions": [],
      "jtm": "36"
    }
  ],
  "missing_subjects": [
    "Koding"
  ],
  "columns": [
    "1A",
    "1B",
    "1C",
    "1D",
    "2A",
    "2B",
    "2C",
    "2D",
    "3A",
    "3B",
    "3C",
    "4A",
    "4B",
    "4C",
    "5A",
    "5B",
    "5C",
    "6A",
    "6B",
    "6C"
  ]
}
JSON, true);

        $subjectDefinitions = [
            'Adab' => ['ADB', 'Adab'],
            'Aqidah' => ['AQA', 'Aqidah'],
            'Bahasa Arab' => ['BAR', 'Bahasa Arab'],
            'Bahasa Indonesia' => ['BIN', 'Bahasa Indonesia'],
            'Bahasa Inggris' => ['BIG', 'Bahasa Inggris'],
            'Fikih' => ['FQH', 'Fikih'],
            'Fiqih' => ['FQH', 'Fikih'],
            'Hadits' => ['HDS', 'Hafalan Hadits'],
            'IPAS' => ['IPA', 'IPAS'],
            'Koding' => ['KOD', 'Koding'],
            'Matematika' => ['MAT', 'Matematika'],
            'PJOK' => ['PJOK', 'PJOK'],
            'PKn' => ['PKN', 'PKn'],
            'Seni budaya' => ['SEN', 'Seni Budaya'],
            'Seni Budaya' => ['SEN', 'Seni Budaya'],
            'Tahfidz' => ['THF', "Tahfidzul Qur'an"],
            'Guru Tahfizh' => ['THF', "Tahfidzul Qur'an"],
        ];

        $subjectIds = [];
        foreach ($subjectDefinitions as $label => [$code, $name]) {
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

        $groupNames = [
            '1A', '1B', '1C', '1D', '2A', '2B', '2C', '2D',
            '3A', '3B', '3C', '4A', '4B', '4C',
            '5A', '5B', '5C', '6A', '6B', '6C',
        ];

        $groupIds = [];
        foreach ($groupNames as $name) {
            $level = (int) substr($name, 0, 1);
            $group = StudyGroup::firstOrCreate(
                [
                    'school_id' => $school->id,
                    'academic_year_id' => $academicYear->id,
                    'name' => $name,
                ],
                [
                    'grade_level_id' => $gradeIds[$level] ?? null,
                    'code' => $name,
                    'capacity' => 30,
                    'curriculum_type' => 'merdeka',
                    'shift' => 'pagi',
                    'is_active' => true,
                ]
            );
            $groupIds[$name] = $group->id;
        }

        // Jabatan struktural yang disebut pada SK (memakai master yang ada).
        $positionUpdates = [
            '5b93f910-ea32-4c34-a19f-778dba011059' => 'Kepala Tata Usaha', // Ferry
            'd0bcb4d9-f0d7-4e03-a4ae-f356305769cb' => 'Tata Usaha',        // Agus Sofian
            'fecf5b05-0006-4858-bfe8-ac64009644b6' => 'Tata Usaha',        // Hamrol
            '299fe697-8419-4e73-942e-5c0f2a53ae48' => 'Operator Sekolah',  // Thamrin
        ];

        foreach ($positionUpdates as $userId => $positionName) {
            $position = StructuralPosition::where('name', $positionName)->first();
            if (! $position) {
                continue;
            }

            GtkWorkUnit::where('user_id', $userId)
                ->where('work_unit_id', $school->work_unit_id)
                ->update([
                    'position_id' => $position->id,
                    'jabatan' => $position->name,
                ]);
        }

        $taskDefinitions = [
            'Wali Kelas' => ['wali-kelas', 'Wali Kelas'],
            'Koord. Guru Agama' => ['koordinator-guru-agama', 'Koordinator Guru Agama'],
            'Koord. Guru B. Arab' => ['koordinator-guru-bahasa-arab', 'Koordinator Guru Bahasa Arab'],
            'Koord. Guru Tahfizh' => ['koordinator-guru-tahfidz', 'Koordinator Guru Tahfidz'],
            'Koord. Guru Umum' => ['koordinator-guru-umum', 'Koordinator Guru Umum'],
            'Koord. Kesiswaan' => ['koordinator-kesiswaan', 'Koordinator Kesiswaan'],
            'Koord. Sarpras' => ['koordinator-sarpras-satuan-pendidikan', 'Koordinator Sarpras Satuan Pendidikan'],
            'Bendahara Sekolah' => ['bendahara-sekolah', 'Bendahara Sekolah'],
        ];

        $taskTypes = [];
        foreach ($taskDefinitions as $label => [$code, $name]) {
            $type = AdditionalTaskType::where('kode', $code)->first();
            if ($type) {
                $taskTypes[$label] = $type;
            }
        }

        $decree = InstitutionDecree::updateOrCreate(
            ['decree_number' => '110/PAH/SDIT-Pa/SK/VII/2026'],
            [
                'decree_type' => 'SK Pembagian Tugas',
                'title' => 'Pemenuhan Beban Kerja GTK, Pembagian Tugas Guru dalam Proses Belajar Mengajar, Tugas Tambahan Semester Genap Tahun Pelajaran 2025/2026',
                'description' => 'SK Kepala SDIT Putra Abu Hurairah Mataram tentang pemenuhan beban kerja GTK, pembagian tugas guru, dan tugas tambahan semester genap 2025/2026.',
                'school_id' => $school->id,
                'academic_year_id' => $academicYear->id,
                'issued_date' => '2026-07-01',
                'effective_date' => '2026-07-01',
                'end_date' => null,
                'signed_by' => '2e8346be-5b37-4ede-a2dd-c3ce767615ad',
                'signed_position' => 'Kepala Sekolah',
                'status' => 'active',
            ]
        );

        $assignments = [];
        $tasks = [];

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
                $type = $taskTypes[$taskRow['label']] ?? null;
                if (! $type) {
                    continue;
                }

                $groupId = $taskRow['group'] ? ($groupIds[$taskRow['group']] ?? null) : null;

                $tasks[] = [
                    'teacher_id' => $teacherId,
                    'task_name' => $type->nama,
                    'task_code' => $type->kode,
                    'weekly_hours' => 0,
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
            'SK %s: %d penugasan, %d tugas tambahan.',
            $decree->decree_number,
            $result['assignments_created'] + $result['assignments_updated'],
            $result['tasks_created'] + $result['tasks_updated']
        ));
    }
}
