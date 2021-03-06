<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Pemeriksaan extends CI_Controller
{

	public function __construct()
	{
		parent::__construct();
		if (!$this->session->userdata('username')) {
			redirect(base_url("auth"));
		}

		$this->load->model('Pasien_model');
		$this->load->model('Pemeriksaan_model');
		$this->load->model('Pembayaran_model');
		$this->load->model('Karyawan_model');
		$this->load->model('Obat_model');
		$this->load->model('m_id');
		$this->load->library('form_validation');
		$this->load->library('Pdf'); // MEMANGGIL LIBRARY YANG KITA BUAT TADI
		$this->load->helper('url');
	}


	public function index()
	{
		$judul['judul'] = 'Halaman Pemeriksaan';
		$data['dokter'] = $this->db->get_where('dokter', ['username' => $this->session->userdata('username')])->row_array();
		$data['pasien'] = $this->Pasien_model->getAllPasien()->result();
		$this->load->helper('date');


		$this->load->view('templates/home_header', $judul);
		$this->load->view('templates/home_sidebar', $data);
		$this->load->view('templates/home_topbar', $data);
		$this->load->view('pemeriksaan/index', $data);
		$this->load->view('templates/home_footer');
	}


	public function periksa($kd_rm)
	{

		$judul['judul'] = 'Pemeriksaan';
		$data['desc'] = 'Tambah Pemeriksaan';
		$data['kodeperiksa'] = $this->m_id->buat_kode_periksa();
		$data['tanggal'] = date("d-m-Y");
		$data['dokter'] = $this->db->get_where('dokter', ['username' => $this->session->userdata('username')])->row_array();
		$where1 = array('kd_rm' => $kd_rm);
		$data1['pasien'] = $this->Pemeriksaan_model->tampil_detail($where1)->result();
		$data2['pemeriksaan'] = $this->Pemeriksaan_model->tampil_pemeriksaan($where1)->result();
		$data['dokter'] = $this->db->get_where('dokter', ['username' => $this->session->userdata('username')])->row_array();
		$data['tarif'] = $this->Pembayaran_model->tampil();


		$this->load->view('templates/home_header', $judul);
		$this->load->view('templates/home_sidebar', $data);
		$this->load->view('templates/home_topbar', $data);
		$this->load->view('pemeriksaan/detail', $data1);
		$this->load->view('pemeriksaan/input', $data2);
		$this->load->view('templates/home_footer');
	}

	function tambah_aksi()
	{

		$username = $this->session->userdata('username');
		$kd_rm = $this->input->post('kd_rm');
		$id_periksa = $this->input->post('id_periksa');
		$keluhan = $this->input->post('keluhan');
		$diagnosa = $this->input->post('diagnosa');
		$tindakan = implode(' , ', $this->input->post('tindakan', TRUE));
		$tanggal = $this->input->post('tanggal');
		$id_dokter = $this->db->query("SELECT id_dokter FROM dokter WHERE username='$username'")->row_array();

		$data = array(
			'kd_rm' => $kd_rm,
			'id_periksa' => $id_periksa,
			'keluhan' => $keluhan,
			'diagnosa' => $diagnosa,
			'tindakan' => $tindakan,
			'tanggal' => $tanggal,
			'id_dokter' => $id_dokter['id_dokter']
		);

		$this->Pemeriksaan_model->input_data($data, 'pemeriksaan');
		redirect('pemeriksaan/periksa/' . $kd_rm, '');
	}



	public function hapus($id_periksa)
	{
		$this->Pemeriksaan_model->hapus_data($id_periksa);
		redirect('pemeriksaan/index');
	}


	/*LAPORAN TRANSAKSI*/

	function laporan()
	{

		if (isset($_GET['filter']) && !empty($_GET['filter'])) {

			$filter = $_GET['filter'];

			if ($filter == '1') {
				$tanggal1 = $_GET['tanggal'];
				$tanggal2 = $_GET['tanggal2'];
				$ket = 'Data Rekam Medis dari Tanggal ' . date('d-m-y', strtotime($tanggal1)) . ' - ' . date('d-m-y', strtotime($tanggal2));
				$url_cetak = 'pemeriksaan/cetak1?tanggal1=' . $tanggal1 . '&tanggal2=' . $tanggal2 . '';
				$pemeriksaan = $this->Pemeriksaan_model->view_by_date($tanggal1, $tanggal2);
			} else if ($filter == '2') {
				$kd_rm = $_GET['kd_rm'];
				$ket = 'Data Rekam Medis ';
				$url_cetak = 'pemeriksaan/cetak2?&kd_rm=' . $kd_rm;
				$pemeriksaan = $this->Pemeriksaan_model->view_by_kd_rm($kd_rm);
			}

			// else if($filter == '3'){                
			// $kelas = $_GET['kd_pasien'];                                                
			// $ket = 'Data Pasien '.$pasien;                
			// $url_cetak = 'pemeriksaan/cetak3?&pasien='.$pasien;                
			// $pasien = $this->Pemeriksaan_model->view_by_kd_pasien($pasien)->result();             
			// }



		} else {

			$ket = 'Semua Data Rekam Medis';
			$url_cetak = 'pemeriksaan/cetak';
			$pemeriksaan = $this->Pemeriksaan_model->view_all();
		}

		$data['ket'] = $ket;
		$data['url_cetak'] = base_url($url_cetak);
		$data['pemeriksaan'] = $pemeriksaan;
		$data['kd_rm'] = $this->Pemeriksaan_model->kd_rm();
		$data['kd_pasien'] = $this->Pemeriksaan_model->kd_pasien();



		$data['judul'] = 'Laporan Data Rekam Medis';
		$data['admin'] = $this->db->get_where('admin', ['username' => $this->session->userdata('username')])->row_array();

		$this->load->view('templates/home_header', $data);
		$this->load->view('templates/home_sidebar', $data);
		$this->load->view('templates/topbar', $data);
		$this->load->view('pemeriksaan/laporan', $data);
		$this->load->view('templates/home_footer');
	}

	public function pdfExport($alamat, $ket, $pemeriksaan)
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
		$pdf->MultiCell(31, 0.7, "DATA REKAM MEDIS", 0, 'C');
		$pdf->SetFont('Arial', '', 10);
		$pdf->MultiCell(31, 0.7, '' . $ket . '', 0, 'C');
		$pdf->SetFont('Arial', '', 10);
		$pdf->Cell(5, 0.6, "Di cetak pada : " . date("d/m/Y"), 0, 0, 'C');
		$pdf->ln(1);

		$pdf->SetFont('Arial', 'B', 10);
		$pdf->Cell(1, 0.8, 'NO', 1, 0, 'C');
		$pdf->Cell(2.5, 0.8, 'TANGGAL', 1, 0, 'C');
		$pdf->Cell(2.5, 0.8, 'KODE RM', 1, 0, 'C');
		$pdf->Cell(2.5, 0.8, 'KODE PERIKSA', 1, 0, 'C');
		$pdf->Cell(8, 0.8, 'KELUHAN', 1, 0, 'C');
		$pdf->Cell(4, 0.8, 'DIAGNOSIS', 1, 0, 'C');

		$pdf->Cell(10, 0.8, 'TINDAKAN', 1, 0, 'C');
		$pdf->ln();

		if (!empty($pemeriksaan)) {
			$no = 1;
			foreach ($pemeriksaan as $data) {
				$pdf->SetFont('Arial', '', 10);
				$pdf->Cell(1, 0.6, $no++, 1, 0, 'C');
				$pdf->Cell(2.5, 0.6, date('d-m-Y', strtotime($data['tanggal'])), 1, 0, 'C');

				$pdf->Cell(2.5, 0.6, $data['kd_rm'], 1, 0, 'L');
				$pdf->Cell(2.5, 0.6, $data['id_periksa'], 1, 0, 'L');
				$pdf->Cell(8, 0.6, $data['keluhan'], 1, 0, 'L');
				$pdf->Cell(4, 0.6, $data['diagnosa'], 1, 0, 'L');
				$pdf->Cell(10, 0.6, $data['tindakan'], 1, 0, 'L');
				$pdf->ln();
			}
		}

		$pdf->Output("Laporan Rekam Medis.pdf", "I");
	}

	public function cetak()
	{

		$ket = 'Semua Data Rekam Medis';
		$alamat = 'Kp. Cibereum No.18 RT/RW 04/01 Tanjungjaya';

		$pemeriksaan =  $this->Pemeriksaan_model->view_all();
		$this->pdfExport($alamat, $ket, $pemeriksaan);
	}

	public function cetak1()
	{

		$tanggal1 = $_GET['tanggal1'];
		$tanggal2 = $_GET['tanggal2'];
		$ket = 'Data Rekam Medis dari Tanggal ' . date('d-m-y', strtotime($tanggal1)) . ' s/d ' . date('d-m-y', strtotime($tanggal2));
		$alamat = 'Kp. Cibereum No.18 RT/RW 04/01 Tanjungjaya';
		$pemeriksaan = $this->Pemeriksaan_model->view_by_date($tanggal1, $tanggal2);
		$this->pdfExport($alamat, $ket, $pemeriksaan);
	}

	public function cetak2()
	{

		$kd_rm = $_GET['kd_rm'];
		$data['nama_pasien'] = $this->db->query("SELECT nama_pasien FROM pasien WHERE kd_rm = '$kd_rm'")->result();
		$ket = 'Kode RM   '   . $kd_rm;
		$alamat = 'Kp. Cibereum No.18 RT/RW 04/01 Tanjungjaya';
		ob_start();
		require('assets/pdf/fpdf.php');
		$pemeriksaan = $this->Pemeriksaan_model->view_by_kd_rm($kd_rm);

		$this->pdfExport($alamat, $ket, $pemeriksaan);
	}
}
