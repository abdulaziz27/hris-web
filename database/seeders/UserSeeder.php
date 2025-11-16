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
            ['name' => 'Afsul Gani', 'kebun' => 'Kalianta', 'bagian' => 'Pembibitan'],
            ['name' => 'Vredi Putra', 'kebun' => 'Kalianta', 'bagian' => 'Kantor/Umum'],
            ['name' => 'Ibnu Yazid', 'kebun' => 'Kalianta', 'bagian' => 'Kantor/Umum'],
            ['name' => 'Diki Kurniawan', 'kebun' => 'Kalianta', 'bagian' => 'Kantor/Umum'],
            ['name' => 'Rahman', 'kebun' => 'Kalianta', 'bagian' => 'Pembibitan'],
            ['name' => 'Muliadi Nasution', 'kebun' => 'Kalianta', 'bagian' => 'Kantor/Umum'],
            ['name' => 'Martini', 'kebun' => 'Kalianta', 'bagian' => 'Kantor/Umum'],
            ['name' => 'Gofar Alpajri', 'kebun' => 'Kalianta', 'bagian' => 'Pembibitan'],
            ['name' => 'Ardi', 'kebun' => 'Kalianta', 'bagian' => 'Pembibitan'],
            ['name' => 'Chandra', 'kebun' => 'Kalianta', 'bagian' => 'Kantor/Umum'],
            ['name' => 'Ryan Egi Arnanda', 'kebun' => 'Kalianta', 'bagian' => 'Pembibitan'],
            ['name' => 'Aldi Nurmanto', 'kebun' => 'Kalianta', 'bagian' => 'Pembibitan'],
            ['name' => 'Zainal Abidin', 'kebun' => 'Kalianta', 'bagian' => 'Pembibitan'],
            ['name' => 'Amal Bakti', 'kebun' => 'Kalianta', 'bagian' => 'Pembibitan'],
            ['name' => 'Mareston Purba', 'kebun' => 'Kalianta', 'bagian' => 'Pembibitan'],
            ['name' => 'Swiono Hutagalung', 'kebun' => 'Kalianta', 'bagian' => 'Pembibitan'],
            ['name' => 'Mariman Sibarani', 'kebun' => 'Kalianta', 'bagian' => 'Pembibitan'],
            ['name' => 'Sukisman', 'kebun' => 'Kalianta', 'bagian' => 'Pembibitan'],
            ['name' => 'Fikri Wahyudi', 'kebun' => 'Kalianta', 'bagian' => 'Pembibitan'],
            ['name' => 'Wahyu Adha', 'kebun' => 'Kalianta', 'bagian' => 'Pembibitan'],
            ['name' => 'Doni Angga Lasmana', 'kebun' => 'Kalianta', 'bagian' => 'Pembibitan'],
            ['name' => 'Alpin Muliandri', 'kebun' => 'Kalianta', 'bagian' => 'Pembibitan'],
            ['name' => 'Ilham Sumardi Sitorus', 'kebun' => 'Kalianta', 'bagian' => 'Pembibitan'],
            ['name' => 'Eka Purwana', 'kebun' => 'Kalianta', 'bagian' => 'Pembibitan'],
            ['name' => 'Vina Lisa Eka Devi', 'kebun' => 'Kalianta', 'bagian' => 'Kantor/Umum'],
            ['name' => 'Endrayanti Sibarani', 'kebun' => 'Kalianta', 'bagian' => 'Kantor/Umum'],
            ['name' => 'Tuti', 'kebun' => 'Kalianta', 'bagian' => 'Tanaman/TBS'],
            ['name' => 'Ariani Purnama', 'kebun' => 'Kalianta', 'bagian' => 'Kantor/Umum'],
            ['name' => 'Fitri Nurul Hidayanti', 'kebun' => 'Kalianta', 'bagian' => 'Kantor/Umum'],
            ['name' => 'Nasya Iwinscy Hutabarat', 'kebun' => 'Kalianta', 'bagian' => 'Tanaman/TBS'],
            ['name' => 'Saparudin', 'kebun' => 'Kalianta', 'bagian' => 'Kantor/Umum'],
            
            // Dalu-Dalu
            ['name' => 'Pariyan', 'kebun' => 'Dalu-Dalu', 'bagian' => 'Tanaman/TBS'],
            ['name' => 'Puja Pratiwi', 'kebun' => 'Dalu-Dalu', 'bagian' => 'Kantor/Umum'],
            ['name' => 'Maharani', 'kebun' => 'Dalu-Dalu', 'bagian' => 'Kantor/Umum'],
            ['name' => 'Ayu Mentari', 'kebun' => 'Dalu-Dalu', 'bagian' => 'Kantor/Umum'],
            ['name' => 'Wardani', 'kebun' => 'Dalu-Dalu', 'bagian' => 'Tanaman/TBS'],
            ['name' => 'Dewi Ratna Sari', 'kebun' => 'Dalu-Dalu', 'bagian' => 'Tanaman/TBS'],
            ['name' => 'M.uhammad Arif Widodo', 'kebun' => 'Dalu-Dalu', 'bagian' => 'Tanaman/TBS'],
            ['name' => 'Reno Aldi Tampubolon', 'kebun' => 'Dalu-Dalu', 'bagian' => 'Kantor/Umum'],
            ['name' => 'Dermawan', 'kebun' => 'Dalu-Dalu', 'bagian' => 'Kantor/Umum'],
            ['name' => 'Anggi Anugrah Lubis', 'kebun' => 'Dalu-Dalu', 'bagian' => 'Kantor/Umum'],
            ['name' => 'Dian Faniansyah Hsb', 'kebun' => 'Dalu-Dalu', 'bagian' => 'Kantor/Umum'],
            ['name' => 'Erlansius Nababan', 'kebun' => 'Dalu-Dalu', 'bagian' => 'Kantor/Umum'],
            ['name' => 'Syalom Dileando Harefa', 'kebun' => 'Dalu-Dalu', 'bagian' => 'Kantor/Umum'],
            ['name' => 'Al Reza Mahendra', 'kebun' => 'Dalu-Dalu', 'bagian' => 'Kantor/Umum'],
            ['name' => 'Jefrizal', 'kebun' => 'Dalu-Dalu', 'bagian' => 'SUS BHT'],
            ['name' => 'Agus Irawan', 'kebun' => 'Dalu-Dalu', 'bagian' => 'SUS BHT'],
            ['name' => 'Kelvin Dion S', 'kebun' => 'Dalu-Dalu', 'bagian' => 'SUS BHT'],
            ['name' => 'Fazar Aditya', 'kebun' => 'Dalu-Dalu', 'bagian' => 'SUS BHT'],
            ['name' => 'Deri Rahmadani', 'kebun' => 'Dalu-Dalu', 'bagian' => 'SUS BHT'],
            ['name' => 'Sri Lestari', 'kebun' => 'Dalu-Dalu', 'bagian' => 'Kantor/Umum'],
            ['name' => 'Zamiah', 'kebun' => 'Dalu-Dalu', 'bagian' => 'Kantor/Umum'],
            ['name' => 'Iswatun Hasanah', 'kebun' => 'Dalu-Dalu', 'bagian' => 'Kantor/Umum'],
            
            // P.Mandrsah
            ['name' => 'MHD RIZKY DUHARI', 'kebun' => 'P.Mandrsah', 'bagian' => 'Tanaman/TBS'],
            ['name' => 'ADJI SWARMADIKA', 'kebun' => 'P.Mandrsah', 'bagian' => 'Kantor/Umum'],
            ['name' => 'AHMAD UJA SIAGIAN', 'kebun' => 'P.Mandrsah', 'bagian' => 'Tanaman/TBS'],
            ['name' => 'SAPARUDDIN HASIBUAN', 'kebun' => 'P.Mandrsah', 'bagian' => 'Kantor/Umum'],
            ['name' => 'SRI ANJARWATI', 'kebun' => 'P.Mandrsah', 'bagian' => 'Kantor/Umum'],
            ['name' => 'MOCH FAJAR SETIAWAN JS', 'kebun' => 'P.Mandrsah', 'bagian' => 'Kantor/Umum'],
            ['name' => 'SARMAN HASIBUAN', 'kebun' => 'P.Mandrsah', 'bagian' => 'Kantor/Umum'],
            ['name' => 'ALPIAN', 'kebun' => 'P.Mandrsah', 'bagian' => 'Tanaman/TBS'],
            ['name' => 'SOFIAN HASIBUAN', 'kebun' => 'P.Mandrsah', 'bagian' => 'Pembibitan'],
            ['name' => 'MUHAMMAD RIFAI', 'kebun' => 'P.Mandrsah', 'bagian' => 'Pembibitan'],
            ['name' => 'RUDY SINAGA', 'kebun' => 'P.Mandrsah', 'bagian' => 'Pembibitan'],
            ['name' => 'ROMA HARTO', 'kebun' => 'P.Mandrsah', 'bagian' => 'Pembibitan'],
            ['name' => 'ARDIYANSYAH SIREGAR', 'kebun' => 'P.Mandrsah', 'bagian' => 'Pembibitan'],
            ['name' => 'RAJA JUNJUNGAN PULUNGAN', 'kebun' => 'P.Mandrsah', 'bagian' => 'Tanaman/TBS'],
            
            // Sarolangun
            ['name' => 'Kaspul Anwar', 'kebun' => 'Sarolangun', 'bagian' => 'Kantor/Umum'],
            ['name' => 'Muslimin', 'kebun' => 'Sarolangun', 'bagian' => 'Kantor/Umum'],
            ['name' => 'Al muhsi', 'kebun' => 'Sarolangun', 'bagian' => 'Kantor/Umum'],
            ['name' => 'M. Arsyad', 'kebun' => 'Sarolangun', 'bagian' => 'Kantor/Umum'],
            ['name' => 'Jamsuri', 'kebun' => 'Sarolangun', 'bagian' => 'Kantor/Umum'],
            ['name' => 'A. Salek', 'kebun' => 'Sarolangun', 'bagian' => 'Kantor/Umum'],
            ['name' => 'Jalaluddin', 'kebun' => 'Sarolangun', 'bagian' => 'Kantor/Umum'],
            ['name' => 'Muhhammad Arfan', 'kebun' => 'Sarolangun', 'bagian' => 'Kantor/Umum'],
            ['name' => 'Syamsul Arifin', 'kebun' => 'Sarolangun', 'bagian' => 'Kantor/Umum'],
            ['name' => 'Muhammad Nur', 'kebun' => 'Sarolangun', 'bagian' => 'Kantor/Umum'],
            ['name' => 'Rais Mu\'allimin', 'kebun' => 'Sarolangun', 'bagian' => 'Kantor/Umum'],
            ['name' => 'Tia Juarni', 'kebun' => 'Sarolangun', 'bagian' => 'Kantor/Umum'],
            ['name' => 'Teguh Iman Sobandingon', 'kebun' => 'Sarolangun', 'bagian' => 'Kantor/Umum'],
            ['name' => 'Muhammad Izzadsyah', 'kebun' => 'Sarolangun', 'bagian' => 'Kantor/Umum'],
            ['name' => 'Riky', 'kebun' => 'Sarolangun', 'bagian' => 'Kantor/Umum'],
            ['name' => 'Muhammad Usman', 'kebun' => 'Sarolangun', 'bagian' => 'Kantor/Umum'],
            ['name' => 'Irvan Fadli', 'kebun' => 'Sarolangun', 'bagian' => 'Kantor/Umum'],
            ['name' => 'Anwar', 'kebun' => 'Sarolangun', 'bagian' => 'Pembibitan'],
            ['name' => 'Ahmad Sobirin', 'kebun' => 'Sarolangun', 'bagian' => 'Pembibitan'],
            ['name' => 'Azwen Ade Saputra', 'kebun' => 'Sarolangun', 'bagian' => 'Pembibitan'],
            ['name' => 'Asuro', 'kebun' => 'Sarolangun', 'bagian' => 'Pembibitan'],
            ['name' => 'Fahruddin', 'kebun' => 'Sarolangun', 'bagian' => 'Pembibitan'],
            ['name' => 'Husnul Hotimah', 'kebun' => 'Sarolangun', 'bagian' => 'Pembibitan'],
            ['name' => 'Huzaimah', 'kebun' => 'Sarolangun', 'bagian' => 'Pembibitan'],
            ['name' => 'Marsidah', 'kebun' => 'Sarolangun', 'bagian' => 'Pembibitan'],
            ['name' => 'Morhasiah', 'kebun' => 'Sarolangun', 'bagian' => 'Pembibitan'],
            ['name' => 'Mhd. Irwansyah/Alfarisi', 'kebun' => 'Sarolangun', 'bagian' => 'Pembibitan'],
            ['name' => 'Murni', 'kebun' => 'Sarolangun', 'bagian' => 'Pembibitan'],
            ['name' => 'Mardiah', 'kebun' => 'Sarolangun', 'bagian' => 'Pembibitan'],
            ['name' => 'Nurasiah', 'kebun' => 'Sarolangun', 'bagian' => 'Pembibitan'],
            ['name' => 'Nurlis Hasanah', 'kebun' => 'Sarolangun', 'bagian' => 'Pembibitan'],
            ['name' => 'Raudah', 'kebun' => 'Sarolangun', 'bagian' => 'Pembibitan'],
            ['name' => 'Rita Umami', 'kebun' => 'Sarolangun', 'bagian' => 'Pembibitan'],
            ['name' => 'Siti Hajir', 'kebun' => 'Sarolangun', 'bagian' => 'Pembibitan'],
            ['name' => 'Siti Asiah', 'kebun' => 'Sarolangun', 'bagian' => 'Pembibitan'],
            ['name' => 'Siti Rafiah/Yuli Ardianti', 'kebun' => 'Sarolangun', 'bagian' => 'Pembibitan'],
            ['name' => 'Siti Rugayah', 'kebun' => 'Sarolangun', 'bagian' => 'Pembibitan'],
            ['name' => 'Siti Faridah', 'kebun' => 'Sarolangun', 'bagian' => 'Pembibitan'],
            ['name' => 'Siti Zahara', 'kebun' => 'Sarolangun', 'bagian' => 'Pembibitan'],
            ['name' => 'Umi Kalsum', 'kebun' => 'Sarolangun', 'bagian' => 'Pembibitan'],
            ['name' => 'Zuhra', 'kebun' => 'Sarolangun', 'bagian' => 'Pembibitan'],
            ['name' => 'Muhammad Syarif', 'kebun' => 'Sarolangun', 'bagian' => 'Pembibitan'],
            ['name' => 'Syukur', 'kebun' => 'Sarolangun', 'bagian' => 'Pembibitan'],
            ['name' => 'Andreas Simbolon', 'kebun' => 'Sarolangun', 'bagian' => 'Pembibitan'],
            ['name' => 'Fitria', 'kebun' => 'Sarolangun', 'bagian' => 'Pembibitan'],
            ['name' => 'Kurnia Diazta Damanik', 'kebun' => 'Sarolangun', 'bagian' => 'Pembibitan'],
            ['name' => 'Koni', 'kebun' => 'Sarolangun', 'bagian' => 'Pembibitan'],
            ['name' => 'Manogar Siringo-ringo', 'kebun' => 'Sarolangun', 'bagian' => 'Pembibitan'],
            ['name' => 'Rip\'at', 'kebun' => 'Sarolangun', 'bagian' => 'Pembibitan'],
            ['name' => 'Almuttakin', 'kebun' => 'Sarolangun', 'bagian' => 'Pembibitan'],
            ['name' => 'Apriko', 'kebun' => 'Sarolangun', 'bagian' => 'Pembibitan'],
            ['name' => 'Muhammad Faisal', 'kebun' => 'Sarolangun', 'bagian' => 'Pembibitan'],
            ['name' => 'Ibrahim', 'kebun' => 'Sarolangun', 'bagian' => 'Tanaman/TBS'],
            ['name' => 'Rasidun', 'kebun' => 'Sarolangun', 'bagian' => 'Tanaman/TBS'],
            ['name' => 'Sudirman', 'kebun' => 'Sarolangun', 'bagian' => 'Tanaman/TBS'],
            ['name' => 'Abu Bakar', 'kebun' => 'Sarolangun', 'bagian' => 'Tanaman/TBS'],
            ['name' => 'Chairil Hidayat', 'kebun' => 'Sarolangun', 'bagian' => 'Tanaman/TBS'],
            ['name' => 'Muhammad HL', 'kebun' => 'Sarolangun', 'bagian' => 'Tanaman/TBS'],
            ['name' => 'M. Akher', 'kebun' => 'Sarolangun', 'bagian' => 'Tanaman/TBS'],
            ['name' => 'Amad Tarmuzi', 'kebun' => 'Sarolangun', 'bagian' => 'Tanaman/TBS'],
            ['name' => 'Junaidi', 'kebun' => 'Sarolangun', 'bagian' => 'Tanaman/TBS'],
            ['name' => 'Edyi Sofyan', 'kebun' => 'Sarolangun', 'bagian' => 'Tanaman/TBS'],
            ['name' => 'Alfatah Alimin. R / Muhammad Zul Ahmi', 'kebun' => 'Sarolangun', 'bagian' => 'Tanaman/TBS'],
            ['name' => 'Nur Asiah', 'kebun' => 'Sarolangun', 'bagian' => 'Tanaman/TBS'],
            ['name' => 'Nur Hasanah / Ismail Fahmi', 'kebun' => 'Sarolangun', 'bagian' => 'Tanaman/TBS'],
            ['name' => 'Umi Latipah', 'kebun' => 'Sarolangun', 'bagian' => 'Tanaman/TBS'],
            ['name' => 'Solma', 'kebun' => 'Sarolangun', 'bagian' => 'Tanaman/TBS'],
            ['name' => 'Asnaini/Dodi Arpiandi', 'kebun' => 'Sarolangun', 'bagian' => 'Tanaman/TBS'],
            ['name' => 'Nur Ainun/Abd. Malik', 'kebun' => 'Sarolangun', 'bagian' => 'Tanaman/TBS'],
            ['name' => 'Siti Hamidah', 'kebun' => 'Sarolangun', 'bagian' => 'Tanaman/TBS'],
            ['name' => 'Tamami', 'kebun' => 'Sarolangun', 'bagian' => 'Tanaman/TBS'],
            ['name' => 'M. Anriansah/Assolihin', 'kebun' => 'Sarolangun', 'bagian' => 'Tanaman/TBS'],
            ['name' => 'Komarudin', 'kebun' => 'Sarolangun', 'bagian' => 'Tanaman/TBS'],
            ['name' => 'Hidayatul Mustofik', 'kebun' => 'Sarolangun', 'bagian' => 'Tanaman/TBS'],
            ['name' => 'Zulfikar', 'kebun' => 'Sarolangun', 'bagian' => 'Tanaman/TBS'],
            ['name' => 'Hednri Wijaya', 'kebun' => 'Sarolangun', 'bagian' => 'Tanaman/TBS'],
            ['name' => 'Muhlisin Nalahuddin', 'kebun' => 'Sarolangun', 'bagian' => 'Tanaman/TBS'],
            ['name' => 'Nurul Hazmi Saputra', 'kebun' => 'Sarolangun', 'bagian' => 'Tanaman/TBS'],
            ['name' => 'Uzaipah', 'kebun' => 'Sarolangun', 'bagian' => 'Tanaman/TBS'],
            ['name' => 'Ahmad Husein/Huznul Izzati', 'kebun' => 'Sarolangun', 'bagian' => 'Tanaman/TBS'],
            ['name' => 'Syarif Hidayat', 'kebun' => 'Sarolangun', 'bagian' => 'Tanaman/TBS'],
            ['name' => 'Hasan Basari', 'kebun' => 'Sarolangun', 'bagian' => 'Tanaman/TBS'],
            ['name' => 'Najmi', 'kebun' => 'Sarolangun', 'bagian' => 'Tanaman/TBS'],
            ['name' => 'Yusmiati', 'kebun' => 'Sarolangun', 'bagian' => 'Tanaman/TBS'],
            ['name' => 'Idham Kholid', 'kebun' => 'Sarolangun', 'bagian' => 'Tanaman/TBS'],
            ['name' => 'Dodo Sanghai', 'kebun' => 'Sarolangun', 'bagian' => 'Tanaman/TBS'],
            ['name' => 'Muhammad Naim', 'kebun' => 'Sarolangun', 'bagian' => 'Tanaman/TBS'],
            
            // T.Dalam/P.Maria
            ['name' => 'Rinto M Siregar', 'kebun' => 'T.Dalam/P.Maria', 'bagian' => 'Tanaman/TBS'],
            ['name' => 'Sofian Marpaung', 'kebun' => 'T.Dalam/P.Maria', 'bagian' => 'Pembibitan'],
            ['name' => 'Munaji', 'kebun' => 'T.Dalam/P.Maria', 'bagian' => 'Pembibitan'],
            ['name' => 'Sutan Batang Onang Harahap', 'kebun' => 'T.Dalam/P.Maria', 'bagian' => 'Kantor/Umum'],
            ['name' => 'Satria Putra Irawan Siregar', 'kebun' => 'T.Dalam/P.Maria', 'bagian' => 'Kelapa Kopyor'],
            ['name' => 'Lokot Harahap', 'kebun' => 'T.Dalam/P.Maria', 'bagian' => 'Kelapa Kopyor'],
            ['name' => 'Indra Kesuma', 'kebun' => 'T.Dalam/P.Maria', 'bagian' => 'Pembibitan'],
            ['name' => 'Rikson Sihombing', 'kebun' => 'T.Dalam/P.Maria', 'bagian' => 'Tanaman/TBS'],
            ['name' => 'Ebennejer sihombing Lumbantoruan', 'kebun' => 'T.Dalam/P.Maria', 'bagian' => 'Pembibitan'],
            ['name' => 'Muhammad Taufiq Marpaung', 'kebun' => 'T.Dalam/P.Maria', 'bagian' => 'Pembibitan'],
            ['name' => 'Budiman', 'kebun' => 'T.Dalam/P.Maria', 'bagian' => 'Tanaman/TBS'],
            
            // Simirik/Pargarutan
            ['name' => 'Eggy Syaputra Damanik', 'kebun' => 'Simirik/Pargarutan', 'bagian' => 'Pembibitan'],
            ['name' => 'M.Yani', 'kebun' => 'Simirik/Pargarutan', 'bagian' => 'Pembibitan'],
            ['name' => 'Riswanti', 'kebun' => 'Simirik/Pargarutan', 'bagian' => 'Pembibitan'],
            ['name' => 'Wagiati', 'kebun' => 'Simirik/Pargarutan', 'bagian' => 'Pembibitan'],
            ['name' => 'Legiman', 'kebun' => 'Simirik/Pargarutan', 'bagian' => 'Pembibitan'],
            ['name' => 'Wawan Ariadi', 'kebun' => 'Simirik/Pargarutan', 'bagian' => 'Pembibitan'],
            ['name' => 'Muhammad Asian', 'kebun' => 'Simirik/Pargarutan', 'bagian' => 'Pembibitan'],
            ['name' => 'Sarianto', 'kebun' => 'Simirik/Pargarutan', 'bagian' => 'Pembibitan'],
            ['name' => 'Riki Hamdani', 'kebun' => 'Simirik/Pargarutan', 'bagian' => 'Pembibitan'],
            ['name' => 'Suhendra', 'kebun' => 'Simirik/Pargarutan', 'bagian' => 'Pembibitan'],
            ['name' => 'Ayu Lestari', 'kebun' => 'Simirik/Pargarutan', 'bagian' => 'Pembibitan'],
            
            // Unit Usaha Marihat
            ['name' => 'Ahmad Amin', 'kebun' => 'Unit Usaha Marihat', 'bagian' => ''],
            ['name' => 'Angga Deo Firmansyah', 'kebun' => 'Unit Usaha Marihat', 'bagian' => ''],
            ['name' => 'Bayu Dwi Syam Azhari', 'kebun' => 'Unit Usaha Marihat', 'bagian' => ''],
            ['name' => 'Dico Rahdamsyah', 'kebun' => 'Unit Usaha Marihat', 'bagian' => ''],
            ['name' => 'Dodi Kusuma', 'kebun' => 'Unit Usaha Marihat', 'bagian' => ''],
            ['name' => 'Harry Sutrisno', 'kebun' => 'Unit Usaha Marihat', 'bagian' => ''],
            ['name' => 'Muhammad Rizal Harahap', 'kebun' => 'Unit Usaha Marihat', 'bagian' => ''],
            ['name' => 'Norman', 'kebun' => 'Unit Usaha Marihat', 'bagian' => ''],
            ['name' => 'Poniran', 'kebun' => 'Unit Usaha Marihat', 'bagian' => ''],
            ['name' => 'Samiran', 'kebun' => 'Unit Usaha Marihat', 'bagian' => ''],
            ['name' => 'Tukiman', 'kebun' => 'Unit Usaha Marihat', 'bagian' => ''],
            ['name' => 'Kiki sundari', 'kebun' => 'Unit Usaha Marihat', 'bagian' => ''],
            ['name' => 'Sukarseh', 'kebun' => 'Unit Usaha Marihat', 'bagian' => ''],
            ['name' => 'Ahmad Fauzi Purba', 'kebun' => 'Unit Usaha Marihat', 'bagian' => ''],
            ['name' => 'Al Mukhlisan', 'kebun' => 'Unit Usaha Marihat', 'bagian' => ''],
            ['name' => 'Diki Ferdiansyah', 'kebun' => 'Unit Usaha Marihat', 'bagian' => ''],
            ['name' => 'Hizkia Saib Kirfalani batu bara', 'kebun' => 'Unit Usaha Marihat', 'bagian' => ''],
            ['name' => 'Lina Rismayani', 'kebun' => 'Unit Usaha Marihat', 'bagian' => ''],
            ['name' => 'Meliana Sari Siregar', 'kebun' => 'Unit Usaha Marihat', 'bagian' => ''],
            ['name' => 'arijal fadlan nst', 'kebun' => 'Unit Usaha Marihat', 'bagian' => ''],
            ['name' => 'Tika Febrianti', 'kebun' => 'Unit Usaha Marihat', 'bagian' => ''],
            ['name' => 'Viqi Fahrendi', 'kebun' => 'Unit Usaha Marihat', 'bagian' => ''],
            ['name' => 'Yopi Nazham', 'kebun' => 'Unit Usaha Marihat', 'bagian' => ''],
            ['name' => 'Yusliana', 'kebun' => 'Unit Usaha Marihat', 'bagian' => ''],
            ['name' => 'wenny', 'kebun' => 'Unit Usaha Marihat', 'bagian' => ''],
            ['name' => 'Habibilah Hasyim', 'kebun' => 'Unit Usaha Marihat', 'bagian' => ''],
            ['name' => 'Dea Aulia', 'kebun' => 'Unit Usaha Marihat', 'bagian' => ''],
            ['name' => 'Yudi Setiawan', 'kebun' => 'Unit Usaha Marihat', 'bagian' => ''],
            ['name' => 'Ali Usnan Prd', 'kebun' => 'Unit Usaha Marihat', 'bagian' => ''],
            ['name' => 'Nila Kusuma Lubis', 'kebun' => 'Unit Usaha Marihat', 'bagian' => ''],
            ['name' => 'Lola Amalia Juninda', 'kebun' => 'Unit Usaha Marihat', 'bagian' => ''],
            ['name' => 'Syuhdi Abdullah lubis', 'kebun' => 'Unit Usaha Marihat', 'bagian' => ''],
            ['name' => 'Vivi dwi Santi', 'kebun' => 'Unit Usaha Marihat', 'bagian' => ''],
            ['name' => 'Yudi Haryadi', 'kebun' => 'Unit Usaha Marihat', 'bagian' => ''],
            ['name' => 'Amey cahgita saragih', 'kebun' => 'Unit Usaha Marihat', 'bagian' => ''],
            ['name' => 'Amanda Putri Harahap', 'kebun' => 'Unit Usaha Marihat', 'bagian' => ''],
            ['name' => 'Fandi Pratama', 'kebun' => 'Unit Usaha Marihat', 'bagian' => ''],
            ['name' => 'Nuryakum Rangkuti', 'kebun' => 'Unit Usaha Marihat', 'bagian' => ''],
            ['name' => 'RENNY Marina Aritonang', 'kebun' => 'Unit Usaha Marihat', 'bagian' => ''],
            ['name' => 'Ella Mawarni', 'kebun' => 'Unit Usaha Marihat', 'bagian' => ''],
            ['name' => 'Eko susanto', 'kebun' => 'Unit Usaha Marihat', 'bagian' => ''],
            ['name' => 'irwan', 'kebun' => 'Unit Usaha Marihat', 'bagian' => ''],
            ['name' => 'Rumaris tindaon', 'kebun' => 'Unit Usaha Marihat', 'bagian' => ''],
            ['name' => 'Abdi wira prayugo', 'kebun' => 'Unit Usaha Marihat', 'bagian' => ''],
            ['name' => 'Yeni Rahman', 'kebun' => 'Unit Usaha Marihat', 'bagian' => ''],
            ['name' => 'fanny Aulia zanna', 'kebun' => 'Unit Usaha Marihat', 'bagian' => ''],
            ['name' => 'Elivia Kumala', 'kebun' => 'Unit Usaha Marihat', 'bagian' => ''],
            ['name' => 'Huji bin Maruku', 'kebun' => 'Unit Usaha Marihat', 'bagian' => ''],
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
            $departemenId = !empty($userData['bagian']) 
                ? ($departemenIds[$userData['bagian']] ?? null) 
                : null;

            $users[] = [
                'name' => $userData['name'],
                'email' => $email,
                'role' => 'employee',
                'position' => 'Pekerja', // Legacy field
                'department' => $userData['bagian'] ?: null, // Legacy field
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
