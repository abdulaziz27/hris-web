<?php

namespace Database\Seeders;

use App\Models\Departemen;
use App\Models\Jabatan;
use App\Models\Location;
use App\Models\ShiftKerja;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $shiftIds = ShiftKerja::pluck('id', 'name');
        $departemenIds = Departemen::pluck('id', 'name');
        $jabatanIds = Jabatan::pluck('id', 'name');
        $locationIds = Location::pluck('id', 'name');

        // Helper function to generate email from name
        $generateEmail = function ($name) {
            $email = strtolower(trim($name));
            $email = preg_replace('/[^a-z0-9\s]/', '', $email); // Remove special characters
            $email = preg_replace('/\s+/', '', $email); // Remove spaces
            return $email . '@sairnapaor.com';
        };

        // Data dari spreadsheet client (ambil data unik berdasarkan nama, prioritas bulan terakhir)
        $rawData = [
            // Kalianta
            ['name' => 'Afsul Gani', 'kebun' => 'Kalianta', 'bagian' => 'Pembibitan', 'jobdesk' => 'Jaga Malam'],
            ['name' => 'Vredi Putra', 'kebun' => 'Kalianta', 'bagian' => 'Kantor/Umum', 'jobdesk' => 'Jaga Malam'],
            ['name' => 'Ibnu Yazid', 'kebun' => 'Kalianta', 'bagian' => 'Kantor/Umum', 'jobdesk' => 'Jaga Malam'],
            ['name' => 'Diki Kurniawan', 'kebun' => 'Kalianta', 'bagian' => 'Kantor/Umum', 'jobdesk' => 'Jaga Malam'],
            ['name' => 'Rahman', 'kebun' => 'Kalianta', 'bagian' => 'Pembibitan', 'jobdesk' => 'Jaga Malam'],
            ['name' => 'Muliadi Nasution', 'kebun' => 'Kalianta', 'bagian' => 'Kantor/Umum', 'jobdesk' => 'Jaga Malam'],
            ['name' => 'Martini', 'kebun' => 'Kalianta', 'bagian' => 'Kantor/Umum', 'jobdesk' => 'Pelayan Mess'],
            ['name' => 'Gofar Alpajri', 'kebun' => 'Kalianta', 'bagian' => 'Pembibitan', 'jobdesk' => 'Pemb. Operator Mesin Air III'],
            ['name' => 'Ardi', 'kebun' => 'Kalianta', 'bagian' => 'Pembibitan', 'jobdesk' => 'Jaga Siang Bibitan'],
            ['name' => 'Chandra', 'kebun' => 'Kalianta', 'bagian' => 'Kantor/Umum', 'jobdesk' => 'Pemb. Opr Mesin Listrik'],
            ['name' => 'Ryan Egi Arnanda', 'kebun' => 'Kalianta', 'bagian' => 'Pembibitan', 'jobdesk' => 'Pemb. Opr Mesin Air II'],
            ['name' => 'Aldi Nurmanto', 'kebun' => 'Kalianta', 'bagian' => 'Pembibitan', 'jobdesk' => 'Pemb. Opr Mesin Air III'],
            ['name' => 'Zainal Abidin', 'kebun' => 'Kalianta', 'bagian' => 'Pembibitan', 'jobdesk' => 'Jaga Siang Bibitan'],
            ['name' => 'Amal Bakti', 'kebun' => 'Kalianta', 'bagian' => 'Pembibitan', 'jobdesk' => 'Pemb. Opr Mesin Listrik Baru'],
            ['name' => 'Mareston Purba', 'kebun' => 'Kalianta', 'bagian' => 'Pembibitan', 'jobdesk' => 'Jaga Siang Bibitan'],
            ['name' => 'Swiono Hutagalung', 'kebun' => 'Kalianta', 'bagian' => 'Pembibitan', 'jobdesk' => 'Opr. Mesin Air II + perwatan lapangan'],
            ['name' => 'Mariman Sibarani', 'kebun' => 'Kalianta', 'bagian' => 'Pembibitan', 'jobdesk' => 'Opr. Mesin Listrik Bibitan + peratan pembibitan'],
            ['name' => 'Sukisman', 'kebun' => 'Kalianta', 'bagian' => 'Pembibitan', 'jobdesk' => 'Opr. Mesin Listrik Bibitan Baru'],
            ['name' => 'Fikri Wahyudi', 'kebun' => 'Kalianta', 'bagian' => 'Pembibitan', 'jobdesk' => 'Pemb. Opr Mesin Air II'],
            ['name' => 'Wahyu Adha', 'kebun' => 'Kalianta', 'bagian' => 'Pembibitan', 'jobdesk' => 'Opr. Mesin Air III + Perawatan Pembibitan'],
            ['name' => 'Doni Angga Lasmana', 'kebun' => 'Kalianta', 'bagian' => 'Pembibitan', 'jobdesk' => 'Pemb. Opr Mesin Air III + Perawatan Pembibitan'],
            ['name' => 'Alpin Muliandri', 'kebun' => 'Kalianta', 'bagian' => 'Pembibitan', 'jobdesk' => 'Pemb. Opr Mesin Air Baru + membantu tenaga bibitan'],
            ['name' => 'Ilham Sumardi Sitorus', 'kebun' => 'Kalianta', 'bagian' => 'Pembibitan', 'jobdesk' => 'Opr. Mesin Rumput'],
            ['name' => 'Eka Purwana', 'kebun' => 'Kalianta', 'bagian' => 'Pembibitan', 'jobdesk' => 'Opr. Mesin Rumput ( 1 Hektar )'],
            ['name' => 'Vina Lisa Eka Devi', 'kebun' => 'Kalianta', 'bagian' => 'Kantor/Umum', 'jobdesk' => 'Emplasment'],
            ['name' => 'Endrayanti Sibarani', 'kebun' => 'Kalianta', 'bagian' => 'Kantor/Umum', 'jobdesk' => 'Emplasment'],
            ['name' => 'Tuti', 'kebun' => 'Kalianta', 'bagian' => 'Tanaman/TBS', 'jobdesk' => 'Emplasment'],
            ['name' => 'Ariani Purnama', 'kebun' => 'Kalianta', 'bagian' => 'Kantor/Umum', 'jobdesk' => 'Emplasment'],
            ['name' => 'Fitri Nurul Hidayanti', 'kebun' => 'Kalianta', 'bagian' => 'Kantor/Umum', 'jobdesk' => 'Pelayan dan kebersihan kantor'],
            ['name' => 'Nasya Iwinscy Hutabarat', 'kebun' => 'Kalianta', 'bagian' => 'Tanaman/TBS', 'jobdesk' => 'Pemb. Admin Produksi'],
            ['name' => 'Saparudin', 'kebun' => 'Kalianta', 'bagian' => 'Kantor/Umum', 'jobdesk' => 'Opr. Mesin Perkins'],
            
            // Dalu-Dalu
            ['name' => 'Pariyan', 'kebun' => 'Dalu-Dalu', 'bagian' => 'Tanaman/TBS', 'jobdesk' => 'Babat Rumput'],
            ['name' => 'Puja Pratiwi', 'kebun' => 'Dalu-Dalu', 'bagian' => 'Kantor/Umum', 'jobdesk' => 'Pemb.Administrasi'],
            ['name' => 'Maharani', 'kebun' => 'Dalu-Dalu', 'bagian' => 'Kantor/Umum', 'jobdesk' => 'Pely. Kantor'],
            ['name' => 'Ayu Mentari', 'kebun' => 'Dalu-Dalu', 'bagian' => 'Kantor/Umum', 'jobdesk' => 'Pely. Mess'],
            ['name' => 'Wardani', 'kebun' => 'Dalu-Dalu', 'bagian' => 'Tanaman/TBS', 'jobdesk' => 'Emplasment'],
            ['name' => 'Dewi Ratna Sari', 'kebun' => 'Dalu-Dalu', 'bagian' => 'Tanaman/TBS', 'jobdesk' => 'Emplasment'],
            ['name' => 'M.uhammad Arif Widodo', 'kebun' => 'Dalu-Dalu', 'bagian' => 'Tanaman/TBS', 'jobdesk' => 'Emplasment'],
            ['name' => 'Reno Aldi Tampubolon', 'kebun' => 'Dalu-Dalu', 'bagian' => 'Kantor/Umum', 'jobdesk' => 'Security'],
            ['name' => 'Dermawan', 'kebun' => 'Dalu-Dalu', 'bagian' => 'Kantor/Umum', 'jobdesk' => 'Security'],
            ['name' => 'Anggi Anugrah Lubis', 'kebun' => 'Dalu-Dalu', 'bagian' => 'Kantor/Umum', 'jobdesk' => 'Security'],
            ['name' => 'Dian Faniansyah Hsb', 'kebun' => 'Dalu-Dalu', 'bagian' => 'Kantor/Umum', 'jobdesk' => 'Security'],
            ['name' => 'Erlansius Nababan', 'kebun' => 'Dalu-Dalu', 'bagian' => 'Kantor/Umum', 'jobdesk' => 'Security'],
            ['name' => 'Syalom Dileando Harefa', 'kebun' => 'Dalu-Dalu', 'bagian' => 'Kantor/Umum', 'jobdesk' => 'Centeng Kebun'],
            ['name' => 'Al Reza Mahendra', 'kebun' => 'Dalu-Dalu', 'bagian' => 'Kantor/Umum', 'jobdesk' => 'Supir Bus Sekolah'],
            ['name' => 'Jefrizal', 'kebun' => 'Dalu-Dalu', 'bagian' => 'SUS BHT', 'jobdesk' => 'Perawatan BRD Jumat dan sabtu ke bibitan'],
            ['name' => 'Agus Irawan', 'kebun' => 'Dalu-Dalu', 'bagian' => 'SUS BHT', 'jobdesk' => 'Perawatan BRD Jumat dan sabtu ke bibitan'],
            ['name' => 'Kelvin Dion S', 'kebun' => 'Dalu-Dalu', 'bagian' => 'SUS BHT', 'jobdesk' => 'Perawatan BRD'],
            ['name' => 'Fazar Aditya', 'kebun' => 'Dalu-Dalu', 'bagian' => 'SUS BHT', 'jobdesk' => 'Perawatan BRD'],
            ['name' => 'Deri Rahmadani', 'kebun' => 'Dalu-Dalu', 'bagian' => 'SUS BHT', 'jobdesk' => 'Perawatan BRD'],
            ['name' => 'Sri Lestari', 'kebun' => 'Dalu-Dalu', 'bagian' => 'Kantor/Umum', 'jobdesk' => 'Babat dan kebersihan'],
            ['name' => 'Zamiah', 'kebun' => 'Dalu-Dalu', 'bagian' => 'Kantor/Umum', 'jobdesk' => 'Emplasment'],
            ['name' => 'Iswatun Hasanah', 'kebun' => 'Dalu-Dalu', 'bagian' => 'Kantor/Umum', 'jobdesk' => 'Emplasment'],
            
            // P.Mandrsah
            ['name' => 'MHD RIZKY DUHARI', 'kebun' => 'P.Mandrsah', 'bagian' => 'Tanaman/TBS', 'jobdesk' => 'Perawatan jalan/Emplasmen'],
            ['name' => 'ADJI SWARMADIKA', 'kebun' => 'P.Mandrsah', 'bagian' => 'Kantor/Umum', 'jobdesk' => 'Olah data penjualan pembibitan'],
            ['name' => 'AHMAD UJA SIAGIAN', 'kebun' => 'P.Mandrsah', 'bagian' => 'Tanaman/TBS', 'jobdesk' => 'Perawatan jalan/Emplasmen'],
            ['name' => 'SAPARUDDIN HASIBUAN', 'kebun' => 'P.Mandrsah', 'bagian' => 'Kantor/Umum', 'jobdesk' => 'Driver/Mekanik Bus Sekolah'],
            ['name' => 'SRI ANJARWATI', 'kebun' => 'P.Mandrsah', 'bagian' => 'Kantor/Umum', 'jobdesk' => 'Pelayan Kantor/Perawatan pekarang kantor'],
            ['name' => 'MOCH FAJAR SETIAWAN JS', 'kebun' => 'P.Mandrsah', 'bagian' => 'Kantor/Umum', 'jobdesk' => 'register kas/bank/voucher keluar/verifikasi bon'],
            ['name' => 'SARMAN HASIBUAN', 'kebun' => 'P.Mandrsah', 'bagian' => 'Kantor/Umum', 'jobdesk' => 'Humas/Pengawas'],
            ['name' => 'ALPIAN', 'kebun' => 'P.Mandrsah', 'bagian' => 'Tanaman/TBS', 'jobdesk' => 'Perwatan jalan/Emplasmen'],
            ['name' => 'SOFIAN HASIBUAN', 'kebun' => 'P.Mandrsah', 'bagian' => 'Pembibitan', 'jobdesk' => 'Pengawas Jaga Malam/Perawatan Pembibitan'],
            ['name' => 'MUHAMMAD RIFAI', 'kebun' => 'P.Mandrsah', 'bagian' => 'Pembibitan', 'jobdesk' => 'Pengawas Jaga Malam/Perawatan Pembibitan'],
            ['name' => 'RUDY SINAGA', 'kebun' => 'P.Mandrsah', 'bagian' => 'Pembibitan', 'jobdesk' => 'Pengawas Jaga Malam/Perawatan Pembibitan'],
            ['name' => 'ROMA HARTO', 'kebun' => 'P.Mandrsah', 'bagian' => 'Pembibitan', 'jobdesk' => 'Pengawas Jaga Malam/Perawatan Pembibitan'],
            ['name' => 'ARDIYANSYAH SIREGAR', 'kebun' => 'P.Mandrsah', 'bagian' => 'Pembibitan', 'jobdesk' => 'Pengawas Jaga Malam/Perawatan Pembibitan'],
            ['name' => 'RAJA JUNJUNGAN PULUNGAN', 'kebun' => 'P.Mandrsah', 'bagian' => 'Tanaman/TBS', 'jobdesk' => 'Perawatan jalan/Emplasmen'],
            
            // Sarolangun
            ['name' => 'Kaspul Anwar', 'kebun' => 'Sarolangun', 'bagian' => 'Kantor/Umum', 'jobdesk' => 'Sucurity/Pengaman'],
            ['name' => 'Muslimin', 'kebun' => 'Sarolangun', 'bagian' => 'Kantor/Umum', 'jobdesk' => 'Sucurity/Pengaman'],
            ['name' => 'Al muhsi', 'kebun' => 'Sarolangun', 'bagian' => 'Kantor/Umum', 'jobdesk' => 'Sucurity/Pengaman'],
            ['name' => 'M. Arsyad', 'kebun' => 'Sarolangun', 'bagian' => 'Kantor/Umum', 'jobdesk' => 'Security'],
            ['name' => 'Jamsuri', 'kebun' => 'Sarolangun', 'bagian' => 'Kantor/Umum', 'jobdesk' => 'Humas'],
            ['name' => 'A. Salek', 'kebun' => 'Sarolangun', 'bagian' => 'Kantor/Umum', 'jobdesk' => 'Koordinator Lapangan/Mandor/Monitoring'],
            ['name' => 'Jalaluddin', 'kebun' => 'Sarolangun', 'bagian' => 'Kantor/Umum', 'jobdesk' => 'Humas'],
            ['name' => 'Muhhammad Arfan', 'kebun' => 'Sarolangun', 'bagian' => 'Kantor/Umum', 'jobdesk' => 'Humas'],
            ['name' => 'Syamsul Arifin', 'kebun' => 'Sarolangun', 'bagian' => 'Kantor/Umum', 'jobdesk' => 'Humas'],
            ['name' => 'Muhammad Nur', 'kebun' => 'Sarolangun', 'bagian' => 'Kantor/Umum', 'jobdesk' => 'Security'],
            ['name' => 'Rais Mu\'allimin', 'kebun' => 'Sarolangun', 'bagian' => 'Kantor/Umum', 'jobdesk' => 'Keamanan Jalan/perbantukan keamanan'],
            ['name' => 'Tia Juarni', 'kebun' => 'Sarolangun', 'bagian' => 'Kantor/Umum', 'jobdesk' => 'Ibu Mess'],
            ['name' => 'Teguh Iman Sobandingon', 'kebun' => 'Sarolangun', 'bagian' => 'Kantor/Umum', 'jobdesk' => 'Laporan Panen/Manjemen'],
            ['name' => 'Muhammad Izzadsyah', 'kebun' => 'Sarolangun', 'bagian' => 'Kantor/Umum', 'jobdesk' => 'Driver'],
            ['name' => 'Riky', 'kebun' => 'Sarolangun', 'bagian' => 'Kantor/Umum', 'jobdesk' => 'Adm Gudang'],
            ['name' => 'Muhammad Usman', 'kebun' => 'Sarolangun', 'bagian' => 'Kantor/Umum', 'jobdesk' => 'Security'],
            ['name' => 'Irvan Fadli', 'kebun' => 'Sarolangun', 'bagian' => 'Kantor/Umum', 'jobdesk' => 'Pembantu KTU'],
            ['name' => 'Anwar', 'kebun' => 'Sarolangun', 'bagian' => 'Pembibitan', 'jobdesk' => 'Perawatan Pembibitan/Perawatan Lapangan'],
            ['name' => 'Ahmad Sobirin', 'kebun' => 'Sarolangun', 'bagian' => 'Pembibitan', 'jobdesk' => 'Frunning Tanaman'],
            ['name' => 'Azwen Ade Saputra', 'kebun' => 'Sarolangun', 'bagian' => 'Pembibitan', 'jobdesk' => 'Security'],
            ['name' => 'Asuro', 'kebun' => 'Sarolangun', 'bagian' => 'Pembibitan', 'jobdesk' => 'Perawatan Pembibitan/Lapangan Frunning Tanaman'],
            ['name' => 'Fahruddin', 'kebun' => 'Sarolangun', 'bagian' => 'Pembibitan', 'jobdesk' => 'Perawatan Pembibitan/Lapangan Frunning Tanaman'],
            ['name' => 'Husnul Hotimah', 'kebun' => 'Sarolangun', 'bagian' => 'Pembibitan', 'jobdesk' => 'Perawatan Pembibitan/Lapangan Frunning Tanaman'],
            ['name' => 'Huzaimah', 'kebun' => 'Sarolangun', 'bagian' => 'Pembibitan', 'jobdesk' => 'Perawatan Pembibitan/Lapangan Frunning Tanaman'],
            ['name' => 'Marsidah', 'kebun' => 'Sarolangun', 'bagian' => 'Pembibitan', 'jobdesk' => 'Perawatan Pembibitan Tembas nyemprot'],
            ['name' => 'Morhasiah', 'kebun' => 'Sarolangun', 'bagian' => 'Pembibitan', 'jobdesk' => 'Perawatan Bibit/lapangan untuk nyemprot gulma'],
            ['name' => 'Mhd. Irwansyah/Alfarisi', 'kebun' => 'Sarolangun', 'bagian' => 'Pembibitan', 'jobdesk' => 'Frunning Tanaman'],
            ['name' => 'Murni', 'kebun' => 'Sarolangun', 'bagian' => 'Pembibitan', 'jobdesk' => 'Perawatan Bibit/lapangan untuk nyemprot gulma'],
            ['name' => 'Mardiah', 'kebun' => 'Sarolangun', 'bagian' => 'Pembibitan', 'jobdesk' => 'Perawatan Pembibitan/Lapangan Frunning Tanaman'],
            ['name' => 'Nurasiah', 'kebun' => 'Sarolangun', 'bagian' => 'Pembibitan', 'jobdesk' => 'Perawatan Bibit/lapangan untuk nyemprot gulma'],
            ['name' => 'Nurlis Hasanah', 'kebun' => 'Sarolangun', 'bagian' => 'Pembibitan', 'jobdesk' => 'Perawatan lapangan tanaman'],
            ['name' => 'Raudah', 'kebun' => 'Sarolangun', 'bagian' => 'Pembibitan', 'jobdesk' => 'Nyemprot gulma TBS'],
            ['name' => 'Rita Umami', 'kebun' => 'Sarolangun', 'bagian' => 'Pembibitan', 'jobdesk' => 'Frunning Tanaman'],
            ['name' => 'Siti Hajir', 'kebun' => 'Sarolangun', 'bagian' => 'Pembibitan', 'jobdesk' => 'Babat/Piringan/Perawatan'],
            ['name' => 'Siti Asiah', 'kebun' => 'Sarolangun', 'bagian' => 'Pembibitan', 'jobdesk' => 'Tebas gulma lapangan'],
            ['name' => 'Siti Rafiah/Yuli Ardianti', 'kebun' => 'Sarolangun', 'bagian' => 'Pembibitan', 'jobdesk' => 'Perawatan Bibit/lapangan untuk nyemprot gulma'],
            ['name' => 'Siti Rugayah', 'kebun' => 'Sarolangun', 'bagian' => 'Pembibitan', 'jobdesk' => 'Perawatan Pembibitan/Lapangan Frunning Tanaman'],
            ['name' => 'Siti Faridah', 'kebun' => 'Sarolangun', 'bagian' => 'Pembibitan', 'jobdesk' => 'Perawatan Pembibitan/Lapangan Frunning Tanaman'],
            ['name' => 'Siti Zahara', 'kebun' => 'Sarolangun', 'bagian' => 'Pembibitan', 'jobdesk' => 'Tebas gulma lapangan'],
            ['name' => 'Umi Kalsum', 'kebun' => 'Sarolangun', 'bagian' => 'Pembibitan', 'jobdesk' => 'Perawatan Pembibitan/Perawatan lapangan tebas anak kayu dan pelepah'],
            ['name' => 'Zuhra', 'kebun' => 'Sarolangun', 'bagian' => 'Pembibitan', 'jobdesk' => 'Perawatan Pembibitan dipekerjakan dilapangan 1 minggu kerja 3 hari senin s/d rabu (perkerjaan bisa di pembibitan bisa dilapangan tergantung kebutuhan'],
            ['name' => 'Muhammad Syarif', 'kebun' => 'Sarolangun', 'bagian' => 'Pembibitan', 'jobdesk' => 'Security'],
            ['name' => 'Syukur', 'kebun' => 'Sarolangun', 'bagian' => 'Pembibitan', 'jobdesk' => 'Security'],
            ['name' => 'Andreas Simbolon', 'kebun' => 'Sarolangun', 'bagian' => 'Pembibitan', 'jobdesk' => 'Security'],
            ['name' => 'Fitria', 'kebun' => 'Sarolangun', 'bagian' => 'Pembibitan', 'jobdesk' => 'Perawatan pembibitan'],
            ['name' => 'Kurnia Diazta Damanik', 'kebun' => 'Sarolangun', 'bagian' => 'Pembibitan', 'jobdesk' => 'Perawatan pembibitan'],
            ['name' => 'Koni', 'kebun' => 'Sarolangun', 'bagian' => 'Pembibitan', 'jobdesk' => 'Jaga malam'],
            ['name' => 'Manogar Siringo-ringo', 'kebun' => 'Sarolangun', 'bagian' => 'Pembibitan', 'jobdesk' => 'Security'],
            ['name' => 'Rip\'at', 'kebun' => 'Sarolangun', 'bagian' => 'Pembibitan', 'jobdesk' => 'Humas dan Keamanan'],
            ['name' => 'Almuttakin', 'kebun' => 'Sarolangun', 'bagian' => 'Pembibitan', 'jobdesk' => 'Humas'],
            ['name' => 'Apriko', 'kebun' => 'Sarolangun', 'bagian' => 'Pembibitan', 'jobdesk' => 'Humas dan Keamanan'],
            ['name' => 'Muhammad Faisal', 'kebun' => 'Sarolangun', 'bagian' => 'Pembibitan', 'jobdesk' => 'HUMAS dan Keamanan'],
            ['name' => 'Ibrahim', 'kebun' => 'Sarolangun', 'bagian' => 'Tanaman/TBS', 'jobdesk' => 'Mandor Frunning'],
            ['name' => 'Rasidun', 'kebun' => 'Sarolangun', 'bagian' => 'Tanaman/TBS', 'jobdesk' => 'Mandor TBS'],
            ['name' => 'Sudirman', 'kebun' => 'Sarolangun', 'bagian' => 'Tanaman/TBS', 'jobdesk' => 'Frunning Tanaman'],
            ['name' => 'Abu Bakar', 'kebun' => 'Sarolangun', 'bagian' => 'Tanaman/TBS', 'jobdesk' => 'Babat/Piringan/Perawatan'],
            ['name' => 'Chairil Hidayat', 'kebun' => 'Sarolangun', 'bagian' => 'Tanaman/TBS', 'jobdesk' => 'Babat/Piringan/Perawatan'],
            ['name' => 'Muhammad HL', 'kebun' => 'Sarolangun', 'bagian' => 'Tanaman/TBS', 'jobdesk' => 'Frunning Tanaman'],
            ['name' => 'M. Akher', 'kebun' => 'Sarolangun', 'bagian' => 'Tanaman/TBS', 'jobdesk' => 'Babat/Piringan/Perawatan Lapangan'],
            ['name' => 'Amad Tarmuzi', 'kebun' => 'Sarolangun', 'bagian' => 'Tanaman/TBS', 'jobdesk' => 'Perwatan Lapangan/Tebas/Piringan'],
            ['name' => 'Junaidi', 'kebun' => 'Sarolangun', 'bagian' => 'Tanaman/TBS', 'jobdesk' => 'Mandor Babat/Piringan/Perawatan Lapangan'],
            ['name' => 'Edyi Sofyan', 'kebun' => 'Sarolangun', 'bagian' => 'Tanaman/TBS', 'jobdesk' => 'Tebas Gulma lapangan'],
            ['name' => 'Alfatah Alimin. R / Muhammad Zul Ahmi', 'kebun' => 'Sarolangun', 'bagian' => 'Tanaman/TBS', 'jobdesk' => 'Perwatan Lapangan/Tebas/Piringan'],
            ['name' => 'Nur Asiah', 'kebun' => 'Sarolangun', 'bagian' => 'Tanaman/TBS', 'jobdesk' => 'Tebas Gulma lapangan'],
            ['name' => 'Nur Hasanah / Ismail Fahmi', 'kebun' => 'Sarolangun', 'bagian' => 'Tanaman/TBS', 'jobdesk' => 'Babat/Piringan/Perawatan Lapangan'],
            ['name' => 'Umi Latipah', 'kebun' => 'Sarolangun', 'bagian' => 'Tanaman/TBS', 'jobdesk' => 'Perawatan lapangan tanaman'],
            ['name' => 'Solma', 'kebun' => 'Sarolangun', 'bagian' => 'Tanaman/TBS', 'jobdesk' => 'Babat/Piringan/Perawatan'],
            ['name' => 'Asnaini/Dodi Arpiandi', 'kebun' => 'Sarolangun', 'bagian' => 'Tanaman/TBS', 'jobdesk' => 'Perwatan Lapangan/Tebas/Piringan'],
            ['name' => 'Nur Ainun/Abd. Malik', 'kebun' => 'Sarolangun', 'bagian' => 'Tanaman/TBS', 'jobdesk' => 'Perwatan Lapangan/Tebas/Piringan'],
            ['name' => 'Siti Hamidah', 'kebun' => 'Sarolangun', 'bagian' => 'Tanaman/TBS', 'jobdesk' => 'Babat/Piringan/Perawatan'],
            ['name' => 'Tamami', 'kebun' => 'Sarolangun', 'bagian' => 'Tanaman/TBS', 'jobdesk' => 'Perwatan Lapangan/Tebas/Piringan'],
            ['name' => 'M. Anriansah/Assolihin', 'kebun' => 'Sarolangun', 'bagian' => 'Tanaman/TBS', 'jobdesk' => 'Frunning Tanaman'],
            ['name' => 'Komarudin', 'kebun' => 'Sarolangun', 'bagian' => 'Tanaman/TBS', 'jobdesk' => 'Frunning Tanaman'],
            ['name' => 'Hidayatul Mustofik', 'kebun' => 'Sarolangun', 'bagian' => 'Tanaman/TBS', 'jobdesk' => 'Frunning Tanaman'],
            ['name' => 'Zulfikar', 'kebun' => 'Sarolangun', 'bagian' => 'Tanaman/TBS', 'jobdesk' => 'Frunning Tanaman'],
            ['name' => 'Hednri Wijaya', 'kebun' => 'Sarolangun', 'bagian' => 'Tanaman/TBS', 'jobdesk' => 'Tebas Gulma'],
            ['name' => 'Muhlisin Nalahuddin', 'kebun' => 'Sarolangun', 'bagian' => 'Tanaman/TBS', 'jobdesk' => 'Frunning Tanaman'],
            ['name' => 'Nurul Hazmi Saputra', 'kebun' => 'Sarolangun', 'bagian' => 'Tanaman/TBS', 'jobdesk' => 'Frunning Tanaman'],
            ['name' => 'Uzaipah', 'kebun' => 'Sarolangun', 'bagian' => 'Tanaman/TBS', 'jobdesk' => 'Frunning Tanaman'],
            ['name' => 'Ahmad Husein/Huznul Izzati', 'kebun' => 'Sarolangun', 'bagian' => 'Tanaman/TBS', 'jobdesk' => 'Security'],
            ['name' => 'Syarif Hidayat', 'kebun' => 'Sarolangun', 'bagian' => 'Tanaman/TBS', 'jobdesk' => 'Tebas Gulma'],
            ['name' => 'Hasan Basari', 'kebun' => 'Sarolangun', 'bagian' => 'Tanaman/TBS', 'jobdesk' => 'Tebas Gulma'],
            ['name' => 'Najmi', 'kebun' => 'Sarolangun', 'bagian' => 'Tanaman/TBS', 'jobdesk' => 'Perawatan Pembibitan/Perawatan Lapangan'],
            ['name' => 'Yusmiati', 'kebun' => 'Sarolangun', 'bagian' => 'Tanaman/TBS', 'jobdesk' => 'Perawatan Perawatan Lapangan'],
            ['name' => 'Idham Kholid', 'kebun' => 'Sarolangun', 'bagian' => 'Tanaman/TBS', 'jobdesk' => 'Perawatan Bibit/lapangan untuk nyemprot gulma'],
            ['name' => 'Dodo Sanghai', 'kebun' => 'Sarolangun', 'bagian' => 'Tanaman/TBS', 'jobdesk' => 'Sucurity/Pengaman'],
            ['name' => 'Muhammad Naim', 'kebun' => 'Sarolangun', 'bagian' => 'Tanaman/TBS', 'jobdesk' => 'Security'],
            
            // T.Dalam/P.Maria
            ['name' => 'Rinto M Siregar', 'kebun' => 'T.Dalam/P.Maria', 'bagian' => 'Tanaman/TBS', 'jobdesk' => 'Security Kebun Teluk Dalam'],
            ['name' => 'Sofian Marpaung', 'kebun' => 'T.Dalam/P.Maria', 'bagian' => 'Pembibitan', 'jobdesk' => 'Security Pembibitan'],
            ['name' => 'Munaji', 'kebun' => 'T.Dalam/P.Maria', 'bagian' => 'Pembibitan', 'jobdesk' => 'Security Pembibitan'],
            ['name' => 'Sutan Batang Onang Harahap', 'kebun' => 'T.Dalam/P.Maria', 'bagian' => 'Kantor/Umum', 'jobdesk' => 'Adm. Biro/SPB'],
            ['name' => 'Satria Putra Irawan Siregar', 'kebun' => 'T.Dalam/P.Maria', 'bagian' => 'Kelapa Kopyor', 'jobdesk' => 'Security K. Kopyor'],
            ['name' => 'Lokot Harahap', 'kebun' => 'T.Dalam/P.Maria', 'bagian' => 'Kelapa Kopyor', 'jobdesk' => 'Security K. Kopyor'],
            ['name' => 'Indra Kesuma', 'kebun' => 'T.Dalam/P.Maria', 'bagian' => 'Pembibitan', 'jobdesk' => 'Perawatan Pembibitan/Penyiraman/Operator Mesin Penyiraman'],
            ['name' => 'Rikson Sihombing', 'kebun' => 'T.Dalam/P.Maria', 'bagian' => 'Tanaman/TBS', 'jobdesk' => 'Security Kebun Teluk Dalam'],
            ['name' => 'Ebennejer sihombing Lumbantoruan', 'kebun' => 'T.Dalam/P.Maria', 'bagian' => 'Pembibitan', 'jobdesk' => 'Security Pembibitan'],
            ['name' => 'Muhammad Taufiq Marpaung', 'kebun' => 'T.Dalam/P.Maria', 'bagian' => 'Pembibitan', 'jobdesk' => 'Security Pembibitan'],
            ['name' => 'Budiman', 'kebun' => 'T.Dalam/P.Maria', 'bagian' => 'Tanaman/TBS', 'jobdesk' => 'Security Kebun Teluk Dalam'],
            
            // Simirik/Pargarutan
            ['name' => 'Eggy Syaputra Damanik', 'kebun' => 'Simirik/Pargarutan', 'bagian' => 'Pembibitan', 'jobdesk' => 'Driver/Input data'],
            ['name' => 'M.Yani', 'kebun' => 'Simirik/Pargarutan', 'bagian' => 'Pembibitan', 'jobdesk' => 'Security/pembasmi Hama'],
            ['name' => 'Riswanti', 'kebun' => 'Simirik/Pargarutan', 'bagian' => 'Pembibitan', 'jobdesk' => 'Ibu Mess/Perawatan Kantor'],
            ['name' => 'Wagiati', 'kebun' => 'Simirik/Pargarutan', 'bagian' => 'Pembibitan', 'jobdesk' => 'Perawatan bibitan/penyiraman dll'],
            ['name' => 'Legiman', 'kebun' => 'Simirik/Pargarutan', 'bagian' => 'Pembibitan', 'jobdesk' => 'Security/pembasmi Hama'],
            ['name' => 'Wawan Ariadi', 'kebun' => 'Simirik/Pargarutan', 'bagian' => 'Pembibitan', 'jobdesk' => 'Perawatan bibitan/penyiraman dll'],
            ['name' => 'Muhammad Asian', 'kebun' => 'Simirik/Pargarutan', 'bagian' => 'Pembibitan', 'jobdesk' => 'Perawatan bibitan/penyiraman dll'],
            ['name' => 'Sarianto', 'kebun' => 'Simirik/Pargarutan', 'bagian' => 'Pembibitan', 'jobdesk' => 'Perawatan bibitan/penyiraman dll'],
            ['name' => 'Riki Hamdani', 'kebun' => 'Simirik/Pargarutan', 'bagian' => 'Pembibitan', 'jobdesk' => 'Perawatan bibitan/penyiraman dll'],
            ['name' => 'Suhendra', 'kebun' => 'Simirik/Pargarutan', 'bagian' => 'Pembibitan', 'jobdesk' => 'Perawatan bibitan/penyiraman dll'],
            ['name' => 'Ayu Lestari', 'kebun' => 'Simirik/Pargarutan', 'bagian' => 'Pembibitan', 'jobdesk' => 'Pencatatan PJK/Pencatatan buku besar'],
            
            // Unit Usaha Marihat
            // Note: Bagian menggunakan default "Kantor/Umum" dan jobdesk menggunakan default "Pekerja Unit Usaha Marihat" karena tidak ada di spreadsheet
            // Bisa diubah manual melalui admin panel jika diperlukan
            ['name' => 'Ahmad Amin', 'kebun' => 'Unit Usaha Marihat', 'bagian' => 'Kantor/Umum', 'jobdesk' => 'Pekerja Unit Usaha Marihat'],
            ['name' => 'Angga Deo Firmansyah', 'kebun' => 'Unit Usaha Marihat', 'bagian' => 'Kantor/Umum', 'jobdesk' => 'Pekerja Unit Usaha Marihat'],
            ['name' => 'Bayu Dwi Syam Azhari', 'kebun' => 'Unit Usaha Marihat', 'bagian' => 'Kantor/Umum', 'jobdesk' => 'Pekerja Unit Usaha Marihat'],
            ['name' => 'Dico Rahdamsyah', 'kebun' => 'Unit Usaha Marihat', 'bagian' => 'Kantor/Umum', 'jobdesk' => 'Pekerja Unit Usaha Marihat'],
            ['name' => 'Dodi Kusuma', 'kebun' => 'Unit Usaha Marihat', 'bagian' => 'Kantor/Umum', 'jobdesk' => 'Pekerja Unit Usaha Marihat'],
            ['name' => 'Harry Sutrisno', 'kebun' => 'Unit Usaha Marihat', 'bagian' => 'Kantor/Umum', 'jobdesk' => 'Pekerja Unit Usaha Marihat'],
            ['name' => 'Muhammad Rizal Harahap', 'kebun' => 'Unit Usaha Marihat', 'bagian' => 'Kantor/Umum', 'jobdesk' => 'Pekerja Unit Usaha Marihat'],
            ['name' => 'Norman', 'kebun' => 'Unit Usaha Marihat', 'bagian' => 'Kantor/Umum', 'jobdesk' => 'Pekerja Unit Usaha Marihat'],
            ['name' => 'Poniran', 'kebun' => 'Unit Usaha Marihat', 'bagian' => 'Kantor/Umum', 'jobdesk' => 'Pekerja Unit Usaha Marihat'],
            ['name' => 'Samiran', 'kebun' => 'Unit Usaha Marihat', 'bagian' => 'Kantor/Umum', 'jobdesk' => 'Pekerja Unit Usaha Marihat'],
            ['name' => 'Tukiman', 'kebun' => 'Unit Usaha Marihat', 'bagian' => 'Kantor/Umum', 'jobdesk' => 'Pekerja Unit Usaha Marihat'],
            ['name' => 'Kiki sundari', 'kebun' => 'Unit Usaha Marihat', 'bagian' => 'Kantor/Umum', 'jobdesk' => 'Pekerja Unit Usaha Marihat'],
            ['name' => 'Sukarseh', 'kebun' => 'Unit Usaha Marihat', 'bagian' => 'Kantor/Umum', 'jobdesk' => 'Pekerja Unit Usaha Marihat'],
            ['name' => 'Ahmad Fauzi Purba', 'kebun' => 'Unit Usaha Marihat', 'bagian' => 'Kantor/Umum', 'jobdesk' => 'Pekerja Unit Usaha Marihat'],
            ['name' => 'Al Mukhlisan', 'kebun' => 'Unit Usaha Marihat', 'bagian' => 'Kantor/Umum', 'jobdesk' => 'Pekerja Unit Usaha Marihat'],
            ['name' => 'Diki Ferdiansyah', 'kebun' => 'Unit Usaha Marihat', 'bagian' => 'Kantor/Umum', 'jobdesk' => 'Pekerja Unit Usaha Marihat'],
            ['name' => 'Hizkia Saib Kirfalani batu bara', 'kebun' => 'Unit Usaha Marihat', 'bagian' => 'Kantor/Umum', 'jobdesk' => 'Pekerja Unit Usaha Marihat'],
            ['name' => 'Lina Rismayani', 'kebun' => 'Unit Usaha Marihat', 'bagian' => 'Kantor/Umum', 'jobdesk' => 'Pekerja Unit Usaha Marihat'],
            ['name' => 'Meliana Sari Siregar', 'kebun' => 'Unit Usaha Marihat', 'bagian' => 'Kantor/Umum', 'jobdesk' => 'Pekerja Unit Usaha Marihat'],
            ['name' => 'arijal fadlan nst', 'kebun' => 'Unit Usaha Marihat', 'bagian' => 'Kantor/Umum', 'jobdesk' => 'Pekerja Unit Usaha Marihat'],
            ['name' => 'Tika Febrianti', 'kebun' => 'Unit Usaha Marihat', 'bagian' => 'Kantor/Umum', 'jobdesk' => 'Pekerja Unit Usaha Marihat'],
            ['name' => 'Viqi Fahrendi', 'kebun' => 'Unit Usaha Marihat', 'bagian' => 'Kantor/Umum', 'jobdesk' => 'Pekerja Unit Usaha Marihat'],
            ['name' => 'Yopi Nazham', 'kebun' => 'Unit Usaha Marihat', 'bagian' => 'Kantor/Umum', 'jobdesk' => 'Pekerja Unit Usaha Marihat'],
            ['name' => 'Yusliana', 'kebun' => 'Unit Usaha Marihat', 'bagian' => 'Kantor/Umum', 'jobdesk' => 'Pekerja Unit Usaha Marihat'],
            ['name' => 'wenny', 'kebun' => 'Unit Usaha Marihat', 'bagian' => 'Kantor/Umum', 'jobdesk' => 'Pekerja Unit Usaha Marihat'],
            ['name' => 'Habibilah Hasyim', 'kebun' => 'Unit Usaha Marihat', 'bagian' => 'Kantor/Umum', 'jobdesk' => 'Pekerja Unit Usaha Marihat'],
            ['name' => 'Dea Aulia', 'kebun' => 'Unit Usaha Marihat', 'bagian' => 'Kantor/Umum', 'jobdesk' => 'Pekerja Unit Usaha Marihat'],
            ['name' => 'Yudi Setiawan', 'kebun' => 'Unit Usaha Marihat', 'bagian' => 'Kantor/Umum', 'jobdesk' => 'Pekerja Unit Usaha Marihat'],
            ['name' => 'Ali Usnan Prd', 'kebun' => 'Unit Usaha Marihat', 'bagian' => 'Kantor/Umum', 'jobdesk' => 'Pekerja Unit Usaha Marihat'],
            ['name' => 'Nila Kusuma Lubis', 'kebun' => 'Unit Usaha Marihat', 'bagian' => 'Kantor/Umum', 'jobdesk' => 'Pekerja Unit Usaha Marihat'],
            ['name' => 'Lola Amalia Juninda', 'kebun' => 'Unit Usaha Marihat', 'bagian' => 'Kantor/Umum', 'jobdesk' => 'Pekerja Unit Usaha Marihat'],
            ['name' => 'Syuhdi Abdullah lubis', 'kebun' => 'Unit Usaha Marihat', 'bagian' => 'Kantor/Umum', 'jobdesk' => 'Pekerja Unit Usaha Marihat'],
            ['name' => 'Vivi dwi Santi', 'kebun' => 'Unit Usaha Marihat', 'bagian' => 'Kantor/Umum', 'jobdesk' => 'Pekerja Unit Usaha Marihat'],
            ['name' => 'Yudi Haryadi', 'kebun' => 'Unit Usaha Marihat', 'bagian' => 'Kantor/Umum', 'jobdesk' => 'Pekerja Unit Usaha Marihat'],
            ['name' => 'Amey cahgita saragih', 'kebun' => 'Unit Usaha Marihat', 'bagian' => 'Kantor/Umum', 'jobdesk' => 'Pekerja Unit Usaha Marihat'],
            ['name' => 'Amanda Putri Harahap', 'kebun' => 'Unit Usaha Marihat', 'bagian' => 'Kantor/Umum', 'jobdesk' => 'Pekerja Unit Usaha Marihat'],
            ['name' => 'Fandi Pratama', 'kebun' => 'Unit Usaha Marihat', 'bagian' => 'Kantor/Umum', 'jobdesk' => 'Pekerja Unit Usaha Marihat'],
            ['name' => 'Nuryakum Rangkuti', 'kebun' => 'Unit Usaha Marihat', 'bagian' => 'Kantor/Umum', 'jobdesk' => 'Pekerja Unit Usaha Marihat'],
            ['name' => 'RENNY Marina Aritonang', 'kebun' => 'Unit Usaha Marihat', 'bagian' => 'Kantor/Umum', 'jobdesk' => 'Pekerja Unit Usaha Marihat'],
            ['name' => 'Ella Mawarni', 'kebun' => 'Unit Usaha Marihat', 'bagian' => 'Kantor/Umum', 'jobdesk' => 'Pekerja Unit Usaha Marihat'],
            ['name' => 'Eko susanto', 'kebun' => 'Unit Usaha Marihat', 'bagian' => 'Kantor/Umum', 'jobdesk' => 'Pekerja Unit Usaha Marihat'],
            ['name' => 'irwan', 'kebun' => 'Unit Usaha Marihat', 'bagian' => 'Kantor/Umum', 'jobdesk' => 'Pekerja Unit Usaha Marihat'],
            ['name' => 'Rumaris tindaon', 'kebun' => 'Unit Usaha Marihat', 'bagian' => 'Kantor/Umum', 'jobdesk' => 'Pekerja Unit Usaha Marihat'],
            ['name' => 'Abdi wira prayugo', 'kebun' => 'Unit Usaha Marihat', 'bagian' => 'Kantor/Umum', 'jobdesk' => 'Pekerja Unit Usaha Marihat'],
            ['name' => 'Yeni Rahman', 'kebun' => 'Unit Usaha Marihat', 'bagian' => 'Kantor/Umum', 'jobdesk' => 'Pekerja Unit Usaha Marihat'],
            ['name' => 'fanny Aulia zanna', 'kebun' => 'Unit Usaha Marihat', 'bagian' => 'Kantor/Umum', 'jobdesk' => 'Pekerja Unit Usaha Marihat'],
            ['name' => 'Elivia Kumala', 'kebun' => 'Unit Usaha Marihat', 'bagian' => 'Kantor/Umum', 'jobdesk' => 'Pekerja Unit Usaha Marihat'],
            ['name' => 'Huji bin Maruku', 'kebun' => 'Unit Usaha Marihat', 'bagian' => 'Kantor/Umum', 'jobdesk' => 'Pekerja Unit Usaha Marihat'],
        ];

        // Remove duplicates based on name (keep first occurrence)
        $uniqueUsers = [];
        $seenNames = [];
        foreach ($rawData as $user) {
            $nameKey = strtolower(trim($user['name']));
            if (!isset($seenNames[$nameKey])) {
                $uniqueUsers[] = $user;
                $seenNames[$nameKey] = true;
            }
        }

        // Prepare users array
        $users = [];
        $jabatanPekerjaId = $jabatanIds['Pekerja'] ?? null;
        $jabatanAdminId = $jabatanIds['Admin'] ?? null;
        $jabatanManagerId = $jabatanIds['Manager'] ?? null;
        $shiftPagiId = $shiftIds['Shift Pagi'] ?? $shiftIds->first();
        $departemenKantorId = $departemenIds['Kantor/Umum'] ?? null;

        // Add Admin and Manager accounts
        $kantorLocationId = $locationIds['Kantor'] ?? $locationIds->first();
        
        $users[] = [
                'name' => 'Admin User',
                'email' => 'admin@admin.com',
                'role' => 'admin',
            'position' => 'Admin', // Legacy field
            'department' => 'Kantor/Umum', // Legacy field
            'departemen_id' => $departemenKantorId,
            'jabatan_id' => $jabatanAdminId,
            'location_id' => $kantorLocationId,
                'shift_name' => 'Shift Pagi',
            'phone' => null,
        ];

        $users[] = [
            'name' => 'Manager User',
            'email' => 'manager@sairnapaor.com',
                'role' => 'manager',
            'position' => 'Manager', // Legacy field
            'department' => 'Kantor/Umum', // Legacy field
            'departemen_id' => $departemenKantorId,
            'jabatan_id' => $jabatanManagerId,
            'location_id' => $kantorLocationId,
                'shift_name' => 'Shift Pagi',
            'phone' => null,
        ];

        foreach ($uniqueUsers as $userData) {
            $email = $generateEmail($userData['name']);
            $locationId = $locationIds[$userData['kebun']] ?? null;
            
            // If bagian is empty, use default "Kantor/Umum" for Unit Usaha Marihat
            $bagian = !empty($userData['bagian']) 
                ? $userData['bagian'] 
                : ($userData['kebun'] === 'Unit Usaha Marihat' ? 'Kantor/Umum' : null);
            
            $departemenId = !empty($bagian) 
                ? ($departemenIds[$bagian] ?? null) 
                : null;

            $users[] = [
                'name' => $userData['name'],
                'email' => $email,
                'role' => 'employee',
                'position' => $userData['jobdesk'] ?? 'Pekerja', // Jobdesk spesifik atau default 'Pekerja'
                'department' => $bagian, // Legacy field
                'departemen_id' => $departemenId,
                'jabatan_id' => $jabatanPekerjaId,
                'location_id' => $locationId,
                'shift_name' => 'Shift Pagi',
                'phone' => null,
            ];
        }

        // Create users
        foreach ($users as $userData) {
            $shiftId = $shiftIds->get($userData['shift_name']) ?? $shiftPagiId;

            User::updateOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'password' => Hash::make('password'),
                    'phone' => $userData['phone'],
                    'role' => $userData['role'],
                    'position' => $userData['position'],
                    'department' => $userData['department'],
                    'departemen_id' => $userData['departemen_id'],
                    'jabatan_id' => $userData['jabatan_id'],
                    'shift_kerja_id' => $shiftId,
                    'location_id' => $userData['location_id'],
                ]
            );
        }

        $this->command->info(count($users) . ' users created/updated successfully (1 Admin + 1 Manager + ' . count($uniqueUsers) . ' Employees from client data).');
    }
}
