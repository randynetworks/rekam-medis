<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Obat_masuk extends CI_Controller
{

	public function __construct()
	{
		parent::__construct();
		if (!$this->session->userdata('username')) {
			redirect(base_url("auth"));
		}
		$this->load->model('Pasien_model');
		$this->load->model('Pemeriksaan_model');
		$this->load->model('Apoteker_model');
		$this->load->model('Resep_model');
		$this->load->model('Obat_model');
		$this->load->model('m_id');
		$this->load->library('form_validation');
		$this->load->library('Pdf'); // MEMANGGIL LIBRARY YANG KITA BUAT TADI
	}


	public function index()
	{
		$judul['judul'] = 'Halaman Obat Masuk';
		$data['obat'] = $this->db->query("SELECT * FROM obat_masuk JOIN petugas_obat ON obat_masuk.id_petugas_obat = petugas_obat.id  ORDER BY kd_masuk DESC")->result();
		$data['petugas_obat'] = $this->db->get_where('petugas_obat', ['username' => $this->session->userdata('username')])->row_array();

		$this->load->view('templates/home_header', $judul);
		$this->load->view('templates/home_sidebar');
		$this->load->view('templates/topbar_apoteker', $data);
		$this->load->view('obat_masuk/index', $data);
		$this->load->view('templates/home_footer');
	}

	public function tambah()
	{

		$judul['judul'] = 'Halaman Tambah Transaksi';
		$data['kodemasuk'] = $this->m_id->buat_kode_masuk();
		$kodemasuk = $this->m_id->buat_kode_masuk();
		$data['obat'] = $this->db->query("SELECT * FROM obat ORDER BY nama_obat ASC")->result();
		$data['masuk'] = $this->db->query("SELECT * FROM detail_masuk JOIN obat ON detail_masuk.kd_obat =obat.id_obat WHERE kd_masuk ='$kodemasuk'")->result();
		$data['subtotal'] = $this->Resep_model->hitung('detail_masuk', ['kd_masuk' => $this->m_id->buat_kode_masuk()]);
		$data['petugas_obat'] = $this->db->get_where('petugas_obat', ['username' => $this->session->userdata('username')])->row_array();


		$this->load->view('templates/home_header', $judul);
		$this->load->view('templates/home_sidebar');
		$this->load->view('templates/topbar_apoteker', $data);
		$this->load->view('obat_masuk/input', $data);
		$this->load->view('templates/home_footer');
	}



	function tambah_aksi()
	{
		$username = $this->session->userdata('username');
		$kd_obat = $this->input->post('kd_obat');
		$kodemasuk = $this->input->post('kd_masuk');
		$kd_masuk = $this->input->post('kd_masuk');
		$cek = $this->db->query("SELECT kd_obat FROM detail_masuk WHERE kd_masuk='$kd_masuk' AND kd_obat='$kd_obat'")->num_rows();
		$cek2 = $this->db->query("SELECT stok_in, stok_tot FROM detail_masuk WHERE kd_masuk='$kd_masuk' AND kd_obat='$kd_obat'")->row_array();
		$stok = $this->input->post('stok');
		$stok_in = $this->input->post('stok_in');
		$stok_in2 = floatval($stok_in) + $cek2['stok_in'];
		$stok_tot = floatval($stok) + floatval($stok_in);
		$stok_tot2 = floatval($stok_in) + $cek2['stok_tot'];
		$harga = $this->input->post('harga');
		$total = floatval($stok_in) * floatval($harga);
		$total2 = floatval($stok_in2) * floatval($harga);
		$tambah = $this->input->post('tambah');
		$simpan = $this->input->post('simpan');
		$tanggal = $this->input->post('tanggal');
		$subtotal = $this->Resep_model->hitung('detail_masuk', ['kd_masuk' => $kd_masuk]);
		$id_petugas_obat = $this->db->query("SELECT id FROM petugas_obat WHERE username='$username'")->row_array();

		if ($tambah) {
			if ($cek > 0) {
				$this->db->query("UPDATE detail_masuk set stok_in='$stok_in2', stok_tot='$stok_tot2', total='$total2' WHERE kd_masuk='$kd_masuk' AND kd_obat='$kd_obat'");
				redirect('Obat_masuk/tambah');
			} else {
				$data = array(
					'kd_masuk'  => $kd_masuk,
					'kd_obat'  => $kd_obat,
					'stok_in'  => $stok_in,
					'stok_tot'  => $stok_tot,
					'total'  => $total
				);

				$this->Resep_model->save($data, 'detail_masuk');
				redirect('Obat_masuk/tambah');
			}
		} elseif ($simpan) {
			$data = array(
				'kd_masuk' => $kd_masuk,
				'tanggal' => $tanggal,
				'subtotal' => $subtotal,
				'id_petugas_obat' => $id_petugas_obat['id']
			);

			$this->Resep_model->save1($data, 'obat_masuk');

			$this->db->query("UPDATE obat JOIN detail_masuk ON obat.id_obat = detail_masuk.kd_obat SET obat.stok = detail_masuk.stok_tot WHERE detail_masuk.kd_masuk = '$kd_masuk'");

			redirect('Obat_masuk');
		}
	}





	public function hapus($kd_masuk)
	{
		$this->Resep_model->hapus_data_masuk($kd_masuk);
		redirect('obat_masuk/index');
	}


	public function cek_obat()
	{
		$kd_obat = $this->input->post('kd_obat');
		$cek = $this->db->query("SELECT * FROM obat WHERE id_obat='$kd_obat'")->row();
		$data = array(
			'stok' => $cek->stok,
			'harga' => $cek->harga,
			'id_obat' => $cek->id_obat
		);
		echo json_encode($data);
	}



	function hapus_detail_masuk($kodemasuk)
	{
		$where = array('kd_masuk' => $kodemasuk);
		$this->Resep_model->hapus($where, 'detail_masuk');
		redirect('obat_masuk/index');
	}



	/*LAPORAN TRANSAKSI*/

	function laporan()
	{

		if (isset($_GET['filter']) && !empty($_GET['filter'])) {

			$filter = $_GET['filter'];

			if ($filter == '1') {
				$tanggal1 = $_GET['tanggal'];
				$tanggal2 = $_GET['tanggal2'];
				$ket = 'Data Obat Masuk dari Tanggal ' . date('d-m-y', strtotime($tanggal1)) . ' - ' . date('d-m-y', strtotime($tanggal2));
				$url_cetak = 'obat_masuk/cetak1?tanggal1=' . $tanggal1 . '&tanggal2=' . $tanggal2 . '';
				$obat_masuk = $this->Apoteker_model->view_by_date1($tanggal1, $tanggal2);
			} else if ($filter == '2') {
				$kd_masuk = $_GET['kd_masuk'];
				$ket = 'Data Obat Masuk ';
				$url_cetak = 'obat_masuk/cetak2?&kd_masuk=' . $kd_masuk;
				$obat_masuk = $this->Apoteker_model->view_by_kd_masuk1($kd_masuk);
			}
		} else {

			$ket = 'Semua Data Obat Masuk';
			$url_cetak = 'obat_masuk/cetak';
			$obat_masuk = $this->Apoteker_model->view_all();
		}

		$data['ket'] = $ket;
		$data['url_cetak'] = base_url($url_cetak);
		$data['obat_masuk'] = $obat_masuk;
		$data['kd_masuk'] = $this->Apoteker_model->kd_masuk();
		$data['judul'] = 'Laporan Data Obat Masuk';
		$data['admin'] = $this->db->get_where('admin', ['username' => $this->session->userdata('username')])->row_array();

		$this->load->view('templates/home_header', $data);
		$this->load->view('templates/home_sidebar', $data);
		$this->load->view('templates/topbar', $data);
		$this->load->view('obat_masuk/laporan', $data);
		$this->load->view('templates/home_footer');
	}

	public function pdfExport($ket, $alamat, $obat_masuk)
	{
		$pdf = new FPDF("P", "cm", "Legal");

		$pdf->SetMargins(2, 1, 1);
		$pdf->AliasNbPages();
		$pdf->AddPage();
		$pdf->SetFont('Times', '', 12);
		// $pdf->Image('assets/img/aplikasi/logo.png',2.5,0.5,3,2.5);
		$pdf->SetX(8);
		$pdf->MultiCell(9, 0.6, "DOKTER PRAKTIK PERORANGAN", 0, 'C');
		$pdf->SetFont('Times', 'B', 14);
		$pdf->SetX(8);
		$pdf->MultiCell(9, 0.6, "DOKTER CECEP ISMAWAN", 0, 'C');
		$pdf->SetFont('Times', '', 12);
		$pdf->SetX(8);
		$pdf->MultiCell(9, 0.6, '' . $alamat . '', 0, 'C');
		$pdf->Line(2, 3.1, 31, 3.1);
		$pdf->SetLineWidth(0.1);
		$pdf->Line(2, 3.2, 31, 3.2);
		$pdf->SetLineWidth(0);
		$pdf->ln(1);
		$pdf->SetFont('Arial', 'B', 11);
		$pdf->MultiCell(18, 0.7, "DATA TRANSAKSI OBAT MASUK", 0, 'C');
		$pdf->SetFont('Arial', '', 10);
		$pdf->MultiCell(18, 0.7, '' . $ket . '', 0, 'C');
		$pdf->SetFont('Arial', '', 10);
		$pdf->Cell(5, 0.6, "Di cetak pada : " . date("d/m/Y"), 0, 0, 'C');
		$pdf->ln(1);

		$pdf->SetFont('Arial', 'B', 10);
		$pdf->Cell(1, 0.8, 'NO', 1, 0, 'C');
		$pdf->Cell(3.5, 0.8, 'TANGGAL', 1, 0, 'C');
		$pdf->Cell(3.5, 0.8, 'KODE TRANSAKSI', 1, 0, 'C');
		$pdf->Cell(5, 0.8, 'NAMA', 1, 0, 'C');
		$pdf->Cell(3.5, 0.8, 'TOTAL BAYAR', 1, 0, 'C');
		$pdf->ln();

		if (!empty($obat_masuk)) {
			$no = 1;
			foreach ($obat_masuk as $data) {
				$pdf->SetFont('Arial', '', 10);
				$pdf->Cell(1, 0.6, $no++, 1, 0, 'C');
				$pdf->Cell(3.5, 0.6, date('d-m-Y', strtotime($data->tanggal)), 1, 0, 'C');
				$pdf->Cell(3.5, 0.6, $data->kd_masuk, 1, 0, 'C');
				$pdf->Cell(5, 0.6, $data->nama, 1, 0, 'C');
				$pdf->Cell(3.5, 0.6, 'Rp. ' . number_format($data->subtotal, 0, ',', '.'), 1, 0, 'L');

				$pdf->ln();
			}
		}

		$pdf->Output("Laporan Transaksi Obat Masuk.pdf", "I");
	}

	public function cetak()
	{

		$ket = 'Semua Data Obat Masuk';
		$alamat = 'Kp. Cibereum No.18 RT/RW 04/01 Tanjungjaya';

		$obat_masuk = $this->Apoteker_model->view_all();
		$this->pdfExport($ket, $alamat, $obat_masuk);
	}

	public function cetak1()
	{

		$tanggal1 = $_GET['tanggal1'];
		$tanggal2 = $_GET['tanggal2'];
		$ket = 'Data Obat Masuk dari Tanggal ' . date('d-m-y', strtotime($tanggal1)) . ' s/d ' . date('d-m-y', strtotime($tanggal2));
		$alamat = 'Kp. Cibereum No.18 RT/RW 04/01 Tanjungjaya';

		$obat_masuk = $this->Apoteker_model->view_by_date1($tanggal1, $tanggal2);
		$this->pdfExport($ket, $alamat, $obat_masuk);
	}

	public function cetak2()
	{

		$kd_masuk = $_GET['kd_masuk'];
		$ket = 'Kode Transaksi Obat Masuk   '   . $kd_masuk;
		$alamat = 'Kp. Cibereum No.18 RT/RW 04/01 Tanjungjaya';
		$obat_masuk = $this->Apoteker_model->view_by_kd_masuk($kd_masuk);
		
		$this->pdfExport($ket, $alamat, $obat_masuk);
	}
}
