<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Data_obat extends CI_Controller
{


	public function __construct()
	{
		parent::__construct();
		if (!$this->session->userdata('username')) {
			redirect(base_url("auth"));
		}

		$this->load->library('Pdf'); // MEMANGGIL LIBRARY YANG KITA BUAT TADI
		$this->load->model('Obat_model');
		$this->load->library('form_validation');
	}

	public function index()
	{
		$judul['judul'] = 'Halaman Data Obat';
		$data['obat'] = $this->Obat_model->getAllObat()->result();
		$data['petugas_obat'] = $this->db->get_where('petugas_obat', ['username' => $this->session->userdata('username')])->row_array();

		$this->load->view('templates/home_header', $judul);
		$this->load->view('templates/home_sidebar');
		$this->load->view('templates/topbar_apoteker', $data);
		$this->load->view('data_obat/index', $data);
		$this->load->view('templates/home_footer');
	}
	public function tambah()
	{

		$judul['judul'] = 'Halaman Tambah Data Obat';
		$data['petugas_obat'] = $this->db->get_where('petugas_obat', ['username' => $this->session->userdata('username')])->row_array();

		$this->form_validation->set_rules('nama_obat', 'Nama Obat', 'required');
		$this->form_validation->set_rules('harga', 'Harga', 'required');


		if ($this->form_validation->run() == FALSE) {
			$this->load->view('templates/home_header', $judul);
			$this->load->view('templates/home_sidebar');
			$this->load->view('templates/topbar_apoteker', $data);
			$this->load->view('data_obat/input', $data);
			$this->load->view('templates/home_footer');
		} else {
			$this->Obat_model->tambah_data();
			redirect('data_obat/index');
		}
	}

	public function hapus($id_obat)
	{
		$this->Obat_model->hapus_data($id_obat);
		redirect('data_obat/index');
	}

	public function detail($id_obat)
	{
		$judul['judul'] = 'Halaman Data Obat';
		$data['obat'] = $this->Obat_model->getObatById($id_obat);
		$data['petugas_obat'] = $this->db->get_where('petugas_obat', ['username' => $this->session->userdata('username')])->row_array();
		$this->load->view('templates/home_header', $judul);
		$this->load->view('templates/home_sidebar');
		$this->load->view('templates/topbar_apoteker', $data);
		$this->load->view('data_obat/detail', $data);
		$this->load->view('templates/home_footer');
	}

	public function ubah($id_obat)
	{

		$judul['judul'] = 'Halaman Ubah Data Obat';
		$data['obat'] = $this->Obat_model->getObatById($id_obat);
		$data['petugas_obat'] = $this->db->get_where('petugas_obat', ['username' => $this->session->userdata('username')])->row_array();

		$this->form_validation->set_rules('nama_obat', 'Nama Obat', 'required');
		$this->form_validation->set_rules('harga', 'harga', 'required');



		if ($this->form_validation->run() == FALSE) {
			$this->load->view('templates/home_header', $judul);
			$this->load->view('templates/home_sidebar');
			$this->load->view('templates/topbar_apoteker', $data);
			$this->load->view('data_obat/ubah', $data);
			$this->load->view('templates/home_footer');
		} else {
			$this->Obat_model->ubah_data();
			redirect('data_obat/index');
		}
	}

	/*LAPORAN TRANSAKSI*/

	function laporan()
	{


		$ket = 'Semua Data Obat';
		$url_cetak = 'data_obat/cetak';
		$data['ket'] = $ket;
		$data['url_cetak'] = base_url($url_cetak);
		$data['obat'] = $this->Obat_model->getAllObat()->result();
		$data['judul'] = 'Laporan Data Obat';
		$data['admin'] = $this->db->get_where('admin', ['username' => $this->session->userdata('username')])->row_array();

		$this->load->view('templates/home_header', $data);
		$this->load->view('templates/home_sidebar', $data);
		$this->load->view('templates/topbar', $data);
		$this->load->view('data_obat/laporan', $data);
		$this->load->view('templates/home_footer');
	}

	public function cetak()
	{

		$ket = 'Semua Data Obat';
		$alamat = 'Kp. Cibereum No.18 RT/RW 04/01 Tanjungjaya';

		$obat = $this->Obat_model->getAllObat()->result();

		$pdf = new FPDF("P", "cm", "Legal");

		$pdf->SetMargins(2, 1, 1);
		$pdf->AliasNbPages();
		$pdf->AddPage();
		$pdf->SetFont('Times', '', 12);
		// $pdf->Image('assets/img/aplikasi/logo.png',2.5,0.5,3,2.5);
		$pdf->SetX(8);
		$pdf->MultiCell(9, 0.7, "DOKTER PRAKTIK PERORANGAN", 0, 'C');
		$pdf->SetFont('Times', 'B', 14);
		$pdf->SetX(8);
		$pdf->MultiCell(9, 0.7, "DOKTER CECEP ISMAWAN", 0, 'C');
		$pdf->SetFont('Times', '', 12);
		$pdf->SetX(8);
		$pdf->MultiCell(9, 0.7, '' . $alamat . '', 0, 'C');
		$pdf->Line(2, 3.1, 20, 3.1);
		$pdf->SetLineWidth(0.1);
		$pdf->Line(2, 3.2, 20, 3.2);
		$pdf->SetLineWidth(0);
		$pdf->ln(1);
		$pdf->SetFont('Arial', 'B', 11);
		$pdf->MultiCell(18, 0.7, "DATA OBAT", 0, 'C');
		$pdf->SetFont('Arial', '', 10);
		$pdf->MultiCell(18, 0.7, '' . $ket . '', 0, 'C');
		$pdf->SetFont('Arial', '', 10);
		$pdf->Cell(5, 0.6, "Di cetak pada : " . date("d/m/Y"), 0, 0, 'C');
		$pdf->ln(1);

		$pdf->SetFont('Arial', 'B', 10);
		$pdf->Cell(1, 0.8, 'NO', 1, 0, 'C');
		$pdf->Cell(6, 0.8, 'NAMA OBAT', 1, 0, 'L');
		$pdf->Cell(5, 0.8, 'HARGA', 1, 0, 'C');
		$pdf->Cell(4, 0.8, 'STOK', 1, 0, 'C');
		$pdf->ln();

		if (!empty($obat)) {
			$no = 1;
			foreach ($obat as $data) {
				$pdf->SetFont('Arial', '', 10);
				$pdf->Cell(1, 0.6, $no++, 1, 0, 'C');
				$pdf->Cell(6, 0.6, $data->nama_obat, 1, 0, 'L');
				$pdf->Cell(5, 0.6,  'Rp. ' . number_format($data->harga, 0, ',', '.'), 1, 0, 'C');
				$pdf->Cell(4, 0.6, $data->stok, 1, 0, 'C');
				$pdf->ln();
			}
		}

		$pdf->Output("Laporan Semua Data Obat.pdf", "I");
	}
}
