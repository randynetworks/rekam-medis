<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Data_pasien extends CI_Controller
{

	public function __construct()
	{
		parent::__construct();
		if (!$this->session->userdata('username')) {
			redirect(base_url("auth"));
		}

		$this->load->library('Pdf'); // MEMANGGIL LIBRARY YANG KITA BUAT TADI
		$this->load->model('Pasien_model');
		$this->load->model('m_id');
		$this->load->library('form_validation');
	}


	public function index()
	{
		$judul['judul'] = 'Halaman Data Pasien';
		$data['pasien'] = $this->Pasien_model->getAllPasien()->result();
		$data['kodeunik'] = $this->m_id->buat_kode();
		$kodeunik = $this->m_id->buat_kode();
		$this->load->helper('date');
		$cek = $this->db->query("SELECT tanggal_lahir FROM pasien where kd_rm = '$kodeunik'")->row_array();
		$awal = strtotime($cek);
		$ayena = time();
		$data['umur'] = timespan($awal, $ayena, 1);
		$data['admin'] = $this->db->get_where('admin', ['username' => $this->session->userdata('username')])->row_array();

		$this->load->view('templates/home_header', $judul);
		$this->load->view('templates/home_sidebar', $data);
		$this->load->view('templates/topbar', $data);
		$this->load->view('data_pasien/index', $data);
		$this->load->view('templates/home_footer');
	}


	public function tambah()
	{

		$judul['judul'] = 'Halaman Tambah Data Pasien';
		$data['kodeunik'] = $this->m_id->buat_kode();
		$data['admin'] = $this->db->get_where('admin', ['username' => $this->session->userdata('username')])->row_array();

		$this->form_validation->set_rules('nama_pasien', 'Nama Pasien', 'required');
		$this->form_validation->set_rules('tempat_lahir', 'Tempat Lahir', 'required');
		$this->form_validation->set_rules('jenkel', 'Jenis Kelamin', 'required');
		$this->form_validation->set_rules('tanggal_lahir', 'Tanggal Lahir', 'required');
		$this->form_validation->set_rules('alamat', 'Alamat', 'required');
		$this->form_validation->set_rules('pengobatan', 'Pengobatan', 'required');
		$this->form_validation->set_rules('no_bpjs', 'No BPJS', 'required');
		$this->form_validation->set_rules('telp', 'Nomor HP/Telepon', 'required');


		if ($this->form_validation->run() == FALSE) {
			$this->load->view('templates/home_header', $judul);
			$this->load->view('templates/home_sidebar', $data);
			$this->load->view('templates/topbar', $data);
			$this->load->view('data_pasien/input', $data);
			$this->load->view('templates/home_footer');
		} else {
			$this->Pasien_model->tambah_data();
			redirect('data_pasien/index');
		}
	}

	public function hapus($kd_rm)
	{
		$this->Pasien_model->hapus_data($kd_rm);
		redirect('data_pasien/index');
	}


	public function ubah($kd_rm)
	{

		$judul['judul'] = 'Halaman Ubah Data Pasien';
		$data['pasien'] = $this->Pasien_model->getPasienById($kd_rm);
		$data['admin'] = $this->db->get_where('admin', ['username' => $this->session->userdata('username')])->row_array();


		$this->form_validation->set_rules('nama_pasien', 'Nama Pasien', 'required');
		$this->form_validation->set_rules('tempat_lahir', 'Tempat Lahir', 'required');
		$this->form_validation->set_rules('tanggal_lahir', 'Tanggal Lahir', 'required');
		$this->form_validation->set_rules('alamat', 'Alamat', 'required');
		$this->form_validation->set_rules('telp', 'Nomor HP/Telepon', 'required');


		if ($this->form_validation->run() == FALSE) {
			$this->load->view('templates/home_header', $judul);
			$this->load->view('templates/home_sidebar', $data);
			$this->load->view('templates/topbar', $data);
			$this->load->view('data_pasien/ubah', $data);
			$this->load->view('templates/home_footer');
		} else {
			$this->Pasien_model->ubah_data();
			redirect('data_pasien/index');
		}
	}



	/*LAPORAN TRANSAKSI*/

	function laporan()
	{

		if (isset($_GET['filter']) && !empty($_GET['filter'])) {

			$filter = $_GET['filter'];


			if ($filter == '1') {
				$kd_rm = $_GET['kd_rm'];
				$ket = 'Data Pasien ';
				$url_cetak = 'data_pasien/cetak2?&kd_rm=' . $kd_rm;
				$pasien = $this->Pasien_model->view_by_kd_rm($kd_rm);
			}
		} else {

			$ket = 'Semua Data Pasien';
			$url_cetak = 'data_pasien/cetak';
			$pasien = $this->Pasien_model->view_all();
		}

		$data['ket'] = $ket;
		$data['url_cetak'] = base_url($url_cetak);
		$data['pasien'] = $pasien;
		$data['kd_rm'] = $this->Pasien_model->kd_rm();



		$data['judul'] = 'Laporan Pasien';
		$data['admin'] = $this->db->get_where('admin', ['username' => $this->session->userdata('username')])->row_array();

		$this->load->view('templates/home_header', $data);
		$this->load->view('templates/home_sidebar', $data);
		$this->load->view('templates/topbar', $data);
		$this->load->view('data_pasien/laporan', $data);
		$this->load->view('templates/home_footer');
	}
	public function pdfExport($alamat, $ket, $pasien)
	{
		$pdf = new FPDF("L", "cm", "Legal");

		$pdf->SetMargins(2, 1, 1);
		$pdf->AliasNbPages();
		$pdf->AddPage();
		$pdf->SetFont('Times', '', 12);
		// $pdf->Image('assets/img/aplikasi/logo.png',2.5,0.5,3,2.5);
		$pdf->SetX(8);
		$pdf->MultiCell(19.5, 0.7, "DOKTER PRAKTIK PERORANGAN", 0, 'C');
		$pdf->SetFont('Times', 'B', 14);
		$pdf->SetX(8);
		$pdf->MultiCell(19.5, 0.7, "DOKTER CECEP ISMAWAN", 0, 'C');
		$pdf->SetFont('Times', '', 12);
		$pdf->SetX(8);
		$pdf->MultiCell(19.5, 0.7, '' . $alamat . '', 0, 'C');
		$pdf->Line(2, 3.1, 31, 3.1);
		$pdf->SetLineWidth(0.1);
		$pdf->Line(2, 3.2, 31, 3.2);
		$pdf->SetLineWidth(0);
		$pdf->ln(1);
		$pdf->SetFont('Arial', 'B', 11);
		$pdf->MultiCell(31, 0.7, "DATA PASIEN", 0, 'C');
		$pdf->SetFont('Arial', '', 10);
		$pdf->MultiCell(31, 0.7, '' . $ket . '', 0, 'C');
		$pdf->SetFont('Arial', '', 10);
		$pdf->Cell(5, 0.6, "Di cetak pada : " . date("d/m/Y"), 0, 0, 'C');
		$pdf->ln(1);

		$pdf->SetFont('Arial', 'B', 10);
		$pdf->Cell(1, 0.8, 'NO', 1, 0, 'C');
		$pdf->Cell(3.5, 0.8, 'KODE RM', 1, 0, 'C');
		$pdf->Cell(5, 0.8, 'NAMA', 1, 0, 'C');
		$pdf->Cell(4, 0.8, 'JENIS KELAMIN', 1, 0, 'C');
		$pdf->Cell(4, 0.8, 'TAMPAT LAHIR', 1, 0, 'C');
		$pdf->Cell(4, 0.8, 'TANGGAL LAHIR', 1, 0, 'C');
		$pdf->Cell(6, 0.8, 'ALAMAT', 1, 0, 'C');
		$pdf->ln();

		if (!empty($pasien)) {
			$no = 1;
			foreach ($pasien as $data) {
				$pdf->SetFont('Arial', '', 10);
				$pdf->Cell(1, 0.6, $no++, 1, 0, 'C');
				$pdf->Cell(3.5, 0.6, $data['kd_rm'], 1, 0, 'C');
				$pdf->Cell(5, 0.6, $data['nama_pasien'], 1, 0, 'L');
				$pdf->Cell(4, 0.6, $data['jenkel'], 1, 0, 'L');
				$pdf->Cell(4, 0.6, $data['tempat_lahir'], 1, 0, 'C');
				$pdf->Cell(4, 0.6, $data['tanggal_lahir'], 1, 0, 'C');
				$pdf->Cell(6, 0.6, $data['alamat'], 1, 0, 'L');
				$pdf->ln();
			}
		}

		$pdf->Output("Laporan Semua Data Pasien.pdf", "I");
	}

	public function cetak()
	{

		$ket = 'Semua Data Pasien';
		$alamat = 'Kp. Cibereum No.18 RT/RW 04/01 Tanjungjaya';

		$pasien = $this->Pasien_model->view_all();
		$this->pdfExport($alamat, $ket, $pasien);
	}

	public function cetak1()
	{

		$ket = 'Data Pasien';
		$alamat = 'Kp. Cibereum No.18 RT/RW 04/01 Tanjungjaya';

		$pasien = $this->Pasien_model->view_by_kd_rm();
		$this->pdfExport($alamat, $ket, $pasien);
	}
}
